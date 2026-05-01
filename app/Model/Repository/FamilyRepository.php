<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\StatusFamilyTableMap;
use Nette\Database\Row;

class FamilyRepository extends BaseRepository
{
	private const int TYPE_FAMILY = 1;
	private const int TYPE_PROJECT = 2;

	protected function getTableName(): string
	{
		return FamilyTableMap::TABLE_NAME;
	}

	/**
	 * @return list<array{id:int,name:string,surname:string,statusLabel:string,statusColor:string}>
	 */
	public function findLastForOffcanvas(int $limit): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$sf = StatusFamilyTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$sf." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$sf." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			LEFT JOIN $sf ON $sf." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $f." . FamilyTableMap::COL_TYPE . " = ?
				AND $f." . FamilyTableMap::COL_DELETED . " = 0
			ORDER BY $f." . FamilyTableMap::COL_ID . " DESC
			LIMIT ?
		";

		$rows = array_values($this->database->query($sql, self::TYPE_FAMILY, $limit)->fetchAll());

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$rows,
		);
	}

	/**
	 * @return list<array{id:int,title:string,color:string,count:int}>
	 */
	public function getStatusCountsForOffcanvas(): array
	{
		$rows = array_values(
			$this->database->table(StatusFamilyTableMap::TABLE_NAME)
				->order(StatusFamilyTableMap::COL_ID . ' ASC')
				->fetchAll(),
		);

		$items = array_map(
			fn ($row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->status ?? ''),
				'color' => (string) ($row->color ?? ''),
				'count' => $this->countFamiliesBy(FamilyTableMap::COL_STATUS, (int) $row->id),
			],
			$rows,
		);

		return array_values(array_filter($items, static fn (array $item): bool => $item['count'] > 0));
	}

	/**
	 * @return list<array{id:int,title:string,image:string,count:int}>
	 */
	public function getCountryCountsForOffcanvas(): array
	{
		$rows = array_values(
			$this->database->table(CountryTableMap::TABLE_NAME)
				->order(CountryTableMap::COL_ID . ' ASC')
				->fetchAll(),
		);

		$items = array_map(
			fn ($row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->name ?? ''),
				'image' => (string) ($row->image ?? ''),
				'count' => $this->countFamiliesBy(FamilyTableMap::COL_STATE, (int) $row->id),
			],
			$rows,
		);

		return array_values(array_filter($items, static fn (array $item): bool => $item['count'] > 0));
	}

	/**
	 * @return list<array{id:int,name:string,surname:string,statusId:int,statusLabel:string,statusColor:string}>
	 */
	public function findLastProjectsForOffcanvas(int $limit): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$sf = StatusFamilyTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$f." . FamilyTableMap::COL_STATUS . " AS status_id,
				$sf." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$sf." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			LEFT JOIN $sf ON $sf." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $f." . FamilyTableMap::COL_TYPE . " = ?
				AND $f." . FamilyTableMap::COL_DELETED . " = 0
			ORDER BY $f." . FamilyTableMap::COL_ID . " DESC
			LIMIT ?
		";

		$rows = array_values($this->database->query($sql, self::TYPE_PROJECT, $limit)->fetchAll());

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$rows,
		);
	}

	/**
	 * @return list<array{id:int,title:string,color:string,count:int}>
	 */
	public function getProjectStatusCountsForOffcanvas(): array
	{
		$rows = array_values(
			$this->database->table(StatusFamilyTableMap::TABLE_NAME)
				->order(StatusFamilyTableMap::COL_ID . ' ASC')
				->fetchAll(),
		);

		$items = array_map(
			fn ($row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->status ?? ''),
				'color' => (string) ($row->color ?? ''),
				'count' => $this->countProjectsBy(FamilyTableMap::COL_STATUS, (int) $row->id),
			],
			$rows,
		);

		return array_values(array_filter($items, static fn (array $item): bool => $item['count'] > 0));
	}

	/**
	 * @return list<array{id:int,title:string,image:string,count:int}>
	 */
	public function getProjectCountryCountsForOffcanvas(): array
	{
		$rows = array_values(
			$this->database->table(CountryTableMap::TABLE_NAME)
				->order(CountryTableMap::COL_ID . ' ASC')
				->fetchAll(),
		);

		$items = array_map(
			fn ($row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->name ?? ''),
				'image' => (string) ($row->image ?? ''),
				'count' => $this->countProjectsBy(FamilyTableMap::COL_STATE, (int) $row->id),
			],
			$rows,
		);

		return array_values(array_filter($items, static fn (array $item): bool => $item['count'] > 0));
	}

	private function countFamiliesBy(string $column, int $value): int
	{
		return $this->database->table(FamilyTableMap::TABLE_NAME)
			->where($column, $value)
			->where(FamilyTableMap::COL_TYPE, self::TYPE_FAMILY)
			->where(FamilyTableMap::COL_DELETED, 0)
			->count('*');
	}

	private function countProjectsBy(string $column, int $value): int
	{
		return $this->database->table(FamilyTableMap::TABLE_NAME)
			->where($column, $value)
			->where(FamilyTableMap::COL_TYPE, self::TYPE_PROJECT)
			->where(FamilyTableMap::COL_DELETED, 0)
			->count('*');
	}
}
