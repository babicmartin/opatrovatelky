<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Factory\BaseFactory;
use App\Model\Factory\CountryFactory;
use App\Model\Table\CountryTableMap;
use App\Model\Utils\Date\DateService;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

class CountryRepository extends BaseRepository
{
	public function __construct(
		Explorer $database,
		DateService $dateService,
		private readonly CountryFactory $countryFactory,
	) {
		parent::__construct($database, $dateService);
	}

	protected function getTableName(): string
	{
		return CountryTableMap::TABLE_NAME;
	}

	protected function getFactory(): BaseFactory
	{
		return $this->countryFactory;
	}

	/**
	 * @return list<array{id:int,name:string,german:string,image:string,active:int}>
	 */
	public function findActiveRows(): array
	{
		$rows = $this->findAll()
			->where(CountryTableMap::COL_ACTIVE, 1)
			->order(CountryTableMap::COL_ID . ' DESC')
			->fetchAll();

		return array_map([$this, 'mapRow'], array_values($rows));
	}

	/**
	 * @return array{id:int,name:string,german:string,image:string,active:int}|null
	 */
	public function findRowById(int $id): ?array
	{
		$row = $this->getItem($id);

		return $row === null ? null : $this->mapRow($row);
	}

	public function createEmpty(): int
	{
		$row = $this->insert([
			CountryTableMap::COL_NAME => '',
			CountryTableMap::COL_GERMAN => '',
			CountryTableMap::COL_IMAGE => '',
			CountryTableMap::COL_ACTIVE => 1,
		]);

		if (!$row instanceof ActiveRow) {
			throw new \RuntimeException('Krajinu sa nepodarilo vytvoriť.');
		}

		return (int) $row->{CountryTableMap::COL_ID};
	}

	/**
	 * @param array{name:mixed,german:mixed} $data
	 */
	public function updateTextFields(int $id, array $data): void
	{
		$this->update($id, [
			CountryTableMap::COL_NAME => (string) ($data['name'] ?? ''),
			CountryTableMap::COL_GERMAN => (string) ($data['german'] ?? ''),
		]);
	}

	public function updateImage(int $id, string $image): void
	{
		$this->update($id, [
			CountryTableMap::COL_IMAGE => $image,
		]);
	}

	/**
	 * @return array{id:int,name:string,german:string,image:string,active:int}
	 */
	private function mapRow(ActiveRow $row): array
	{
		return [
			'id' => (int) $row->{CountryTableMap::COL_ID},
			'name' => (string) ($row->{CountryTableMap::COL_NAME} ?? ''),
			'german' => (string) ($row->{CountryTableMap::COL_GERMAN} ?? ''),
			'image' => (string) ($row->{CountryTableMap::COL_IMAGE} ?? ''),
			'active' => (int) ($row->{CountryTableMap::COL_ACTIVE} ?? 0),
		];
	}
}
