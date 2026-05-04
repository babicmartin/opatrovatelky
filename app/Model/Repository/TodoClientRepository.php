<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\StatusTodoTableMap;
use App\Model\Table\TodoClientTableMap;
use App\Model\Table\UserTableMap;
use Nette\Database\Row;
use Nette\Database\Table\ActiveRow;

class TodoClientRepository extends BaseRepository
{
	private const int STATUS_DONE = 1;

	protected function getTableName(): string
	{
		return TodoClientTableMap::TABLE_NAME;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findActiveTodoRows(?int $userId, bool $canViewAll, ?int $statusId): array
	{
		$tc = TodoClientTableMap::TABLE_NAME;
		$st = StatusTodoTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;

		$where = ['tc.' . TodoClientTableMap::COL_STATUS . ' <> ?'];
		$params = [self::STATUS_DONE];

		if ($statusId !== null) {
			$where[] = 'tc.' . TodoClientTableMap::COL_STATUS . ' = ?';
			$params[] = $statusId;
		}

		if (!$canViewAll) {
			$visibleUserId = $userId ?? 0;
			$where[] = '(tc.' . TodoClientTableMap::COL_TODO_FROM_USER . ' = ?'
				. ' OR tc.' . TodoClientTableMap::COL_TODO_TO_USER_1 . ' = ?'
				. ' OR tc.' . TodoClientTableMap::COL_TODO_TO_USER_2 . ' = ?'
				. ' OR (tc.' . TodoClientTableMap::COL_TODO_FROM_USER . ' = 0'
				. ' AND tc.' . TodoClientTableMap::COL_TODO_TO_USER_1 . ' = 0'
				. ' AND tc.' . TodoClientTableMap::COL_TODO_TO_USER_2 . ' = 0))';
			$params[] = $visibleUserId;
			$params[] = $visibleUserId;
			$params[] = $visibleUserId;
		}

		$whereSql = implode(' AND ', $where);

		$sql = "
			SELECT
				tc." . TodoClientTableMap::COL_ID . " AS id,
				tc." . TodoClientTableMap::COL_TITLE . " AS title,
				tc." . TodoClientTableMap::COL_FAMILY_ID . " AS family_id,
				tc." . TodoClientTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				tc." . TodoClientTableMap::COL_TODO_FROM_USER . " AS from_user_id,
				tc." . TodoClientTableMap::COL_TODO_TO_USER_1 . " AS to_user_1_id,
				tc." . TodoClientTableMap::COL_TODO_TO_USER_2 . " AS to_user_2_id,
				tc." . TodoClientTableMap::COL_TODO_CREATED . " AS created_date,
				tc." . TodoClientTableMap::COL_TODO_DEADLINE . " AS deadline_date,
				tc." . TodoClientTableMap::COL_STATUS . " AS status_id,
				st." . StatusTodoTableMap::COL_STATUS . " AS status_label,
				st." . StatusTodoTableMap::COL_COLOR . " AS status_color,
				f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				f." . FamilyTableMap::COL_CLIENT_NUMBER . " AS family_client_number,
				b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				b." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . " AS babysitter_client_number,
				uf." . UserTableMap::COL_ACRONYM . " AS from_user_acronym,
				uf." . UserTableMap::COL_COLOR . " AS from_user_color,
				ut1." . UserTableMap::COL_ACRONYM . " AS to_user_1_acronym,
				ut1." . UserTableMap::COL_COLOR . " AS to_user_1_color,
				ut2." . UserTableMap::COL_ACRONYM . " AS to_user_2_acronym,
				ut2." . UserTableMap::COL_COLOR . " AS to_user_2_color
			FROM $tc tc
			LEFT JOIN $st st ON st." . StatusTodoTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_STATUS . "
			LEFT JOIN $f f ON f." . FamilyTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b b ON b." . OpatrovatelkaTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $u uf ON uf." . UserTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_TODO_FROM_USER . "
			LEFT JOIN $u ut1 ON ut1." . UserTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_TODO_TO_USER_1 . "
			LEFT JOIN $u ut2 ON ut2." . UserTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_TODO_TO_USER_2 . "
			WHERE $whereSql
			ORDER BY tc." . TodoClientTableMap::COL_ID . " DESC
		";

		return $this->mapRows($this->database->query($sql, ...$params)->fetchAll());
	}

	/**
	 * @param int $pageCount populated by reference with the resolved page count
	 * @return list<array<string, mixed>>
	 */
	public function findDoneTodoRows(
		int $page,
		int $itemsPerPage,
		?int $userId,
		bool $canViewAll,
		?int $statusId,
		int &$pageCount,
	): array
	{
		if ($statusId !== null && $statusId !== self::STATUS_DONE) {
			$pageCount = 1;
			return [];
		}

		$tc = TodoClientTableMap::TABLE_NAME;
		$st = StatusTodoTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;

		$where = ['tc.' . TodoClientTableMap::COL_STATUS . ' = ?'];
		$params = [self::STATUS_DONE];

		if (!$canViewAll) {
			$visibleUserId = $userId ?? 0;
			$where[] = '(tc.' . TodoClientTableMap::COL_TODO_FROM_USER . ' = ?'
				. ' OR tc.' . TodoClientTableMap::COL_TODO_TO_USER_1 . ' = ?'
				. ' OR tc.' . TodoClientTableMap::COL_TODO_TO_USER_2 . ' = ?'
				. ' OR (tc.' . TodoClientTableMap::COL_TODO_FROM_USER . ' = 0'
				. ' AND tc.' . TodoClientTableMap::COL_TODO_TO_USER_1 . ' = 0'
				. ' AND tc.' . TodoClientTableMap::COL_TODO_TO_USER_2 . ' = 0))';
			$params[] = $visibleUserId;
			$params[] = $visibleUserId;
			$params[] = $visibleUserId;
		}

		$whereSql = implode(' AND ', $where);

		$totalCount = (int) $this->database->query(
			"SELECT COUNT(*) FROM $tc tc WHERE $whereSql",
			...$params,
		)->fetchField();

		$itemsPerPage = max(1, $itemsPerPage);
		$pageCount = max(1, (int) ceil($totalCount / $itemsPerPage));
		$page = min(max(1, $page), $pageCount);
		$offset = ($page - 1) * $itemsPerPage;

		$sql = "
			SELECT
				tc." . TodoClientTableMap::COL_ID . " AS id,
				tc." . TodoClientTableMap::COL_TITLE . " AS title,
				tc." . TodoClientTableMap::COL_FAMILY_ID . " AS family_id,
				tc." . TodoClientTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				tc." . TodoClientTableMap::COL_TODO_FROM_USER . " AS from_user_id,
				tc." . TodoClientTableMap::COL_TODO_TO_USER_1 . " AS to_user_1_id,
				tc." . TodoClientTableMap::COL_TODO_TO_USER_2 . " AS to_user_2_id,
				tc." . TodoClientTableMap::COL_TODO_CREATED . " AS created_date,
				tc." . TodoClientTableMap::COL_TODO_DEADLINE . " AS deadline_date,
				tc." . TodoClientTableMap::COL_STATUS . " AS status_id,
				st." . StatusTodoTableMap::COL_STATUS . " AS status_label,
				st." . StatusTodoTableMap::COL_COLOR . " AS status_color,
				f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				f." . FamilyTableMap::COL_CLIENT_NUMBER . " AS family_client_number,
				b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				b." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . " AS babysitter_client_number,
				uf." . UserTableMap::COL_ACRONYM . " AS from_user_acronym,
				uf." . UserTableMap::COL_COLOR . " AS from_user_color,
				ut1." . UserTableMap::COL_ACRONYM . " AS to_user_1_acronym,
				ut1." . UserTableMap::COL_COLOR . " AS to_user_1_color,
				ut2." . UserTableMap::COL_ACRONYM . " AS to_user_2_acronym,
				ut2." . UserTableMap::COL_COLOR . " AS to_user_2_color
			FROM $tc tc
			LEFT JOIN $st st ON st." . StatusTodoTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_STATUS . "
			LEFT JOIN $f f ON f." . FamilyTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b b ON b." . OpatrovatelkaTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $u uf ON uf." . UserTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_TODO_FROM_USER . "
			LEFT JOIN $u ut1 ON ut1." . UserTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_TODO_TO_USER_1 . "
			LEFT JOIN $u ut2 ON ut2." . UserTableMap::COL_ID . " = tc." . TodoClientTableMap::COL_TODO_TO_USER_2 . "
			WHERE $whereSql
			ORDER BY tc." . TodoClientTableMap::COL_ID . " DESC
			LIMIT ? OFFSET ?
		";

		$queryParams = [...$params, $itemsPerPage, $offset];

		return $this->mapRows($this->database->query($sql, ...$queryParams)->fetchAll());
	}

	public function getItemForUser(int $id, ?int $userId, bool $canViewAll): ?ActiveRow
	{
		$row = $this->getItem($id);
		if ($row === null) {
			return null;
		}

		if ($canViewAll) {
			return $row;
		}

		$fromUser = (int) ($row->{TodoClientTableMap::COL_TODO_FROM_USER} ?? 0);
		$toUser1 = (int) ($row->{TodoClientTableMap::COL_TODO_TO_USER_1} ?? 0);
		$toUser2 = (int) ($row->{TodoClientTableMap::COL_TODO_TO_USER_2} ?? 0);

		if ($fromUser === 0 && $toUser1 === 0 && $toUser2 === 0) {
			return $row;
		}

		if ($userId !== null && in_array($userId, [$fromUser, $toUser1, $toUser2], true)) {
			return $row;
		}

		return null;
	}

	public function createEmptyTodo(int $createdByUserId): int
	{
		$row = $this->insert([
			TodoClientTableMap::COL_TODO_FROM_USER => $createdByUserId,
			TodoClientTableMap::COL_TODO_CREATED => date('Y-m-d'),
		]);

		if (!$row instanceof ActiveRow) {
			throw new \RuntimeException('Todo row was not created.');
		}

		return (int) $row->{TodoClientTableMap::COL_ID};
	}

	/**
	 * @param list<Row> $rows
	 * @return list<array<string, mixed>>
	 */
	private function mapRows(array $rows): array
	{
		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->title ?? ''),
				'familyId' => (int) ($row->family_id ?? 0),
				'familySurname' => (string) ($row->family_surname ?? ''),
				'familyClientNumber' => (string) ($row->family_client_number ?? ''),
				'babysitterId' => (int) ($row->babysitter_id ?? 0),
				'babysitterSurname' => (string) ($row->babysitter_surname ?? ''),
				'babysitterClientNumber' => (string) ($row->babysitter_client_number ?? ''),
				'fromUserId' => (int) ($row->from_user_id ?? 0),
				'fromUserAcronym' => (string) ($row->from_user_acronym ?? ''),
				'fromUserColor' => (string) ($row->from_user_color ?? ''),
				'toUser1Id' => (int) ($row->to_user_1_id ?? 0),
				'toUser1Acronym' => (string) ($row->to_user_1_acronym ?? ''),
				'toUser1Color' => (string) ($row->to_user_1_color ?? ''),
				'toUser2Id' => (int) ($row->to_user_2_id ?? 0),
				'toUser2Acronym' => (string) ($row->to_user_2_acronym ?? ''),
				'toUser2Color' => (string) ($row->to_user_2_color ?? ''),
				'createdDate' => self::formatDate((string) ($row->created_date ?? '')),
				'deadlineDate' => self::formatDate((string) ($row->deadline_date ?? '')),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$rows,
		);
	}

	private static function formatDate(string $date): string
	{
		if ($date === '' || $date === '0000-00-00' || $date === '-0001-11-30 00:00:00') {
			return '';
		}

		$parts = explode('-', substr($date, 0, 10));
		if (count($parts) !== 3) {
			return '';
		}

		return $parts[2] . '.' . $parts[1] . '.' . $parts[0];
	}
}
