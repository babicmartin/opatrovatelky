<?php declare(strict_types=1);

namespace Tests\Unit\Model\Service\Audit;

use App\Model\Repository\ChangeLogRepository;
use App\Model\Service\Audit\ChangeAuditLogger;
use App\Model\Table\FileTableMap;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Security\UserStorage;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Throwable;
use Tests\Support\PHPUnit\TestCase;

final class ChangeAuditLoggerTest extends TestCase
{
	public function testLogCreatedForwardsActionMetadataAndCurrentUser(): void
	{
		$repository = new ChangeLogRepositorySpy();

		$this->logger($repository)->logCreated('family.shortInfo', 'sn_families', 12, 'Rodina', ['source' => 'test']);

		self::assertCount(1, $repository->changes);
		$change = $repository->changes[0];
		self::assertSame('family.shortInfo', $change['context']);
		self::assertSame('sn_families', $change['entityTable']);
		self::assertSame(12, $change['entityId']);
		self::assertSame('record', $change['fieldName']);
		self::assertSame('12', $change['newValueId']);
		self::assertSame('Rodina', $change['newValueLabel']);
		self::assertSame(7, $change['userId']);
		self::assertSame(['action' => 'created', 'source' => 'test'], $change['metadata']);
	}

	public function testLogDocumentUploadedAddsDocumentMetadata(): void
	{
		$repository = new ChangeLogRepositorySpy();

		$this->logger($repository)->logDocumentUploaded('documents.babysitter', 15, 'profile.pdf', 'babysitters/10', 10, ['custom' => 'value']);

		self::assertCount(1, $repository->changes);
		$change = $repository->changes[0];
		self::assertSame('documents.babysitter', $change['context']);
		self::assertSame(FileTableMap::TABLE_NAME, $change['entityTable']);
		self::assertSame(15, $change['entityId']);
		self::assertSame('document', $change['fieldName']);
		self::assertSame('file', $change['valueType']);
		self::assertNull($change['oldValueId']);
		self::assertSame('15', $change['newValueId']);
		self::assertSame('profile.pdf', $change['newValueLabel']);
		self::assertSame([
			'action' => 'uploaded',
			'custom' => 'value',
			'document_id' => 15,
			'document_dir' => 'babysitters/10',
			'owner_id' => 10,
		], $change['metadata']);
	}

	public function testRepositoryFailureIsLoggedAndNotThrown(): void
	{
		$repository = new ChangeLogRepositorySpy(new RuntimeException('database down'));
		$logger = new MemoryLogger();

		$service = new ChangeAuditLogger($repository, $this->user(null), $logger);
		$service->logDeleted('todo.update', 'sn_todo_client', 3, 'Úloha');

		self::assertCount(1, $logger->records);
		$record = $logger->records[0];
		self::assertSame('error', $record['level']);
		self::assertSame('Change audit log failed.', $record['message']);
		self::assertSame('deleted', $record['context']['action']);
		self::assertSame('todo.update', $record['context']['context']);
		self::assertSame('sn_todo_client', $record['context']['entityTable']);
		self::assertSame(3, $record['context']['entityId']);
		self::assertSame(RuntimeException::class, $record['context']['exceptionClass']);
		self::assertSame('database down', $record['context']['exceptionMessage']);
	}

	private function logger(ChangeLogRepository $repository): ChangeAuditLogger
	{
		return new ChangeAuditLogger($repository, $this->user(7), new MemoryLogger());
	}

	private function user(?int $id): User
	{
		$identity = $id !== null ? new SimpleIdentity($id, ['admin']) : null;

		return new User(new class($identity) implements UserStorage {
			public function __construct(private ?IIdentity $identity)
			{
			}

			public function saveAuthentication(IIdentity $identity): void
			{
				$this->identity = $identity;
			}

			public function clearAuthentication(bool $clearIdentity): void
			{
				if ($clearIdentity) {
					$this->identity = null;
				}
			}

			public function getState(): array
			{
				return [$this->identity !== null, $this->identity, null];
			}

			public function setExpiration(?string $expire, bool $clearIdentity): void
			{
			}
		});
	}
}

final class ChangeLogRepositorySpy extends ChangeLogRepository
{
	/** @var list<array<string, mixed>> */
	public array $changes = [];

	public function __construct(private readonly ?Throwable $exception = null)
	{
	}

	/**
	 * @param array<string, mixed> $change
	 */
	public function logChange(array $change): void
	{
		if ($this->exception !== null) {
			throw $this->exception;
		}

		$this->changes[] = $change;
	}
}

final class MemoryLogger extends AbstractLogger
{
	/** @var list<array{level:mixed,message:string,context:array<string, mixed>}> */
	public array $records = [];

	/**
	 * @param array<string, mixed> $context
	 */
	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$this->records[] = [
			'level' => $level,
			'message' => (string) $message,
			'context' => $context,
		];
	}
}
