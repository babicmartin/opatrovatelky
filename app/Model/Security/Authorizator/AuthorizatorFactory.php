<?php declare(strict_types = 1);

namespace App\Model\Security\Authorizator;

use App\Model\Enum\Acl\Resource;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Repository\PageRepository;
use Nette\Security\Permission;

final class AuthorizatorFactory
{
	/** @var array<string, Resource> */
	private const array PAGE_RESOURCE_ALIASES = [
		'opatrovatelky' => Resource::BABYSITTER,
		'opatrovatelky-update' => Resource::BABYSITTER,
		'families' => Resource::FAMILY,
		'families-update' => Resource::FAMILY,
		'rodiny' => Resource::FAMILY,
		'rodiny-update' => Resource::FAMILY,
		'partneri' => Resource::PARTNER,
		'partneri-update' => Resource::PARTNER,
		'agencies' => Resource::AGENCY,
		'agencies-update' => Resource::AGENCY,
		'projekty' => Resource::PROJECT,
		'pracovnici' => Resource::WORKER,
		'proposal-records' => Resource::PROPOSAL,
		'proposal-update' => Resource::PROPOSAL,
	];

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

		foreach (Resource::cases() as $resource) {
			if (!$acl->hasResource($resource->value)) {
				$acl->addResource($resource->value);
			}
		}

		$acl->allow(UserRole::CEO->value, Resource::FAMILY_MANAGEMENT->value);
		$acl->allow(UserRole::CEO->value, Resource::WORKER_MANAGEMENT->value);
		$acl->allow(UserRole::CEO->value, Resource::TODO_VIEW_ALL->value);
		$acl->allow(UserRole::CEO->value, Resource::CHANGE_LOG->value);
		$acl->allow(UserRole::CEO->value, Resource::LOGIN_LOG->value);

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
			$this->allowResourceAlias($acl, $role->value, $url);

			if ($url === 'user-management') {
				$acl->allow($role->value, Resource::USER_MANAGEMENT->value);
			}
		}

		return $acl;
	}

	private function allowResourceAlias(Permission $acl, string $role, string $url): void
	{
		$resource = self::PAGE_RESOURCE_ALIASES[$url] ?? null;
		if ($resource === null) {
			return;
		}

		$acl->allow($role, $resource->value);
	}
}
