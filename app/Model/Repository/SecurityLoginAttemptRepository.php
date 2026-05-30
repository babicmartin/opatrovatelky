<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\SecurityLoginAttemptTableMap;
use DateTimeImmutable;

final class SecurityLoginAttemptRepository extends BaseRepository
{
	protected function getTableName(): string
	{
		return SecurityLoginAttemptTableMap::TABLE_NAME;
	}

	public function recordAttempt(string $email, string $ipAddress, bool $success, ?string $failureReason = null): void
	{
		$this->insert([
			SecurityLoginAttemptTableMap::COL_EMAIL => $email,
			SecurityLoginAttemptTableMap::COL_IP_ADDRESS => $ipAddress,
			SecurityLoginAttemptTableMap::COL_SUCCESS => $success ? 1 : 0,
			SecurityLoginAttemptTableMap::COL_FAILURE_REASON => $failureReason,
		]);
	}

	/**
	 * @return array{failureCount:int,latestFailureAt:?string}
	 */
	public function findFailureSummary(string $email, string $ipAddress, DateTimeImmutable $since): array
	{
		$row = $this->database->query(
			'SELECT COUNT(*) AS failure_count, MAX(' . SecurityLoginAttemptTableMap::COL_CREATED_AT . ') AS latest_failure_at
			FROM ' . SecurityLoginAttemptTableMap::TABLE_NAME . '
			WHERE ' . SecurityLoginAttemptTableMap::COL_SUCCESS . ' = 0
				AND ' . SecurityLoginAttemptTableMap::COL_CREATED_AT . ' >= ?
				AND (' . SecurityLoginAttemptTableMap::COL_EMAIL . ' = ? OR ' . SecurityLoginAttemptTableMap::COL_IP_ADDRESS . ' = ?)',
			$since->format('Y-m-d H:i:s'),
			$email,
			$ipAddress,
		)->fetch();

		if (!$row) {
			return [
				'failureCount' => 0,
				'latestFailureAt' => null,
			];
		}

		return [
			'failureCount' => (int) ($row->failure_count ?? 0),
			'latestFailureAt' => $row->latest_failure_at !== null ? (string) $row->latest_failure_at : null,
		];
	}
}
