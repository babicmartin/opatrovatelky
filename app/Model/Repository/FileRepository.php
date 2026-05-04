<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\FileTableMap;
use App\Model\Table\StatusDocumentTableMap;
use App\Model\Table\UserTableMap;
use Nette\Database\Row;

class FileRepository extends BaseRepository
{
	protected function getTableName(): string
	{
		return FileTableMap::TABLE_NAME;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findDocuments(string $dir, int $ownerId): array
	{
		$path = $dir . '/' . $ownerId;
		$f = FileTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$f." . FileTableMap::COL_ID . " AS id,
				$f." . FileTableMap::COL_DIR . " AS dir,
				$f." . FileTableMap::COL_NAME . " AS name,
				$f." . FileTableMap::COL_TYPE . " AS type,
				$f." . FileTableMap::COL_UPLOAD . " AS upload,
				$f." . FileTableMap::COL_NOTICE . " AS notice,
				$f." . FileTableMap::COL_VALID_FROM . " AS valid_from,
				$f." . FileTableMap::COL_VALID_TO . " AS valid_to,
				$f." . FileTableMap::COL_STATUS . " AS status_id,
				$f." . FileTableMap::COL_USER . " AS user_id,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym,
				$u." . UserTableMap::COL_COLOR . " AS user_color
			FROM $f
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $f." . FileTableMap::COL_USER . "
			WHERE $f." . FileTableMap::COL_DIR . " = ?
				AND $f." . FileTableMap::COL_ACTIVE . " = 1
			ORDER BY $f." . FileTableMap::COL_ID . " DESC
		";

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'dir' => (string) ($row->dir ?? $path),
				'name' => (string) ($row->name ?? ''),
				'type' => (string) ($row->type ?? 'file'),
				'upload' => self::formatUpload((string) ($row->upload ?? '')),
				'notice' => (string) ($row->notice ?? ''),
				'validFrom' => self::formatDate((string) ($row->valid_from ?? '')),
				'validTo' => self::formatDate((string) ($row->valid_to ?? '')),
				'status' => (int) ($row->status_id ?? 0),
				'userId' => (int) ($row->user_id ?? 0),
				'userAcronym' => (string) ($row->user_acronym ?? ''),
				'userColor' => (string) ($row->user_color ?? ''),
			],
			$this->database->query($sql, $path)->fetchAll(),
		);
	}

	public function insertDocument(string $dir, int $ownerId, string $name, string $type, int $userId): void
	{
		$this->insert([
			FileTableMap::COL_DIR => $dir . '/' . $ownerId,
			FileTableMap::COL_NAME => $name,
			FileTableMap::COL_TYPE => $type,
			FileTableMap::COL_USER => $userId,
			FileTableMap::COL_UPLOAD => date('Y-m-d H:i:s'),
			FileTableMap::COL_ACTIVE => 1,
		]);
	}

	/**
	 * @param array{notice:string,validFrom:string,validTo:string,status:int} $values
	 */
	public function updateDocument(int $id, array $values): void
	{
		$this->update($id, [
			FileTableMap::COL_NOTICE => $values['notice'],
			FileTableMap::COL_VALID_FROM => $this->normalizeDate($values['validFrom']),
			FileTableMap::COL_VALID_TO => $this->normalizeDate($values['validTo']),
			FileTableMap::COL_STATUS => $values['status'],
		]);
	}

	public function softDelete(int $id): void
	{
		$this->update($id, [
			FileTableMap::COL_ACTIVE => 0,
		]);
	}

	/**
	 * @return array<int, string>
	 */
	public function findStatusOptions(): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table(StatusDocumentTableMap::TABLE_NAME)
			->order(StatusDocumentTableMap::COL_STATUS . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{StatusDocumentTableMap::COL_ID}] = (string) $row->{StatusDocumentTableMap::COL_STATUS};
		}

		return $options;
	}

	private static function formatUpload(string $dateTime): string
	{
		if ($dateTime === '') {
			return 'Nahraté';
		}

		$date = substr($dateTime, 0, 10);
		$time = substr($dateTime, 11, 5);

		return 'Nahraté ' . self::formatDate($date) . ($time !== '' ? ' o ' . $time : '');
	}

	private static function formatDate(string $date): string
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
