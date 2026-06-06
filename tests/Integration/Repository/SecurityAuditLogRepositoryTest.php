<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\SecurityAuditLogRepository;
use App\Model\Table\SecurityAuditLogTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class SecurityAuditLogRepositoryTest extends DatabaseTestCase
{
	public function testAuditLogStoresRowsAndFindsLoginRowsWithFiltersPaginationAndOptions(): void
	{
		$repository = $this->getContainer()->getByType(SecurityAuditLogRepository::class);

		$repository->logEvent(
			'password_reset',
			5,
			'user@example.com',
			'127.0.0.1',
			'PHPUnit',
			['source' => 'test'],
		);

		$row = $this->getDatabase()
			->table(SecurityAuditLogTableMap::TABLE_NAME)
			->where(SecurityAuditLogTableMap::COL_EVENT_TYPE, 'password_reset')
			->fetch();

		self::assertNotNull($row);
		self::assertSame('password_reset', $row->{SecurityAuditLogTableMap::COL_EVENT_TYPE});
		self::assertSame('user@example.com', $row->{SecurityAuditLogTableMap::COL_EMAIL});
		self::assertSame('{"source":"test"}', $row->{SecurityAuditLogTableMap::COL_METADATA});

		$fixtures = $this->seedLoginAuditRows();

		$pageCount = 0;
		$firstPage = $repository->findLoginRows(1, 2, $pageCount);

		self::assertSame(2, $pageCount);
		self::assertSame([
			$fixtures['audit']['blocked'],
			$fixtures['audit']['failed'],
		], array_column($firstPage, 'id'));
		self::assertSame(['login_blocked', 'login_failed'], array_column($firstPage, 'eventType'));
		self::assertSame('Blokované', $firstPage[0]['eventLabel']);
		self::assertSame('bg-warning text-dark', $firstPage[0]['eventClass']);
		self::assertSame('Boris Bezpecny', $firstPage[0]['userName']);
		self::assertSame('guest@example.test', $firstPage[1]['userName']);
		self::assertSame('Neúspešné', $firstPage[1]['eventLabel']);
		self::assertSame('bg-danger', $firstPage[1]['eventClass']);

		$pageCount = 0;
		$lastPage = $repository->findLoginRows(99, 2, $pageCount);

		self::assertSame(2, $pageCount);
		self::assertSame([$fixtures['audit']['success']], array_column($lastPage, 'id'));
		self::assertSame('Úspešné', $lastPage[0]['eventLabel']);
		self::assertSame('bg-success', $lastPage[0]['eventClass']);

		$this->assertLoginEventTypes($repository, ['event' => 'login_success'], ['login_success']);
		$this->assertLoginEventTypes($repository, ['user' => (string) $fixtures['users']['boris']], ['login_blocked']);
		$this->assertLoginEventTypes($repository, ['dateFrom' => '02.06.2026', 'dateTo' => '02.06.2026'], ['login_failed']);
		$this->assertLoginEventTypes($repository, ['ip' => '192.168'], ['login_blocked']);
		$this->assertLoginEventTypes($repository, ['q' => 'Firefox Failure'], ['login_failed']);
		$this->assertLoginEventTypes($repository, ['q' => 'Auditova'], ['login_success']);

		self::assertSame([
			'login_success' => 'Úspešné',
			'login_failed' => 'Neúspešné',
			'login_blocked' => 'Blokované',
		], $repository->getLoginEventOptions());

		self::assertSame([
			$fixtures['users']['anna'] => 'Anna Auditova',
			$fixtures['users']['boris'] => 'Boris Bezpecny',
		], $repository->findLoginUserOptions());
	}

	/**
	 * @param array<string, string> $filters
	 * @param list<string> $expectedEventTypes
	 */
	private function assertLoginEventTypes(SecurityAuditLogRepository $repository, array $filters, array $expectedEventTypes): void
	{
		$pageCount = 0;
		$rows = $repository->findLoginRows(1, 10, $pageCount, $filters);

		self::assertSame($expectedEventTypes, array_column($rows, 'eventType'));
		self::assertSame(1, $pageCount);
	}

	/**
	 * @return array{
	 *     users: array{anna:int, boris:int, unused:int},
	 *     audit: array{success:int, failed:int, blocked:int, ignored:int}
	 * }
	 */
	private function seedLoginAuditRows(): array
	{
		$annaId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Anna',
			UserTableMap::COL_SECOND_NAME => 'Auditova',
			UserTableMap::COL_ACRONYM => 'AA',
			UserTableMap::COL_EMAIL => 'anna.auditova@example.test',
		]);
		$borisId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Boris',
			UserTableMap::COL_SECOND_NAME => 'Bezpecny',
			UserTableMap::COL_ACRONYM => 'BB',
			UserTableMap::COL_EMAIL => 'boris.bezpecny@example.test',
		]);
		$unusedId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'No',
			UserTableMap::COL_SECOND_NAME => 'Login',
			UserTableMap::COL_ACRONYM => 'NL',
			UserTableMap::COL_EMAIL => 'no.login@example.test',
		]);

		$successId = TestDatabase::insert(SecurityAuditLogTableMap::TABLE_NAME, [
			SecurityAuditLogTableMap::COL_USER_ID => $annaId,
			SecurityAuditLogTableMap::COL_EVENT_TYPE => 'login_success',
			SecurityAuditLogTableMap::COL_EMAIL => 'anna.auditova@example.test',
			SecurityAuditLogTableMap::COL_IP_ADDRESS => '10.0.0.11',
			SecurityAuditLogTableMap::COL_USER_AGENT => 'Chrome Audit Agent',
			SecurityAuditLogTableMap::COL_METADATA => '{"attempt":1}',
			SecurityAuditLogTableMap::COL_CREATED_AT => '2026-06-01 08:00:00',
		]);
		$failedId = TestDatabase::insert(SecurityAuditLogTableMap::TABLE_NAME, [
			SecurityAuditLogTableMap::COL_USER_ID => null,
			SecurityAuditLogTableMap::COL_EVENT_TYPE => 'login_failed',
			SecurityAuditLogTableMap::COL_EMAIL => 'guest@example.test',
			SecurityAuditLogTableMap::COL_IP_ADDRESS => '10.0.0.22',
			SecurityAuditLogTableMap::COL_USER_AGENT => 'Firefox Failure Agent',
			SecurityAuditLogTableMap::COL_METADATA => '{"reason":"bad_password"}',
			SecurityAuditLogTableMap::COL_CREATED_AT => '2026-06-02 09:00:00',
		]);
		$blockedId = TestDatabase::insert(SecurityAuditLogTableMap::TABLE_NAME, [
			SecurityAuditLogTableMap::COL_USER_ID => $borisId,
			SecurityAuditLogTableMap::COL_EVENT_TYPE => 'login_blocked',
			SecurityAuditLogTableMap::COL_EMAIL => 'boris.bezpecny@example.test',
			SecurityAuditLogTableMap::COL_IP_ADDRESS => '192.168.10.33',
			SecurityAuditLogTableMap::COL_USER_AGENT => 'Edge Blocked Agent',
			SecurityAuditLogTableMap::COL_METADATA => '{"reason":"rate_limit"}',
			SecurityAuditLogTableMap::COL_CREATED_AT => '2026-06-03 10:00:00',
		]);
		$ignoredId = TestDatabase::insert(SecurityAuditLogTableMap::TABLE_NAME, [
			SecurityAuditLogTableMap::COL_USER_ID => $unusedId,
			SecurityAuditLogTableMap::COL_EVENT_TYPE => 'password_reset',
			SecurityAuditLogTableMap::COL_EMAIL => 'no.login@example.test',
			SecurityAuditLogTableMap::COL_IP_ADDRESS => '10.0.0.44',
			SecurityAuditLogTableMap::COL_USER_AGENT => 'Ignored Agent',
			SecurityAuditLogTableMap::COL_METADATA => '{"source":"test"}',
			SecurityAuditLogTableMap::COL_CREATED_AT => '2026-06-04 11:00:00',
		]);

		return [
			'users' => [
				'anna' => $annaId,
				'boris' => $borisId,
				'unused' => $unusedId,
			],
			'audit' => [
				'success' => $successId,
				'failed' => $failedId,
				'blocked' => $blockedId,
				'ignored' => $ignoredId,
			],
		];
	}
}
