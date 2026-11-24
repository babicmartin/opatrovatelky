<?php declare(strict_types=1);

namespace App\Model\Security;

use App\Model\Repository\SecurityLoginAttemptRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final class LoginRateLimiter
{
	/** Failed attempts on one account, from any address. */
	private const int MAX_ACCOUNT_FAILURES = 10;

	/**
	 * Failed attempts from one address, on any account. Higher than the account limit,
	 * so one person's mistakes do not lock out colleagues behind the same address.
	 */
	private const int MAX_IP_FAILURES = 30;
	private const string WINDOW_MODIFIER = '-15 minutes';
	private const string BLOCK_MODIFIER = '+10 minutes';

	public function __construct(
		private readonly SecurityLoginAttemptRepository $loginAttemptRepository,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @throws LoginRateLimitUnavailableException when the attempts cannot be read
	 */
	public function getBlockedUntil(string $email, string $ipAddress): ?DateTimeImmutable
	{
		$since = new DateTimeImmutable(self::WINDOW_MODIFIER);

		try {
			$accountSummary = $this->loginAttemptRepository->findFailureSummaryByEmail($this->normalizeEmail($email), $since);
			$ipSummary = $this->loginAttemptRepository->findFailureSummaryByIpAddress($this->normalizeIpAddress($ipAddress), $since);
		} catch (\Throwable $exception) {
			$this->logger->error('Login rate limit check failed: ' . $exception->getMessage());
			throw new LoginRateLimitUnavailableException('Login attempts could not be read.', 0, $exception);
		}

		$accountBlockedUntil = $this->resolveBlockedUntil($accountSummary, self::MAX_ACCOUNT_FAILURES);
		$ipBlockedUntil = $this->resolveBlockedUntil($ipSummary, self::MAX_IP_FAILURES);

		if ($accountBlockedUntil === null || $ipBlockedUntil === null) {
			return $accountBlockedUntil ?? $ipBlockedUntil;
		}

		return max($accountBlockedUntil, $ipBlockedUntil);
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

	/**
	 * @param array{failureCount:int,latestFailureAt:?string} $summary
	 */
	private function resolveBlockedUntil(array $summary, int $maxFailures): ?DateTimeImmutable
	{
		if ($summary['failureCount'] < $maxFailures || $summary['latestFailureAt'] === null) {
			return null;
		}

		$latestFailureAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $summary['latestFailureAt']);
		if (!$latestFailureAt instanceof DateTimeImmutable) {
			return null;
		}

		$blockedUntil = $latestFailureAt->modify(self::BLOCK_MODIFIER);
		return $blockedUntil > new DateTimeImmutable() ? $blockedUntil : null;
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
