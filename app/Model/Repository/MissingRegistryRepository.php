<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\DTO\MissingRegistry\MissingRegistryInput;
use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Table\MissingRegistryTableMap;
use DateTimeImmutable;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class MissingRegistryRepository extends BaseRepository
{
	protected function getTableName(): string
	{
		return MissingRegistryTableMap::TABLE_NAME;
	}

	/**
	 * @param int<1, max> $page
	 * @param int<1, max> $itemsPerPage
	 * @return list<MissingRegistryRecord>
	 */
	public function findVisibleRows(int $page, int $itemsPerPage, ?int $excludeId, ?int &$pageCount = null): array
	{
		$selection = $this->findNotDeleted()
			->order(MissingRegistryTableMap::COL_DATE_FROM . ' DESC');

		if ($excludeId !== null) {
			$selection->where(MissingRegistryTableMap::COL_ID . ' != ?', $excludeId);
		}

		$selection->page($page, $itemsPerPage, $pageCount);

		return $this->mapRows($selection);
	}

	/**
	 * The newest row that has no date yet, the one the list offers as "Nový záznam".
	 * With $userId only a row of that user is returned.
	 */
	public function findLastEmptyRow(?int $userId = null): ?MissingRegistryRecord
	{
		$selection = $this->findNotDeleted()
			->where('(' . MissingRegistryTableMap::COL_DATE_FROM . ' IS NULL OR ' . MissingRegistryTableMap::COL_DATE_FROM . " = '')")
			->order(MissingRegistryTableMap::COL_ID . ' DESC')
			->limit(1);

		if ($userId !== null) {
			$selection->where(MissingRegistryTableMap::COL_USER_ID, $userId);
		}

		$row = $selection->fetch();

		return $row === null ? null : $this->mapRow($row);
	}

	public function findActive(int $id): ?MissingRegistryRecord
	{
		$row = $this->findNotDeleted()
			->where(MissingRegistryTableMap::COL_ID, $id)
			->fetch();

		return $row === null ? null : $this->mapRow($row);
	}

	/**
	 * Records that have at least one day inside the given period, oldest first.
	 *
	 * @return list<MissingRegistryRecord>
	 */
	public function findInRange(DateTimeImmutable $from, DateTimeImmutable $to): array
	{
		$dateFrom = MissingRegistryTableMap::COL_DATE_FROM;
		$dateTo = MissingRegistryTableMap::COL_DATE_TO;

		$selection = $this->findNotDeleted()
			->where("$dateFrom IS NOT NULL")
			->where("$dateFrom <= ?", $to->format('Y-m-d'))
			->where("GREATEST(COALESCE($dateTo, $dateFrom), $dateFrom) >= ?", $from->format('Y-m-d'))
			->order("$dateFrom ASC, " . MissingRegistryTableMap::COL_ID . ' ASC');

		return $this->mapRows($selection);
	}

	public function createEmpty(int $userId = 0): int
	{
		return $this->insertRow([MissingRegistryTableMap::COL_USER_ID => $userId]);
	}

	public function create(int $userId, MissingRegistryInput $input): int
	{
		return $this->insertRow($this->toRow($userId, $input));
	}

	/**
	 * Returns false when the record does not exist or is deleted.
	 */
	public function updateRecord(int $id, int $userId, MissingRegistryInput $input): bool
	{
		$selection = $this->findNotDeleted()->where(MissingRegistryTableMap::COL_ID, $id);
		if ($selection->count('*') === 0) {
			return false;
		}

		$selection->update($this->toRow($userId, $input));

		return true;
	}

	public function softDelete(int $id): bool
	{
		return $this->findNotDeleted()
			->where(MissingRegistryTableMap::COL_ID, $id)
			->update([MissingRegistryTableMap::COL_DELETED => 1]) === 1;
	}

	/**
	 * @return Selection<ActiveRow>
	 */
	private function findNotDeleted(): Selection
	{
		return $this->findAll()->where(MissingRegistryTableMap::COL_DELETED, 0);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function insertRow(array $data): int
	{
		$row = $this->insert($data + [
			MissingRegistryTableMap::COL_ACTIVE => 1,
			MissingRegistryTableMap::COL_DELETED => 0,
		]);

		if (!$row instanceof ActiveRow) {
			throw new \RuntimeException('Missing registry row was not created.');
		}

		return (int) $row->{MissingRegistryTableMap::COL_ID};
	}

	/**
	 * @return array<string, mixed>
	 */
	private function toRow(int $userId, MissingRegistryInput $input): array
	{
		$row = [
			MissingRegistryTableMap::COL_USER_ID => $userId,
			MissingRegistryTableMap::COL_DATE_FROM => $input->dateFrom?->format('Y-m-d'),
			MissingRegistryTableMap::COL_DATE_TO => $input->dateTo?->format('Y-m-d'),
			MissingRegistryTableMap::COL_NOTICE => $input->notice,
		];

		foreach (MissingType::cases() as $type) {
			$row[$type->column()] = in_array($type, $input->types, true) ? 1 : 0;
		}

		return $row;
	}

	/**
	 * @param Selection<ActiveRow> $selection
	 * @return list<MissingRegistryRecord>
	 */
	private function mapRows(Selection $selection): array
	{
		return array_map($this->mapRow(...), array_values($selection->fetchAll()));
	}

	private function mapRow(ActiveRow $row): MissingRegistryRecord
	{
		return new MissingRegistryRecord(
			(int) $row->{MissingRegistryTableMap::COL_ID},
			(int) ($row->{MissingRegistryTableMap::COL_USER_ID} ?? 0),
			$this->dateService->tryCreateFromDb((string) ($row->{MissingRegistryTableMap::COL_DATE_FROM} ?? '')),
			$this->dateService->tryCreateFromDb((string) ($row->{MissingRegistryTableMap::COL_DATE_TO} ?? '')),
			array_values(array_filter(
				MissingType::cases(),
				static fn (MissingType $type): bool => (bool) $row->{$type->column()},
			)),
			(string) ($row->{MissingRegistryTableMap::COL_NOTICE} ?? ''),
		);
	}
}
