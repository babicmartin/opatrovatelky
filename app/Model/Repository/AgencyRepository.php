<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Form\DTO\Admin\Agency\AgencyUpdate\AgencyUpdateForm;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\StatusBabysitterTableMap;
use App\Model\Table\StatusFamilyTableMap;
use App\Model\Table\StatusPartnerTableMap;
use App\Model\Table\TurnusTableMap;
use Nette\Database\Row;

class AgencyRepository extends BaseRepository
{
	private const int MAX_TURNUS_STATUS = 30;

	protected function getTableName(): string
	{
		return AgencyTableMap::TABLE_NAME;
	}

	public function createEmptyAgency(): int
	{
		$row = $this->insert([]);

		if (!is_object($row) || !isset($row->{AgencyTableMap::COL_ID})) {
			throw new \RuntimeException('Agentúru sa nepodarilo vytvoriť.');
		}

		return (int) $row->{AgencyTableMap::COL_ID};
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findUpdateRow(int $id): ?array
	{
		$row = $this->findAll()
			->where(AgencyTableMap::COL_ID, $id)
			->where(AgencyTableMap::COL_ACTIVE, 1)
			->fetch();

		if ($row === null) {
			return null;
		}

		return [
			'id' => (int) $row->{AgencyTableMap::COL_ID},
			'name' => (string) ($row->{AgencyTableMap::COL_NAME} ?? ''),
			'street' => (string) ($row->{AgencyTableMap::COL_STREET} ?? ''),
			'streetNumber' => (string) ($row->{AgencyTableMap::COL_STREET_NUMBER} ?? ''),
			'psc' => (string) ($row->{AgencyTableMap::COL_PSC} ?? ''),
			'city' => (string) ($row->{AgencyTableMap::COL_CITY} ?? ''),
			'state' => (int) ($row->{AgencyTableMap::COL_STATE} ?? 0),
			'dateStart' => $this->dateService->tryCreateFromDb((string) ($row->{AgencyTableMap::COL_DATE_START} ?? '')),
			'personSurname' => (string) ($row->{AgencyTableMap::COL_PERSON_SURNAME} ?? ''),
			'personName' => (string) ($row->{AgencyTableMap::COL_PERSON_NAME} ?? ''),
			'ico' => (string) ($row->{AgencyTableMap::COL_ICO} ?? ''),
			'icDph' => (string) ($row->{AgencyTableMap::COL_IC_DPH} ?? ''),
			'web' => (string) ($row->{AgencyTableMap::COL_WEB} ?? ''),
			'phone' => (string) ($row->{AgencyTableMap::COL_PHONE} ?? ''),
			'email' => (string) ($row->{AgencyTableMap::COL_EMAIL} ?? ''),
			'status' => (int) ($row->{AgencyTableMap::COL_STATUS} ?? 0),
			'notice' => (string) ($row->{AgencyTableMap::COL_NOTICE} ?? ''),
		];
	}

	/**
	 * @return list<array{id:int,name:string,websiteUrl:string,web:string,phone:string,email:string,emailLabel:string,countryId:int,countryImage:string,statusId:int,statusLabel:string,statusColor:string}>
	 */
	public function findAgencyRows(?int $countryId, ?int $statusId): array
	{
		$a = AgencyTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$s = StatusPartnerTableMap::TABLE_NAME;

		$where = ["$a." . AgencyTableMap::COL_ACTIVE . ' = ?'];
		$params = [1];
		$order = "$a." . AgencyTableMap::COL_ID . ' DESC';

		if ($countryId !== null) {
			$where[] = "$a." . AgencyTableMap::COL_STATE . ' = ?';
			$params[] = $countryId;
			$order = "$a." . AgencyTableMap::COL_NAME . ' ASC';
		}

		if ($statusId !== null) {
			$where[] = "$a." . AgencyTableMap::COL_STATUS . ' = ?';
			$params[] = $statusId;
		}

		$whereSql = implode(' AND ', $where);
		$sql = "
			SELECT
				$a." . AgencyTableMap::COL_ID . " AS id,
				$a." . AgencyTableMap::COL_NAME . " AS name,
				$a." . AgencyTableMap::COL_WEB . " AS web,
				$a." . AgencyTableMap::COL_PHONE . " AS phone,
				$a." . AgencyTableMap::COL_EMAIL . " AS email,
				$a." . AgencyTableMap::COL_STATE . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$a." . AgencyTableMap::COL_STATUS . " AS status_id,
				$s." . StatusPartnerTableMap::COL_STATUS . " AS status_label,
				$s." . StatusPartnerTableMap::COL_COLOR . " AS status_color
			FROM $a
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $a." . AgencyTableMap::COL_STATE . "
			LEFT JOIN $s ON $s." . StatusPartnerTableMap::COL_ID . " = $a." . AgencyTableMap::COL_STATUS . "
			WHERE $whereSql
			ORDER BY $order
		";

		return array_map(
			function (Row $row): array {
				$email = (string) ($row->email ?? '');

				return [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'web' => (string) ($row->web ?? ''),
				'websiteUrl' => $this->formatWebsiteUrl((string) ($row->web ?? '')),
				'phone' => (string) ($row->phone ?? ''),
				'email' => $email,
				'emailLabel' => strip_tags($email),
				'countryId' => (int) ($row->country_id ?? 0),
				'countryImage' => (string) ($row->country_image ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				];
			},
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

	public function updateFromForm(AgencyUpdateForm $form): void
	{
		$this->update($form->id, [
			AgencyTableMap::COL_NAME => $form->name,
			AgencyTableMap::COL_STREET => $form->street,
			AgencyTableMap::COL_STREET_NUMBER => $form->streetNumber,
			AgencyTableMap::COL_PSC => $form->psc,
			AgencyTableMap::COL_CITY => $form->city,
			AgencyTableMap::COL_STATE => $form->state,
			AgencyTableMap::COL_DATE_START => $form->dateStart?->format('Y-m-d'),
			AgencyTableMap::COL_PERSON_SURNAME => $form->personSurname,
			AgencyTableMap::COL_PERSON_NAME => $form->personName,
			AgencyTableMap::COL_ICO => $form->ico,
			AgencyTableMap::COL_IC_DPH => $form->icDph,
			AgencyTableMap::COL_WEB => $form->web,
			AgencyTableMap::COL_PHONE => $form->phone,
			AgencyTableMap::COL_EMAIL => $form->email,
			AgencyTableMap::COL_STATUS => $form->status,
			AgencyTableMap::COL_NOTICE => $form->notice,
		]);
	}

	/**
	 * @return list<array{id:int,name:string,surname:string,countryId:int,countryImage:string,statusId:int,statusLabel:string,statusColor:string}>
	 */
	public function findBabysittersForAgency(int $agencyId): array
	{
		$o = OpatrovatelkaTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$s = StatusBabysitterTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$o." . OpatrovatelkaTableMap::COL_ID . " AS id,
				$o." . OpatrovatelkaTableMap::COL_NAME . " AS name,
				$o." . OpatrovatelkaTableMap::COL_SURNAME . " AS surname,
				$o." . OpatrovatelkaTableMap::COL_COUNTRY . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$o." . OpatrovatelkaTableMap::COL_STATUS . " AS status_id,
				$s." . StatusBabysitterTableMap::COL_STATUS . " AS status_label,
				$s." . StatusBabysitterTableMap::COL_COLOR . " AS status_color
			FROM $o
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_COUNTRY . "
			LEFT JOIN $s ON $s." . StatusBabysitterTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_STATUS . "
			WHERE $o." . OpatrovatelkaTableMap::COL_AGENCY_ID . " = ?
			ORDER BY $o." . OpatrovatelkaTableMap::COL_NAME . " ASC
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
			$this->database->query($sql, $agencyId)->fetchAll(),
		);
	}

	/**
	 * @return list<array{id:int,name:string,surname:string,countryId:int,countryImage:string,statusId:int,statusLabel:string,statusColor:string}>
	 */
	public function findFamiliesForAgency(int $agencyId): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$t = TurnusTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$s = StatusFamilyTableMap::TABLE_NAME;

		$sql = "
			SELECT DISTINCT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$f." . FamilyTableMap::COL_STATE . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$f." . FamilyTableMap::COL_STATUS . " AS status_id,
				$s." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$s." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			INNER JOIN $t ON $t." . TurnusTableMap::COL_FAMILY_ID . " = $f." . FamilyTableMap::COL_ID . "
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			LEFT JOIN $s ON $s." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $t." . TurnusTableMap::COL_AGENCY_ID . " = ?
				AND $t." . TurnusTableMap::COL_STATUS . " < ?
			ORDER BY $f." . FamilyTableMap::COL_SURNAME . " ASC
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
			$this->database->query($sql, $agencyId, self::MAX_TURNUS_STATUS)->fetchAll(),
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
