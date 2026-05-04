<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Form\DTO\Admin\Family\FamilyAddress\FamilyAddressForm;
use App\Model\Form\DTO\Admin\Family\FamilyInfo\FamilyInfoForm;
use App\Model\Form\DTO\Admin\Family\FamilyShortInfo\FamilyShortInfoForm;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\SelectFamilyProjectTableMap;
use App\Model\Table\SelectWorkStatusStaffTableMap;
use App\Model\Table\StatusDocumentTableMap;
use App\Model\Table\StatusFamilyTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use Nette\Database\Row;

class FamilyRepository extends BaseRepository
{
	private const int TYPE_FAMILY = 1;
	private const int TYPE_PROJECT = 2;

	protected function getTableName(): string
	{
		return FamilyTableMap::TABLE_NAME;
	}

	public function createEmptyFamily(): int
	{
		$row = $this->insert([
			FamilyTableMap::COL_TYPE => self::TYPE_FAMILY,
		]);

		if (!$row instanceof \Nette\Database\Table\ActiveRow) {
			throw new \RuntimeException('Family row was not created.');
		}

		return (int) $row->{FamilyTableMap::COL_ID};
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findUpdateRow(int $id): ?array
	{
		$row = $this->findAll()
			->where(FamilyTableMap::COL_ID, $id)
			->where(FamilyTableMap::COL_DELETED, 0)
			->fetch();

		if ($row === null) {
			return null;
		}

		return [
			'id' => (int) $row->{FamilyTableMap::COL_ID},
			'clientNumber' => (string) ($row->{FamilyTableMap::COL_CLIENT_NUMBER} ?? ''),
			'name' => (string) ($row->{FamilyTableMap::COL_NAME} ?? ''),
			'surname' => (string) ($row->{FamilyTableMap::COL_SURNAME} ?? ''),
			'street' => (string) ($row->{FamilyTableMap::COL_STREET} ?? ''),
			'streetNumber' => (string) ($row->{FamilyTableMap::COL_STREET_NUMBER} ?? ''),
			'psc' => (string) ($row->{FamilyTableMap::COL_PSC} ?? ''),
			'city' => (string) ($row->{FamilyTableMap::COL_CITY} ?? ''),
			'state' => (int) ($row->{FamilyTableMap::COL_STATE} ?? 0),
			'phone' => (string) ($row->{FamilyTableMap::COL_PHONE} ?? ''),
			'personEmail' => (string) ($row->{FamilyTableMap::COL_PERSON_EMAIL} ?? ''),
			'dateStart' => self::formatDate((string) ($row->{FamilyTableMap::COL_DATE_START} ?? '')),
			'dateTo' => self::formatDate((string) ($row->{FamilyTableMap::COL_DATE_TO} ?? '')),
			'status' => (int) ($row->{FamilyTableMap::COL_STATUS} ?? 0),
			'personName' => (string) ($row->{FamilyTableMap::COL_PERSON_NAME} ?? ''),
			'personSurname' => (string) ($row->{FamilyTableMap::COL_PERSON_SURNAME} ?? ''),
			'personPhone' => (string) ($row->{FamilyTableMap::COL_PERSON_PHONE} ?? ''),
			'notice' => (string) ($row->{FamilyTableMap::COL_NOTICE} ?? ''),
			'billing' => (string) ($row->{FamilyTableMap::COL_BILLING} ?? ''),
			'partnerId' => (int) ($row->{FamilyTableMap::COL_PARTNER_ID} ?? 0),
			'userId' => (int) ($row->{FamilyTableMap::COL_USER_ID} ?? 0),
			'acquiredByUserId' => (int) ($row->{FamilyTableMap::COL_ACQUIRED_BY_USER_ID} ?? 0),
			'orderStatus' => (int) ($row->{FamilyTableMap::COL_ORDER_STATUS} ?? 0),
			'contractStatus' => (int) ($row->{FamilyTableMap::COL_CONTRACT_STATUS} ?? 0),
			'patientPhone' => (string) ($row->{FamilyTableMap::COL_PATIENT_PHONE} ?? ''),
			'type' => (int) ($row->{FamilyTableMap::COL_TYPE} ?? self::TYPE_FAMILY),
			'companyName' => (string) ($row->{FamilyTableMap::COL_COMPANY_NAME} ?? ''),
			'employer' => (string) ($row->{FamilyTableMap::COL_EMPLOYER} ?? ''),
			'accommodationAddress' => (string) ($row->{FamilyTableMap::COL_ACCOMMODATION_ADDRESS} ?? ''),
			'deProjectNumber' => (string) ($row->{FamilyTableMap::COL_DE_PROJECT_NUMBER} ?? ''),
			'projectDescription' => (string) ($row->{FamilyTableMap::COL_PROJECT_DESCRIPTION} ?? ''),
			'projectPositions' => (string) ($row->{FamilyTableMap::COL_PROJECT_POSITIONS} ?? ''),
			'projectAvailablePositions' => (string) ($row->{FamilyTableMap::COL_PROJECT_AVAILABLE_POSITIONS} ?? ''),
			'workStatusStaff' => (int) ($row->{FamilyTableMap::COL_WORK_STATUS_STAFF} ?? 0),
		];
	}

	public function generateClientNumberIfMissing(int $id): void
	{
		$row = $this->getItem($id);
		if ($row === null || (string) ($row->{FamilyTableMap::COL_CLIENT_NUMBER} ?? '') !== '') {
			return;
		}

		$prefix = 'KN' . date('y');
		$index = 1;
		do {
			$clientNumber = $prefix . str_pad((string) $index, 3, '0', STR_PAD_LEFT);
			$exists = $this->findAll()
				->where(FamilyTableMap::COL_CLIENT_NUMBER, $clientNumber)
				->fetch() !== null;
			$index++;
		} while ($exists);

		$this->update($id, [
			FamilyTableMap::COL_CLIENT_NUMBER => $clientNumber,
		]);
	}

	public function updateInfoFromForm(FamilyInfoForm $form): void
	{
		$this->update($form->id, [
			FamilyTableMap::COL_TYPE => $form->type,
			FamilyTableMap::COL_PARTNER_ID => $form->partnerId,
			FamilyTableMap::COL_ACQUIRED_BY_USER_ID => $form->acquiredByUserId,
			FamilyTableMap::COL_USER_ID => $form->userId,
			FamilyTableMap::COL_STATUS => $form->status,
			FamilyTableMap::COL_PHONE => $form->phone,
			FamilyTableMap::COL_DATE_START => $this->normalizeDate($form->dateStart),
			FamilyTableMap::COL_DATE_TO => $this->normalizeDate($form->dateTo),
			FamilyTableMap::COL_ORDER_STATUS => $form->orderStatus,
			FamilyTableMap::COL_CONTRACT_STATUS => $form->contractStatus,
			FamilyTableMap::COL_WORK_STATUS_STAFF => $form->workStatusStaff,
			FamilyTableMap::COL_PROJECT_DESCRIPTION => $form->projectDescription,
			FamilyTableMap::COL_PROJECT_POSITIONS => $form->projectPositions,
			FamilyTableMap::COL_PROJECT_AVAILABLE_POSITIONS => $form->projectAvailablePositions,
		]);
	}

	public function updateAddressFromForm(FamilyAddressForm $form): void
	{
		$this->update($form->id, [
			FamilyTableMap::COL_COMPANY_NAME => $form->companyName,
			FamilyTableMap::COL_NAME => $form->name,
			FamilyTableMap::COL_SURNAME => $form->surname,
			FamilyTableMap::COL_STREET => $form->street,
			FamilyTableMap::COL_STREET_NUMBER => $form->streetNumber,
			FamilyTableMap::COL_PSC => $form->psc,
			FamilyTableMap::COL_CITY => $form->city,
			FamilyTableMap::COL_BILLING => $form->billing,
			FamilyTableMap::COL_EMPLOYER => $form->employer,
			FamilyTableMap::COL_ACCOMMODATION_ADDRESS => $form->accommodationAddress,
			FamilyTableMap::COL_NOTICE => $form->notice,
			FamilyTableMap::COL_PERSON_SURNAME => $form->personSurname,
			FamilyTableMap::COL_PERSON_NAME => $form->personName,
			FamilyTableMap::COL_PERSON_PHONE => $form->personPhone,
			FamilyTableMap::COL_PERSON_EMAIL => $form->personEmail,
			FamilyTableMap::COL_PATIENT_PHONE => $form->patientPhone,
		]);
	}

	public function updateShortInfoFromForm(FamilyShortInfoForm $form): void
	{
		$this->update($form->id, [
			FamilyTableMap::COL_DE_PROJECT_NUMBER => $form->deProjectNumber,
			FamilyTableMap::COL_STATE => $form->state,
		]);
	}

	public function createTurnusForFamily(int $familyId, int $userId): int
	{
		$family = $this->getItem($familyId);
		if ($family === null) {
			throw new \RuntimeException('Family row was not found.');
		}

		$row = $this->database->table(TurnusTableMap::TABLE_NAME)->insert([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_PARTNER_ID => (int) ($family->{FamilyTableMap::COL_PARTNER_ID} ?? 0),
			TurnusTableMap::COL_DATE_CREATED => date('Y-m-d'),
			TurnusTableMap::COL_USER_CREATED => $userId,
			TurnusTableMap::COL_USER_ID => (int) ($family->{FamilyTableMap::COL_USER_ID} ?? 0),
		]);

		if (!$row instanceof \Nette\Database\Table\ActiveRow) {
			throw new \RuntimeException('Turnus row was not created.');
		}

		return (int) $row->{TurnusTableMap::COL_ID};
	}

	public function canDeleteFamily(int $familyId): bool
	{
		return $this->database->table(TurnusTableMap::TABLE_NAME)
			->where(TurnusTableMap::COL_FAMILY_ID, $familyId)
			->where(TurnusTableMap::COL_DELETED, 0)
			->where(TurnusTableMap::COL_DATE_FROM . ' IS NOT NULL')
			->count('*') === 0;
	}

	public function softDeleteIfNoTurnus(int $familyId): void
	{
		if (!$this->canDeleteFamily($familyId)) {
			throw new \RuntimeException('Family has turnus rows and cannot be deleted.');
		}

		$this->update($familyId, [
			FamilyTableMap::COL_DELETED => 1,
		]);
	}

	/**
	 * @return list<array{id:int,clientNumber:string,name:string,surname:string,countryId:int,countryImage:string,personEmail:string,partnerId:int,partnerName:string,userId:int,userAcronym:string,userColor:string,statusId:int,statusLabel:string,statusColor:string}>
	 */
	public function findFamilyRows(
		int $page,
		int $itemsPerPage,
		?int $countryId,
		?int $statusId,
		?int $partnerId,
		?string $firstLetter,
		?string $city,
		?int $userId,
		int &$pageCount,
	): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$p = PartnerTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;
		$sf = StatusFamilyTableMap::TABLE_NAME;

		$where = [
			"$f." . FamilyTableMap::COL_TYPE . ' = ?',
			"$f." . FamilyTableMap::COL_DELETED . ' = ?',
		];
		$params = [self::TYPE_FAMILY, 0];

		if ($countryId !== null) {
			$where[] = "$f." . FamilyTableMap::COL_STATE . ' = ?';
			$params[] = $countryId;
		}

		if ($statusId !== null) {
			$where[] = "$f." . FamilyTableMap::COL_STATUS . ' = ?';
			$params[] = $statusId;
		}

		if ($partnerId !== null) {
			$where[] = "$f." . FamilyTableMap::COL_PARTNER_ID . ' = ?';
			$params[] = $partnerId;
		}

		if ($firstLetter !== null && $firstLetter !== '') {
			$where[] = "$f." . FamilyTableMap::COL_SURNAME . ' LIKE ?';
			$params[] = $firstLetter . '%';
		}

		if ($city !== null && $city !== '') {
			$where[] = "$f." . FamilyTableMap::COL_CITY . ' = ?';
			$params[] = $city;
		}

		if ($userId !== null) {
			$where[] = "$f." . FamilyTableMap::COL_USER_ID . ' = ?';
			$params[] = $userId;
		}

		$whereSql = implode(' AND ', $where);
		$totalCount = (int) $this->database->query(
			"SELECT COUNT(*) FROM $f WHERE $whereSql",
			...$params,
		)->fetchField();
		$pageCount = max(1, (int) ceil($totalCount / max(1, $itemsPerPage)));
		$page = min(max(1, $page), $pageCount);
		$offset = ($page - 1) * max(1, $itemsPerPage);

		$sql = "
			SELECT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_CLIENT_NUMBER . " AS client_number,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$f." . FamilyTableMap::COL_STATE . " AS country_id,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$f." . FamilyTableMap::COL_PERSON_EMAIL . " AS person_email,
				$f." . FamilyTableMap::COL_PARTNER_ID . " AS partner_id,
				$p." . PartnerTableMap::COL_NAME . " AS partner_name,
				$f." . FamilyTableMap::COL_USER_ID . " AS user_id,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym,
				$u." . UserTableMap::COL_COLOR . " AS user_color,
				$f." . FamilyTableMap::COL_STATUS . " AS status_id,
				$sf." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$sf." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			LEFT JOIN $p ON $p." . PartnerTableMap::COL_ID . " = $f." . FamilyTableMap::COL_PARTNER_ID . "
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $f." . FamilyTableMap::COL_USER_ID . "
			LEFT JOIN $sf ON $sf." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $whereSql
			ORDER BY $f." . FamilyTableMap::COL_ID . " DESC
			LIMIT ? OFFSET ?
		";
		$queryParams = [...$params, max(1, $itemsPerPage), $offset];
		$rows = $this->database->query($sql, ...$queryParams)->fetchAll();

		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->id,
				'clientNumber' => (string) ($row->client_number ?? ''),
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'countryId' => (int) ($row->country_id ?? 0),
				'countryImage' => (string) ($row->country_image ?? ''),
				'personEmail' => (string) ($row->person_email ?? ''),
				'partnerId' => (int) ($row->partner_id ?? 0),
				'partnerName' => (string) ($row->partner_name ?? ''),
				'userId' => (int) ($row->user_id ?? 0),
				'userAcronym' => (string) ($row->user_acronym ?? ''),
				'userColor' => (string) ($row->user_color ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
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
	 * @return list<array{id:int,status:string}>
	 */
	public function findStatusOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{StatusFamilyTableMap::COL_ID},
				'status' => (string) ($row->{StatusFamilyTableMap::COL_STATUS} ?? ''),
			],
			array_values($this->database->table(StatusFamilyTableMap::TABLE_NAME)->order(StatusFamilyTableMap::COL_STATUS . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return list<array{id:int,name:string}>
	 */
	public function findPartnerOptions(): array
	{
		return array_map(
			static fn ($row): array => [
				'id' => (int) $row->{PartnerTableMap::COL_ID},
				'name' => (string) ($row->{PartnerTableMap::COL_NAME} ?? ''),
			],
			array_values($this->database->table(PartnerTableMap::TABLE_NAME)->order(PartnerTableMap::COL_NAME . ' ASC')->fetchAll()),
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function findFamilyTypeOptions(): array
	{
		$options = [];
		$rows = $this->database->table(SelectFamilyProjectTableMap::TABLE_NAME)
			->order(SelectFamilyProjectTableMap::COL_SLOVAK . ' DESC');

		foreach ($rows as $row) {
			$options[(int) $row->{SelectFamilyProjectTableMap::COL_ID}] = (string) $row->{SelectFamilyProjectTableMap::COL_SLOVAK};
		}

		return $options;
	}

	/**
	 * @return array<int, string>
	 */
	public function findPartnerSelectOptions(): array
	{
		return $this->formatOptionRows(PartnerTableMap::TABLE_NAME, PartnerTableMap::COL_NAME, PartnerTableMap::COL_NAME . ' ASC', true);
	}

	/**
	 * @return array<int, string>
	 */
	public function findCountrySelectOptions(): array
	{
		return $this->formatOptionRows(CountryTableMap::TABLE_NAME, CountryTableMap::COL_NAME, CountryTableMap::COL_NAME . ' ASC');
	}

	/**
	 * @return array<int, string>
	 */
	public function findStatusSelectOptions(): array
	{
		return $this->formatOptionRows(StatusFamilyTableMap::TABLE_NAME, StatusFamilyTableMap::COL_STATUS, StatusFamilyTableMap::COL_STATUS . ' ASC', true);
	}

	/**
	 * @return array<int, string>
	 */
	public function findDocumentStatusSelectOptions(): array
	{
		return $this->formatOptionRows(StatusDocumentTableMap::TABLE_NAME, StatusDocumentTableMap::COL_STATUS, StatusDocumentTableMap::COL_STATUS . ' ASC', true);
	}

	/**
	 * @return array<int, string>
	 */
	public function findWorkStatusStaffOptions(): array
	{
		return $this->formatOptionRows(SelectWorkStatusStaffTableMap::TABLE_NAME, SelectWorkStatusStaffTableMap::COL_CONTRACT, SelectWorkStatusStaffTableMap::COL_CONTRACT . ' ASC', true);
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
	 * @return list<array<string, mixed>>
	 */
	public function findTurnusRowsForFamily(int $familyId): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$st = StatusTurnusTableMap::TABLE_NAME;

		$sql = "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_DATE_FROM . " AS date_from,
				$t." . TurnusTableMap::COL_DATE_TO . " AS date_to,
				$t." . TurnusTableMap::COL_STATUS . " AS status_id,
				$st." . StatusTurnusTableMap::COL_STATUS . " AS status,
				$st." . StatusTurnusTableMap::COL_COLOR . " AS status_color
			FROM $t
			LEFT JOIN $st ON $st." . StatusTurnusTableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS . "
			WHERE $t." . TurnusTableMap::COL_FAMILY_ID . " = ?
				AND $t." . TurnusTableMap::COL_DELETED . " = 0
			ORDER BY
				CASE WHEN $t." . TurnusTableMap::COL_DATE_FROM . " IS NULL OR $t." . TurnusTableMap::COL_DATE_FROM . " = '0000-00-00' THEN 0 ELSE 1 END ASC,
				$t." . TurnusTableMap::COL_DATE_FROM . " DESC,
				$t." . TurnusTableMap::COL_ID . " DESC
		";

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'dateFrom' => self::formatDate((string) ($row->date_from ?? '')),
				'dateTo' => self::formatDate((string) ($row->date_to ?? '')),
				'status' => (string) ($row->status ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$this->database->query($sql, $familyId)->fetchAll(),
		);
	}

	/**
	 * @return list<string>
	 */
	public function findCityOptions(): array
	{
		$rows = $this->database->table(FamilyTableMap::TABLE_NAME)
			->select('DISTINCT(' . FamilyTableMap::COL_CITY . ') AS city')
			->where(FamilyTableMap::COL_TYPE, self::TYPE_FAMILY)
			->order(FamilyTableMap::COL_CITY . ' ASC')
			->fetchAll();

		return array_values(array_filter(
			array_map(static fn ($row): string => (string) ($row->city ?? ''), array_values($rows)),
			static fn (string $city): bool => $city !== '',
		));
	}

	/**
	 * @return list<array{id:int,name:string,secondName:string,count:int}>
	 */
	public function findManagerCounts(): array
	{
		$rows = $this->database->table(UserTableMap::TABLE_NAME)
			->where(UserTableMap::COL_PERMISSION . ' < ?', 10)
			->order(UserTableMap::COL_NAME . ' ASC')
			->fetchAll();

		return array_map(
			fn ($row): array => [
				'id' => (int) $row->{UserTableMap::COL_ID},
				'name' => (string) ($row->{UserTableMap::COL_NAME} ?? ''),
				'secondName' => (string) ($row->{UserTableMap::COL_SECOND_NAME} ?? ''),
				'count' => $this->countManagedActiveFamilies((int) $row->{UserTableMap::COL_ID}),
			],
			array_values($rows),
		);
	}

	/**
	 * @return list<array{id:int,name:string,surname:string,statusLabel:string,statusColor:string}>
	 */
	public function findLastForOffcanvas(int $limit): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$sf = StatusFamilyTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$sf." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$sf." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			LEFT JOIN $sf ON $sf." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $f." . FamilyTableMap::COL_TYPE . " = ?
				AND $f." . FamilyTableMap::COL_DELETED . " = 0
			ORDER BY $f." . FamilyTableMap::COL_ID . " DESC
			LIMIT ?
		";

		$rows = $this->database->query($sql, self::TYPE_FAMILY, $limit)->fetchAll();

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$rows,
		);
	}

	/**
	 * @return list<array{id:int,title:string,color:string,count:int}>
	 */
	public function getStatusCountsForOffcanvas(): array
	{
		$rows = array_values(
			$this->database->table(StatusFamilyTableMap::TABLE_NAME)
				->order(StatusFamilyTableMap::COL_ID . ' ASC')
				->fetchAll(),
		);

		$items = array_map(
			fn ($row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->status ?? ''),
				'color' => (string) ($row->color ?? ''),
				'count' => $this->countFamiliesBy(FamilyTableMap::COL_STATUS, (int) $row->id),
			],
			$rows,
		);

		return array_values(array_filter($items, static fn (array $item): bool => $item['count'] > 0));
	}

	/**
	 * @return list<array{id:int,title:string,image:string,count:int}>
	 */
	public function getCountryCountsForOffcanvas(): array
	{
		$rows = array_values(
			$this->database->table(CountryTableMap::TABLE_NAME)
				->order(CountryTableMap::COL_ID . ' ASC')
				->fetchAll(),
		);

		$items = array_map(
			fn ($row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->name ?? ''),
				'image' => (string) ($row->image ?? ''),
				'count' => $this->countFamiliesBy(FamilyTableMap::COL_STATE, (int) $row->id),
			],
			$rows,
		);

		return array_values(array_filter($items, static fn (array $item): bool => $item['count'] > 0));
	}

	/**
	 * @return list<array{id:int,name:string,surname:string,statusId:int,statusLabel:string,statusColor:string}>
	 */
	public function findLastProjectsForOffcanvas(int $limit): array
	{
		$f = FamilyTableMap::TABLE_NAME;
		$sf = StatusFamilyTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$f." . FamilyTableMap::COL_ID . " AS id,
				$f." . FamilyTableMap::COL_NAME . " AS name,
				$f." . FamilyTableMap::COL_SURNAME . " AS surname,
				$f." . FamilyTableMap::COL_STATUS . " AS status_id,
				$sf." . StatusFamilyTableMap::COL_STATUS . " AS status_label,
				$sf." . StatusFamilyTableMap::COL_COLOR . " AS status_color
			FROM $f
			LEFT JOIN $sf ON $sf." . StatusFamilyTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATUS . "
			WHERE $f." . FamilyTableMap::COL_TYPE . " = ?
				AND $f." . FamilyTableMap::COL_DELETED . " = 0
			ORDER BY $f." . FamilyTableMap::COL_ID . " DESC
			LIMIT ?
		";

		$rows = $this->database->query($sql, self::TYPE_PROJECT, $limit)->fetchAll();

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'name' => (string) ($row->name ?? ''),
				'surname' => (string) ($row->surname ?? ''),
				'statusId' => (int) ($row->status_id ?? 0),
				'statusLabel' => (string) ($row->status_label ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
			],
			$rows,
		);
	}

