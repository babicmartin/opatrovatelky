<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\BabysitterVideoTableMap;
use App\Model\Table\UserTableMap;
use DateTimeImmutable;
use Nette\Database\Row;
use Nette\Database\Table\ActiveRow;

class BabysitterVideoRepository extends BaseRepository
{
	protected function getTableName(): string
	{
		return BabysitterVideoTableMap::TABLE_NAME;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findForBabysitter(int $babysitterId): array
	{
		$v = BabysitterVideoTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$v." . BabysitterVideoTableMap::COL_ID . " AS id,
				$v." . BabysitterVideoTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$v." . BabysitterVideoTableMap::COL_ORIGINAL_NAME . " AS original_name,
				$v." . BabysitterVideoTableMap::COL_STORED_NAME . " AS stored_name,
				$v." . BabysitterVideoTableMap::COL_EXTENSION . " AS extension,
				$v." . BabysitterVideoTableMap::COL_MIME_TYPE . " AS mime_type,
				$v." . BabysitterVideoTableMap::COL_SIZE_BYTES . " AS size_bytes,
				$v." . BabysitterVideoTableMap::COL_DURATION_SECONDS . " AS duration_seconds,
				$v." . BabysitterVideoTableMap::COL_CHECKSUM_SHA256 . " AS checksum_sha256,
				$v." . BabysitterVideoTableMap::COL_UPLOADED_BY_USER_ID . " AS uploaded_by_user_id,
				$v." . BabysitterVideoTableMap::COL_UPLOADED_AT . " AS uploaded_at,
				$u." . UserTableMap::COL_NAME . " AS user_name,
				$u." . UserTableMap::COL_SECOND_NAME . " AS user_second_name,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym,
				$u." . UserTableMap::COL_COLOR . " AS user_color
			FROM $v
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $v." . BabysitterVideoTableMap::COL_UPLOADED_BY_USER_ID . "
			WHERE $v." . BabysitterVideoTableMap::COL_BABYSITTER_ID . " = ?
				AND $v." . BabysitterVideoTableMap::COL_ACTIVE . " = 1
			ORDER BY $v." . BabysitterVideoTableMap::COL_UPLOADED_AT . " DESC, $v." . BabysitterVideoTableMap::COL_ID . " DESC
		";

		return array_map(
			fn (Row $row): array => $this->mapRow($row),
			$this->database->query($sql, $babysitterId)->fetchAll(),
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findForBabysitterById(int $babysitterId, int $id): ?array
	{
		foreach ($this->findForBabysitter($babysitterId) as $video) {
			if ((int) $video['id'] === $id) {
				return $video;
			}
		}

		return null;
	}

	/**
	 * @param array{
	 *     babysitterId:int,
	 *     originalName:string,
	 *     storedName:string,
	 *     extension:string,
	 *     mimeType:string,
	 *     sizeBytes:int,
	 *     durationSeconds:?int,
	 *     checksumSha256:string,
	 *     uploadedByUserId:int
	 * } $data
	 */
	public function insertVideo(array $data): int
	{
		$row = $this->insert([
			BabysitterVideoTableMap::COL_BABYSITTER_ID => $data['babysitterId'],
			BabysitterVideoTableMap::COL_ORIGINAL_NAME => $data['originalName'],
			BabysitterVideoTableMap::COL_STORED_NAME => $data['storedName'],
			BabysitterVideoTableMap::COL_EXTENSION => $data['extension'],
			BabysitterVideoTableMap::COL_MIME_TYPE => $data['mimeType'],
			BabysitterVideoTableMap::COL_SIZE_BYTES => $data['sizeBytes'],
			BabysitterVideoTableMap::COL_DURATION_SECONDS => $data['durationSeconds'],
			BabysitterVideoTableMap::COL_CHECKSUM_SHA256 => $data['checksumSha256'],
			BabysitterVideoTableMap::COL_UPLOADED_BY_USER_ID => $data['uploadedByUserId'],
			BabysitterVideoTableMap::COL_UPLOADED_AT => date('Y-m-d H:i:s'),
			BabysitterVideoTableMap::COL_ACTIVE => 1,
		]);

		if (!$row instanceof ActiveRow) {
			throw new \RuntimeException('Video sa nepodarilo uložiť.');
		}

		return (int) $row->getPrimary();
	}

	public function softDelete(int $id, int $userId): void
	{
		$this->update($id, [
			BabysitterVideoTableMap::COL_ACTIVE => 0,
			BabysitterVideoTableMap::COL_DELETED_BY_USER_ID => $userId,
			BabysitterVideoTableMap::COL_DELETED_AT => date('Y-m-d H:i:s'),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function mapRow(Row $row): array
	{
		$uploadedAt = $this->createDateTime((string) ($row->uploaded_at ?? ''));
		$userName = trim((string) ($row->user_name ?? '') . ' ' . (string) ($row->user_second_name ?? ''));

		return [
			'id' => (int) $row->id,
			'babysitterId' => (int) $row->babysitter_id,
			'originalName' => (string) ($row->original_name ?? ''),
			'storedName' => (string) ($row->stored_name ?? ''),
			'extension' => (string) ($row->extension ?? ''),
			'mimeType' => (string) ($row->mime_type ?? ''),
			'sizeBytes' => (int) ($row->size_bytes ?? 0),
			'durationSeconds' => $row->duration_seconds === null ? null : (int) $row->duration_seconds,
			'checksumSha256' => (string) ($row->checksum_sha256 ?? ''),
			'uploadedByUserId' => (int) ($row->uploaded_by_user_id ?? 0),
			'uploadedAt' => $uploadedAt,
			'uploadedAtLabel' => $uploadedAt?->format('d.m.Y H:i') ?? '',
			'userName' => $userName,
			'userAcronym' => (string) ($row->user_acronym ?? ''),
			'userColor' => (string) ($row->user_color ?? ''),
			'sizeLabel' => $this->formatBytes((int) ($row->size_bytes ?? 0)),
			'durationLabel' => $this->formatDuration($row->duration_seconds === null ? null : (int) $row->duration_seconds),
		];
	}

	private function createDateTime(string $value): ?DateTimeImmutable
	{
		$value = trim($value);
		if ($value === '' || str_starts_with($value, '0000-00-00')) {
			return null;
		}

		$dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
		return $dt === false ? null : $dt;
	}

	private function formatBytes(int $bytes): string
	{
		if ($bytes >= 1_073_741_824) {
			return number_format($bytes / 1_073_741_824, 2, ',', ' ') . ' GB';
		}
		if ($bytes >= 1_048_576) {
			return number_format($bytes / 1_048_576, 2, ',', ' ') . ' MB';
		}
		if ($bytes >= 1024) {
			return number_format($bytes / 1024, 2, ',', ' ') . ' kB';
		}

		return $bytes . ' B';
	}

	private function formatDuration(?int $seconds): string
	{
		if ($seconds === null || $seconds <= 0) {
			return '-';
		}

		$hours = intdiv($seconds, 3600);
		$minutes = intdiv($seconds % 3600, 60);
		$remainingSeconds = $seconds % 60;

		if ($hours > 0) {
			return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
		}

		return sprintf('%d:%02d', $minutes, $remainingSeconds);
	}
}
