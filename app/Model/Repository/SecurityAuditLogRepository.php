<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\SecurityAuditLogTableMap;

final class SecurityAuditLogRepository extends BaseRepository
{
	protected function getTableName(): string
	{
		return SecurityAuditLogTableMap::TABLE_NAME;
	}

	/**
	 * @param array<string, mixed>|null $metadata
	 */
	public function logEvent(
		string $eventType,
		?int $userId,
		?string $email,
		?string $ipAddress,
		?string $userAgent,
		?array $metadata = null,
	): void {
		$metadataJson = $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
		if ($metadataJson === false) {
			$metadataJson = null;
		}

		$this->insert([
			SecurityAuditLogTableMap::COL_USER_ID => $userId,
			SecurityAuditLogTableMap::COL_EVENT_TYPE => substr($eventType, 0, 80),
			SecurityAuditLogTableMap::COL_EMAIL => $email !== null ? substr($email, 0, 190) : null,
			SecurityAuditLogTableMap::COL_IP_ADDRESS => $ipAddress !== null ? substr($ipAddress, 0, 45) : null,
			SecurityAuditLogTableMap::COL_USER_AGENT => $userAgent !== null ? substr($userAgent, 0, 512) : null,
			SecurityAuditLogTableMap::COL_METADATA => $metadataJson,
		]);
	}
}
