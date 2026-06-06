<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\SecurityAuditLogRepository;
use App\Model\Table\SecurityAuditLogTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class SecurityAuditLogRepositoryReadTest extends DatabaseTestCase
{
	public function testFindLoginRowsMapsKnownLoginEventsAndIgnoresOtherAuditEvents(): void
	{
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Login',
			UserTableMap::COL_SECOND_NAME => 'User',
		]);
		$this->insertAudit('login_success', $userId, 'login@example.test', '127.0.0.1', 'Browser A', '2026-06-06 08:00:00');
		$this->insertAudit('logout', $userId, 'login@example.test', '127.0.0.1', 'Browser A', '2026-06-06 09:00:00');

		$pageCount = 0;
		$rows = $this->repository()->findLoginRows(1, 10, $pageCount);

		self::assertSame(1, $pageCount);
		self::assertCount(1, $rows);
		self::assertSame('login_success', $rows[0]['eventType']);
		self::assertSame('Úspešné', $rows[0]['eventLabel']);
		self::assertSame('bg-success', $rows[0]['eventClass']);
		self::assertSame('Login User', $rows[0]['userName']);
		self::assertSame('login@example.test', $rows[0]['email']);
	}

	public function testFindLoginRowsFiltersByEventUserDateIpAndQuery(): void
	{
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Filter',
			UserTableMap::COL_SECOND_NAME => 'User',
			UserTableMap::COL_EMAIL => 'filter.user@example.test',
		]);
		$otherUserId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'other.user@example.test',
		]);

		$targetId = $this->insertAudit('login_failed', $userId, 'filter-login@example.test', '10.20.30.40', 'SpecialAgent', '2026-06-06 10:00:00');
		$this->insertAudit('login_blocked', $otherUserId, 'blocked@example.test', '10.20.30.41', 'OtherAgent', '2026-06-07 10:00:00');

		$this->assertSingleFilteredRow(['event' => 'login_failed'], $targetId);
		$this->assertSingleFilteredRow(['user' => (string) $userId], $targetId);
		$this->assertSingleFilteredRow(['dateFrom' => '6.6.2026', 'dateTo' => '6.6.2026'], $targetId);
		$this->assertSingleFilteredRow(['ip' => '30.40'], $targetId);
		$this->assertSingleFilteredRow(['q' => 'SpecialAgent'], $targetId);
		$this->assertSingleFilteredRow(['q' => 'Filter'], $targetId);
	}

	public function testLoginEventAndUserOptionsAreLimitedToLoginAuditRows(): void
	{
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Option',
			UserTableMap::COL_SECOND_NAME => 'User',
		]);
		TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'no.login@example.test',
		]);
		$this->insertAudit('login_blocked', $userId, 'blocked@example.test', '127.0.0.1', 'Browser', '2026-06-06 10:00:00');
		$this->insertAudit('logout', $userId, 'blocked@example.test', '127.0.0.1', 'Browser', '2026-06-06 11:00:00');

		self::assertSame([
			'login_success' => 'Úspešné',
			'login_failed' => 'Neúspešné',
			'login_blocked' => 'Blokované',
		], $this->repository()->getLoginEventOptions());
		self::assertSame([(string) $userId => 'Option User'], $this->repository()->findLoginUserOptions());
	}

	/**
	 * @param array<string, string> $filters
	 */
	private function assertSingleFilteredRow(array $filters, int $expectedId): void
	{
		$pageCount = 0;
		$rows = $this->repository()->findLoginRows(1, 10, $pageCount, $filters);

		self::assertSame(1, $pageCount);
		self::assertCount(1, $rows);
		self::assertSame($expectedId, $rows[0]['id']);
	}

	private function insertAudit(string $eventType, ?int $userId, string $email, string $ip, string $userAgent, string $createdAt): int
	{
		return TestDatabase::insert(SecurityAuditLogTableMap::TABLE_NAME, [
			SecurityAuditLogTableMap::COL_USER_ID => $userId,
			SecurityAuditLogTableMap::COL_EVENT_TYPE => $eventType,
			SecurityAuditLogTableMap::COL_EMAIL => $email,
			SecurityAuditLogTableMap::COL_IP_ADDRESS => $ip,
			SecurityAuditLogTableMap::COL_USER_AGENT => $userAgent,
			SecurityAuditLogTableMap::COL_CREATED_AT => $createdAt,
		]);
	}

	private function repository(): SecurityAuditLogRepository
	{
		return $this->getContainer()->getByType(SecurityAuditLogRepository::class);
	}
}
