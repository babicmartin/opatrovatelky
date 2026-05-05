<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\FamilyTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\StatusFamilyTableMap;
use App\Model\Table\StatusPartnerTableMap;
use App\Model\Form\DTO\Admin\Partner\PartnerUpdate\PartnerUpdateForm;
use Nette\Database\Row;

class PartnerRepository extends BaseRepository
{
	private const int ACTIVE_FAMILY_STATUS = 1;

	protected function getTableName(): string
	{
		return PartnerTableMap::TABLE_NAME;
	}

	public function createEmptyPartner(): int
	{
		$row = $this->insert([]);

		if (!is_object($row) || !isset($row->{PartnerTableMap::COL_ID})) {
			throw new \RuntimeException('Partner sa nepodarilo vytvoriť.');
		}

		return (int) $row->{PartnerTableMap::COL_ID};
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findUpdateRow(int $id): ?array
	{
		$row = $this->findAll()
			->where(PartnerTableMap::COL_ID, $id)
			->where(PartnerTableMap::COL_ACTIVE, 1)
			->fetch();

		if ($row === null) {
			return null;
		}

		return [
			'id' => (int) $row->{PartnerTableMap::COL_ID},
			'name' => (string) ($row->{PartnerTableMap::COL_NAME} ?? ''),
			'street' => (string) ($row->{PartnerTableMap::COL_STREET} ?? ''),
			'streetNumber' => (string) ($row->{PartnerTableMap::COL_STREET_NUMBER} ?? ''),
			'psc' => (string) ($row->{PartnerTableMap::COL_PSC} ?? ''),
			'city' => (string) ($row->{PartnerTableMap::COL_CITY} ?? ''),
			'state' => (int) ($row->{PartnerTableMap::COL_STATE} ?? 0),
			'dateStart' => $this->dateService->tryCreateFromDb((string) ($row->{PartnerTableMap::COL_DATE_START} ?? '')),
			'personSurname' => (string) ($row->{PartnerTableMap::COL_PERSON_SURNAME} ?? ''),
			'personName' => (string) ($row->{PartnerTableMap::COL_PERSON_NAME} ?? ''),
			'ico' => (string) ($row->{PartnerTableMap::COL_ICO} ?? ''),
			'icDph' => (string) ($row->{PartnerTableMap::COL_IC_DPH} ?? ''),
			'web' => (string) ($row->{PartnerTableMap::COL_WEB} ?? ''),
			'phone' => (string) ($row->{PartnerTableMap::COL_PHONE} ?? ''),
			'email' => (string) ($row->{PartnerTableMap::COL_EMAIL} ?? ''),
			'status' => (int) ($row->{PartnerTableMap::COL_STATUS} ?? 0),
			'notice' => (string) ($row->{PartnerTableMap::COL_NOTICE} ?? ''),
		];
	}

	/**
	 * @return list<array{id:int,name:string,websiteUrl:string,web:string,phone:string,email:string,countryId:int,countryImage:string,statusId:int,statusLabel:string,statusColor:string}>
	 */
	public function findPartnerRows(?int $countryId, ?int $statusId): array
	{
		$p = PartnerTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$s = StatusPartnerTableMap::TABLE_NAME;

		$where = ["$p." . PartnerTableMap::COL_ACTIVE . ' = ?'];
		$params = [1];
		$order = "$p." . PartnerTableMap::COL_ID . ' DESC';

		if ($countryId !== null) {
			$where[] = "$p." . PartnerTableMap::COL_STATE . ' = ?';
			$params[] = $countryId;
			$order = "$p." . PartnerTableMap::COL_NAME . ' ASC';
		} elseif ($statusId !== null) {
			$where[] = "$p." . PartnerTableMap::COL_STATUS . ' = ?';
			$params[] = $statusId;
		}

		$whereSql = implode(' AND ', $where);
		$sql = "
			SELECT
				$p." . PartnerTableMap::COL_ID . " AS id,
				$p." . PartnerTableMap::COL_NAME . " AS name,
				$p." . PartnerTableMap::COL_WEB . " AS web,
				$p." . PartnerTableMap::COL_PHONE . " AS phone,
				$p." . PartnerTableMap::COL_EMAIL . " AS email,
				$p." . PartnerTableMap::COL_STATE . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$p." . PartnerTableMap::COL_STATUS . " AS status_id,
				$s." . StatusPartnerTableMap::COL_STATUS . " AS status_label,
				$s." . StatusPartnerTableMap::COL_COLOR . " AS status_color
			FROM $p
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $p." . PartnerTableMap::COL_STATE . "
			LEFT JOIN $s ON $s." . StatusPartnerTableMap::COL_ID . " = $p." . PartnerTableMap::COL_STATUS . "
			WHERE $whereSql
			ORDER BY $order
		";

		return array_map(
			fn (Row $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'web' => (string) ($row->web ?? ''),
				'websiteUrl' => $this->formatWebsiteUrl((string) ($row->web ?? '')),
				'phone' => (string) ($row->phone ?? ''),
				'email' => (string) ($row->email ?? ''),
				'countryId' => (int) ($row->country_id ?? 0),
				'countryImage' => (string) ($row->country_image ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$this->database->query($sql, ...$params)->fetchAll(),
		);
	}

	/**
	 * @return list<array{id:int,name:string}>
	 */
	public function findCountryOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{CountryTableMap::COL_ID},
				'name' => (string) ($row->{CountryTableMap::COL_NAME} ?? ''),
			],
			array_values($this->database->table(CountryTableMap::TABLE_NAME)->order(CountryTableMap::COL_NAME . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return list<array{id:int,status:string}>
	 */
	public function findStatusOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{StatusPartnerTableMap::COL_ID},
				'status' => (string) ($row->{StatusPartnerTableMap::COL_STATUS} ?? ''),
			],
			array_values($this->database->table(StatusPartnerTableMap::TABLE_NAME)->order(StatusPartnerTableMap::COL_STATUS . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function findCountrySelectOptions(): array
	{
		return $this->findSelectOptions(CountryTableMap::TABLE_NAME, CountryTableMap::COL_ID, CountryTableMap::COL_NAME);
	}

	/**
	 * @return array<int, string>
	 */
	public function findStatusSelectOptions(): array
	{
		return $this->findSelectOptions(StatusPartnerTableMap::TABLE_NAME, StatusPartnerTableMap::COL_ID, StatusPartnerTableMap::COL_STATUS);
	}

	public function updateFromForm(PartnerUpdateForm $form): void
	{
		$this->update($form->id, [
			PartnerTableMap::COL_NAME => $form->name,
			PartnerTableMap::COL_STREET => $form->street,
			PartnerTableMap::COL_STREET_NUMBER => $form->streetNumber,
			PartnerTableMap::COL_PSC => $form->psc,
			PartnerTableMap::COL_CITY => $form->city,
			PartnerTableMap::COL_STATE => $form->state,
			PartnerTableMap::COL_DATE_START => $form->dateStart?->format('Y-m-d'),
			PartnerTableMap::COL_PERSON_SURNAME => $form->personSurname,
			PartnerTableMap::COL_PERSON_NAME => $form->personName,
			PartnerTableMap::COL_ICO => $form->ico,
			PartnerTableMap::COL_IC_DPH => $form->icDph,
			PartnerTableMap::COL_WEB => $form->web,
			PartnerTableMap::COL_PHONE => $form->phone,
			PartnerTableMap::COL_EMAIL => $form->email,
			PartnerTableMap::COL_STATUS => $form->status,
			PartnerTableMap::COL_NOTICE => $form->notice,
		]);
	}

	/**
	 * @return list<array{id:int,name:string,surname:string,countryId:int,countryImage:string,statusId:int,statusLabel:string,statusColor:string}>
	 */
	public function findFamiliesForPartner(int $partnerId): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$s = StatusFamilyTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$f." . FamilyTableMap::COL_STATE . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$f." . FamilyTableMap::COL_STATUS . " AS status_id,
				$s." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$s." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			LEFT JOIN $s ON $s." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $f." . FamilyTableMap::COL_PARTNER_ID . " = ?
			ORDER BY $f." . FamilyTableMap::COL_NAME . " ASC
		";

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'countryId' => (int) ($row->country_id ?? 0),
				'countryImage' => (string) ($row->country_image ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$this->database->query($sql, $partnerId)->fetchAll(),
		);
	}

	/**
	 * @return list<array{id:int,title:string,count:int}>
	 */
	public function getActiveFamilyCountsForOffcanvas(): array
	{
		$p = PartnerTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$p." . PartnerTableMap::COL_ID . " AS id,
				$p." . PartnerTableMap::COL_NAME . " AS title,
				COUNT($f." . FamilyTableMap::COL_ID . ") AS family_count
			FROM $p
			LEFT JOIN $f ON $f." . FamilyTableMap::COL_PARTNER_ID . " = $p." . PartnerTableMap::COL_ID . "
				AND $f." . FamilyTableMap::COL_STATUS . " = ?
			GROUP BY $p." . PartnerTableMap::COL_ID . ", $p." . PartnerTableMap::COL_NAME . "
			ORDER BY $p." . PartnerTableMap::COL_ID . " DESC
		";

		$rows = $this->database->query($sql, self::ACTIVE_FAMILY_STATUS)->fetchAll();

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->title ?? ''),
				'count' => (int) $row->family_count,
			],
			$rows,
		);
	}

	/**
	 * @return array<int, string>
	 */
	private function findSelectOptions(string $table, string $idColumn, string $labelColumn): array
	{
		$options = [0 => '---'];
		foreach ($this->database->table($table)->order($labelColumn . ' ASC') as $row) {
			$options[(int) $row->{$idColumn}] = (string) ($row->{$labelColumn} ?? '');
		}

		return $options;
	}

	private function formatWebsiteUrl(string $url): string
	{
		$url = trim($url);
		if ($url === '') {
			return '';
		}

		return str_starts_with($url, 'http') ? $url : 'http://' . $url;
	}
}
