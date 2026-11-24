<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\SecurityAuditLogTableMap;
use App\Model\Table\UserTableMap;
use Dom\HTMLDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class PasswordChangeHttpTest extends DatabaseTestCase
{
	private const OldPassword = 'Original123!';
	private const NewPassword = 'Changed123!';
	private const Email = 'owner@example.test';
	private const OwnForm = 'frm-userPasswordUpdateForm';
	private const ResetForm = 'frm-userPasswordResetForm';
	private const AccessForm = 'frm-userAccessUpdateForm';

	/** @var list<TestWebClient> */
	private array $browsers = [];


	protected function tearDown(): void
	{
		foreach ($this->browsers as $browser) {
			$browser->stop();
		}
		parent::tearDown();
	}


	public function testOwnChangeKeepsOnlyTheSubmittingBrowserSignedIn(): void
	{
		$id = $this->createAccount(self::Email, UserRole::ADMIN);
		$first = $this->login(self::Email);
		$second = $this->login(self::Email);
		$path = '/user-management/update/' . $id;
		$page = (string) $first->get($path)->getBody();
		self::assertStringContainsString('name="currentPassword"', $page);
		self::assertStringContainsString('Na zmenu hesla zadajte aj svoje pôvodné heslo.', $page);
		$this->assertPasswordRequirements($page, self::OwnForm);
		self::assertStringNotContainsString(self::ResetForm, $page);
		$fields = ['password' => self::NewPassword, 'passwordRepeat' => self::NewPassword];
		foreach (['' => 'Zadajte aktuálne heslo.', 'incorrect' => 'Aktuálne heslo nie je správne.'] as $current => $error) {
			$response = $first->submitForm($path, self::OwnForm, $fields + ['currentPassword' => $current]);
			self::assertSame(200, $response->getStatusCode());
			self::assertStringContainsString($error, (string) $response->getBody());
			self::assertSame(200, $second->get($path)->getStatusCode());
			self::assertSame(0, $this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)
				->where(SecurityAuditLogTableMap::COL_EVENT_TYPE, 'password_changed')->count('*'));
		}
		$third = $this->login(self::Email);
		self::assertSame(303, $first->submitForm($path, self::OwnForm, $fields + ['currentPassword' => self::OldPassword])->getStatusCode());
		self::assertSame(200, $first->get($path)->getStatusCode());
		$this->assertLoginRequired($second, $path);
		$this->assertLoginRequired($third, $path);
		$this->assertPasswordAudit('password_changed', $id, $id);
		self::assertSame(200, $second->login(self::Email, self::OldPassword)->getStatusCode());
		self::assertSame(303, $second->login(self::Email, self::NewPassword)->getStatusCode());
		self::assertSame(200, $second->get($path)->getStatusCode());
	}


	public function testAdminResetRevokesEveryTargetSession(): void
	{
		$id = $this->createAccount(self::Email, UserRole::DEALER);
		$adminId = $this->createAccount('admin@example.test', UserRole::ADMIN);
		$first = $this->login(self::Email);
		$second = $this->login(self::Email);
		$admin = $this->login('admin@example.test');
		$path = '/user-management/update/' . $id;
		$page = (string) $admin->get($path)->getBody();
		self::assertStringContainsString(self::ResetForm, $page);
		self::assertStringNotContainsString('name="currentPassword"', $page);
		self::assertStringNotContainsString('Na zmenu hesla zadajte aj svoje pôvodné heslo.', $page);
		$this->assertPasswordRequirements($page, self::ResetForm);
		self::assertSame(303, $admin->submitForm($path, self::ResetForm, [
			'password' => self::NewPassword,
			'passwordRepeat' => self::NewPassword,
		])->getStatusCode());
		$this->assertLoginRequired($first, $path);
		$this->assertLoginRequired($second, $path);
		self::assertSame(200, $admin->get($path)->getStatusCode());
		self::assertSame(200, $first->login(self::Email, self::OldPassword)->getStatusCode());
		self::assertSame(303, $first->login(self::Email, self::NewPassword)->getStatusCode());
		$this->assertPasswordAudit('password_reset', $adminId, $id);
	}


	private function assertPasswordRequirements(string $html, string $formId): void
	{
		$page = HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
		$form = $page->getElementById($formId);
		self::assertNotNull($form);
		$password = $form->querySelector('input[name=password]');
		self::assertNotNull($password);
		$list = $page->getElementById((string) $password->getAttribute('aria-describedby'));
		self::assertNotNull($list);
		$labels = [];
		foreach ($list->querySelectorAll('li') as $item) {
			$labels[] = trim($item->textContent ?? '');
		}
		self::assertSame([
			'Aspoň 8 znakov',
			'Aspoň jedna číslica (0–9)',
			'Aspoň jeden špeciálny znak, napr. !, ? alebo @',
		], $labels);
	}


	public function testNewPasswordRequirementsAreEnforcedOnSubmit(): void
	{
		$id = $this->createAccount(self::Email, UserRole::ADMIN);
		$browser = $this->login(self::Email);
		$before = $this->readAccessAndPassword($id);
		foreach ([
			['Abcd1!🙂', 'Abcd1!🙂', 'Heslo musí mať aspoň 8 znakov.'],
			['abcdefg!', 'abcdefg!', 'Heslo musí obsahovať aspoň jednu číslicu.'],
			['abcdefg1', 'abcdefg1', 'Heslo musí obsahovať aspoň jeden špeciálny znak.'],
			[self::NewPassword, self::OldPassword, 'Heslá sa nezhodujú.'],
		] as [$password, $repeat, $error]) {
			$response = $browser->submitForm('/user-management/update', self::OwnForm, [
				'currentPassword' => self::OldPassword,
				'password' => $password,
				'passwordRepeat' => $repeat,
			]);
			self::assertSame(200, $response->getStatusCode());
			$page = HTMLDocument::createFromString((string) $response->getBody(), LIBXML_NOERROR, 'UTF-8');
			self::assertSame($error, $page->querySelector('.password-form .text-danger')?->textContent);
			self::assertSame($before, $this->readAccessAndPassword($id));
		}
	}


	public function testResetChecksAccessWhenReadingAndSubmitting(): void
	{
		$id = $this->createAccount(self::Email, UserRole::ADMIN);
		$this->createAccount('ceo@example.test', UserRole::CEO);
		$this->createAccount('dealer@example.test', UserRole::DEALER);
		$path = '/user-management/update/' . $id;
		$admin = $this->login(self::Email);
		$fields = ['_do' => 'userPasswordResetForm-submit', 'password' => self::NewPassword, 'passwordRepeat' => self::NewPassword];
		self::assertSame(403, $admin->post($path, $fields)->getStatusCode());
		foreach (['ceo@example.test', 'dealer@example.test'] as $email) {
			$browser = $this->login($email);
			self::assertSame(403, $browser->get($path)->getStatusCode());
			self::assertSame(403, $browser->post($path, $fields)->getStatusCode());
		}
		self::assertSame(0, $this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)
			->where(SecurityAuditLogTableMap::COL_EVENT_TYPE, 'password_reset')->count('*'));
		self::assertSame(200, $this->login(self::Email)->get($path)->getStatusCode());
	}


	/**
	 * Actor => owner of the profile. User management starts at CEO (AuthorizatorFactory),
	 * and a CEO may not touch an admin account.
	 *
	 * @return array<string, array{?UserRole, UserRole}>
	 */
	public static function deniedProfiles(): array
	{
		return [
			'guest' => [null, UserRole::DEALER],
			'junior editing dealer' => [UserRole::DEALER_JUNIOR, UserRole::DEALER],
			'junior editing another junior' => [UserRole::DEALER_JUNIOR, UserRole::DEALER_JUNIOR],
			'dealer editing another dealer' => [UserRole::DEALER, UserRole::DEALER],
			'dealer editing junior' => [UserRole::DEALER, UserRole::DEALER_JUNIOR],
			'CEO editing admin' => [UserRole::CEO, UserRole::ADMIN],
		];
	}


	#[DataProvider('deniedProfiles')]
	public function testDeniedForeignProfile(?UserRole $actor, UserRole $target): void
	{
		$id = $this->createAccount(self::Email, $target);
		$before = $this->readAccessAndPassword($id);
		$path = '/user-management/update/' . $id;
		$browser = $this->openBrowser();
		if ($actor !== null) {
			$this->createAccount('actor@example.test', $actor);
			self::assertSame(303, $browser->login('actor@example.test', self::OldPassword)->getStatusCode());
		}

		$response = $browser->get($path);
		$this->assertPrivateProfileDenied($response, $actor === null ? 302 : 403);
		self::assertSame($before, $this->readAccessAndPassword($id));

		foreach ([
			['_do' => 'userAccessUpdateForm-submit', 'permission' => (string) UserRole::CEO->getPermissionId(), 'active' => '1'],
			['_do' => 'userPasswordResetForm-submit', 'password' => self::NewPassword, 'passwordRepeat' => self::NewPassword],
		] as $fields) {
			$response = $browser->post($path, $fields + ['id' => (string) $id]);
			$this->assertPrivateProfileDenied($response, $actor === null ? 303 : 403);
			self::assertSame($before, $this->readAccessAndPassword($id));
		}

		$response = $this->autosaveRole($browser, $id, UserRole::CEO);
		$this->assertPrivateProfileDenied($response, $actor === null ? 200 : 403);
		if ($actor === null) {
			$payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
			self::assertSame('/login/', parse_url($payload['redirect'], PHP_URL_PATH));
		}
		self::assertSame($before, $this->readAccessAndPassword($id));
	}


	private function assertPrivateProfileDenied(ResponseInterface $response, int $status): void
	{
		self::assertSame($status, $response->getStatusCode());
		if ($status === 302 || $status === 303) {
			self::assertSame('/login/', parse_url($response->getHeaderLine('Location'), PHP_URL_PATH));
		}
		$html = (string) $response->getBody();
		self::assertStringNotContainsString(self::Email, $html);
		self::assertStringNotContainsString(self::AccessForm, $html);
		self::assertStringNotContainsString(self::ResetForm, $html);
	}


	/** @return array<string, array{UserRole, UserRole}> */
	public static function allowedProfiles(): array
	{
		return [
			'CEO editing dealer' => [UserRole::CEO, UserRole::DEALER],
			'admin editing another admin' => [UserRole::ADMIN, UserRole::ADMIN],
		];
	}


	#[DataProvider('allowedProfiles')]
	public function testAllowedForeignProfile(UserRole $actor, UserRole $target): void
	{
		$id = $this->createAccount(self::Email, $target);
		$this->createAccount('manager@example.test', $actor);
		$browser = $this->login('manager@example.test');
		$path = '/user-management/update/' . $id;
		$before = $this->readAccessAndPassword($id);
		$response = $browser->get($path);
		self::assertSame(200, $response->getStatusCode());
		$page = HTMLDocument::createFromString((string) $response->getBody(), LIBXML_NOERROR, 'UTF-8');
		self::assertNotNull($page->getElementById(self::ResetForm));
		$access = $page->getElementById(self::AccessForm);
		self::assertNotNull($access);
		$adminOption = $access->querySelector('select[name=permission] option[value="' . UserRole::ADMIN->getPermissionId() . '"]');
		self::assertSame($actor === UserRole::ADMIN, $adminOption !== null);

		$response = $browser->submitForm($path, self::ResetForm, ['password' => self::NewPassword, 'passwordRepeat' => self::NewPassword]);
		self::assertSame(303, $response->getStatusCode());
		$afterReset = $this->readAccessAndPassword($id);
		self::assertSame($before['role'], $afterReset['role']);
		self::assertTrue(password_verify(self::NewPassword, $afterReset['hash']));
		self::assertFalse(password_verify(self::OldPassword, $afterReset['hash']));

		$role = UserRole::DEALER_JUNIOR->getPermissionId();
		$response = $browser->submitForm($path, self::AccessForm, ['permission' => (string) $role, 'active' => '1']);
		self::assertSame(303, $response->getStatusCode());
		self::assertSame(['role' => $role, 'hash' => $afterReset['hash']], $this->readAccessAndPassword($id));

		$response = $this->autosaveRole($browser, $id, $actor);
		self::assertSame(200, $response->getStatusCode());
		self::assertSame(['success' => true], json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR));
		self::assertSame(['role' => $actor->getPermissionId(), 'hash' => $afterReset['hash']], $this->readAccessAndPassword($id));
	}


	public function testCeoCanOnlyAssignRolesOfferedByTheAccessForm(): void
	{
		$id = $this->createAccount(self::Email, UserRole::DEALER);
		$this->createAccount('ceo@example.test', UserRole::CEO);
		$browser = $this->login('ceo@example.test');
		$before = $this->readAccessAndPassword($id);
		$response = $browser->submitForm('/user-management/update/' . $id, self::AccessForm, [
			'permission' => (string) UserRole::ADMIN->getPermissionId(),
			'active' => '1',
		]);
		self::assertSame(200, $response->getStatusCode());
		self::assertSame($before, $this->readAccessAndPassword($id));

		$response = $this->autosaveRole($browser, $id, UserRole::ADMIN);
		self::assertSame(403, $response->getStatusCode());
		self::assertFalse(json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR)['success']);
		self::assertSame($before, $this->readAccessAndPassword($id));
	}


	private function autosaveRole(TestWebClient $browser, int $id, UserRole $role): ResponseInterface
	{
		return $browser->post('/user-management/update/' . $id, [
			'_do' => 'autosavePartial',
			'id' => (string) $id,
			'__autosave_context' => 'user.access',
			'__autosave_field' => 'permission',
			'__autosave_value' => (string) $role->getPermissionId(),
			'permission' => (string) $role->getPermissionId(),
		], ['X-Requested-With' => 'XMLHttpRequest', 'Sec-Fetch-Site' => 'same-origin']);
	}


	/** @return array{role: int, hash: string} */
	private function readAccessAndPassword(int $id): array
	{
		$row = $this->getDatabase()->table(UserTableMap::TABLE_NAME)->get($id);
		self::assertNotNull($row);
		return ['role' => (int) $row->{UserTableMap::COL_PERMISSION}, 'hash' => (string) $row->{UserTableMap::COL_PASSWORD}];
	}


	private function createAccount(string $email, UserRole $role): int
	{
		return TestDatabase::createUser([
			UserTableMap::COL_EMAIL => $email,
			UserTableMap::COL_PASSWORD => password_hash(self::OldPassword, PASSWORD_BCRYPT, ['cost' => 4]),
			UserTableMap::COL_PERMISSION => $role->getPermissionId(),
		]);
	}


	private function login(string $email): TestWebClient
	{
		$browser = $this->openBrowser();
		self::assertSame(303, $browser->login($email, self::OldPassword)->getStatusCode());
		return $browser;
	}


	private function openBrowser(): TestWebClient
	{
		$browser = new TestWebClient();
		$this->browsers[] = $browser;
		return $browser;
	}


	private function assertLoginRequired(TestWebClient $browser, string $path): void
	{
		$response = $browser->get($path);
		self::assertSame(302, $response->getStatusCode());
		self::assertSame('/login/', parse_url($response->getHeaderLine('Location'), PHP_URL_PATH));
	}


	private function assertPasswordAudit(string $event, int $actorId, int $targetId): void
	{
		$events = $this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)
			->where(SecurityAuditLogTableMap::COL_EVENT_TYPE, ['password_changed', 'password_reset']);
		self::assertSame(1, $events->count('*'));
		$row = $events->fetch();
		self::assertNotNull($row);
		self::assertSame($event, $row->{SecurityAuditLogTableMap::COL_EVENT_TYPE});
		self::assertSame($actorId, $row->{SecurityAuditLogTableMap::COL_USER_ID});
		self::assertNotNull($row->{SecurityAuditLogTableMap::COL_CREATED_AT});
		self::assertSame(['targetUserId' => $targetId], json_decode($row->{SecurityAuditLogTableMap::COL_METADATA}, true, flags: JSON_THROW_ON_ERROR));
	}
}
