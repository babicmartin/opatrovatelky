<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Service\Autosave\AutosaveFieldUpdateService;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\TurnusTableMap;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\RedirectResponse;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\FakePostRequest;
use Tests\Support\PHPUnit\ConfirmedFormWorkflowTrait;
use Tests\Support\PHPUnit\DatabaseTestCase;

/**
 * In-process end-to-end workflows driven through the real DI container, authenticator,
 * presenters and services. No browser required; the Playwright scaffold (see playwright/)
 * covers the real-browser layer separately.
 */
final class AdminWorkflowE2ETest extends DatabaseTestCase
{
	use ConfirmedFormWorkflowTrait;

	private const string PASSWORD = 'secret123!';

	public function testLoginNavigateAutosaveAuditAndLogout(): void
	{
		$email = 'ceo.e2e@example.test';
		TestDatabase::createUser([
			'email' => $email,
			'permission' => UserRole::CEO->getPermissionId(),
			'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
		]);

		$user = $this->getContainer()->getByType(User::class);
		$user->login($email, self::PASSWORD);
		self::assertTrue($user->isLoggedIn());

		// Navigate to a protected page.
		$settings = $this->renderPresenter('Admin:Settings');
		self::assertStringContainsString('Evidencia zmien', $settings);

		// Edit a field via autosave -> DB update + audit row.
		$babysitterId = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Anna']);
		$this->getContainer()->getByType(AutosaveFieldUpdateService::class)->tryHandleRequest(new FakePostRequest([
			'id' => (string) $babysitterId,
			'__autosave_context' => 'babysitter.address',
			'__autosave_field' => 'name',
			'__autosave_value' => 'Eva',
		]));

		self::assertSame('Eva', $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($babysitterId)?->{OpatrovatelkaTableMap::COL_NAME});
		$log = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->order(ChangeLogTableMap::COL_ID . ' DESC')->fetch();
		self::assertNotNull($log);
		self::assertSame('Eva', $log->{ChangeLogTableMap::COL_NEW_VALUE_LABEL});

		// The change surfaces on the audit page.
		$changeLogPage = $this->renderPresenter('Admin:ChangeLog');
		self::assertStringContainsString('Evidencia zmien', $changeLogPage);
		self::assertStringContainsString('Eva', $changeLogPage);

		$user->logout(true);
		self::assertFalse($user->isLoggedIn());
	}

	public function testWrongPasswordIsRejected(): void
	{
		$email = 'reject.e2e@example.test';
		TestDatabase::createUser([
			'email' => $email,
			'permission' => UserRole::CEO->getPermissionId(),
			'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
		]);

		$user = $this->getContainer()->getByType(User::class);

		$this->expectException(AuthenticationException::class);
		$user->login($email, 'wrong-password');
	}

	public function testDealerCannotAccessChangeLog(): void
	{
		$this->loginAs(UserRole::DEALER);

		try {
			$this->runPresenter('Admin:ChangeLog');
			self::fail('Dealer must not open the change log page.');
		} catch (BadRequestException $exception) {
			self::assertSame(403, $exception->getCode());
		}
	}

	public function testTurnusCreateAndDeleteWorkflowWritesAudit(): void
	{
		$userId = $this->loginAs(UserRole::ADMIN);

		// Create
		$response = $this->submitConfirmedCreate('Admin:Turnus', 'Pridať nový turnus');
		self::assertInstanceOf(RedirectResponse::class, $response);
		$turnus = $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->order(TurnusTableMap::COL_ID . ' DESC')->fetch();
		self::assertNotNull($turnus);
		$turnusId = (int) $turnus->{TurnusTableMap::COL_ID};
		self::assertSame('{"action":"created"}', (string) $this->latestLog()->{ChangeLogTableMap::COL_METADATA});

		// Delete (soft)
		$params = ['action' => 'update', 'id' => $turnusId];
		$post = $this->readDeleteForm($this->renderPresenter('Admin:Turnus', $params), 'frm-deleteTurnusForm');
		$response = $this->runFormRequest('Admin:Turnus', 'POST', $params, $post);
		self::assertInstanceOf(RedirectResponse::class, $response);
		self::assertSame(1, (int) $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->get($turnusId)?->{TurnusTableMap::COL_DELETED});
		self::assertSame('{"action":"deleted"}', (string) $this->latestLog()->{ChangeLogTableMap::COL_METADATA});
		self::assertSame((string) $userId, (string) $this->latestLog()->{ChangeLogTableMap::COL_USER_ID});
	}

	protected function tearDown(): void
	{
		$this->logout();
		$this->getContainer()->removeService('http.request');

		parent::tearDown();
	}

	private function latestLog(): \Nette\Database\Table\ActiveRow
	{
		$log = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->order(ChangeLogTableMap::COL_ID . ' DESC')->fetch();
		self::assertNotNull($log);

		return $log;
	}
}
