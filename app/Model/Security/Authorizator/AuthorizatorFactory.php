<?php declare(strict_types = 1);

namespace App\Model\Security\Authorizator;

use App\Model\Enum\Acl\Resource;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Repository\PageRepository;
use Nette\Security\Permission;

final class AuthorizatorFactory
{
	public function __construct(
		private readonly PageRepository $pageRepository,
	) {
	}

	public function create(): Permission
	{
		$acl = new Permission();

		$acl->addRole('guest');
		$acl->addRole(UserRole::DEALER_JUNIOR->value);
		$acl->addRole(UserRole::DEALER->value, UserRole::DEALER_JUNIOR->value);
		$acl->addRole(UserRole::CEO->value, UserRole::DEALER->value);
		$acl->addRole(UserRole::ADMIN->value, UserRole::CEO->value);

		$acl->allow(UserRole::ADMIN->value);
		$acl->allow('guest');

		foreach (Resource::cases() as $resource) {
			if (!$acl->hasResource($resource->value)) {
				$acl->addResource($resource->value);
			}
		}

		foreach ($this->pageRepository->getAll() as $page) {
			$url = $page->url;
			$permission = $page->permission;
			if ($url === null || $permission === null) {
				continue;
			}

			if (!$acl->hasResource($url)) {
				$acl->addResource($url);
			}

			$role = UserRole::fromPermissionId($permission);
			$acl->allow($role->value, $url);
		}

		return $acl;
	}
}
