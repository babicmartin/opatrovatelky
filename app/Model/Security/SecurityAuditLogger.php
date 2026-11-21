<?php declare(strict_types=1);

namespace App\Model\Security;

use App\Model\Repository\SecurityAuditLogRepository;
use Nette\Http\IRequest;
use Psr\Log\LoggerInterface;

final class SecurityAuditLogger
{
	public function __construct(
		private readonly SecurityAuditLogRepository $securityAuditLogRepository,
		private readonly IRequest $httpRequest,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @param array<string, mixed>|null $metadata
	 */
	public function log(string $eventType, ?int $userId = null, ?string $email = null, ?array $metadata = null): void
	{
		try {
			$this->logRequired($eventType, $userId, $email, $metadata);
		} catch (\Throwable $exception) {
			$this->logger->error('Security audit logging failed: ' . $exception->getMessage());
		}
	}

	/** @param array<string, mixed>|null $metadata */
	public function logRequired(string $eventType, ?int $userId, ?string $email, ?array $metadata): void
	{
		$this->securityAuditLogRepository->logEvent(
			$eventType,
			$userId,
			$email !== null ? strtolower(trim($email)) : null,
			$this->httpRequest->getRemoteAddress(),
			$this->httpRequest->getHeader('User-Agent'),
			$metadata,
		);
	}
}
