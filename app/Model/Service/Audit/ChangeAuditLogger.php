<?php declare(strict_types=1);

namespace App\Model\Service\Audit;

use App\Model\Repository\ChangeLogRepository;
use App\Model\Table\FileTableMap;
use Nette\Security\User;
use Psr\Log\LoggerInterface;

final class ChangeAuditLogger
{
	public function __construct(
		private readonly ChangeLogRepository $changeLogRepository,
		private readonly User $user,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function logCreated(string $context, string $entityTable, int $entityId, string $label = 'Záznam', array $metadata = []): void
	{
		$this->logAction($context, $entityTable, $entityId, 'record', 'Záznam', 'text', null, null, (string) $entityId, $label, 'created', $metadata);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function logDeleted(string $context, string $entityTable, int $entityId, string $label = 'Záznam', array $metadata = []): void
	{
		$this->logAction($context, $entityTable, $entityId, 'record', 'Záznam', 'text', (string) $entityId, $label, null, null, 'deleted', $metadata);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function logDocumentUploaded(string $context, int $documentId, string $fileName, string $dir, int $ownerId, array $metadata = []): void
	{
		$this->logAction(
			$context,
			FileTableMap::TABLE_NAME,
			$documentId,
			'document',
			'Dokument',
			'file',
			null,
			null,
			(string) $documentId,
			$fileName,
			'uploaded',
			$metadata + [
				'document_id' => $documentId,
				'document_dir' => $dir,
				'owner_id' => $ownerId,
			],
		);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function logDocumentDeleted(string $context, int $documentId, string $fileName, string $dir, int $ownerId, array $metadata = []): void
	{
		$this->logAction(
			$context,
			FileTableMap::TABLE_NAME,
			$documentId,
			'document',
			'Dokument',
			'file',
			(string) $documentId,
			$fileName,
			null,
			null,
			'deleted',
			$metadata + [
				'document_id' => $documentId,
				'document_dir' => $dir,
				'owner_id' => $ownerId,
			],
		);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	private function logAction(
		string $context,
		string $entityTable,
		int $entityId,
		string $fieldName,
		string $fieldLabel,
		string $valueType,
		?string $oldValueId,
		?string $oldValueLabel,
		?string $newValueId,
		?string $newValueLabel,
		string $action,
		array $metadata,
	): void {
		try {
			$this->changeLogRepository->logChange([
				'context' => $context,
				'entityTable' => $entityTable,
				'entityId' => $entityId,
				'fieldName' => $fieldName,
				'fieldLabel' => $fieldLabel,
				'columnName' => null,
				'valueType' => $valueType,
				'oldValueId' => $oldValueId,
				'oldValueLabel' => $oldValueLabel,
				'newValueId' => $newValueId,
				'newValueLabel' => $newValueLabel,
				'userId' => $this->getUserId(),
				'metadata' => ['action' => $action] + $metadata,
			]);
		} catch (\Throwable $e) {
			$this->logger->error('Change audit log failed.', [
				'action' => $action,
				'context' => $context,
				'entityTable' => $entityTable,
				'entityId' => $entityId,
				'exceptionClass' => $e::class,
				'exceptionMessage' => $e->getMessage(),
			]);
		}
	}

	private function getUserId(): ?int
	{
		return $this->user->isLoggedIn() && is_int($this->user->getId()) ? (int) $this->user->getId() : null;
	}
}
