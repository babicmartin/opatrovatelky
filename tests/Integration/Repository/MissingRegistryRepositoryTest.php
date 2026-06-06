<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\MissingRegistryRepository;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class MissingRegistryRepositoryTest extends DatabaseTestCase
{
	public function testMissingRegistryRepositoryCreatesFindsUpdatesAndSoftDeletesRows(): void
	{
		$repository = $this->getContainer()->getByType(MissingRegistryRepository::class);
		$userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'missing.registry@example.test',
		]);
		$emptyId = $repository->createEmpty();
		$otherId = TestDatabase::insert(MissingRegistryTableMap::TABLE_NAME, [
			MissingRegistryTableMap::COL_USER_ID => $userId,
			MissingRegistryTableMap::COL_DATE_FROM => '2026-06-10',
			MissingRegistryTableMap::COL_DATE_TO => '2026-06-11',
			MissingRegistryTableMap::COL_ACTIVE => 1,
			MissingRegistryTableMap::COL_DELETED => 0,
		]);

		$lastEmpty = $repository->findLastEmptyRow();
		$pageCount = 0;
		$visibleRows = $repository->findVisibleRows(1, 10, $emptyId, $pageCount);

		$repository->updateRegistryRow($emptyId, [
			'userId' => $userId,
			'dateFrom' => new \DateTimeImmutable('2026-07-01'),
			'dateTo' => new \DateTimeImmutable('2026-07-03'),
			'typePn' => true,
			'typeOcr' => false,
			'typeLekar' => true,
			'typeSviatok' => false,
			'typeZastup' => true,
			'typeSluzba' => false,
			'typeDovolenka' => true,
			'notice' => 'Registry notice',
		]);
		$repository->softDelete($otherId);

		self::assertNotNull($lastEmpty);
		self::assertSame($emptyId, $lastEmpty['id']);
		self::assertSame(1, $pageCount);
		self::assertCount(1, $visibleRows);
		self::assertSame($otherId, $visibleRows[0]['id']);

		$updatedRow = $repository->findVisibleRows(1, 10, null)[0];
		self::assertSame($emptyId, $updatedRow['id']);
		self::assertSame($userId, $updatedRow['userId']);
		self::assertSame('2026-07-01', $updatedRow['dateFrom']->format('Y-m-d'));
		self::assertTrue($updatedRow['typePn']);
		self::assertFalse($updatedRow['typeOcr']);
		self::assertTrue($updatedRow['typeLekar']);
		self::assertTrue($updatedRow['typeZastup']);
		self::assertTrue($updatedRow['typeDovolenka']);
		self::assertSame('Registry notice', $updatedRow['notice']);
		self::assertSame(1, (int) $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->get($otherId)?->{MissingRegistryTableMap::COL_DELETED});
	}
}
