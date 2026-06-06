<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Service\Autosave\AutosaveFieldUpdateService;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\TurnusTableMap;
use App\UI\Admin\Turnus\TurnusPresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Responses\TextResponse;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\FakePostRequest;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * In-process end-to-end workflows driven through the real DI container, authenticator,
 * presenters and services. No browser required; the Playwright scaffold (see playwright/)
 * covers the real-browser layer separately.
 */
final class AdminWorkflowE2ETest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

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
		$presenter = $this->bootstrapTurnusPresenter();

		// Create
		$this->assertRedirects(static fn() => $presenter->handleCreate());
		$turnus = $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->order(TurnusTableMap::COL_ID . ' DESC')->fetch();
		self::assertNotNull($turnus);
		$turnusId = (int) $turnus->{TurnusTableMap::COL_ID};
		self::assertSame('{"action":"created"}', (string) $this->latestLog()->{ChangeLogTableMap::COL_METADATA});

		// Delete (soft)
		$this->assertRedirects(static fn() => $presenter->handleDelete($turnusId));
		self::assertSame(1, (int) $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->get($turnusId)?->{TurnusTableMap::COL_DELETED});
		self::assertSame('{"action":"deleted"}', (string) $this->latestLog()->{ChangeLogTableMap::COL_METADATA});
		self::assertSame((string) $userId, (string) $this->latestLog()->{ChangeLogTableMap::COL_USER_ID});
	}

	protected function tearDown(): void
	{
		$this->logout();

		parent::tearDown();
	}

	private function bootstrapTurnusPresenter(): TurnusPresenter
	{
		$presenter = $this->getContainer()->getByType(IPresenterFactory::class)->createPresenter('Admin:Turnus');
		if (!$presenter instanceof TurnusPresenter) {
			self::fail('Admin:Turnus must be a Turnus presenter.');
		}

		$presenter->autoCanonicalize = false;
		$response = $presenter->run(new ApplicationRequest('Admin:Turnus', 'GET', ['action' => 'default']));
		self::assertInstanceOf(TextResponse::class, $response);

		return $presenter;
	}

	/**
	 * @param callable(): void $callback
	 */
	private function assertRedirects(callable $callback): void
	{
		try {
			$callback();
			self::fail('Handler must finish by redirect.');
		} catch (AbortException) {
			self::addToAssertionCount(1);
		}
	}

	private function latestLog(): \Nette\Database\Table\ActiveRow
	{
		$log = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->order(ChangeLogTableMap::COL_ID . ' DESC')->fetch();
		self::assertNotNull($log);

		return $log;
	}
}
