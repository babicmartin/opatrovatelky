<?php declare(strict_types=1);

namespace Tests\Regression;

use App\Model\Repository\SecurityLoginAttemptRepository;
use App\Model\Security\LoginRateLimiter;
use App\Model\Security\LoginRateLimitUnavailableException;
use App\Model\Table\SecurityLoginAttemptTableMap;
use App\Model\Utils\Date\DateService;
use DateTimeImmutable;
use Psr\Log\NullLogger;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class LoginRateLimiterRegressionTest extends DatabaseTestCase
{
	private const string OfficeAddress = '203.0.113.10';
	private const string OtherAddress = '198.51.100.20';

	public function testNinthFailureDoesNotBlockLogin(): void
	{
		$limiter = $this->createLimiter();

		for ($i = 0; $i < 9; $i++) {
			$limiter->recordFailure('User@Example.com', '127.0.0.1', 'authentication_failed');
		}

		self::assertNull($limiter->getBlockedUntil('user@example.com', '127.0.0.1'));
	}

	public function testTenthFailureBlocksLogin(): void
	{
		$limiter = $this->createLimiter();

		for ($i = 0; $i < 10; $i++) {
			$limiter->recordFailure('User@Example.com', '127.0.0.1', 'authentication_failed');
		}

		$blockedUntil = $limiter->getBlockedUntil(' user@example.com ', '127.0.0.1');

		self::assertInstanceOf(DateTimeImmutable::class, $blockedUntil);
		self::assertGreaterThan(new DateTimeImmutable(), $blockedUntil);
	}

	public function testFailuresOfOneAccountDoNotBlockAnotherAccountOnTheSameAddress(): void
	{
		$limiter = $this->createLimiter();

		for ($i = 0; $i < 10; $i++) {
			$limiter->recordFailure('colleague@example.com', self::OfficeAddress, 'authentication_failed');
		}

		self::assertNull($limiter->getBlockedUntil('me@example.com', self::OfficeAddress));
		self::assertNotNull($limiter->getBlockedUntil('colleague@example.com', self::OfficeAddress));
		// The account limit follows the account, so changing the address does not lift it.
		self::assertNotNull($limiter->getBlockedUntil('colleague@example.com', self::OtherAddress));
	}

	public function testThirtyFailuresFromOneAddressBlockEveryAccountOnIt(): void
	{
		$limiter = $this->createLimiter();

		// Three failures per account stay far below the account limit; only the address adds up.
		for ($i = 0; $i < 29; $i++) {
			$limiter->recordFailure('guess' . intdiv($i, 3) . '@example.com', self::OfficeAddress, 'authentication_failed');
		}
		self::assertNull($limiter->getBlockedUntil('me@example.com', self::OfficeAddress));

		$limiter->recordFailure('guess9@example.com', self::OfficeAddress, 'authentication_failed');

		self::assertNotNull($limiter->getBlockedUntil('me@example.com', self::OfficeAddress));
		self::assertNull($limiter->getBlockedUntil('me@example.com', self::OtherAddress));
	}

	public function testFailuresOlderThanTheWindowAreNotCounted(): void
	{
		$limiter = $this->createLimiter();

		for ($i = 0; $i < 10; $i++) {
			$limiter->recordFailure('user@example.com', self::OfficeAddress, 'authentication_failed');
		}
		self::assertNotNull($limiter->getBlockedUntil('user@example.com', self::OfficeAddress));

		$this->getDatabase()->table(SecurityLoginAttemptTableMap::TABLE_NAME)->update([
			SecurityLoginAttemptTableMap::COL_CREATED_AT => new DateTimeImmutable('-16 minutes'),
		]);

		self::assertNull($limiter->getBlockedUntil('user@example.com', self::OfficeAddress));
	}

	public function testUnreadableAttemptsAreReportedInsteadOfAllowingTheLogin(): void
	{
		$limiter = new LoginRateLimiter(
			new SecurityLoginAttemptRepository(
				TestDatabase::explorerWithoutTables(),
				$this->getContainer()->getByType(DateService::class),
			),
			new NullLogger(),
		);

		$this->expectException(LoginRateLimitUnavailableException::class);

		$limiter->getBlockedUntil('user@example.com', self::OfficeAddress);
	}

	private function createLimiter(): LoginRateLimiter
	{
		return new LoginRateLimiter(
			$this->getContainer()->getByType(SecurityLoginAttemptRepository::class),
			new NullLogger(),
		);
	}
}
