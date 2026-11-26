<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\DTO\MissingRegistry\MissingRegistryInput;
use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Repository\MissingRegistryRepository;
use App\Model\Table\MissingRegistryTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class MissingRegistryRepositoryTest extends DatabaseTestCase
{
	public function testMissingRegistryRepositoryCreatesFindsUpdatesAndSoftDeletesRows(): void
	{
		$repository = $this->repository();
		$userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'missing.registry@example.test',
		]);
		$emptyId = $repository->createEmpty();
		$otherId = $this->insertRecord($userId, '2026-06-10', '2026-06-11');

		$lastEmpty = $repository->findLastEmptyRow();
		$pageCount = 0;
		$visibleRows = $repository->findVisibleRows(1, 10, $emptyId, $pageCount);

		$updated = $repository->updateRecord($emptyId, $userId, new MissingRegistryInput(
			$userId,
			new \DateTimeImmutable('2026-07-01'),
			new \DateTimeImmutable('2026-07-03'),
			[MissingType::PN, MissingType::LEKAR, MissingType::ZASTUP, MissingType::DOVOLENKA],
			'Registry notice',
		));
		$repository->softDelete($otherId);

		self::assertTrue($updated);
		self::assertNotNull($lastEmpty);
		self::assertSame($emptyId, $lastEmpty->id);
		self::assertSame(1, $pageCount);
		self::assertCount(1, $visibleRows);
		self::assertSame($otherId, $visibleRows[0]->id);

		$updatedRow = $repository->findVisibleRows(1, 10, null)[0];
		self::assertSame($emptyId, $updatedRow->id);
		self::assertSame($userId, $updatedRow->userId);
		self::assertSame('2026-07-01', $updatedRow->dateFrom?->format('Y-m-d'));
		self::assertSame('2026-07-03', $updatedRow->dateTo?->format('Y-m-d'));
		self::assertSame([MissingType::PN, MissingType::LEKAR, MissingType::ZASTUP, MissingType::DOVOLENKA], $updatedRow->types);
		self::assertSame('Registry notice', $updatedRow->notice);
		self::assertSame(1, (int) $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->get($otherId)?->{MissingRegistryTableMap::COL_DELETED});
	}

	public function testDeletedRecordIsNeitherFoundNorChanged(): void
	{
		$repository = $this->repository();
		$id = $this->insertRecord(7, '2026-06-10', '2026-06-11', [MissingRegistryTableMap::COL_DELETED => 1]);

		self::assertNull($repository->findActive($id));
		self::assertFalse($repository->softDelete($id));
		self::assertFalse($repository->updateRecord($id, 8, new MissingRegistryInput(8, null, null, [], 'changed')));

		$row = $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->get($id);
		self::assertSame(7, (int) $row?->{MissingRegistryTableMap::COL_USER_ID});
		self::assertNull($row?->{MissingRegistryTableMap::COL_NOTICE});
	}

	public function testCreateStoresEveryTypeInItsOwnColumn(): void
	{
		$repository = $this->repository();

		$id = $repository->create(5, new MissingRegistryInput(
			5,
			new \DateTimeImmutable('2026-03-02'),
			new \DateTimeImmutable('2026-03-04'),
			[MissingType::SLUZBA],
			'Duty',
		));

		$row = $this->getDatabase()->table(MissingRegistryTableMap::TABLE_NAME)->get($id);
		self::assertNotNull($row);
		foreach (MissingType::cases() as $type) {
			self::assertSame($type === MissingType::SLUZBA ? 1 : 0, (int) $row->{$type->column()}, $type->value);
		}
		self::assertSame(5, (int) $row->{MissingRegistryTableMap::COL_USER_ID});
		self::assertSame(0, (int) $row->{MissingRegistryTableMap::COL_DELETED});

		$record = $repository->findActive($id);
		self::assertInstanceOf(MissingRegistryRecord::class, $record);
		self::assertSame([MissingType::SLUZBA], $record->types);
	}

	public function testFindLastEmptyRowCanBeLimitedToOneUser(): void
	{
		$repository = $this->repository();
		$ownId = $repository->createEmpty(3);
		$foreignId = $repository->createEmpty(4);

		self::assertSame($foreignId, $repository->findLastEmptyRow()?->id);
		self::assertSame($ownId, $repository->findLastEmptyRow(3)?->id);
		self::assertNull($repository->findLastEmptyRow(9));
	}

	public function testFindInRangeReturnsRecordsThatTouchThePeriod(): void
	{
		$repository = $this->repository();
		$before = $this->insertRecord(1, '2026-10-20', '2026-10-31');
		$overlapsStart = $this->insertRecord(1, '2026-10-29', '2026-11-02');
		$inside = $this->insertRecord(2, '2026-11-10', '2026-11-12');
		$overlapsEnd = $this->insertRecord(2, '2026-11-30', '2026-12-04');
		$after = $this->insertRecord(1, '2026-12-01', '2026-12-02');
		$withoutEnd = $this->insertRecord(3, '2026-11-15', null);
		$endBeforeStart = $this->insertRecord(3, '2026-11-20', '2026-11-01');
		$deleted = $this->insertRecord(3, '2026-11-05', '2026-11-06', [MissingRegistryTableMap::COL_DELETED => 1]);
		$empty = $repository->createEmpty(3);

		$ids = array_map(
			static fn (MissingRegistryRecord $record): int => $record->id,
			$repository->findInRange(new \DateTimeImmutable('2026-11-01'), new \DateTimeImmutable('2026-11-30')),
		);

		self::assertSame([$overlapsStart, $inside, $withoutEnd, $endBeforeStart, $overlapsEnd], $ids);
		self::assertNotContains($before, $ids);
		self::assertNotContains($after, $ids);
		self::assertNotContains($deleted, $ids);
		self::assertNotContains($empty, $ids);
	}

	private function repository(): MissingRegistryRepository
	{
		return $this->getContainer()->getByType(MissingRegistryRepository::class);
	}

	/**
	 * @param array<string, mixed> $overrides
	 */
	private function insertRecord(int $userId, string $from, ?string $to, array $overrides = []): int
	{
		return TestDatabase::insert(MissingRegistryTableMap::TABLE_NAME, $overrides + [
			MissingRegistryTableMap::COL_USER_ID => $userId,
			MissingRegistryTableMap::COL_DATE_FROM => $from,
			MissingRegistryTableMap::COL_DATE_TO => $to,
			MissingRegistryTableMap::COL_ACTIVE => 1,
			MissingRegistryTableMap::COL_DELETED => 0,
		]);
	}
}
