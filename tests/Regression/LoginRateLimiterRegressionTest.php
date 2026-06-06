<?php declare(strict_types=1);

namespace Tests\Regression;

use App\Model\Repository\SecurityLoginAttemptRepository;
use App\Model\Security\LoginRateLimiter;
use DateTimeImmutable;
use Psr\Log\NullLogger;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class LoginRateLimiterRegressionTest extends DatabaseTestCase
{
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

	private function createLimiter(): LoginRateLimiter
	{
		return new LoginRateLimiter(
			$this->getContainer()->getByType(SecurityLoginAttemptRepository::class),
			new NullLogger(),
		);
	}
}
