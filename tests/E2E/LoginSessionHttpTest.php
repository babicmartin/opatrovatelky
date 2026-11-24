<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Security\MediaAccessCookie;
use App\Model\Table\SecurityAuditLogTableMap;
use App\Model\Table\SecurityLoginAttemptTableMap;
use App\Model\Table\UserTableMap;
use DateTimeImmutable;
use Dom\HTMLDocument;
use Nette\Http\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class LoginSessionHttpTest extends DatabaseTestCase
{
	private const Email = 'session@example.test';
	private const Password = 'session-test-password';
	private TestWebClient $web;

	/** @var list<TestWebClient> */
	private array $otherBrowsers = [];
	private int $userId;


	protected function setUp(): void
	{
		parent::setUp();
		$this->userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => self::Email,
			UserTableMap::COL_PASSWORD => password_hash(self::Password, PASSWORD_BCRYPT, ['cost' => 4]),
			UserTableMap::COL_PERMISSION => UserRole::CEO->getPermissionId(),
		]);
		$this->web = new TestWebClient();
	}


	protected function tearDown(): void
	{
		if (isset($this->web)) {
			$this->web->stop();
		}
		foreach ($this->otherBrowsers as $browser) {
			$browser->stop();
		}
		parent::tearDown();
	}


	public function testInactiveAccountCannotLogInWithCorrectPassword(): void
	{
		$this->getDatabase()->table(UserTableMap::TABLE_NAME)->wherePrimary($this->userId)
			->update([UserTableMap::COL_ACTIVE => 0]);

		$this->assertLoginRejected($this->web->login(self::Email, self::Password));
		$this->assertLoginRequired();
		$this->assertAuditEvents(['login_failed']);
	}


	public function testTenthFailureBlocksCorrectPasswordUntilBlockExpires(): void
	{
		for ($attempt = 1; $attempt <= 10; $attempt++) {
			$this->assertLoginRejected($this->web->login(self::Email, 'incorrect-password'));
		}
		$this->assertLoginRejected($this->web->login(self::Email, self::Password));
		$this->assertLoginRequired();
		$events = [...array_fill(0, 10, 'login_failed'), 'login_blocked'];
		$this->assertAuditEvents($events);

		// Keep all failures inside the 15-minute counting window, just past the 10-minute block.
		$attempts = $this->getDatabase()->table(SecurityLoginAttemptTableMap::TABLE_NAME)
			->where(SecurityLoginAttemptTableMap::COL_EMAIL, self::Email)
			->where(SecurityLoginAttemptTableMap::COL_SUCCESS, 0);
		self::assertSame(10, $attempts->update([
			SecurityLoginAttemptTableMap::COL_CREATED_AT => new DateTimeImmutable('-11 minutes'),
		]));

		$this->assertLoginSucceeds();
		$this->assertAuditEvents([...$events, 'login_success']);
	}


	public function testFailuresOfAnotherAccountOnTheSameAddressDoNotBlockThisAccount(): void
	{
		$colleague = 'colleague@example.test';
		for ($attempt = 1; $attempt <= 10; $attempt++) {
			$this->assertLoginRejected($this->web->login($colleague, 'incorrect-password'));
		}
		// The colleague's account is blocked now; every request came from the same address.
		$this->assertLoginRejected($this->web->login($colleague, 'incorrect-password'));

		$this->assertLoginSucceeds();

		$events = [];
		foreach ($this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)->order(SecurityAuditLogTableMap::COL_ID) as $row) {
			$events[] = $row->{SecurityAuditLogTableMap::COL_EVENT_TYPE} . ' ' . $row->{SecurityAuditLogTableMap::COL_EMAIL};
		}
		self::assertSame([
			...array_fill(0, 10, 'login_failed ' . $colleague),
			'login_blocked ' . $colleague,
			'login_success ' . self::Email,
		], $events);
	}


	public function testLogoutRevokesAccessAndAuditsTheAuthenticatedUser(): void
	{
		$this->assertLoginSucceeds();
		$response = $this->web->submitForm('/change-log', 'frm-logoutForm');
		self::assertSame(303, $response->getStatusCode());
		self::assertSame('/login/', parse_url($response->getHeaderLine('Location'), PHP_URL_PATH));
		$this->assertLoginRequired();
		$this->assertAuditEvents(['login_success', 'logout']);
	}


	public function testDeactivationRevokesAnExistingSessionOnTheNextRequest(): void
	{
		$this->assertLoginSucceeds();
		$this->getDatabase()->table(UserTableMap::TABLE_NAME)->wherePrimary($this->userId)
			->update([UserTableMap::COL_ACTIVE => 0]);

		$this->assertLoginRequired();
	}


	public function testRoleChangeTakesEffectOnTheNextRequestWithoutAnotherLogin(): void
	{
		$this->assertLoginSucceeds();
		$user = $this->getDatabase()->table(UserTableMap::TABLE_NAME)->wherePrimary($this->userId);
		$user->update([UserTableMap::COL_PERMISSION => UserRole::DEALER->getPermissionId()]);
		self::assertSame(403, $this->web->get('/change-log')->getStatusCode());

		$user->update([UserTableMap::COL_PERMISSION => UserRole::CEO->getPermissionId()]);
		self::assertSame(200, $this->web->get('/change-log')->getStatusCode());
		$this->assertAuditEvents(['login_success']);
	}


	public function testLoginIssuesTheMediaCookieAndLogoutRemovesIt(): void
	{
		$login = $this->web->login(self::Email, self::Password);
		self::assertSame(303, $login->getStatusCode());
		$this->assertMediaCookieIssued($login);

		$logout = $this->web->submitForm('/change-log', 'frm-logoutForm');
		self::assertSame(303, $logout->getStatusCode());
		$this->assertMediaCookieRemoved($logout);
	}


	public function testAuthenticatedRequestRestoresAMissingMediaCookieOnce(): void
	{
		self::assertSame(303, $this->web->login(self::Email, self::Password)->getStatusCode());
		self::assertNull($this->findMediaCookie($this->web->get('/change-log')));

		$this->web->forgetCookie(MediaAccessCookie::Name);
		$restored = $this->web->get('/change-log');
		self::assertSame(200, $restored->getStatusCode());
		$this->assertMediaCookieIssued($restored);

		self::assertNull($this->findMediaCookie($this->web->get('/change-log')));
	}


	public function testRevokedSessionLosesTheMediaCookie(): void
	{
		self::assertSame(303, $this->web->login(self::Email, self::Password)->getStatusCode());
		$this->getDatabase()->table(UserTableMap::TABLE_NAME)->wherePrimary($this->userId)
			->update([UserTableMap::COL_ACTIVE => 0]);

		$response = $this->web->get('/change-log');
		self::assertSame(302, $response->getStatusCode());
		$this->assertMediaCookieRemoved($response);
	}


	public function testGuestOnTheLoginPageGetsNoMediaCookie(): void
	{
		$response = $this->web->get('/login/');
		self::assertSame(200, $response->getStatusCode());
		self::assertNull($this->findMediaCookie($response));
		self::assertNull($this->findMediaCookie($this->web->login(self::Email, 'incorrect-password')));
	}


	/** @return array<string, array{string}> */
	public static function unusableFormTokens(): array
	{
		return [
			'no token' => ['missing'],
			'invented token' => ['invented'],
			'token of another session' => ['foreign'],
		];
	}


	#[DataProvider('unusableFormTokens')]
	public function testLoginWithoutTheFormTokenOfItsSessionIsNotProcessed(string $case): void
	{
		$fields = ['email' => self::Email, 'password' => self::Password] + $this->readLoginFields($this->web);
		$valid = $fields['_token_'];
		if ($case === 'missing') {
			unset($fields['_token_']);
		} elseif ($case === 'invented') {
			$fields['_token_'] = str_repeat('a', strlen($valid));
		} else {
			$fields['_token_'] = $this->readLoginFields($this->openBrowser())['_token_'];
			self::assertNotSame($valid, $fields['_token_']);
		}

		$response = $this->web->post('/login/', $fields);
		self::assertSame(200, $response->getStatusCode());
		// The password is correct, yet the handler never ran: nobody is signed in and nothing was recorded.
		self::assertStringNotContainsString('Prihlásenie sa nepodarilo', (string) $response->getBody());
		$this->assertLoginRequired();
		self::assertSame(0, $this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)->count('*'));
		self::assertSame(0, $this->getDatabase()->table(SecurityLoginAttemptTableMap::TABLE_NAME)->count('*'));

		// The very same request with the token of its own session signs in, so only the token stood in the way.
		$fields['_token_'] = $valid;
		self::assertSame(303, $this->web->post('/login/', $fields)->getStatusCode());
	}


	public function testLoginReplacesASessionIdPlantedByAnAttacker(): void
	{
		$cookie = $this->getContainer()->getByType(Session::class)->getName();
		$attacker = $this->openBrowser();
		self::assertSame(200, $attacker->get('/login/')->getStatusCode());
		$planted = $attacker->cookieValue($cookie);
		self::assertIsString($planted);

		// The victim's browser carries the attacker's ID and the server accepts it on the login page.
		$this->web->setCookie($cookie, $planted);
		self::assertSame(200, $this->web->get('/login/')->getStatusCode());
		self::assertSame($planted, $this->web->cookieValue($cookie));

		self::assertSame(303, $this->web->login(self::Email, self::Password)->getStatusCode());
		$issued = $this->web->cookieValue($cookie);
		self::assertIsString($issued);
		self::assertNotSame($planted, $issued);
		self::assertSame(200, $this->web->get('/change-log')->getStatusCode());

		// The ID the attacker knows was thrown away by the login and opens nothing.
		$response = $attacker->get('/change-log');
		self::assertSame(302, $response->getStatusCode());
		self::assertSame('/login/', parse_url($response->getHeaderLine('Location'), PHP_URL_PATH));
	}


	/** @return array<string, array{string}> */
	public static function outdatedHashes(): array
	{
		return [
			'unsalted SHA-256 of the old CRM' => [crypt(self::Password, '$5$')],
			'bcrypt' => [password_hash(self::Password, PASSWORD_BCRYPT, ['cost' => 4])],
		];
	}


	#[DataProvider('outdatedHashes')]
	public function testOutdatedHashSignsInAndIsReplacedByArgon2id(string $outdated): void
	{
		self::assertStringStartsNotWith('$argon2id$', $outdated);
		$this->getDatabase()->table(UserTableMap::TABLE_NAME)->wherePrimary($this->userId)
			->update([UserTableMap::COL_PASSWORD => $outdated]);

		$this->assertLoginRejected($this->web->login(self::Email, 'incorrect-password'));
		self::assertSame(['hash' => $outdated, 'version' => 0], $this->readPasswordState());

		self::assertSame(303, $this->web->login(self::Email, self::Password)->getStatusCode());
		$upgraded = $this->readPasswordState();
		self::assertStringStartsWith('$argon2id$', $upgraded['hash']);
		// Also proves the new hash fits the column: a truncated one would not verify.
		self::assertTrue(password_verify(self::Password, $upgraded['hash']));
		self::assertFalse(password_verify('incorrect-password', $upgraded['hash']));
		// The upgrade is not a password change, other devices stay signed in.
		self::assertSame(0, $upgraded['version']);

		// The new hash signs in as well and is kept as it is.
		self::assertSame(303, $this->openBrowser()->login(self::Email, self::Password)->getStatusCode());
		self::assertSame($upgraded, $this->readPasswordState());
	}


	public function testUnknownEmailWrongPasswordAndInactiveAccountGetTheSameAnswer(): void
	{
		$unknown = 'nobody@example.test';
		$inactive = 'inactive@example.test';
		TestDatabase::createUser([
			UserTableMap::COL_EMAIL => $inactive,
			UserTableMap::COL_PASSWORD => password_hash(self::Password, PASSWORD_BCRYPT, ['cost' => 4]),
			UserTableMap::COL_ACTIVE => 0,
		]);

		foreach ([$unknown => self::Password, self::Email => 'incorrect-password', $inactive => self::Password] as $email => $password) {
			$response = $this->web->login($email, $password);
			self::assertSame(200, $response->getStatusCode(), $email);
			self::assertSame(['Prihlásenie sa nepodarilo'], $this->readLoginErrors($response), $email);
		}
		$this->assertLoginRequired();

		$audit = [];
		foreach ($this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)->order(SecurityAuditLogTableMap::COL_ID) as $row) {
			$audit[] = [
				$row->{SecurityAuditLogTableMap::COL_EVENT_TYPE},
				$row->{SecurityAuditLogTableMap::COL_EMAIL},
				$row->{SecurityAuditLogTableMap::COL_USER_ID},
			];
		}
		self::assertSame([
			['login_failed', $unknown, null],
			['login_failed', self::Email, null],
			['login_failed', $inactive, null],
		], $audit);

		$attempts = [];
		foreach ($this->getDatabase()->table(SecurityLoginAttemptTableMap::TABLE_NAME)->order(SecurityLoginAttemptTableMap::COL_ID) as $row) {
			$attempts[] = [
				$row->{SecurityLoginAttemptTableMap::COL_EMAIL},
				(int) $row->{SecurityLoginAttemptTableMap::COL_SUCCESS},
				$row->{SecurityLoginAttemptTableMap::COL_FAILURE_REASON},
			];
		}
		self::assertSame([
			[$unknown, 0, 'authentication_failed'],
			[self::Email, 0, 'authentication_failed'],
			[$inactive, 0, 'authentication_failed'],
		], $attempts);
	}


	private function assertMediaCookieIssued(ResponseInterface $response): void
	{
		$header = $this->findMediaCookie($response);
		self::assertNotNull($header);
		$attributes = explode('; ', $header);
		// value from config/.env.test; the real config/.env is never read in tests
		$secret = $_ENV['MEDIA_COOKIE_SECRET'] ?? null;
		self::assertIsString($secret);
		self::assertNotSame('', $secret);
		self::assertSame(MediaAccessCookie::Name . '=' . $secret, $attributes[0]);
		self::assertContains('Max-Age=86400', $attributes);
		self::assertContains('path=/', $attributes);
		self::assertContains('HttpOnly', $attributes);
		self::assertContains('SameSite=Lax', $attributes);
	}


	private function assertMediaCookieRemoved(ResponseInterface $response): void
	{
		$header = $this->findMediaCookie($response);
		self::assertNotNull($header);
		$attributes = explode('; ', $header);
		self::assertSame(MediaAccessCookie::Name . '=', $attributes[0]);
		self::assertContains('Max-Age=0', $attributes);
		self::assertContains('path=/', $attributes);
	}


	private function findMediaCookie(ResponseInterface $response): ?string
	{
		$headers = array_values(array_filter(
			$response->getHeader('Set-Cookie'),
			static fn (string $header): bool => str_starts_with($header, MediaAccessCookie::Name . '='),
		));
		self::assertLessThanOrEqual(1, count($headers));

		return $headers[0] ?? null;
	}


	private function assertLoginSucceeds(): void
	{
		self::assertSame(303, $this->web->login(self::Email, self::Password)->getStatusCode());
		$page = $this->web->get('/change-log');
		self::assertSame(200, $page->getStatusCode());
		self::assertStringContainsString('Audit uložených polí', (string) $page->getBody());
	}


	private function assertLoginRejected(ResponseInterface $response): void
	{
		self::assertSame(200, $response->getStatusCode());
		self::assertStringContainsString('Prihlásenie sa nepodarilo', (string) $response->getBody());
	}


	/** A second, independent browser; stopped in tearDown(). */
	private function openBrowser(): TestWebClient
	{
		return $this->otherBrowsers[] = new TestWebClient();
	}


	/** @return array<string, string> hidden and submit fields of the login form as this browser's session got them */
	private function readLoginFields(TestWebClient $browser): array
	{
		$response = $browser->get('/login/');
		self::assertSame(200, $response->getStatusCode());
		$form = HTMLDocument::createFromString((string) $response->getBody(), LIBXML_NOERROR, 'UTF-8')
			->getElementById('frm-login-form');
		self::assertNotNull($form);
		$fields = [];
		foreach ($form->querySelectorAll('input[type=hidden], button[type=submit]') as $input) {
			$fields[(string) $input->getAttribute('name')] = $input->getAttribute('value') ?? '';
		}
		self::assertNotSame('', $fields['_token_'] ?? '');

		return $fields;
	}


	/** @return list<string> */
	private function readLoginErrors(ResponseInterface $response): array
	{
		$page = HTMLDocument::createFromString((string) $response->getBody(), LIBXML_NOERROR, 'UTF-8');
		$errors = [];
		foreach ($page->querySelectorAll('#frm-login-form .alert-danger') as $alert) {
			$errors[] = trim($alert->textContent ?? '');
		}

		return $errors;
	}


	/** @return array{hash: string, version: int} */
	private function readPasswordState(): array
	{
		$row = $this->getDatabase()->table(UserTableMap::TABLE_NAME)->get($this->userId);
		self::assertNotNull($row);

		return [
			'hash' => (string) $row->{UserTableMap::COL_PASSWORD},
			'version' => (int) $row->{UserTableMap::COL_SESSION_VERSION},
		];
	}


	private function assertLoginRequired(): void
	{
		$response = $this->web->get('/change-log');
		self::assertSame(302, $response->getStatusCode());
		self::assertSame('/login/', parse_url($response->getHeaderLine('Location'), PHP_URL_PATH));
	}


	/** @param list<string> $expected */
	private function assertAuditEvents(array $expected): void
	{
		$rows = $this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)
			->order(SecurityAuditLogTableMap::COL_ID);
		$events = [];
		foreach ($rows as $row) {
			$event = $row->{SecurityAuditLogTableMap::COL_EVENT_TYPE};
			$events[] = $event;
			self::assertSame(self::Email, $row->{SecurityAuditLogTableMap::COL_EMAIL});
			$actor = in_array($event, ['login_success', 'logout'], true) ? $this->userId : null;
			self::assertSame($actor, $row->{SecurityAuditLogTableMap::COL_USER_ID});
		}
		self::assertSame($expected, $events);
	}
}
