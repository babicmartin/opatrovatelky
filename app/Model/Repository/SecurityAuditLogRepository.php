<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\SecurityAuditLogTableMap;
use App\Model\Table\UserTableMap;
use DateTimeImmutable;
use DateTimeInterface;
use Nette\Database\Row;
use Nette\Database\SqlLiteral;

final class SecurityAuditLogRepository extends BaseRepository
{
	/** @var array<string, string> */
	private const array LOGIN_EVENT_OPTIONS = [
		'login_success' => 'Úspešné',
		'login_failed' => 'Neúspešné',
		'login_blocked' => 'Blokované',
	];

	protected function getTableName(): string
	{
		return SecurityAuditLogTableMap::TABLE_NAME;
	}

	/**
	 * @param array<string, mixed>|null $metadata
	 */
	public function logEvent(
		string $eventType,
		?int $userId,
		?string $email,
		?string $ipAddress,
		?string $userAgent,
		?array $metadata = null,
	): void {
		$metadataJson = $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
		if ($metadataJson === false) {
			$metadataJson = null;
		}

		$this->insert([
			SecurityAuditLogTableMap::COL_USER_ID => $userId,
			SecurityAuditLogTableMap::COL_EVENT_TYPE => substr($eventType, 0, 80),
			SecurityAuditLogTableMap::COL_EMAIL => $email !== null ? substr($email, 0, 190) : null,
			SecurityAuditLogTableMap::COL_IP_ADDRESS => $ipAddress !== null ? substr($ipAddress, 0, 45) : null,
			SecurityAuditLogTableMap::COL_USER_AGENT => $userAgent !== null ? substr($userAgent, 0, 512) : null,
			SecurityAuditLogTableMap::COL_METADATA => $metadataJson,
		]);
	}

