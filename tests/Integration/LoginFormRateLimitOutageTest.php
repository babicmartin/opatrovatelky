<?php declare(strict_types=1);

namespace Tests\Integration;

use App\Model\Form\DTO\Login\LoginForm\LoginFormDTO;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Repository\SecurityLoginAttemptRepository;
use App\Model\Security\AdminAuthenticator;
use App\Model\Security\LoginRateLimiter;
use App\Model\Security\MediaAccessCookie;
use App\Model\Security\SecurityAuditLogger;
use App\Model\Table\SecurityAuditLogTableMap;
use App\Model\Table\UserTableMap;
use App\Model\Utils\Date\DateService;
use App\UI\Login\Control\Login\LoginFormFactory;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Security\User;
use Psr\Log\NullLogger;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

/**
 * When the login attempts cannot be read, the login form must refuse the login
 * and leave an audit event, even for the correct password.
 */
final class LoginFormRateLimitOutageTest extends DatabaseTestCase
{
	private const string Email = 'outage@example.test';
	private const string Password = 'outage-test-password';

	protected function tearDown(): void
	{
		$this->getContainer()->getByType(User::class)->logout(true);

		parent::tearDown();
	}

	public function testCorrectPasswordIsRefusedAndAuditedWhileAttemptsAreUnreadable(): void
	{
		TestDatabase::createUser([
			UserTableMap::COL_EMAIL => self::Email,
			UserTableMap::COL_PASSWORD => password_hash(self::Password, PASSWORD_BCRYPT, ['cost' => 4]),
		]);
		$container = $this->getContainer();
		$user = $container->getByType(User::class);
		$factory = new LoginFormFactory(
			$user,
			$container->getByType(AdminAuthenticator::class),
			new NullLogger(),
			$container->getByType(BaseFormFactory::class),
			$container->getByType(Session::class),
			$container->getByType(IRequest::class),
			// Only the attempts table is unreachable; users and the audit log work as usual.
			new LoginRateLimiter(
				new SecurityLoginAttemptRepository(
					TestDatabase::explorerWithoutTables(),
					$container->getByType(DateService::class),
				),
				new NullLogger(),
			),
			$container->getByType(SecurityAuditLogger::class),
			$container->getByType(MediaAccessCookie::class),
		);
		$form = $factory->create();

		$factory->onSuccess($form, new LoginFormDTO(self::Email, self::Password));

		self::assertFalse($user->isLoggedIn());
		self::assertSame(['Prihlásenie sa nepodarilo'], $form->getErrors());

		$events = $this->getDatabase()->table(SecurityAuditLogTableMap::TABLE_NAME)->fetchAll();
		self::assertCount(1, $events);
		$event = array_values($events)[0];
		self::assertSame('login_limit_unavailable', $event->{SecurityAuditLogTableMap::COL_EVENT_TYPE});
		self::assertSame(self::Email, $event->{SecurityAuditLogTableMap::COL_EMAIL});
		self::assertNull($event->{SecurityAuditLogTableMap::COL_USER_ID});
	}
}
