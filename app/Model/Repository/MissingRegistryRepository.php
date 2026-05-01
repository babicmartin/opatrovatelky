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
	 * @param array<string, mixed> $data
	 */
	public function updateRegistryRow(int $id, array $data): void
	{
		$this->update($id, [
			MissingRegistryTableMap::COL_USER_ID => (int) ($data['userId'] ?? 0),
			MissingRegistryTableMap::COL_DATE_FROM => $this->normalizeDate((string) ($data['dateFrom'] ?? '')),
			MissingRegistryTableMap::COL_DATE_TO => $this->normalizeDate((string) ($data['dateTo'] ?? '')),
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
			'dateFrom' => $this->formatDate((string) ($row->{MissingRegistryTableMap::COL_DATE_FROM} ?? '')),
			'dateTo' => $this->formatDate((string) ($row->{MissingRegistryTableMap::COL_DATE_TO} ?? '')),
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

	private function formatDate(string $date): string
	{
		if ($date === '' || $date === '0000-00-00' || $date === '-0001-11-30 00:00:00') {
			return '';
		}

		$parts = explode('-', substr($date, 0, 10));
		if (count($parts) !== 3) {
			return '';
		}

		return $parts[2] . '.' . $parts[1] . '.' . $parts[0];
	}

	private function normalizeDate(string $date): ?string
	{
		$date = trim($date);
		if ($date === '') {
			return null;
		}

		$parts = explode('.', $date);
		if (count($parts) !== 3) {
			return null;
		}

		return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
	}
}
