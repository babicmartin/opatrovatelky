<?php declare(strict_types = 1);

namespace App\Model\Security;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\UserTableMap;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IdentityHandler;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Psr\Log\LoggerInterface;

final class AdminAuthenticator implements Authenticator, IdentityHandler
{
	private const string AUTHENTICATION_FAILED_MESSAGE = 'Prihlásenie sa nepodarilo.';
	private const string INACTIVE_ACCOUNT_STATUS = 'inactive_account';
	private const string MISSING_SESSION_USER_STATUS = 'missing_session_user';

	public function __construct(
		private readonly AccountPasswordService $passwords,
		private readonly Explorer $database,
		private readonly LoggerInterface $logger,
	)
	{
	}

	public function authenticate(string $username, #[\SensitiveParameter] string $password): IIdentity
	{
		$user = $this->database->table(UserTableMap::TABLE_NAME)
			->select('*')
			->where(UserTableMap::COL_EMAIL, $username)
			->fetch();

		if (!$user) {
			throw new AuthenticationException('Užívateľ neexistuje.');
		}

		$storedHash = $user->{UserTableMap::COL_PASSWORD};
		if (!is_string($storedHash)) {
			throw new AuthenticationException('Chyba v databáze: heslo nie je reťazec.');
		}
		if (!$this->passwords->verify($password, $storedHash)) {
			throw new AuthenticationException('Nesprávne heslo.');
		}

		if (!$this->isUserActive($user)) {
			$this->logInactiveAccountRejection($user, 'login');
			throw new AuthenticationException(self::AUTHENTICATION_FAILED_MESSAGE);
		}

		if (!$this->passwords->upgradeHashIfNeeded(
			(int) $user->{UserTableMap::COL_ID},
			(int) $user->{UserTableMap::COL_SESSION_VERSION},
			$password,
			$storedHash,
		)) {
			throw new AuthenticationException(self::AUTHENTICATION_FAILED_MESSAGE);
		}

		return $this->createIdentity($user);
	}

	public function sleepIdentity(IIdentity $identity): IIdentity
	{
		return new SimpleIdentity($identity->getId(), $identity->getRoles(), [
			'sessionVersion' => $identity instanceof SimpleIdentity ? ($identity->getData()['sessionVersion'] ?? 0) : 0,
		]);
	}

	public function wakeupIdentity(IIdentity $identity): ?IIdentity
	{
		$user = $this->database->table(UserTableMap::TABLE_NAME)
			->where(UserTableMap::COL_ID, $identity->getId())
			->fetch();

		if (!$user) {
			$this->logger->warning('Session identity rejected: user does not exist.', [
				'status' => self::MISSING_SESSION_USER_STATUS,
				'identityId' => $identity->getId(),
				'identityRoles' => $identity->getRoles(),
			]);
			return null;
		}

		if (!$this->isUserActive($user)) {
			$this->logInactiveAccountRejection($user, 'session_wakeup');
			return null;
		}

		// Sessions issued before versioning belong to version zero.
		$storedVersion = $identity instanceof SimpleIdentity ? ($identity->getData()['sessionVersion'] ?? 0) : 0;
		if ($storedVersion !== (int) $user->{UserTableMap::COL_SESSION_VERSION}) {
			return null;
		}

		return $this->createIdentity($user);
	}

	private function isUserActive(ActiveRow $user): bool
	{
		return (int) $user->{UserTableMap::COL_ACTIVE} === 1;
	}

	private function logInactiveAccountRejection(ActiveRow $user, string $context): void
	{
		$this->logger->warning('Login rejected: inactive account.', [
			'status' => self::INACTIVE_ACCOUNT_STATUS,
			'context' => $context,
			'userId' => (int) $user->{UserTableMap::COL_ID},
			'email' => (string) $user->{UserTableMap::COL_EMAIL},
		]);
	}

	private function createIdentity(ActiveRow $user): SimpleIdentity
	{
		$permissionValue = $user->{UserTableMap::COL_PERMISSION};
		$permissionId = is_numeric($permissionValue) ? (int) $permissionValue : 0;
		$role = UserRole::fromPermissionId($permissionId);

		return new SimpleIdentity(
			$user->{UserTableMap::COL_ID},
			[$role->value],
			[
				'sessionVersion' => (int) $user->{UserTableMap::COL_SESSION_VERSION},
				'name' => $user->{UserTableMap::COL_NAME},
				'email' => $user->{UserTableMap::COL_EMAIL},
				'image' => $user->{UserTableMap::COL_IMAGE},
			],
		);

	}
}
