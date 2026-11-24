<?php declare(strict_types=1);

namespace Tests\E2E;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Security\MediaAccessCookie;
use App\Model\Table\SecurityAuditLogTableMap;
use App\Model\Table\SecurityLoginAttemptTableMap;
use App\Model\Table\UserTableMap;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Database\TestDatabase;
use Tests\Support\Http\TestWebClient;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class LoginSessionHttpTest extends DatabaseTestCase
{
	private const Email = 'session@example.test';
	private const Password = 'session-test-password';
	private TestWebClient $web;
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
