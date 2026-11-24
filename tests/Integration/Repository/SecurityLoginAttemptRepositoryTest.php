<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\SecurityLoginAttemptRepository;
use DateTimeImmutable;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class SecurityLoginAttemptRepositoryTest extends DatabaseTestCase
{
	public function testRecordsFailureAndFindsItByEmailAndByAddress(): void
	{
		$repository = $this->getContainer()->getByType(SecurityLoginAttemptRepository::class);
		$since = new DateTimeImmutable('-15 minutes');

		$repository->recordAttempt('test@example.com', '127.0.0.1', false, 'invalid_password');

		$byEmail = $repository->findFailureSummaryByEmail('test@example.com', $since);
		self::assertSame(1, $byEmail['failureCount']);
		self::assertIsString($byEmail['latestFailureAt']);

		$byAddress = $repository->findFailureSummaryByIpAddress('127.0.0.1', $since);
		self::assertSame(1, $byAddress['failureCount']);
		self::assertIsString($byAddress['latestFailureAt']);
	}

	public function testEmailAndAddressAreCountedSeparately(): void
	{
		$repository = $this->getContainer()->getByType(SecurityLoginAttemptRepository::class);
		$since = new DateTimeImmutable('-15 minutes');

		$repository->recordAttempt('first@example.com', '10.0.0.1', false, 'invalid_password');
		$repository->recordAttempt('first@example.com', '10.0.0.2', false, 'invalid_password');
		$repository->recordAttempt('second@example.com', '10.0.0.1', false, 'invalid_password');

		self::assertSame(2, $repository->findFailureSummaryByEmail('first@example.com', $since)['failureCount']);
		self::assertSame(1, $repository->findFailureSummaryByEmail('second@example.com', $since)['failureCount']);
		self::assertSame(2, $repository->findFailureSummaryByIpAddress('10.0.0.1', $since)['failureCount']);
		self::assertSame(1, $repository->findFailureSummaryByIpAddress('10.0.0.2', $since)['failureCount']);
		self::assertSame(0, $repository->findFailureSummaryByIpAddress('10.0.0.3', $since)['failureCount']);
	}

	public function testSuccessfulAttemptDoesNotCountAsFailure(): void
	{
		$repository = $this->getContainer()->getByType(SecurityLoginAttemptRepository::class);
		$since = new DateTimeImmutable('-15 minutes');

		$repository->recordAttempt('test@example.com', '127.0.0.1', true);

		$byEmail = $repository->findFailureSummaryByEmail('test@example.com', $since);
		self::assertSame(0, $byEmail['failureCount']);
		self::assertNull($byEmail['latestFailureAt']);
		self::assertSame(0, $repository->findFailureSummaryByIpAddress('127.0.0.1', $since)['failureCount']);
	}
}