	/**
	 * @return list<array{id:int,title:string,color:string,count:int}>
	 */
	public function getProjectStatusCountsForOffcanvas(): array
	{
		$rows = array_values(
			$this->database->table(StatusFamilyTableMap::TABLE_NAME)
				->order(StatusFamilyTableMap::COL_ID . ' ASC')
				->fetchAll(),
		);

		$items = array_map(
			fn ($row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->status ?? ''),
				'color' => (string) ($row->color ?? ''),
				'count' => $this->countProjectsBy(FamilyTableMap::COL_STATUS, (int) $row->id),
			],
			$rows,
		);

		return array_values(array_filter($items, static fn (array $item): bool => $item['count'] > 0));
	}

	/**
	 * @return list<array{id:int,title:string,image:string,count:int}>
	 */
	public function getProjectCountryCountsForOffcanvas(): array
	{
		$rows = array_values(
			$this->database->table(CountryTableMap::TABLE_NAME)
				->order(CountryTableMap::COL_ID . ' ASC')
				->fetchAll(),
		);

		$items = array_map(
			fn ($row): array => [
				'id' => (int) $row->id,
				'title' => (string) ($row->name ?? ''),
				'image' => (string) ($row->image ?? ''),
				'count' => $this->countProjectsBy(FamilyTableMap::COL_STATE, (int) $row->id),
			],
			$rows,
		);

		return array_values(array_filter($items, static fn (array $item): bool => $item['count'] > 0));
	}

	private function countFamiliesBy(string $column, int $value): int
	{
		return $this->database->table(FamilyTableMap::TABLE_NAME)
			->where($column, $value)
			->where(FamilyTableMap::COL_TYPE, self::TYPE_FAMILY)
			->where(FamilyTableMap::COL_DELETED, 0)
			->count('*');
	}

	private function countProjectsBy(string $column, int $value): int
	{
		return $this->database->table(FamilyTableMap::TABLE_NAME)
			->where($column, $value)
			->where(FamilyTableMap::COL_TYPE, self::TYPE_PROJECT)
			->where(FamilyTableMap::COL_DELETED, 0)
			->count('*');
	}

	private function countManagedActiveFamilies(int $userId): int
	{
		return $this->database->table(FamilyTableMap::TABLE_NAME)
			->where(FamilyTableMap::COL_USER_ID, $userId)
			->where(FamilyTableMap::COL_STATUS, 1)
			->where(FamilyTableMap::COL_TYPE, self::TYPE_FAMILY)
			->count('*');
	}

	/**
	 * @return array<int, string>
	 */
	private function formatOptionRows(string $table, string $labelColumn, string $order, bool $includeEmpty = false): array
	{
		$options = $includeEmpty ? [0 => '---'] : [];
		$rows = $this->database->table($table)->order($order);

		foreach ($rows as $row) {
			$options[(int) $row->id] = (string) ($row->{$labelColumn} ?? '');
		}

		return $options;
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
