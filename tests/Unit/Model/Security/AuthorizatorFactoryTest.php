<?php declare(strict_types=1);

namespace Tests\Unit\Model\Security;

use App\Model\Enum\Acl\Resource;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Security\Authorizator\AuthorizatorFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PHPUnit\TestCase;

/**
 * Pins who may use what. The expectation is written here on purpose and not read from the factory:
 * any change of permissions in the code must fail this test and be confirmed by editing it.
 */
final class AuthorizatorFactoryTest extends TestCase
{
	/** Roles from the lowest to the highest; a role inherits everything of the roles before it. */
	private const array RoleOrder = [
		UserRole::DEALER_JUNIOR,
		UserRole::DEALER,
		UserRole::CEO,
		UserRole::ADMIN,
	];

	/**
	 * Resource value => lowest role that may use it.
	 */
	private const array LowestRole = [
		'homepage' => UserRole::DEALER_JUNIOR,
		'babysitter' => UserRole::DEALER_JUNIOR,
		'family' => UserRole::DEALER_JUNIOR,
		'worker' => UserRole::DEALER_JUNIOR,
		'project' => UserRole::DEALER_JUNIOR,
		'turnus' => UserRole::DEALER_JUNIOR,
		'stats' => UserRole::DEALER_JUNIOR,
		'todo' => UserRole::DEALER_JUNIOR,
		'proposal' => UserRole::DEALER_JUNIOR,
		'missing-registry' => UserRole::DEALER_JUNIOR,
		'partner' => UserRole::DEALER,
		'agency' => UserRole::DEALER,
		'settings' => UserRole::CEO,
		'country' => UserRole::CEO,
		'userManagement' => UserRole::CEO,
		'translation' => UserRole::CEO,
		'familyManagement' => UserRole::CEO,
		'workerManagement' => UserRole::CEO,
		'todoViewAll' => UserRole::CEO,
		'change-log' => UserRole::CEO,
		'login-log' => UserRole::CEO,
		'partnerFamily' => UserRole::CEO,
	];

	/**
	 * @return iterable<string, array{UserRole, Resource, bool}>
	 */
	public static function roleResourceMatrix(): iterable
	{
		foreach (self::RoleOrder as $rolePosition => $role) {
			foreach (Resource::cases() as $resource) {
				$lowestRole = self::LowestRole[$resource->value];
				$allowed = $rolePosition >= array_search($lowestRole, self::RoleOrder, true);

				yield $role->value . ' / ' . $resource->value => [$role, $resource, $allowed];
			}
		}
	}

	#[DataProvider('roleResourceMatrix')]
	public function testRoleMayUseExactlyItsResources(UserRole $role, Resource $resource, bool $allowed): void
	{
		$acl = (new AuthorizatorFactory())->create();

		self::assertSame($allowed, $acl->isAllowed($role->value, $resource->value, 'default'));
	}

	public function testExpectationCoversEveryResourceAndRole(): void
	{
		$resources = array_map(static fn (Resource $resource): string => $resource->value, Resource::cases());
		sort($resources);
		$expected = array_keys(self::LowestRole);
		sort($expected);

		self::assertSame($resources, $expected, 'A resource was added or removed: write its lowest role into this test.');
		self::assertEqualsCanonicalizing(UserRole::cases(), self::RoleOrder);
	}

	public function testGuestMayUseNothing(): void
	{
		$acl = (new AuthorizatorFactory())->create();

		foreach (Resource::cases() as $resource) {
			self::assertFalse($acl->isAllowed('guest', $resource->value, 'default'), $resource->value);
		}
	}

	public function testResourcesAreExactlyTheEnumAndNotMenuUrls(): void
	{
		$acl = (new AuthorizatorFactory())->create();

		$resources = $acl->getResources();
		sort($resources);
		$expected = array_map(static fn (Resource $resource): string => $resource->value, Resource::cases());
		sort($expected);

		self::assertSame($expected, $resources);
		foreach (['opatrovatelky', 'families', 'partneri', 'user-management', 'dokumenty'] as $menuUrl) {
			self::assertFalse($acl->hasResource($menuUrl), $menuUrl);
		}
	}
}
