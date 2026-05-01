<?php declare(strict_types = 1);

namespace App\Model\Security;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Table\UserTableMap;
use Nette\Database\Explorer;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\IdentityHandler;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

final class AdminAuthenticator implements Authenticator, IdentityHandler
{
	public function __construct(
		private readonly Passwords $passwords,
		private readonly Explorer $database,
	)
	{
	}

	public function authenticate(string $username, string $password): IIdentity
	{
		$user = $this->database->table(UserTableMap::TABLE_NAME)
			->where(UserTableMap::COL_EMAIL, $username)
			->fetch();

		if (!$user) {
			throw new AuthenticationException('Užívateľ neexistuje.');
		}

		$storedHash = $user->{UserTableMap::COL_PASSWORD};
		if (!is_string($storedHash)) {
			throw new AuthenticationException('Chyba v databáze: heslo nie je reťazec.');
		}
		$verified = false;

		if ($this->passwords->verify($password, $storedHash)) {
			$verified = true;
		} elseif (crypt($password, '$5$') === $storedHash) {
			// Legacy SHA-256 crypt format - migrate to Argon2ID
			$verified = true;
		}

		if (!$verified) {
			throw new AuthenticationException('Nesprávne heslo.');
		}

		if (!str_starts_with($storedHash, '$argon2id$') || $this->passwords->needsRehash($storedHash)) {
			$userId = $user->{UserTableMap::COL_ID};
			if (!is_int($userId)) {
				throw new \RuntimeException('ID užívateľa nie je celé číslo.');
			}
			$this->database->table(UserTableMap::TABLE_NAME)
				->where(UserTableMap::COL_ID, $userId)
				->update([UserTableMap::COL_PASSWORD => $this->passwords->hash($password)]);
		}

		return $this->createIdentity($user);
	}

    public function sleepIdentity(IIdentity $identity): IIdentity
    {
        // Musíš vrátiť identitu aj s rolami, inak Nette doplní 'authenticated'
        return new SimpleIdentity($identity->getId(), $identity->getRoles());
    }

    public function wakeupIdentity(IIdentity $identity): ?IIdentity
    {
        $user = $this->database->table(UserTableMap::TABLE_NAME)
            ->where(UserTableMap::COL_ID, $identity->getId())
            ->fetch();

        if (!$user) {
            return null;
        }

        // Tu sa znova vytvorí čerstvá identita s tvojím Enumom
        return $this->createIdentity($user);
    }

	private function createIdentity(\Nette\Database\Table\ActiveRow $user): SimpleIdentity
	{
		$permissionValue = $user->{UserTableMap::COL_PERMISSION};
		$permissionId = is_numeric($permissionValue) ? (int) $permissionValue : 0;
		$role = UserRole::fromPermissionId($permissionId);

        return new SimpleIdentity(
			$user->{UserTableMap::COL_ID},
			[$role->value],
			[
				'name' => $user->{UserTableMap::COL_NAME},
				'email' => $user->{UserTableMap::COL_EMAIL},
				'image' => $user->{UserTableMap::COL_IMAGE},
			],
		);

	}
}
