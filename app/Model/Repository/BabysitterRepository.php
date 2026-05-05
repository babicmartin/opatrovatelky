<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Form\DTO\Admin\Babysitter\BabysitterAddress\BabysitterAddressForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterEducation\BabysitterEducationForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterMain\BabysitterMainForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterPdf\BabysitterPdfForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterProfile\BabysitterProfileForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterWorkProfile\BabysitterWorkProfileForm;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\BabysitterDiseaseTableMap;
use App\Model\Table\BabysitterPositionPreferenceTableMap;
use App\Model\Table\BabysitterQualificationTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PohlavieTableMap;
use App\Model\Table\SelectAccommodationTypeTableMap;
use App\Model\Table\SelectDrivingLicenceTableMap;
use App\Model\Table\SelectDiseaseTableMap;
use App\Model\Table\SelectEducationTableMap;
use App\Model\Table\SelectLanguageTableMap;
use App\Model\Table\SelectSmokerTableMap;
use App\Model\Table\SelectWorkPositionTableMap;
use App\Model\Table\SelectWorkRoleTableMap;
use App\Model\Table\SelectWorkingStatusTableMap;
use App\Model\Table\SelectYesNoTableMap;
use App\Model\Table\StatusBabysitterTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use Nette\Database\Row;

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
	 * @return array<string, mixed>|null
	 */
	public function findUpdateRow(int $id): ?array
	{
		$row = $this->findAll()
			->where(OpatrovatelkaTableMap::COL_ID, $id)
			->where(OpatrovatelkaTableMap::COL_ACTIVE, 1)
			->fetch();

		if ($row === null) {
			return null;
		}

		return [
			'id' => (int) $row->{OpatrovatelkaTableMap::COL_ID},
			'clientNumber' => (string) ($row->{OpatrovatelkaTableMap::COL_CLIENT_NUMBER} ?? ''),
			'name' => (string) ($row->{OpatrovatelkaTableMap::COL_NAME} ?? ''),
			'surname' => (string) ($row->{OpatrovatelkaTableMap::COL_SURNAME} ?? ''),
			'pohlavie' => (int) ($row->{OpatrovatelkaTableMap::COL_POHLAVIE} ?? 0),
			'country' => (int) ($row->{OpatrovatelkaTableMap::COL_COUNTRY} ?? 0),
			'status' => (int) ($row->{OpatrovatelkaTableMap::COL_STATUS} ?? 0),
			'birthday' => self::formatDate((string) ($row->{OpatrovatelkaTableMap::COL_BIRTHDAY} ?? '')),
			'image' => (string) ($row->{OpatrovatelkaTableMap::COL_IMAGE} ?? ''),
			'smoker' => (int) ($row->{OpatrovatelkaTableMap::COL_SMOKER} ?? 0),
			'height' => (string) ($row->{OpatrovatelkaTableMap::COL_HEIGHT} ?? ''),
			'weight' => (string) ($row->{OpatrovatelkaTableMap::COL_WEIGHT} ?? ''),
			'phone' => (string) ($row->{OpatrovatelkaTableMap::COL_PHONE} ?? ''),
			'phone2' => (string) ($row->{OpatrovatelkaTableMap::COL_PHONE2} ?? ''),
			'email' => (string) ($row->{OpatrovatelkaTableMap::COL_EMAIL} ?? ''),
			'drivingLicence' => (int) ($row->{OpatrovatelkaTableMap::COL_DRIVING_LICENCE} ?? 0),
			'city' => (string) ($row->{OpatrovatelkaTableMap::COL_CITY} ?? ''),
			'street' => (string) ($row->{OpatrovatelkaTableMap::COL_STREET} ?? ''),
			'postalCode' => (string) ($row->{OpatrovatelkaTableMap::COL_POSTAL_CODE} ?? ''),
			'workingStatus' => (int) ($row->{OpatrovatelkaTableMap::COL_WORKING_STATUS} ?? 0),
			'agencyId' => (int) ($row->{OpatrovatelkaTableMap::COL_AGENCY_ID} ?? 0),
			'contactPersonName' => (string) ($row->{OpatrovatelkaTableMap::COL_CONTACT_PERSON_NAME} ?? ''),
			'contactPersonPhone' => (string) ($row->{OpatrovatelkaTableMap::COL_CONTACT_PERSON_PHONE} ?? ''),
			'requirements' => (string) ($row->{OpatrovatelkaTableMap::COL_REQUIREMENTS} ?? ''),
			'notice' => (string) ($row->{OpatrovatelkaTableMap::COL_NOTICE} ?? ''),
			'blacklist' => (int) ($row->{OpatrovatelkaTableMap::COL_BLACKLIST} ?? 0),
			'firstContactUserId' => (int) ($row->{OpatrovatelkaTableMap::COL_FIRST_CONTACT_USER_ID} ?? 0),
			'about' => (string) ($row->{OpatrovatelkaTableMap::COL_ABOUT} ?? ''),
			'allergy' => (int) ($row->{OpatrovatelkaTableMap::COL_ALLERGY} ?? 0),
			'allergyDetail' => (string) ($row->{OpatrovatelkaTableMap::COL_ALLERGY_DETAIL} ?? ''),
			'education' => (int) ($row->{OpatrovatelkaTableMap::COL_EDUCATION} ?? 0),
			'course' => (int) ($row->{OpatrovatelkaTableMap::COL_COURSE} ?? 0),
			'courseDetail' => (string) ($row->{OpatrovatelkaTableMap::COL_COURSE_DETAIL} ?? ''),
			'readyDrive' => (int) ($row->{OpatrovatelkaTableMap::COL_READY_DRIVE} ?? 0),
			'howLongWork' => (string) ($row->{OpatrovatelkaTableMap::COL_HOW_LONG_WORK} ?? ''),
			'howLongWorkGerman' => (string) ($row->{OpatrovatelkaTableMap::COL_HOW_LONG_WORK_GERMAN} ?? ''),
			'languageSkills' => (int) ($row->{OpatrovatelkaTableMap::COL_LANGUAGE_SKILLS} ?? 0),
			'languageSkillsOther' => (string) ($row->{OpatrovatelkaTableMap::COL_LANGUAGE_SKILLS_OTHER} ?? ''),
			'dailyCare' => (int) ($row->{OpatrovatelkaTableMap::COL_DAILY_CARE} ?? 0),
			'hourlyCare' => (int) ($row->{OpatrovatelkaTableMap::COL_HOURLY_CARE} ?? 0),
			'timeScale' => (string) ($row->{OpatrovatelkaTableMap::COL_TIME_SCALE} ?? ''),
			'workPlace' => (string) ($row->{OpatrovatelkaTableMap::COL_WORK_PLACE} ?? ''),
			'workDescription' => (string) ($row->{OpatrovatelkaTableMap::COL_WORK_DESCRIPTION} ?? ''),
			'generalActivities' => (string) ($row->{OpatrovatelkaTableMap::COL_GENERAL_ACTIVITIES} ?? ''),
			'ratingAgency' => (string) ($row->{OpatrovatelkaTableMap::COL_RATING_AGENCY} ?? ''),
			'profilShowContact' => (int) ($row->{OpatrovatelkaTableMap::COL_PROFIL_SHOW_CONTACT} ?? 0),
			'type' => (int) ($row->{OpatrovatelkaTableMap::COL_TYPE} ?? self::TYPE_BABYSITTER),
			'jobPositionInterest' => (string) ($row->{OpatrovatelkaTableMap::COL_JOB_POSITION_INTEREST} ?? ''),
			'workShoes' => (int) ($row->{OpatrovatelkaTableMap::COL_WORK_SHOES} ?? 0),
			'shoeSize' => (string) ($row->{OpatrovatelkaTableMap::COL_SHOE_SIZE} ?? ''),
			'germanTaxId' => (string) ($row->{OpatrovatelkaTableMap::COL_GERMAN_TAX_ID} ?? ''),
			'accommodationType' => (int) ($row->{OpatrovatelkaTableMap::COL_ACCOMMODATION_TYPE} ?? 0),
		];
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

	/**
	 * @return array<int, string>
	 */
	public function findTypeSelectOptions(): array
	{
		return $this->findSelectOptions(SelectWorkRoleTableMap::TABLE_NAME, SelectWorkRoleTableMap::COL_ID, SelectWorkRoleTableMap::COL_SLOVAK);
	}

	/**
	 * @return array<int, string>
	 */
	public function findAgencySelectOptions(): array
	{
		return $this->findSelectOptions(AgencyTableMap::TABLE_NAME, AgencyTableMap::COL_ID, AgencyTableMap::COL_NAME);
	}

	/**
	 * @return array<int, string>
	 */
	public function findWorkingStatusSelectOptions(): array
	{
		return $this->findSelectOptions(SelectWorkingStatusTableMap::TABLE_NAME, SelectWorkingStatusTableMap::COL_ID, SelectWorkingStatusTableMap::COL_SLOVAK);
	}

	/**
	 * @return array<int, string>
	 */
	public function findStatusSelectOptions(): array
	{
		return $this->findSelectOptions(StatusBabysitterTableMap::TABLE_NAME, StatusBabysitterTableMap::COL_ID, StatusBabysitterTableMap::COL_STATUS);
	}

	/**
	 * @return array<int, string>
	 */
	public function findUserSelectOptions(): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table(UserTableMap::TABLE_NAME)
			->where(UserTableMap::COL_ACTIVE, 1)
			->order(UserTableMap::COL_SECOND_NAME . ' ASC, ' . UserTableMap::COL_NAME . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{UserTableMap::COL_ID}] = trim((string) $row->{UserTableMap::COL_SECOND_NAME} . ' ' . (string) $row->{UserTableMap::COL_NAME});
		}

		return $options;
	}

	/**
	 * @return array<int, string>
	 */
	public function findBlacklistSelectOptions(): array
	{
		return $this->findYesNoSelectOptions();
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
	public function findGenderSelectOptions(): array
	{
		return $this->findSelectOptions(PohlavieTableMap::TABLE_NAME, PohlavieTableMap::COL_ID, PohlavieTableMap::COL_POHLAVIE);
	}

	/**
	 * @return array<int, string>
	 */
	public function findEducationSelectOptions(): array
	{
		return $this->findSelectOptions(SelectEducationTableMap::TABLE_NAME, SelectEducationTableMap::COL_ID, SelectEducationTableMap::COL_SLOVAK);
	}

	/**
	 * @return array<int, string>
	 */
	public function findDrivingLicenceSelectOptions(): array
	{
		return $this->findSelectOptions(SelectDrivingLicenceTableMap::TABLE_NAME, SelectDrivingLicenceTableMap::COL_ID, SelectDrivingLicenceTableMap::COL_SLOVAK);
	}

	/**
	 * @return array<int, string>
	 */
	public function findYesNoSelectOptions(): array
	{
		return $this->findSelectOptions(SelectYesNoTableMap::TABLE_NAME, SelectYesNoTableMap::COL_ID, SelectYesNoTableMap::COL_STATUS);
	}

	/**
	 * @return array<int, string>
	 */
	public function findLanguageSelectOptions(): array
	{
		return $this->findSelectOptions(SelectLanguageTableMap::TABLE_NAME, SelectLanguageTableMap::COL_ID, SelectLanguageTableMap::COL_SLOVAK, SelectLanguageTableMap::COL_STARS);
	}

	/**
	 * @return array<int, string>
	 */
	public function findSmokerSelectOptions(): array
	{
		return $this->findSelectOptions(SelectSmokerTableMap::TABLE_NAME, SelectSmokerTableMap::COL_ID, SelectSmokerTableMap::COL_SLOVAK);
	}

	/**
	 * @return array<int, string>
	 */
	public function findAccommodationSelectOptions(): array
	{
		return $this->findSelectOptions(SelectAccommodationTypeTableMap::TABLE_NAME, SelectAccommodationTypeTableMap::COL_ID, SelectAccommodationTypeTableMap::COL_ACCOMMODATION_TYPE);
	}

	/**
	 * @return array<int, string>
	 */
	public function findWorkPositionSelectOptions(): array
	{
		return $this->findSelectOptions(SelectWorkPositionTableMap::TABLE_NAME, SelectWorkPositionTableMap::COL_ID, SelectWorkPositionTableMap::COL_POSITION);
	}

	/**
	 * @return array<int, string>
	 */
	public function findDiseaseSelectOptions(): array
	{
		return $this->findSelectOptions(SelectDiseaseTableMap::TABLE_NAME, SelectDiseaseTableMap::COL_ID, SelectDiseaseTableMap::COL_SLOVAK);
	}

	/**
	 * @return list<int>
	 */
	public function findDiseaseIds(int $babysitterId): array
	{
		return $this->findJunctionIds(BabysitterDiseaseTableMap::TABLE_NAME, BabysitterDiseaseTableMap::COL_BABYSITTER_ID, BabysitterDiseaseTableMap::COL_DISEASE_ID, $babysitterId);
	}

	/**
	 * @return list<int>
	 */
	public function findQualificationIds(int $babysitterId): array
	{
		return $this->findJunctionIds(BabysitterQualificationTableMap::TABLE_NAME, BabysitterQualificationTableMap::COL_BABYSITTER_ID, BabysitterQualificationTableMap::COL_WORK_POSITION_ID, $babysitterId);
	}

	/**
	 * @return list<int>
	 */
	public function findPreferenceIds(int $babysitterId): array
	{
		return $this->findJunctionIds(BabysitterPositionPreferenceTableMap::TABLE_NAME, BabysitterPositionPreferenceTableMap::COL_BABYSITTER_ID, BabysitterPositionPreferenceTableMap::COL_WORK_POSITION_ID, $babysitterId);
	}

	public function updateMainFromForm(BabysitterMainForm $form): void
	{
		$this->update($form->id, [
			OpatrovatelkaTableMap::COL_TYPE => $form->type,
			OpatrovatelkaTableMap::COL_AGENCY_ID => $form->agencyId,
			OpatrovatelkaTableMap::COL_WORKING_STATUS => $form->workingStatus,
			OpatrovatelkaTableMap::COL_STATUS => $form->status,
			OpatrovatelkaTableMap::COL_FIRST_CONTACT_USER_ID => $form->firstContactUserId,
			OpatrovatelkaTableMap::COL_BLACKLIST => $form->blacklist,
			OpatrovatelkaTableMap::COL_NOTICE => $form->notice,
		]);
	}

	public function updateAddressFromForm(BabysitterAddressForm $form): void
	{
		$this->update($form->id, [
			OpatrovatelkaTableMap::COL_NAME => $form->name,
			OpatrovatelkaTableMap::COL_SURNAME => $form->surname,
			OpatrovatelkaTableMap::COL_BIRTHDAY => $this->normalizeDate($form->birthday),
			OpatrovatelkaTableMap::COL_POHLAVIE => $form->pohlavie,
			OpatrovatelkaTableMap::COL_COUNTRY => $form->country,
			OpatrovatelkaTableMap::COL_CITY => $form->city,
			OpatrovatelkaTableMap::COL_STREET => $form->street,
			OpatrovatelkaTableMap::COL_POSTAL_CODE => $form->postalCode,
			OpatrovatelkaTableMap::COL_PHONE => $form->phone,
			OpatrovatelkaTableMap::COL_PHONE2 => $form->phone2,
			OpatrovatelkaTableMap::COL_EMAIL => $form->email,
			OpatrovatelkaTableMap::COL_HEIGHT => $form->height,
			OpatrovatelkaTableMap::COL_WEIGHT => $form->weight,
			OpatrovatelkaTableMap::COL_ABOUT => $form->about,
			OpatrovatelkaTableMap::COL_REQUIREMENTS => $form->requirements,
			OpatrovatelkaTableMap::COL_CONTACT_PERSON_NAME => $form->contactPersonName,
			OpatrovatelkaTableMap::COL_CONTACT_PERSON_PHONE => $form->contactPersonPhone,
		]);
	}

	public function updateEducationFromForm(BabysitterEducationForm $form): void
	{
		$this->update($form->id, [
			OpatrovatelkaTableMap::COL_EDUCATION => $form->education,
			OpatrovatelkaTableMap::COL_DRIVING_LICENCE => $form->drivingLicence,
			OpatrovatelkaTableMap::COL_READY_DRIVE => $form->readyDrive,
			OpatrovatelkaTableMap::COL_LANGUAGE_SKILLS => $form->languageSkills,
			OpatrovatelkaTableMap::COL_LANGUAGE_SKILLS_OTHER => $form->languageSkillsOther,
			OpatrovatelkaTableMap::COL_COURSE => $form->course,
			OpatrovatelkaTableMap::COL_COURSE_DETAIL => $form->courseDetail,
		]);
	}

	public function updateProfileFromForm(BabysitterProfileForm $form): void
	{
		$this->update($form->id, [
			OpatrovatelkaTableMap::COL_SMOKER => $form->smoker,
			OpatrovatelkaTableMap::COL_ALLERGY => $form->allergy,
			OpatrovatelkaTableMap::COL_ALLERGY_DETAIL => $form->allergyDetail,
			OpatrovatelkaTableMap::COL_HOW_LONG_WORK => $form->howLongWork,
			OpatrovatelkaTableMap::COL_HOW_LONG_WORK_GERMAN => $form->howLongWorkGerman,
			OpatrovatelkaTableMap::COL_DAILY_CARE => $form->dailyCare,
			OpatrovatelkaTableMap::COL_HOURLY_CARE => $form->hourlyCare,
			OpatrovatelkaTableMap::COL_ACCOMMODATION_TYPE => $form->accommodationType,
			OpatrovatelkaTableMap::COL_TIME_SCALE => $form->timeScale,
			OpatrovatelkaTableMap::COL_WORK_PLACE => $form->workPlace,
			OpatrovatelkaTableMap::COL_JOB_POSITION_INTEREST => $form->jobPositionInterest,
			OpatrovatelkaTableMap::COL_WORK_DESCRIPTION => $form->workDescription,
			OpatrovatelkaTableMap::COL_GENERAL_ACTIVITIES => $form->generalActivities,
			OpatrovatelkaTableMap::COL_RATING_AGENCY => $form->ratingAgency,
			OpatrovatelkaTableMap::COL_WORK_SHOES => $form->workShoes,
			OpatrovatelkaTableMap::COL_SHOE_SIZE => $form->shoeSize,
			OpatrovatelkaTableMap::COL_GERMAN_TAX_ID => $form->germanTaxId,
		]);
		$this->replaceJunctionIds(BabysitterDiseaseTableMap::TABLE_NAME, BabysitterDiseaseTableMap::COL_BABYSITTER_ID, BabysitterDiseaseTableMap::COL_DISEASE_ID, $form->id, $form->diseaseIds);
	}

	public function updatePdfFromForm(BabysitterPdfForm $form): void
	{
		$this->update($form->id, [
			OpatrovatelkaTableMap::COL_PROFIL_SHOW_CONTACT => $form->profilShowContact,
		]);
	}

	public function updateImage(int $id, string $image): void
	{
		$this->update($id, [
			OpatrovatelkaTableMap::COL_IMAGE => $image,
		]);
	}

	public function updateWorkProfileFromForm(BabysitterWorkProfileForm $form): void
	{
		$this->replaceJunctionIds(BabysitterQualificationTableMap::TABLE_NAME, BabysitterQualificationTableMap::COL_BABYSITTER_ID, BabysitterQualificationTableMap::COL_WORK_POSITION_ID, $form->id, $form->qualificationIds);
		$this->replaceJunctionIds(BabysitterPositionPreferenceTableMap::TABLE_NAME, BabysitterPositionPreferenceTableMap::COL_BABYSITTER_ID, BabysitterPositionPreferenceTableMap::COL_WORK_POSITION_ID, $form->id, $form->preferenceIds);
	}

	public function createTurnusForBabysitter(int $babysitterId, int $userId): int
	{
		$babysitter = $this->getItem($babysitterId);
		if ($babysitter === null) {
			throw new \RuntimeException('Babysitter row was not found.');
		}

		$row = $this->database->table(TurnusTableMap::TABLE_NAME)->insert([
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_AGENCY_ID => (int) ($babysitter->{OpatrovatelkaTableMap::COL_AGENCY_ID} ?? 0),
			TurnusTableMap::COL_DATE_CREATED => date('Y-m-d'),
			TurnusTableMap::COL_USER_CREATED => $userId,
		]);

		if (!$row instanceof \Nette\Database\Table\ActiveRow) {
			throw new \RuntimeException('Turnus row was not created.');
		}

		return (int) $row->{TurnusTableMap::COL_ID};
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findTurnusRowsForBabysitter(int $babysitterId): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$st = StatusTurnusTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$wp = SelectWorkPositionTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_DATE_FROM . " AS date_from,
				$t." . TurnusTableMap::COL_DATE_TO . " AS date_to,
				$t." . TurnusTableMap::COL_FAMILY_ID . " AS family_id,
				$t." . TurnusTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$st." . StatusTurnusTableMap::COL_STATUS . " AS status,
				$st." . StatusTurnusTableMap::COL_COLOR . " AS status_color,
				$f." . FamilyTableMap::COL_NAME . " AS family_name,
				$f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				$f." . FamilyTableMap::COL_DE_PROJECT_NUMBER . " AS family_project_number,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$wp." . SelectWorkPositionTableMap::COL_POSITION . " AS work_position
			FROM $t
			LEFT JOIN $st ON $st." . StatusTurnusTableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS . "
			LEFT JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $wp ON $wp." . SelectWorkPositionTableMap::COL_ID . " = $t." . TurnusTableMap::COL_WORK_POSITION_ID . "
			WHERE $t." . TurnusTableMap::COL_BABYSITTER_ID . " = ?
				AND $t." . TurnusTableMap::COL_DELETED . " = 0
			ORDER BY
				CASE WHEN $t." . TurnusTableMap::COL_DATE_FROM . " IS NULL OR $t." . TurnusTableMap::COL_DATE_FROM . " = '0000-00-00' THEN 0 ELSE 1 END ASC,
				$t." . TurnusTableMap::COL_DATE_FROM . " DESC,
				$t." . TurnusTableMap::COL_ID . " DESC
		";

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'familyId' => (int) ($row->family_id ?? 0),
				'babysitterId' => (int) ($row->babysitter_id ?? 0),
				'dateFrom' => self::formatDate((string) ($row->date_from ?? '')),
				'dateTo' => self::formatDate((string) ($row->date_to ?? '')),
				'status' => (string) ($row->status ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				'familyName' => trim((string) ($row->family_name ?? '') . ' ' . (string) ($row->family_surname ?? '')),
				'familyProjectNumber' => (string) ($row->family_project_number ?? ''),
				'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
				'workPosition' => (string) ($row->work_position ?? ''),
			],
			$this->database->query($sql, $babysitterId)->fetchAll(),
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

	/**
	 * @return array<int, string>
	 */
	private function findSelectOptions(string $table, string $idColumn, string $labelColumn, ?string $orderColumn = null): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table($table)->order(($orderColumn ?? $labelColumn) . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{$idColumn}] = (string) ($row->{$labelColumn} ?? '');
		}

		return $options;
	}

	/**
	 * @return list<int>
	 */
	private function findJunctionIds(string $table, string $ownerColumn, string $valueColumn, int $ownerId): array
	{
		$rows = $this->database->table($table)->where($ownerColumn, $ownerId)->fetchAll();

		return array_values(array_map(static fn ($row): int => (int) $row->{$valueColumn}, $rows));
	}

	/**
	 * @param list<int> $ids
	 */
	private function replaceJunctionIds(string $table, string $ownerColumn, string $valueColumn, int $ownerId, array $ids): void
	{
		$this->database->table($table)->where($ownerColumn, $ownerId)->delete();

		foreach (array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0))) as $id) {
			$this->database->table($table)->insert([
				$ownerColumn => $ownerId,
				$valueColumn => $id,
			]);
		}
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
