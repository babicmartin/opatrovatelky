<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\SecurityLoginAttemptRepository;
use DateTimeImmutable;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class SecurityLoginAttemptRepositoryTest extends DatabaseTestCase
{
	public function testRecordsFailureAndFindsFailureSummary(): void
	{
		$repository = $this->getContainer()->getByType(SecurityLoginAttemptRepository::class);

		$repository->recordAttempt('test@example.com', '127.0.0.1', false, 'invalid_password');

		$summary = $repository->findFailureSummary('test@example.com', '127.0.0.1', new DateTimeImmutable('-15 minutes'));

		self::assertSame(1, $summary['failureCount']);
		self::assertIsString($summary['latestFailureAt']);
	}

	public function testSuccessfulAttemptDoesNotCountAsFailure(): void
	{
		$repository = $this->getContainer()->getByType(SecurityLoginAttemptRepository::class);

		$repository->recordAttempt('test@example.com', '127.0.0.1', true);

		$summary = $repository->findFailureSummary('test@example.com', '127.0.0.1', new DateTimeImmutable('-15 minutes'));

		self::assertSame(0, $summary['failureCount']);
		self::assertNull($summary['latestFailureAt']);
	}
}
