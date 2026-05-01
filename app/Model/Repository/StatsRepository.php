<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\AgencyTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\StatusBabysitterTableMap;
use App\Model\Table\StatusFamilyTableMap;
use App\Model\Table\StatusPartnerTableMap;
use Nette\Database\Table\ActiveRow;

class StatsRepository extends BaseRepository
{
	protected function getTableName(): string
	{
		return FamilyTableMap::TABLE_NAME;
	}

	/**
	 * @return list<array{title: string, statusItems: list<array{title: string, color: string, count: int, link: array{destination: string|null, parameters: array<string, int>}}>, countryItems: list<array{title: string, image: string, count: int, link: array{destination: string|null, parameters: array<string, int>}}>}>
	 */
	public function getOverview(): array
	{
		return [
			$this->createStatusSection(
				'Opatrovateľky',
				OpatrovatelkaTableMap::TABLE_NAME,
				OpatrovatelkaTableMap::COL_STATUS,
				OpatrovatelkaTableMap::COL_COUNTRY,
				StatusBabysitterTableMap::TABLE_NAME,
				StatusBabysitterTableMap::COL_ID,
				':Admin:Babysitter:default',
			),
			$this->createStatusSection(
				'Rodiny',
				FamilyTableMap::TABLE_NAME,
				FamilyTableMap::COL_STATUS,
				FamilyTableMap::COL_STATE,
				StatusFamilyTableMap::TABLE_NAME,
				StatusFamilyTableMap::COL_ID,
				':Admin:Family:default',
			),
			$this->createStatusSection(
				'Partneri',
				PartnerTableMap::TABLE_NAME,
				PartnerTableMap::COL_STATUS,
				PartnerTableMap::COL_STATE,
				StatusPartnerTableMap::TABLE_NAME,
				StatusPartnerTableMap::COL_ID,
				':Admin:Partner:default',
			),
			$this->createStatusSection(
				'Agentúry',
				AgencyTableMap::TABLE_NAME,
				AgencyTableMap::COL_STATUS,
				AgencyTableMap::COL_STATE,
				StatusPartnerTableMap::TABLE_NAME,
				StatusPartnerTableMap::COL_ID,
				':Admin:Agency:default',
			),
		];
	}

	/**
	 * @return array{title: string, statusItems: list<array{title: string, color: string, count: int, link: array{destination: string|null, parameters: array<string, int>}}>, countryItems: list<array{title: string, image: string, count: int, link: array{destination: string|null, parameters: array<string, int>}}>}
	 */
	private function createStatusSection(
		string $title,
		string $tableName,
		string $statusColumn,
		string $countryColumn,
		string $statusTableName,
		string $statusIdColumn,
		?string $destination,
	): array {
		return [
			'title' => $title,
			'statusItems' => $this->createStatusItems($tableName, $statusColumn, $statusTableName, $statusIdColumn, $destination),
			'countryItems' => $this->createCountryItems($tableName, $countryColumn, $destination),
		];
	}

	/**
	 * @return list<array{title: string, color: string, count: int, link: array{destination: string|null, parameters: array<string, int>}}>
	 */
	private function createStatusItems(
		string $tableName,
		string $statusColumn,
		string $statusTableName,
		string $statusIdColumn,
		?string $destination,
	): array {
		$rows = array_values($this->database->table($statusTableName)->order($statusIdColumn . ' ASC')->fetchAll());

		return array_values(array_filter(array_map(
			fn (ActiveRow $row): array => [
				'title' => (string) $row->status,
				'color' => (string) ($row->color ?? ''),
				'count' => $this->countByColumn($tableName, $statusColumn, (int) $row->id),
				'link' => [
					'destination' => $destination,
					'parameters' => ['status' => (int) $row->id],
				],
			],
			$rows,
		), static fn (array $item): bool => $item['count'] > 0));
	}

	/**
	 * @return list<array{title: string, image: string, count: int, link: array{destination: string|null, parameters: array<string, int>}}>
	 */
	private function createCountryItems(string $tableName, string $countryColumn, ?string $destination): array
	{
		$rows = array_values($this->database->table(CountryTableMap::TABLE_NAME)->order(CountryTableMap::COL_ID . ' ASC')->fetchAll());

		return array_values(array_filter(array_map(
			fn (ActiveRow $row): array => [
				'title' => (string) $row->name,
				'image' => (string) ($row->image ?? ''),
				'count' => $this->countByColumn($tableName, $countryColumn, (int) $row->id),
				'link' => [
					'destination' => $destination,
					'parameters' => ['country' => (int) $row->id],
				],
			],
			$rows,
		), static fn (array $item): bool => $item['count'] > 0));
	}

	private function countByColumn(string $tableName, string $column, int $value): int
	{
		return $this->database->table($tableName)->where($column, $value)->count('*');
	}
}
