<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Menu;

use App\Model\Enum\UserRole\UserRole;
use App\Model\Repository\PageRepository;
use Nette\Application\UI\Control;
use Nette\Security\User;

class MenuControl extends Control
{
	public function __construct(
		private readonly PageRepository $pageRepository,
		private readonly User $user,
	) {
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/MenuControl.latte');

		$permission = $this->resolveUserPermission();
		$template->menuItems = $this->pageRepository->findMenuItems($permission);
		$template->render();
	}

	private function resolveUserPermission(): int
	{
		$identity = $this->user->getIdentity();
		if ($identity === null) {
			return 0;
		}

		$roles = $identity->getRoles();
		$roleName = $roles[0] ?? null;
		if (!is_string($roleName)) {
			return 0;
		}

		$role = UserRole::tryFrom($roleName);

		return $role?->getPermissionId() ?? 0;
	}
}
