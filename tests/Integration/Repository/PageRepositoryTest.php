<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Entity\PageEntity;
use App\Model\Repository\PageRepository;
use App\Model\Table\PageTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class PageRepositoryTest extends DatabaseTestCase
{
	public function testFindMenuItemsFiltersByActiveParentPositionMenuAndPermission(): void
	{
		$repository = $this->getContainer()->getByType(PageRepository::class);
		$lowPermId = $this->createPage('Opatrovatelky', 10, ['permission' => 2]);
		$highPermId = $this->createPage('Nastavenia', 20, ['permission' => 5]);
		$this->createPage('Inactive', 30, ['active' => 0]);
		$this->createPage('Hidden', 40, ['in_menu' => 0]);
		$this->createPage('Child', 50, ['parent' => $lowPermId]);
		$this->createPage('No position', 0);

		$allMenuIds = $this->menuIds($repository->findMenuItems());
		$restrictedIds = $this->menuIds($repository->findMenuItems(2));

		self::assertSame([$lowPermId, $highPermId], $allMenuIds);
		self::assertSame([$lowPermId], $restrictedIds);

		$entities = $repository->findMenuItems(null, true);
		self::assertSame([$lowPermId, $highPermId], array_map(static fn (PageEntity $page): int => $page->getId(), $entities));
	}

	public function testGetAllWrapsEveryRowToEntity(): void
	{
		$repository = $this->getContainer()->getByType(PageRepository::class);
		$createdId = $this->createPage('Extra', 99);

		$entities = $repository->getAll();

		self::assertContains($createdId, array_map(static fn (PageEntity $page): int => $page->getId(), $entities));
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
