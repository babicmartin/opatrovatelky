<?php declare(strict_types=1);

namespace Tests\Unit\Model\Security;

use App\Model\Entity\PageEntity;
use App\Model\Enum\Acl\Resource;
use App\Model\Enum\UserRole\UserRole;
use App\Model\Repository\PageRepository;
use App\Model\Security\Authorizator\AuthorizatorFactory;
use Tests\Support\PHPUnit\TestCase;

final class AuthorizatorFactoryTest extends TestCase
{
	public function testLegacyMenuUrlsGrantCanonicalPresenterResources(): void
	{
		$acl = (new AuthorizatorFactory(new StaticPageRepository([
			$this->page('opatrovatelky', 2),
			$this->page('families', 2),
		])))->create();

		self::assertTrue($acl->isAllowed(UserRole::DEALER->value, Resource::BABYSITTER->value, 'default'));
		self::assertTrue($acl->isAllowed(UserRole::DEALER->value, Resource::FAMILY->value, 'default'));
		self::assertTrue($acl->isAllowed(UserRole::DEALER_JUNIOR->value, Resource::BABYSITTER->value, 'default'));
		self::assertTrue($acl->isAllowed(UserRole::DEALER_JUNIOR->value, Resource::FAMILY->value, 'default'));
	}

	private function page(string $url, int $permission): PageEntity
	{
		return new PageEntity(
			1,
			null,
			$url,
			0,
			$permission,
			1,
			1,
			0,
			0,
			0,
			0,
			0,
			null,
		);
	}
}

final class StaticPageRepository extends PageRepository
{
	/**
	 * @param list<PageEntity> $pages
	 */
	public function __construct(
		private readonly array $pages,
	) {
	}

	/**
	 * @return list<PageEntity>
	 */
	public function getAll(): array
	{
		return $this->pages;
	}
}
