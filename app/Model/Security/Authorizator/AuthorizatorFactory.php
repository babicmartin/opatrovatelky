<?php declare(strict_types = 1);

namespace App\Model\Security\Authorizator;

use App\Model\Enum\Acl\Resource;
use App\Model\Enum\UserRole\UserRole;
use Nette\Security\Permission;

/**
 * The only place that says which role may use which module.
 * The table `sany_pages` drives the menu only and grants nothing.
 */
final class AuthorizatorFactory
{
	/**
	 * Lowest role that may use the resource. A role also has everything of the roles above it in this list
	 * (DEALER_JUNIOR → DEALER → CEO → ADMIN). ADMIN may use every resource, listed here or not.
	 *
	 * @var array<string, list<Resource>>
	 */
	private const array ROLE_RESOURCES = [
		UserRole::DEALER_JUNIOR->value => [
			Resource::HOME,
			Resource::BABYSITTER,
			Resource::FAMILY,
			Resource::WORKER,
			Resource::PROJECT,
			Resource::TURNUS,
			Resource::STATS,
			Resource::TODO,
			Resource::PROPOSAL,
			Resource::MISSING,
		],
		UserRole::DEALER->value => [
			Resource::PARTNER,
			Resource::AGENCY,
		],
		UserRole::CEO->value => [
			Resource::SETTINGS,
			Resource::COUNTRY,
			Resource::USER_MANAGEMENT,
			Resource::TRANSLATION,
			Resource::FAMILY_MANAGEMENT,
			Resource::WORKER_MANAGEMENT,
			Resource::TODO_VIEW_ALL,
			Resource::CHANGE_LOG,
			Resource::LOGIN_LOG,
			Resource::PARTNER_FAMILY,
		],
	];

	public function create(): Permission
	{
		$acl = new Permission();

		$acl->addRole('guest');
		$acl->addRole(UserRole::DEALER_JUNIOR->value);
		$acl->addRole(UserRole::DEALER->value, UserRole::DEALER_JUNIOR->value);
		$acl->addRole(UserRole::CEO->value, UserRole::DEALER->value);
		$acl->addRole(UserRole::ADMIN->value, UserRole::CEO->value);

		foreach (Resource::cases() as $resource) {
			$acl->addResource($resource->value);
		}

		$acl->allow(UserRole::ADMIN->value);

		foreach (self::ROLE_RESOURCES as $role => $resources) {
			foreach ($resources as $resource) {
				$acl->allow($role, $resource->value);
			}
		}

		return $acl;
	}
}
