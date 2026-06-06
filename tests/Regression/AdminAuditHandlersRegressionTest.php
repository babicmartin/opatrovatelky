<?php declare(strict_types=1);

namespace Tests\Regression;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\TurnusTableMap;
use App\UI\Admin\Turnus\TurnusPresenter;
use Nette\Application\AbortException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Responses\TextResponse;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class AdminAuditHandlersRegressionTest extends DatabaseTestCase
{
	public function testTurnusCreateHandlerCreatesAuditLogRow(): void
	{
		$userId = $this->loginAsAdmin();
		$presenter = $this->createBootstrappedTurnusPresenter();

		$this->assertHandlerRedirects(static function () use ($presenter): void {
			$presenter->handleCreate();
		});

		$turnus = $this->getDatabase()
			->table(TurnusTableMap::TABLE_NAME)
			->order(TurnusTableMap::COL_ID . ' DESC')
			->fetch();
		self::assertNotNull($turnus);
		self::assertSame($userId, (int) $turnus->{TurnusTableMap::COL_USER_CREATED});

		$log = $this->fetchLatestChangeLog();
		self::assertNotNull($log);
		self::assertSame('turnus.update', $log->{ChangeLogTableMap::COL_CONTEXT});
		self::assertSame(TurnusTableMap::TABLE_NAME, $log->{ChangeLogTableMap::COL_ENTITY_TABLE});
		self::assertSame((int) $turnus->{TurnusTableMap::COL_ID}, (int) $log->{ChangeLogTableMap::COL_ENTITY_ID});
		self::assertSame((string) $userId, (string) $log->{ChangeLogTableMap::COL_USER_ID});
		self::assertJsonStringEqualsJsonString('{"action":"created"}', (string) $log->{ChangeLogTableMap::COL_METADATA});
	}

	public function testTurnusDeleteHandlerSoftDeletesAndCreatesAuditLogRow(): void
	{
		$userId = $this->loginAsAdmin();
		$presenter = $this->createBootstrappedTurnusPresenter();
		$turnusId = TestDatabase::createTurnus([
			TurnusTableMap::COL_USER_CREATED => $userId,
			TurnusTableMap::COL_USER_ID => $userId,
			TurnusTableMap::COL_DELETED => 0,
		]);

		$this->assertHandlerRedirects(static function () use ($presenter, $turnusId): void {
			$presenter->handleDelete($turnusId);
		});

		$turnus = $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->get($turnusId);
		self::assertNotNull($turnus);
		self::assertSame(1, (int) $turnus->{TurnusTableMap::COL_DELETED});

		$log = $this->fetchLatestChangeLog();
		self::assertNotNull($log);
		self::assertSame('turnus.update', $log->{ChangeLogTableMap::COL_CONTEXT});
		self::assertSame(TurnusTableMap::TABLE_NAME, $log->{ChangeLogTableMap::COL_ENTITY_TABLE});
		self::assertSame($turnusId, (int) $log->{ChangeLogTableMap::COL_ENTITY_ID});
		self::assertSame((string) $userId, (string) $log->{ChangeLogTableMap::COL_USER_ID});
		self::assertJsonStringEqualsJsonString('{"action":"deleted"}', (string) $log->{ChangeLogTableMap::COL_METADATA});
	}

	protected function tearDown(): void
	{
		$this->getContainer()->getByType(User::class)->logout(true);

		parent::tearDown();
	}

	private function loginAsAdmin(): int
	{
		$userId = TestDatabase::createUser([
			'email' => 'admin.audit@example.test',
			'permission' => UserRole::ADMIN->getPermissionId(),
		]);
		$this->getContainer()->getByType(User::class)->login(new SimpleIdentity($userId, [UserRole::ADMIN->value], [
			'email' => 'admin.audit@example.test',
		]));

		return $userId;
	}

	/**
	 * @param callable(): void $callback
	 */
	private function assertHandlerRedirects(callable $callback): void
	{
		try {
			$callback();
			self::fail('Presenter handler must finish by redirect.');
		} catch (AbortException) {
			self::addToAssertionCount(1);
		}
	}

	private function createBootstrappedTurnusPresenter(): TurnusPresenter
	{
		$presenterFactory = $this->getContainer()->getByType(IPresenterFactory::class);
		$presenter = $presenterFactory->createPresenter('Admin:Turnus');
		if (!$presenter instanceof TurnusPresenter) {
			self::fail('Admin:Turnus presenter must be a Turnus presenter.');
		}

		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new ApplicationRequest('Admin:Turnus', 'GET', [
			'action' => 'default',
		]));
		self::assertInstanceOf(TextResponse::class, $response);

		return $presenter;
	}

	private function fetchLatestChangeLog(): ?\Nette\Database\Table\ActiveRow
	{
		return $this->getDatabase()
			->table(ChangeLogTableMap::TABLE_NAME)
			->order(ChangeLogTableMap::COL_ID . ' DESC')
			->fetch();
	}
}
