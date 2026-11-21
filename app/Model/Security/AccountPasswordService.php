<?php declare(strict_types=1);

namespace App\Model\Security;

use App\Model\Repository\UserRepository;
use App\Model\Table\UserTableMap;
use Nette\Database\Explorer;
use Nette\Security\Passwords;
use SensitiveParameter;

final readonly class AccountPasswordService
{
	public function __construct(
		private Explorer $database,
		private UserRepository $users,
		private Passwords $passwords,
		private SecurityAuditLogger $audit,
	) {
	}


	public function verify(#[SensitiveParameter] string $password, #[SensitiveParameter] string $storedHash): bool
	{
		return $this->passwords->verify($password, $storedHash)
			|| (str_starts_with($storedHash, '$5$') && hash_equals($storedHash, crypt($password, '$5$')));
	}


	public function upgradeHashIfNeeded(
		int $userId,
		int $verifiedVersion,
		#[SensitiveParameter] string $password,
		#[SensitiveParameter] string $storedHash,
	): bool
	{
		if (str_starts_with($storedHash, '$argon2id$') && !$this->passwords->needsRehash($storedHash)) {
			return true;
		}

		return $this->users->rehashPassword($userId, $verifiedVersion, $this->passwords->hash($password));
	}


	public function changeOwnPassword(int $userId, #[SensitiveParameter] string $currentPassword, #[SensitiveParameter] string $newPassword): ?int
	{
		return $this->database->transaction(function () use ($userId, $currentPassword, $newPassword): ?int {
			$account = $this->users->lockPasswordState($userId);
			if (!$account || !$this->verify($currentPassword, (string) $account->{UserTableMap::COL_PASSWORD})) {
				return null;
			}

			$this->storePassword($userId, $userId, $newPassword, 'password_changed');
			return (int) $account->{UserTableMap::COL_SESSION_VERSION} + 1;
		});
	}


	public function resetPassword(int $actorId, int $targetId, int $authorizedPermission, #[SensitiveParameter] string $newPassword): bool
	{
		if ($actorId === $targetId) {
			return false;
		}

		return $this->database->transaction(function () use ($actorId, $targetId, $authorizedPermission, $newPassword): bool {
			$account = $this->users->lockPasswordState($targetId);
			if (!$account || (int) $account->{UserTableMap::COL_PERMISSION} !== $authorizedPermission) {
				return false;
			}

			$this->storePassword($actorId, $targetId, $newPassword, 'password_reset');
			return true;
		});
	}


	private function storePassword(int $actorId, int $targetId, #[SensitiveParameter] string $newPassword, string $event): void
	{
		$this->users->changePasswordHash($targetId, $this->passwords->hash($newPassword));
		$this->audit->logRequired($event, $actorId, null, ['targetUserId' => $targetId]);
	}
}
