<?php declare(strict_types=1);

namespace Tests\Api;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\ChangeLogTableMap;
use App\Model\Table\UserTableMap;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as ApplicationRequest;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\Helpers;
use Nette\Http\IResponse;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;
use Tests\Support\PHPUnit\PresenterWorkflowTrait;

/**
 * Autosave of the access fields of an account (`active`, `permission`) through the `autosavePartial` signal.
 *
 * Everyone may open their own profile, so a request sent from the own profile page passes the page check
 * and is stopped only by UserManagementPresenter::validateAutosavePartialRequest(). Both routes are covered.
 */
final class UserAccessAutosaveContractTest extends DatabaseTestCase
{
	use PresenterWorkflowTrait;

	private const string Presenter = 'Admin:UserManagement';

	/** Row of `sn_active`; an account is created with 1, „Aktívny“. */
	private const int Inactive = 2;

	/**
	 * Actor, role of the other account (null: the actor's own account), whether the request comes
	 * from the actor's own profile page, field and value.
	 *
	 * @return iterable<string, array{UserRole, ?UserRole, bool, string, int}>
	 */
	public static function deniedRequests(): iterable
	{
		$junior = UserRole::DEALER_JUNIOR;
		$manager = UserRole::DEALER;
		$ceo = UserRole::CEO;
		$admin = UserRole::ADMIN;

		yield 'junior deactivates a colleague on the colleague\'s page' => [$junior, $junior, false, 'active', self::Inactive];
		yield 'junior deactivates a colleague from the own page' => [$junior, $junior, true, 'active', self::Inactive];
		yield 'junior deactivates the own account' => [$junior, null, true, 'active', self::Inactive];
		yield 'manager deactivates a junior on the junior\'s page' => [$manager, $junior, false, 'active', self::Inactive];
		yield 'manager deactivates a junior from the own page' => [$manager, $junior, true, 'active', self::Inactive];
		yield 'manager deactivates the own account' => [$manager, null, true, 'active', self::Inactive];
		yield 'manager raises the own role' => [$manager, null, true, 'permission', $ceo->getPermissionId()];
		yield 'manager raises a junior from the own page' => [$manager, $junior, true, 'permission', $ceo->getPermissionId()];
		yield 'CEO deactivates an admin on the admin\'s page' => [$ceo, $admin, false, 'active', self::Inactive];
		yield 'CEO deactivates an admin from the own page' => [$ceo, $admin, true, 'active', self::Inactive];
		yield 'CEO raises the own role to admin' => [$ceo, null, true, 'permission', $admin->getPermissionId()];
	}

	#[DataProvider('deniedRequests')]
	public function testRoleWithoutTheRightChangesNothing(UserRole $actor, ?UserRole $other, bool $fromOwnPage, string $field, int $value): void
	{
		$actorId = $this->loginAs($actor);
		$targetId = $other === null ? $actorId : $this->createAccount($other);
		$before = $this->readAccess($targetId);

		[$status, $payload] = $this->autosave($fromOwnPage ? $actorId : $targetId, $targetId, $field, $value);

		self::assertSame(403, $status);
		if ($fromOwnPage) {
			// The page itself is allowed; the refusal comes from the autosave check.
			self::assertSame(['success' => false, 'message' => 'Prístup zamietnutý.'], $payload);
		} else {
			self::assertNull($payload);
		}
		self::assertSame($before, $this->readAccess($targetId));
		self::assertSame(0, $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->count('*'));
	}

	/**
	 * @return iterable<string, array{UserRole, UserRole}>
	 */
	public static function allowedRequests(): iterable
	{
		yield 'CEO deactivates a manager' => [UserRole::CEO, UserRole::DEALER];
		yield 'admin deactivates another admin' => [UserRole::ADMIN, UserRole::ADMIN];
	}

