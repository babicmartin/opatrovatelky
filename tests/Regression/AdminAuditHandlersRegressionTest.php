<?php declare(strict_types=1);

namespace Tests\Regression;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\TurnusTableMap;
use Nette\Application\Responses\RedirectResponse;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\ConfirmedFormWorkflowTrait;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class AdminAuditHandlersRegressionTest extends DatabaseTestCase
{
	use ConfirmedFormWorkflowTrait;

	public function testConfirmedTurnusCreateFormCreatesAuditLogRow(): void
	{
		$userId = $this->loginAsAdmin();
		$response = $this->submitConfirmedCreate('Admin:Turnus', 'Pridať nový turnus');
		self::assertInstanceOf(RedirectResponse::class, $response);

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

	protected function tearDown(): void
	{
		$this->getContainer()->getByType(User::class)->logout(true);
		$this->getContainer()->removeService('http.request');

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

	private function fetchLatestChangeLog(): ?\Nette\Database\Table\ActiveRow
	{
		return $this->getDatabase()
			->table(ChangeLogTableMap::TABLE_NAME)
			->order(ChangeLogTableMap::COL_ID . ' DESC')
			->fetch();
	}
}
