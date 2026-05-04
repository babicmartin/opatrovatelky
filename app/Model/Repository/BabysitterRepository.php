<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Table\AgencyTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PohlavieTableMap;
use App\Model\Table\SelectLanguageTableMap;
use App\Model\Table\SelectWorkingStatusTableMap;
use App\Model\Table\SelectYesNoTableMap;
use App\Model\Table\StatusBabysitterTableMap;

class BabysitterRepository extends BaseRepository
{
	private const int TYPE_BABYSITTER = 1;

	protected function getTableName(): string
	{
		return OpatrovatelkaTableMap::TABLE_NAME;
	}

	public function createEmptyBabysitter(): int
	{
		$row = $this->insert([
			OpatrovatelkaTableMap::COL_TYPE => self::TYPE_BABYSITTER,
			OpatrovatelkaTableMap::COL_ACTIVE => 1,
		]);

		if (!$row instanceof \Nette\Database\Table\ActiveRow) {
			throw new \RuntimeException('Babysitter row was not created.');
		}

		return (int) $row->{OpatrovatelkaTableMap::COL_ID};
	}

	/**
	 * @return list<array{id:int,clientNumber:string,name:string,surname:string,birthday:?string,age:?int,
	 *     pohlavieId:int,pohlavieLabel:string,countryId:int,countryImage:string,
	 *     agencyId:int,agencyName:string,statusId:int,statusLabel:string,statusColor:string,
	 *     image:string}>
	 */
	public function findBabysitterRows(
		int $page,
		int $itemsPerPage,
		?int $countryId,
		?int $languageSkillId,
		?int $workingStatusId,
		?int $genderId,
		?int $driverLicence,
		?int $smokerTypeId,
		?int $agencyId,
		?int $statusId,
		?string $firstLetter,
		int &$pageCount,
	): array
	{
		$o = OpatrovatelkaTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$a = AgencyTableMap::TABLE_NAME;
		$s = StatusBabysitterTableMap::TABLE_NAME;
		$g = PohlavieTableMap::TABLE_NAME;

		$where = [
			"$o." . OpatrovatelkaTableMap::COL_ACTIVE . ' = ?',
			"$o." . OpatrovatelkaTableMap::COL_TYPE . ' = ?',
		];
		$params = [1, self::TYPE_BABYSITTER];

		if ($countryId !== null) {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_COUNTRY . ' = ?';
			$params[] = $countryId;
		}
		if ($languageSkillId !== null) {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_LANGUAGE_SKILLS . ' = ?';
			$params[] = $languageSkillId;
		}
		if ($workingStatusId !== null) {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_WORKING_STATUS . ' = ?';
			$params[] = $workingStatusId;
		}
		if ($genderId !== null) {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_POHLAVIE . ' = ?';
			$params[] = $genderId;
		}
		if ($driverLicence !== null) {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_DRIVING_LICENCE . ' = ?';
			$params[] = $driverLicence;
		}
		if ($smokerTypeId !== null) {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_SMOKER . ' = ?';
			$params[] = $smokerTypeId;
		}
		if ($agencyId !== null) {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_AGENCY_ID . ' = ?';
			$params[] = $agencyId;
		}
		if ($statusId !== null) {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_STATUS . ' = ?';
			$params[] = $statusId;
		}
		if ($firstLetter !== null && $firstLetter !== '') {
			$where[] = "$o." . OpatrovatelkaTableMap::COL_SURNAME . ' LIKE ?';
			$params[] = $firstLetter . '%';
		}

		$whereSql = implode(' AND ', $where);
		$totalCount = (int) $this->database->query(
			"SELECT COUNT(*) FROM $o WHERE $whereSql",
			...$params,
		)->fetchField();
		$pageCount = max(1, (int) ceil($totalCount / max(1, $itemsPerPage)));
		$page = min(max(1, $page), $pageCount);
		$offset = ($page - 1) * max(1, $itemsPerPage);

		$sql = "
			SELECT
				$o." . OpatrovatelkaTableMap::COL_ID . " AS id,
				$o." . OpatrovatelkaTableMap::COL_CLIENT_NUMBER . " AS client_number,
				$o." . OpatrovatelkaTableMap::COL_NAME . " AS name,
				$o." . OpatrovatelkaTableMap::COL_SURNAME . " AS surname,
				$o." . OpatrovatelkaTableMap::COL_BIRTHDAY . " AS birthday,
				$o." . OpatrovatelkaTableMap::COL_IMAGE . " AS image,
				$o." . OpatrovatelkaTableMap::COL_POHLAVIE . " AS pohlavie_id,
				$g." . PohlavieTableMap::COL_POHLAVIE . " AS pohlavie_label,
				$o." . OpatrovatelkaTableMap::COL_COUNTRY . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$o." . OpatrovatelkaTableMap::COL_AGENCY_ID . " AS agency_id,
				$a." . AgencyTableMap::COL_NAME . " AS agency_name,
				$o." . OpatrovatelkaTableMap::COL_STATUS . " AS status_id,
				$s." . StatusBabysitterTableMap::COL_STATUS . " AS status_label,
				$s." . StatusBabysitterTableMap::COL_COLOR . " AS status_color
			FROM $o
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_COUNTRY . "
			LEFT JOIN $a ON $a." . AgencyTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_AGENCY_ID . "
			LEFT JOIN $s ON $s." . StatusBabysitterTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_STATUS . "
			LEFT JOIN $g ON $g." . PohlavieTableMap::COL_ID . " = $o." . OpatrovatelkaTableMap::COL_POHLAVIE . "
			WHERE $whereSql
			ORDER BY $o." . OpatrovatelkaTableMap::COL_ID . " DESC
			LIMIT ? OFFSET ?
		";
		$queryParams = [...$params, max(1, $itemsPerPage), $offset];
		$rows = $this->database->query($sql, ...$queryParams)->fetchAll();

		return array_map(
			static function ($row): array {
				$birthday = $row->birthday !== null ? (string) $row->birthday : null;

				return [
					'id' => (int) $row->id,
					'clientNumber' => (string) ($row->client_number ?? ''),
					'name' => (string) ($row->name ?? ''),
					'surname' => (string) ($row->surname ?? ''),
					'birthday' => $birthday,
					'age' => self::computeAge($birthday),
					'pohlavieId' => (int) ($row->pohlavie_id ?? 0),
					'pohlavieLabel' => (string) ($row->pohlavie_label ?? ''),
					'countryId' => (int) ($row->country_id ?? 0),
					'countryImage' => (string) ($row->country_image ?? ''),
					'agencyId' => (int) ($row->agency_id ?? 0),
					'agencyName' => (string) ($row->agency_name ?? ''),
					'statusId' => (int) ($row->status_id ?? 0),
					'statusLabel' => (string) ($row->status_label ?? ''),
					'statusColor' => (string) ($row->status_color ?? ''),
					'image' => (string) ($row->image ?? ''),
				];
			},
			$rows,
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
	 * @return list<array{id:int,label:string}>
	 */
	public function findLanguageOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{SelectLanguageTableMap::COL_ID},
				'label' => (string) ($row->{SelectLanguageTableMap::COL_SLOVAK} ?? ''),
			],
			array_values($this->database->table(SelectLanguageTableMap::TABLE_NAME)->order(SelectLanguageTableMap::COL_STARS . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return list<array{id:int,label:string}>
	 */
	public function findWorkingStatusOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{SelectWorkingStatusTableMap::COL_ID},
				'label' => (string) ($row->{SelectWorkingStatusTableMap::COL_SLOVAK} ?? ''),
			],
			array_values($this->database->table(SelectWorkingStatusTableMap::TABLE_NAME)->order(SelectWorkingStatusTableMap::COL_SLOVAK . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return list<array{id:int,label:string}>
	 */
	public function findGenderOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{PohlavieTableMap::COL_ID},
				'label' => (string) ($row->{PohlavieTableMap::COL_POHLAVIE} ?? ''),
			],
			array_values($this->database->table(PohlavieTableMap::TABLE_NAME)->order(PohlavieTableMap::COL_POHLAVIE . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return list<array{id:int,label:string}>
	 */
	public function findYesNoOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{SelectYesNoTableMap::COL_ID},
				'label' => (string) ($row->{SelectYesNoTableMap::COL_STATUS} ?? ''),
			],
			array_values($this->database->table(SelectYesNoTableMap::TABLE_NAME)->order(SelectYesNoTableMap::COL_STATUS . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return list<array{id:int,name:string}>
	 */
	public function findAgencyOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{AgencyTableMap::COL_ID},
				'name' => (string) ($row->{AgencyTableMap::COL_NAME} ?? ''),
			],
			array_values($this->database->table(AgencyTableMap::TABLE_NAME)->order(AgencyTableMap::COL_NAME . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return list<array{id:int,label:string,color:string}>
	 */
	public function findStatusOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{StatusBabysitterTableMap::COL_ID},
				'label' => (string) ($row->{StatusBabysitterTableMap::COL_STATUS} ?? ''),
				'color' => (string) ($row->{StatusBabysitterTableMap::COL_COLOR} ?? ''),
			],
			array_values($this->database->table(StatusBabysitterTableMap::TABLE_NAME)->order(StatusBabysitterTableMap::COL_STATUS . ' ASC')->fetchAll()),
		);
	}

	private static function computeAge(?string $birthday): ?int
	{
		if ($birthday === null || $birthday === '' || $birthday === '0000-00-00') {
			return null;
		}

		try {
			$birth = new \DateTimeImmutable($birthday);
		} catch (\Exception) {
			return null;
		}

		return (int) $birth->diff(new \DateTimeImmutable('today'))->format('%y');
	}
}
