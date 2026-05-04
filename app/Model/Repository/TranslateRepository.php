<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Factory\BaseFactory;
use App\Model\Factory\TranslateFactory;
use App\Model\Table\TranslateTableMap;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

class TranslateRepository extends BaseRepository
{
	public function __construct(
		Explorer $database,
		private readonly TranslateFactory $translateFactory,
	) {
		parent::__construct($database);
	}

	protected function getTableName(): string
	{
		return TranslateTableMap::TABLE_NAME;
	}

	protected function getFactory(): BaseFactory
	{
		return $this->translateFactory;
	}

	/**
	 * @return list<array{id:int,slovak:string,german:string}>
	 */
	public function findRows(): array
	{
		$rows = $this->findAll()
			->order(TranslateTableMap::COL_ID . ' DESC')
			->fetchAll();

		return array_map([$this, 'mapRow'], array_values($rows));
	}

	/**
	 * @param array{german:mixed} $data
	 */
	public function updateGerman(int $id, array $data): void
	{
		$this->update($id, [
			TranslateTableMap::COL_GERMAN => (string) ($data['german'] ?? ''),
		]);
	}

	/**
	 * @return array{id:int,slovak:string,german:string}
	 */
	private function mapRow(ActiveRow $row): array
	{
		return [
			'id' => (int) $row->{TranslateTableMap::COL_ID},
			'slovak' => (string) ($row->{TranslateTableMap::COL_SLOVAK} ?? ''),
			'german' => (string) ($row->{TranslateTableMap::COL_GERMAN} ?? ''),
		];
	}
}