	#[DataProvider('allowedRequests')]
	public function testRoleWithTheRightDeactivatesTheAccount(UserRole $actor, UserRole $other): void
	{
		$actorId = $this->loginAs($actor);
		$targetId = $this->createAccount($other);

		[$status, $payload] = $this->autosave($targetId, $targetId, 'active', self::Inactive);

		self::assertSame(200, $status);
		self::assertSame(['success' => true], $payload);
		self::assertSame(['active' => self::Inactive, 'permission' => $other->getPermissionId()], $this->readAccess($targetId));

		$changes = $this->getDatabase()->table(ChangeLogTableMap::TABLE_NAME)->fetchAll();
		self::assertCount(1, $changes);
		$change = reset($changes);
		self::assertNotFalse($change);
		self::assertSame('user.access', $change->{ChangeLogTableMap::COL_CONTEXT});
		self::assertSame('active', $change->{ChangeLogTableMap::COL_FIELD_NAME});
		self::assertSame($targetId, $change->{ChangeLogTableMap::COL_ENTITY_ID});
		self::assertSame($actorId, $change->{ChangeLogTableMap::COL_USER_ID});
	}

	public function testGuestIsSentToTheLoginPageAndChangesNothing(): void
	{
		$targetId = $this->createAccount(UserRole::DEALER);
		$before = $this->readAccess($targetId);
		$this->logout();

		[$status, $payload] = $this->autosave($targetId, $targetId, 'active', self::Inactive);

		self::assertSame(200, $status);
		self::assertIsArray($payload);
		self::assertArrayNotHasKey('success', $payload);
		self::assertIsString($payload['redirect'] ?? null);
		self::assertSame('/login/', parse_url($payload['redirect'], PHP_URL_PATH));
		self::assertSame($before, $this->readAccess($targetId));
	}

	/**
	 * Sends the signal to the profile page of $pageUserId and asks to change the account $targetId.
	 *
	 * @return array{int, array<string, mixed>|null} HTTP status and the JSON payload; null when the page itself was refused
	 */
	private function autosave(int $pageUserId, int $targetId, string $field, int $value): array
	{
		$request = new HttpRequest(
			new UrlScript('https://opatrovatelky.local/'),
			[
				'id' => (string) $targetId,
				'__autosave_context' => 'user.access',
				'__autosave_field' => $field,
				'__autosave_value' => (string) $value,
			],
			[],
			[Helpers::StrictCookieName => '1'],
			['X-Requested-With' => 'XMLHttpRequest'],
			'POST',
		);

		$container = $this->getContainer();
		$container->removeService('http.request');
		$container->addService('http.request', $request);
		$httpResponse = $container->getByType(IResponse::class);
		$httpResponse->setCode(IResponse::S200_OK);

		$presenter = $container->getByType(IPresenterFactory::class)->createPresenter(self::Presenter);
		self::assertInstanceOf(Presenter::class, $presenter);
		$presenter->autoCanonicalize = false;

		try {
			$response = $presenter->run(new ApplicationRequest(self::Presenter, 'POST', [
				'action' => 'update',
				'id' => $pageUserId,
				Presenter::SignalKey => 'autosavePartial',
			]));
		} catch (BadRequestException $exception) {
			return [$exception->getHttpCode(), null];
		}

		self::assertInstanceOf(JsonResponse::class, $response);

		return [$httpResponse->getCode(), (array) $response->getPayload()];
	}

	private function createAccount(UserRole $role): int
	{
		return TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'other.account@example.test',
			UserTableMap::COL_PERMISSION => $role->getPermissionId(),
		]);
	}

	/**
	 * @return array{active: int, permission: int}
	 */
	private function readAccess(int $userId): array
	{
		$row = $this->getDatabase()->table(UserTableMap::TABLE_NAME)->get($userId);
		self::assertNotNull($row);

		return [
			'active' => (int) $row->{UserTableMap::COL_ACTIVE},
			'permission' => (int) $row->{UserTableMap::COL_PERMISSION},
		];
	}

	protected function tearDown(): void
	{
		$this->logout();
		$this->getContainer()->removeService('http.request');

		parent::tearDown();
	}
}
