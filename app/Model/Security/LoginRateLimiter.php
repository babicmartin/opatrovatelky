<?php declare(strict_types=1);

namespace App\Model\Security;

use App\Model\Repository\SecurityLoginAttemptRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final class LoginRateLimiter
{
	private const int MAX_FAILURES = 10;
	private const string WINDOW_MODIFIER = '-15 minutes';
	private const string BLOCK_MODIFIER = '+10 minutes';

	public function __construct(
		private readonly SecurityLoginAttemptRepository $loginAttemptRepository,
		private readonly LoggerInterface $logger,
	) {
	}

	public function getBlockedUntil(string $email, string $ipAddress): ?DateTimeImmutable
	{
		try {
			$summary = $this->loginAttemptRepository->findFailureSummary(
				$this->normalizeEmail($email),
				$this->normalizeIpAddress($ipAddress),
				new DateTimeImmutable(self::WINDOW_MODIFIER),
			);
		} catch (\Throwable $exception) {
			$this->logger->error('Login rate limit check failed: ' . $exception->getMessage());
			return null;
		}

		if ($summary['failureCount'] < self::MAX_FAILURES || $summary['latestFailureAt'] === null) {
			return null;
		}

		$latestFailureAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $summary['latestFailureAt']);
		if (!$latestFailureAt instanceof DateTimeImmutable) {
			return null;
		}

		$blockedUntil = $latestFailureAt->modify(self::BLOCK_MODIFIER);
		return $blockedUntil > new DateTimeImmutable() ? $blockedUntil : null;
	}

	public function recordSuccess(string $email, string $ipAddress): void
	{
		$this->recordAttempt($email, $ipAddress, true, null);
	}

	public function recordFailure(string $email, string $ipAddress, string $failureReason): void
	{
		$this->recordAttempt($email, $ipAddress, false, $failureReason);
	}

	public function normalizeEmail(string $email): string
	{
		return strtolower(trim($email));
	}

	private function normalizeIpAddress(string $ipAddress): string
	{
		$ipAddress = trim($ipAddress);
		return $ipAddress !== '' ? $ipAddress : 'unknown';
	}

	private function recordAttempt(string $email, string $ipAddress, bool $success, ?string $failureReason): void
	{
		try {
			$this->loginAttemptRepository->recordAttempt(
				$this->normalizeEmail($email),
				$this->normalizeIpAddress($ipAddress),
				$success,
				$failureReason,
			);
		} catch (\Throwable $exception) {
			$this->logger->error('Login attempt logging failed: ' . $exception->getMessage());
		}
	}
}