	/**
	 * @param int<1, max> $page
	 * @param int<1, max> $itemsPerPage
	 * @param array<string, string> $filters
	 * @return list<array<string, mixed>>
	 */
	public function findLoginRows(int $page, int $itemsPerPage, int &$pageCount, array $filters = []): array
	{
		$a = SecurityAuditLogTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;
		$where = $this->buildLoginFilterWhere($filters, $a, $u);

		$totalCount = (int) $this->database->query("
			SELECT COUNT(*)
			FROM $a
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $a." . SecurityAuditLogTableMap::COL_USER_ID . "
			WHERE ?
		", $where)->fetchField();

		$pageCount = max(1, (int) ceil($totalCount / max(1, $itemsPerPage)));
		$page = min(max(1, $page), $pageCount);
		$offset = ($page - 1) * max(1, $itemsPerPage);

		return array_map([$this, 'mapLoginRow'], $this->database->query("
			SELECT
				$a.*,
				$u." . UserTableMap::COL_NAME . " AS user_name,
				$u." . UserTableMap::COL_SECOND_NAME . " AS user_second_name,
				$u." . UserTableMap::COL_EMAIL . " AS user_email,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym
			FROM $a
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $a." . SecurityAuditLogTableMap::COL_USER_ID . "
			WHERE ?
			ORDER BY $a." . SecurityAuditLogTableMap::COL_ID . " DESC
			LIMIT ? OFFSET ?
		", $where, max(1, $itemsPerPage), $offset)->fetchAll());
	}

	/**
	 * @return array<string, string>
	 */
	public function getLoginEventOptions(): array
	{
		return self::LOGIN_EVENT_OPTIONS;
	}

	/**
	 * @return array<int|string, string>
	 */
	public function findLoginUserOptions(): array
	{
		$a = SecurityAuditLogTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;
		$where = $this->buildLoginEventWhere($a);
		$sql = "
			SELECT DISTINCT
				$u." . UserTableMap::COL_ID . " AS id,
				$u." . UserTableMap::COL_NAME . " AS user_name,
				$u." . UserTableMap::COL_SECOND_NAME . " AS user_second_name,
				$u." . UserTableMap::COL_EMAIL . " AS user_email,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym
			FROM $a
			INNER JOIN $u ON $u." . UserTableMap::COL_ID . " = $a." . SecurityAuditLogTableMap::COL_USER_ID . "
			WHERE ?
			ORDER BY $u." . UserTableMap::COL_SECOND_NAME . " ASC, $u." . UserTableMap::COL_NAME . " ASC
		";

		$options = [];
		foreach ($this->database->query($sql, $where)->fetchAll() as $row) {
			$name = $this->formatUserName($row);
			if ($name === '') {
				$name = 'Používateľ #' . (int) $row->id;
			}

			$options[(string) (int) $row->id] = $name;
		}

		return $options;
	}

	/**
	 * @param array<string, string> $filters
	 */
	private function buildLoginFilterWhere(array $filters, string $auditTableAlias, string $userTableAlias): SqlLiteral
	{
		$where = [$this->buildLoginEventWhereSql($auditTableAlias)];
		$params = array_keys(self::LOGIN_EVENT_OPTIONS);

		$event = trim((string) ($filters['event'] ?? ''));
		if (isset(self::LOGIN_EVENT_OPTIONS[$event])) {
			$where[] = $auditTableAlias . '.' . SecurityAuditLogTableMap::COL_EVENT_TYPE . ' = ?';
			$params[] = $event;
		}

		$user = trim((string) ($filters['user'] ?? ''));
		if ($user !== '' && ctype_digit($user)) {
			$where[] = $auditTableAlias . '.' . SecurityAuditLogTableMap::COL_USER_ID . ' = ?';
			$params[] = (int) $user;
		}

		$dateFrom = $this->parseFilterDate((string) ($filters['dateFrom'] ?? ''));
		if ($dateFrom !== null) {
			$where[] = $auditTableAlias . '.' . SecurityAuditLogTableMap::COL_CREATED_AT . ' >= ?';
			$params[] = $dateFrom->format('Y-m-d 00:00:00');
		}

		$dateTo = $this->parseFilterDate((string) ($filters['dateTo'] ?? ''));
		if ($dateTo !== null) {
			$where[] = $auditTableAlias . '.' . SecurityAuditLogTableMap::COL_CREATED_AT . ' <= ?';
			$params[] = $dateTo->format('Y-m-d 23:59:59');
		}

		$ipAddress = trim((string) ($filters['ip'] ?? ''));
		if ($ipAddress !== '') {
			$where[] = $auditTableAlias . '.' . SecurityAuditLogTableMap::COL_IP_ADDRESS . ' LIKE ?';
			$params[] = '%' . $ipAddress . '%';
		}

		$query = trim((string) ($filters['q'] ?? ''));
		if ($query !== '') {
			$like = '%' . $query . '%';
			$where[] = '(' . implode(' OR ', [
				$auditTableAlias . '.' . SecurityAuditLogTableMap::COL_EMAIL . ' LIKE ?',
				$auditTableAlias . '.' . SecurityAuditLogTableMap::COL_IP_ADDRESS . ' LIKE ?',
				$auditTableAlias . '.' . SecurityAuditLogTableMap::COL_USER_AGENT . ' LIKE ?',
				$userTableAlias . '.' . UserTableMap::COL_NAME . ' LIKE ?',
				$userTableAlias . '.' . UserTableMap::COL_SECOND_NAME . ' LIKE ?',
				$userTableAlias . '.' . UserTableMap::COL_EMAIL . ' LIKE ?',
				$userTableAlias . '.' . UserTableMap::COL_ACRONYM . ' LIKE ?',
			]) . ')';
			for ($i = 0; $i < 7; $i++) {
				$params[] = $like;
			}
		}

		return $this->database::literal(implode(' AND ', $where), ...$params);
	}

	private function buildLoginEventWhere(string $auditTableAlias): SqlLiteral
	{
		return $this->database::literal(
			$this->buildLoginEventWhereSql($auditTableAlias),
			...array_keys(self::LOGIN_EVENT_OPTIONS),
		);
	}

	private function buildLoginEventWhereSql(string $auditTableAlias): string
	{
		return $auditTableAlias . '.' . SecurityAuditLogTableMap::COL_EVENT_TYPE . ' IN (?, ?, ?)';
	}

	private function parseFilterDate(string $value): ?DateTimeImmutable
	{
		return $this->dateService->tryCreateFromUserInput($value);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mapLoginRow(Row $row): array
	{
		$eventType = (string) $row->{SecurityAuditLogTableMap::COL_EVENT_TYPE};
		$email = $row->{SecurityAuditLogTableMap::COL_EMAIL} !== null ? (string) $row->{SecurityAuditLogTableMap::COL_EMAIL} : '';
		$userName = $this->formatUserName($row);
		if ($userName === '') {
			$userName = $email;
		}

		return [
			'id' => (int) $row->{SecurityAuditLogTableMap::COL_ID},
			'eventType' => $eventType,
			'eventLabel' => $this->resolveLoginEventLabel($eventType),
			'eventClass' => $this->resolveLoginEventClass($eventType),
			'userId' => $row->{SecurityAuditLogTableMap::COL_USER_ID} !== null ? (int) $row->{SecurityAuditLogTableMap::COL_USER_ID} : null,
			'userName' => $userName,
			'email' => $email,
			'ipAddress' => $row->{SecurityAuditLogTableMap::COL_IP_ADDRESS} !== null ? (string) $row->{SecurityAuditLogTableMap::COL_IP_ADDRESS} : '',
			'userAgent' => $row->{SecurityAuditLogTableMap::COL_USER_AGENT} !== null ? (string) $row->{SecurityAuditLogTableMap::COL_USER_AGENT} : '',
			'createdAt' => $this->parseDate($row->{SecurityAuditLogTableMap::COL_CREATED_AT}),
		];
	}

	private function formatUserName(Row $row): string
	{
		$userName = trim((string) ($row->user_name ?? '') . ' ' . (string) ($row->user_second_name ?? ''));
		if ($userName === '') {
			$userName = (string) ($row->user_email ?? '');
		}
		if ($userName === '') {
			$userName = (string) ($row->user_acronym ?? '');
		}

		return $userName;
	}

	private function resolveLoginEventLabel(string $eventType): string
	{
		return self::LOGIN_EVENT_OPTIONS[$eventType] ?? $eventType;
	}

	private function resolveLoginEventClass(string $eventType): string
	{
		return match ($eventType) {
			'login_success' => 'bg-success',
			'login_failed' => 'bg-danger',
			'login_blocked' => 'bg-warning text-dark',
			default => 'bg-secondary',
		};
	}

	private function parseDate(mixed $value): ?DateTimeImmutable
	{
		if ($value instanceof DateTimeImmutable) {
			return $value;
		}

		if ($value instanceof DateTimeInterface) {
			return DateTimeImmutable::createFromInterface($value);
		}

		if (!is_scalar($value)) {
			return null;
		}

		$value = trim((string) $value);
		if ($value === '' || str_starts_with($value, '0000-00-00')) {
			return null;
		}

		$dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
		if ($dt !== false) {
			return $dt;
		}

		return $this->dateService->tryCreateFromDb($value);
	}
}
