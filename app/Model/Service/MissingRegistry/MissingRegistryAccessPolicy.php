<?php declare(strict_types=1);

namespace App\Model\Service\MissingRegistry;

use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\Acl\Resource;
use App\Model\Repository\UserRepository;
use Nette\Security\User;

/**
 * Who may touch which record of the absence registry.
 *
 * Everybody with the module sees the whole team. A record may be created, changed and deleted
 * by its owner; a role with Resource::MISSING_MANAGE_ALL manages the records of everybody.
 * The acting user is always the logged-in one, never a value from the request.
 */
final readonly class MissingRegistryAccessPolicy
{
	public function __construct(
		private User $user,
		private UserRepository $userRepository,
	) {
	}

	public function canView(): bool
	{
		return $this->user->isLoggedIn() && $this->user->isAllowed(Resource::MISSING->value);
	}

	public function canManageAll(): bool
	{
		return $this->canView() && $this->user->isAllowed(Resource::MISSING_MANAGE_ALL->value);
	}

	public function getCurrentUserId(): int
	{
		$id = $this->user->isLoggedIn() ? $this->user->getId() : null;

		return is_numeric($id) ? (int) $id : 0;
	}

	public function canEdit(MissingRegistryRecord $record): bool
	{
		return $this->canManageAll() || $this->isOwnUserId($record->userId);
	}

	/**
	 * The people, out of the given ones, whose records the current user may create.
	 *
	 * @param list<int> $userIds
	 * @return list<int>
	 */
	public function filterManageableUserIds(array $userIds): array
	{
		if ($this->canManageAll()) {
			$assignable = $this->getAssignableUsers();

			return array_values(array_filter($userIds, static fn (int $userId): bool => isset($assignable[$userId])));
		}

		return array_values(array_filter($userIds, $this->isOwnUserId(...)));
	}

	/**
	 * Whose record it will be. Without the manage-all right the answer is always the current user,
	 * whatever was requested. With it the requested person must be assignable; 0 ("nobody yet")
	 * and the present owner of the record are accepted as well. Null means the request is refused.
	 */
	public function resolveTargetUserId(?int $requestedUserId, ?MissingRegistryRecord $record = null): ?int
	{
		if (!$this->canView()) {
			return null;
		}

		if (!$this->canManageAll()) {
			return $this->getCurrentUserId();
		}

		$requestedUserId ??= $record === null ? 0 : $record->userId;

		return $requestedUserId === 0
			|| $requestedUserId === $record?->userId
			|| isset($this->getAssignableUsers()[$requestedUserId])
				? $requestedUserId
				: null;
	}

	/**
	 * @return array<int, string> user id => full name
	 */
	public function getAssignableUsers(): array
	{
		$users = $this->userRepository->findSelectOptions();
		unset($users[0]);

		return $users;
	}

	private function isOwnUserId(int $userId): bool
	{
		return $userId > 0 && $this->canView() && $userId === $this->getCurrentUserId();
	}
}
