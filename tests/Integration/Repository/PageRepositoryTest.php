<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Entity\PageEntity;
use App\Model\Repository\PageRepository;
use App\Model\Table\PageTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class PageRepositoryTest extends DatabaseTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// The seed holds the production menu; these tests need a table they fully control.
		TestDatabase::pdo()->exec('TRUNCATE TABLE `' . PageTableMap::TABLE_NAME . '`');
	}

	public function testFindMenuItemsFiltersByActiveParentPositionAndMenuFlag(): void
	{
		$repository = $this->getContainer()->getByType(PageRepository::class);
		$secondId = $this->createPage('Nastavenia', 20);
		$firstId = $this->createPage('Opatrovatelky', 10);
		$this->createPage('Inactive', 30, ['active' => 0]);
		$this->createPage('Hidden', 40, ['in_menu' => 0]);
		$this->createPage('Child', 50, ['parent' => $firstId]);
		$this->createPage('No position', 0);

		self::assertSame([$firstId, $secondId], $this->menuIds($repository->findMenuItems()));

		$entities = $repository->findMenuItems(wrapToEntity: true);
		self::assertSame([$firstId, $secondId], array_map(static fn (PageEntity $page): int => $page->getId(), $entities));
	}

	public function testFindMenuItemsIgnoresThePermissionColumn(): void
	{
		$repository = $this->getContainer()->getByType(PageRepository::class);
		$lowId = $this->createPage('Low', 10, ['permission' => 2]);
		$highId = $this->createPage('High', 20, ['permission' => 10]);

		self::assertSame([$lowId, $highId], $this->menuIds($repository->findMenuItems()));
	}

	private int $nextPageId = 100;

	/**
	 * @param array<string, int> $overrides
	 */
	private function createPage(string $name, int $position, array $overrides = []): int
	{
		$id = $this->nextPageId++;
		TestDatabase::insert(PageTableMap::TABLE_NAME, $overrides + [
			PageTableMap::COL_ID => $id,
			PageTableMap::COL_NAME => $name,
			PageTableMap::COL_URL => strtolower($name),
			PageTableMap::COL_PARENT => 0,
			PageTableMap::COL_POSITION => $position,
			PageTableMap::COL_IN_MENU => 1,
			PageTableMap::COL_ACTIVE => 1,
			PageTableMap::COL_PERMISSION => 2,
		]);

		return $id;
	}

	/**
	 * @param iterable<\Nette\Database\Table\ActiveRow> $selection
	 * @return list<int>
	 */
	private function menuIds(iterable $selection): array
	{
		$ids = [];
		foreach ($selection as $row) {
			$ids[] = (int) $row->{PageTableMap::COL_ID};
		}

		return $ids;
	}
}
