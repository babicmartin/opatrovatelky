<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\MissingRegistryTableMap;
use Nette\Database\Table\ActiveRow;

class MissingRegistryRepository extends BaseRepository
{
	protected function getTableName(): string
	{
		return MissingRegistryTableMap::TABLE_NAME;
	}

	/**
	 * @param int<1, max> $page
	 * @param int<1, max> $itemsPerPage
	 * @return list<array<string, mixed>>
	 */
	public function findVisibleRows(int $page, int $itemsPerPage, ?int $excludeId, ?int &$pageCount = null): array
	{
		$selection = $this->findAll()
			->where(MissingRegistryTableMap::COL_DELETED, 0)
			->order(MissingRegistryTableMap::COL_DATE_FROM . ' DESC');

		if ($excludeId !== null) {
			$selection->where(MissingRegistryTableMap::COL_ID . ' != ?', $excludeId);
		}

		$selection->page($page, $itemsPerPage, $pageCount);

		$rows = $selection->fetchAll();

		return array_map([$this, 'mapRow'], array_values($rows));
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findLastEmptyRow(): ?array
	{
		$row = $this->findAll()
			->where('(' . MissingRegistryTableMap::COL_DATE_FROM . ' IS NULL OR ' . MissingRegistryTableMap::COL_DATE_FROM . " = '')")
			->where(MissingRegistryTableMap::COL_DELETED, 0)
			->order(MissingRegistryTableMap::COL_DATE_FROM . ' DESC')
			->limit(1)
			->fetch();

		return $row === null ? null : $this->mapRow($row);
	}

	public function createEmpty(): void
	{
		$this->insert([
			MissingRegistryTableMap::COL_USER_ID => 0,
			MissingRegistryTableMap::COL_ACTIVE => 1,
			MissingRegistryTableMap::COL_DELETED => 0,
		]);
	}

	/**
	 * @param array{userId?:int,dateFrom?:?\DateTimeImmutable,dateTo?:?\DateTimeImmutable,typePn?:mixed,typeOcr?:mixed,typeLekar?:mixed,typeSviatok?:mixed,typeZastup?:mixed,typeSluzba?:mixed,typeDovolenka?:mixed,notice?:string} $data
	 */
	public function updateRegistryRow(int $id, array $data): void
	{
		$dateFrom = $data['dateFrom'] ?? null;
		$dateTo = $data['dateTo'] ?? null;
		$this->update($id, [
			MissingRegistryTableMap::COL_USER_ID => (int) ($data['userId'] ?? 0),
			MissingRegistryTableMap::COL_DATE_FROM => $dateFrom instanceof \DateTimeImmutable ? $dateFrom->format('Y-m-d') : null,
			MissingRegistryTableMap::COL_DATE_TO => $dateTo instanceof \DateTimeImmutable ? $dateTo->format('Y-m-d') : null,
			MissingRegistryTableMap::COL_TYPE_PN => !empty($data['typePn']) ? 1 : 0,
			MissingRegistryTableMap::COL_TYPE_OCR => !empty($data['typeOcr']) ? 1 : 0,
			MissingRegistryTableMap::COL_TYPE_LEKAR => !empty($data['typeLekar']) ? 1 : 0,
			MissingRegistryTableMap::COL_TYPE_SVIATOK => !empty($data['typeSviatok']) ? 1 : 0,
			MissingRegistryTableMap::COL_TYPE_ZASTUP => !empty($data['typeZastup']) ? 1 : 0,
			MissingRegistryTableMap::COL_TYPE_SLUZBA => !empty($data['typeSluzba']) ? 1 : 0,
			MissingRegistryTableMap::COL_TYPE_DOVOLENKA => !empty($data['typeDovolenka']) ? 1 : 0,
			MissingRegistryTableMap::COL_NOTICE => (string) ($data['notice'] ?? ''),
		]);
	}

	public function softDelete(int $id): void
	{
		$this->update($id, [
			MissingRegistryTableMap::COL_DELETED => 1,
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mapRow(ActiveRow $row): array
	{
		return [
			'id' => (int) $row->id,
			'formKey' => 'r' . (int) $row->id,
			'userId' => (int) ($row->{MissingRegistryTableMap::COL_USER_ID} ?? 0),
			'dateFrom' => $this->dateService->tryCreateFromDb((string) ($row->{MissingRegistryTableMap::COL_DATE_FROM} ?? '')),
			'dateTo' => $this->dateService->tryCreateFromDb((string) ($row->{MissingRegistryTableMap::COL_DATE_TO} ?? '')),
			'typePn' => (bool) $row->{MissingRegistryTableMap::COL_TYPE_PN},
			'typeOcr' => (bool) $row->{MissingRegistryTableMap::COL_TYPE_OCR},
			'typeLekar' => (bool) $row->{MissingRegistryTableMap::COL_TYPE_LEKAR},
			'typeSviatok' => (bool) $row->{MissingRegistryTableMap::COL_TYPE_SVIATOK},
			'typeZastup' => (bool) $row->{MissingRegistryTableMap::COL_TYPE_ZASTUP},
			'typeSluzba' => (bool) $row->{MissingRegistryTableMap::COL_TYPE_SLUZBA},
			'typeDovolenka' => (bool) $row->{MissingRegistryTableMap::COL_TYPE_DOVOLENKA},
			'notice' => (string) ($row->{MissingRegistryTableMap::COL_NOTICE} ?? ''),
		];
	}

}
