<?php declare(strict_types=1);

namespace App\Model\Repository;

use App\Model\Form\DTO\Admin\Turnus\TurnusUpdate\TurnusUpdateForm;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\CountryTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\SelectPaymentPeriodTableMap;
use App\Model\Table\SelectWorkingStatusTableMap;
use App\Model\Table\SelectWorkPositionTableMap;
use App\Model\Table\StatusComplaintTableMap;
use App\Model\Table\StatusDocumentA1TableMap;
use App\Model\Table\StatusFaTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use DateTimeImmutable;
use Nette\Database\Row;
use Nette\Database\Table\ActiveRow;

class TurnusRepository extends BaseRepository
{
	private const int FINISHED_STATUS_ID = 10;
	private const int CANCELLED_STATUS_ID = 30;
	private const array UNPAID_INVOICE_STATUSES = [0, 1, 2, 4, 6];
	private const array UNPAID_INVOICE_EXCLUDED_TURNUS_STATUSES = [0, 30];
	private const array UNPAID_INVOICE_EXCLUDED_BABYSITTERS = [21, 22, 23, 107, 111, 358];
	private const int GERMANY_COUNTRY_ID = 3;
	private const array SORT_MAP = [
		1 => [TurnusTableMap::COL_DATE_FROM, 'ASC'],
		2 => [TurnusTableMap::COL_DATE_TO, 'ASC'],
		3 => [TurnusTableMap::COL_DATE_FROM, 'DESC'],
		4 => [TurnusTableMap::COL_DATE_TO, 'DESC'],
	];

	protected function getTableName(): string
	{
		return TurnusTableMap::TABLE_NAME;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findTurnusRows(
		int $page,
		int $itemsPerPage,
		int $finish,
		?int $statusId,
		?int $countryId,
		?int $agencyId,
		int $order,
		int &$pageCount,
		int &$totalCount,
	): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$a = AgencyTableMap::TABLE_NAME;
		$u = UserTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$st = StatusTurnusTableMap::TABLE_NAME;
		$a1 = StatusDocumentA1TableMap::TABLE_NAME;

		[$whereSql, $params] = $this->createTurnusListWhere($finish, $statusId, $order, $countryId, $agencyId);
		$totalCount = (int) $this->database->queryArgs(
			"SELECT COUNT(*) FROM $t WHERE $whereSql",
			$params,
		)->fetchField();
		$pageCount = max(1, (int) ceil($totalCount / max(1, $itemsPerPage)));
		$page = min(max(1, $page), $pageCount);
		$offset = ($page - 1) * max(1, $itemsPerPage);
		$orderSql = $this->createTurnusListOrder($finish, $statusId, $order);

		$sql = "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_STATUS . " AS status_id,
				$t." . TurnusTableMap::COL_INVOICE_STATUS . " AS invoice_status_id,
				$t." . TurnusTableMap::COL_DATE_FROM . " AS date_from,
				$t." . TurnusTableMap::COL_DATE_TO . " AS date_to,
				$t." . TurnusTableMap::COL_FAMILY_ID . " AS family_id,
				$t." . TurnusTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$t." . TurnusTableMap::COL_AGENCY_ID . " AS agency_id,
				$t." . TurnusTableMap::COL_USER_ID . " AS user_id,
				$t." . TurnusTableMap::COL_STATUS_A1 . " AS status_a1_id,
				$f." . FamilyTableMap::COL_NAME . " AS family_name,
				$f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				$f." . FamilyTableMap::COL_STATE . " AS family_state,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$a." . AgencyTableMap::COL_NAME . " AS agency_name,
				$u." . UserTableMap::COL_ACRONYM . " AS user_acronym,
				$u." . UserTableMap::COL_COLOR . " AS user_color,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$st." . StatusTurnusTableMap::COL_STATUS . " AS status,
				$st." . StatusTurnusTableMap::COL_COLOR . " AS status_color,
				$a1." . StatusDocumentA1TableMap::COL_STATUS . " AS status_a1,
				$a1." . StatusDocumentA1TableMap::COL_ICON . " AS status_a1_icon
			FROM $t
			LEFT JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $a ON $a." . AgencyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_AGENCY_ID . "
			LEFT JOIN $u ON $u." . UserTableMap::COL_ID . " = $t." . TurnusTableMap::COL_USER_ID . "
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			LEFT JOIN $st ON $st." . StatusTurnusTableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS . "
			LEFT JOIN $a1 ON $a1." . StatusDocumentA1TableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS_A1 . "
			WHERE $whereSql
			ORDER BY $orderSql
			LIMIT ? OFFSET ?
		";
		$rows = $this->database->queryArgs($sql, [...$params, max(1, $itemsPerPage), $offset])->fetchAll();

		return array_map(
			fn (Row $row): array => [
				'id' => (int) $row->id,
				'statusId' => (int) ($row->status_id ?? 0),
				'status' => (string) ($row->status ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				'dateFrom' => $this->dateService->tryCreateFromDb((string) ($row->date_from ?? '')),
				'dateTo' => $this->dateService->tryCreateFromDb((string) ($row->date_to ?? '')),
				'familyId' => (int) ($row->family_id ?? 0),
				'familyName' => trim((string) ($row->family_name ?? '') . ' ' . (string) ($row->family_surname ?? '')),
				'babysitterId' => (int) ($row->babysitter_id ?? 0),
				'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
				'agencyId' => (int) ($row->agency_id ?? 0),
				'agencyName' => (string) ($row->agency_name ?? ''),
				'userId' => (int) ($row->user_id ?? 0),
				'userAcronym' => (string) ($row->user_acronym ?? ''),
				'userColor' => (string) ($row->user_color ?? ''),
				'countryImage' => (string) ($row->country_image ?? ''),
				'statusA1Id' => (int) ($row->status_a1_id ?? 0),
				'statusA1' => (string) ($row->status_a1 ?? ''),
				'statusA1Icon' => (string) ($row->status_a1_icon ?? ''),
				'isInvoiceUnpaid' => $this->isInvoiceUnpaid($row),
			],
			$rows,
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function findUpdateRow(int $id): ?array
	{
		$row = $this->findAll()
			->where(TurnusTableMap::COL_ID, $id)
			->where(TurnusTableMap::COL_DELETED, 0)
			->fetch();

		if ($row === null) {
			return null;
		}

		$babysitterId = (int) ($row->{TurnusTableMap::COL_BABYSITTER_ID} ?? 0);
		$userCreated = (int) ($row->{TurnusTableMap::COL_USER_CREATED} ?? 0);

		return [
			'id' => (int) $row->{TurnusTableMap::COL_ID},
			'babysitterId' => $babysitterId,
			'familyId' => (int) ($row->{TurnusTableMap::COL_FAMILY_ID} ?? 0),
			'agencyId' => (int) ($row->{TurnusTableMap::COL_AGENCY_ID} ?? 0),
			'partnerId' => (int) ($row->{TurnusTableMap::COL_PARTNER_ID} ?? 0),
			'status' => (int) ($row->{TurnusTableMap::COL_STATUS} ?? 0),
			'invoiceNumber' => (string) ($row->{TurnusTableMap::COL_INVOICE_NUMBER} ?? ''),
			'preinvoiceNumber' => (string) ($row->{TurnusTableMap::COL_PREINVOICE_NUMBER} ?? ''),
			'invoiceStatus' => (int) ($row->{TurnusTableMap::COL_INVOICE_STATUS} ?? 0),
			'complaint' => (string) ($row->{TurnusTableMap::COL_COMPLAINT} ?? ''),
			'complaintStatus' => (int) ($row->{TurnusTableMap::COL_COMPLAINT_STATUS} ?? 0),
			'dateCreated' => $this->dateService->tryCreateFromDb((string) ($row->{TurnusTableMap::COL_DATE_CREATED} ?? '')),
			'workingStatus' => (int) ($row->{TurnusTableMap::COL_WORKING_STATUS} ?? 0),
			'userCreated' => $userCreated,
			'userCreatedName' => $this->findUserName($userCreated),
			'userId' => (int) ($row->{TurnusTableMap::COL_USER_ID} ?? 0),
			'bonus' => (string) ($row->{TurnusTableMap::COL_BONUS} ?? ''),
			'holiday' => (string) ($row->{TurnusTableMap::COL_HOLIDAY} ?? ''),
			'commissionComplet' => (string) ($row->{TurnusTableMap::COL_COMMISSION_COMPLET} ?? ''),
			'commissionPartners' => (string) ($row->{TurnusTableMap::COL_COMMISSION_PARTNERS} ?? ''),
			'paymentPeriodPartner' => (int) ($row->{TurnusTableMap::COL_PAYMENT_PERIOD_PARTNER} ?? 0),
			'commission4ms' => (string) ($row->{TurnusTableMap::COL_COMMISSION_4MS} ?? ''),
			'paymentPeriod' => (int) ($row->{TurnusTableMap::COL_PAYMENT_PERIOD} ?? 0),
			'remainingPayment' => (string) ($row->{TurnusTableMap::COL_REMAINING_PAYMENT} ?? ''),
			'travelExpenses' => (string) ($row->{TurnusTableMap::COL_TRAVEL_EXPENSES} ?? ''),
			'sva' => (string) ($row->{TurnusTableMap::COL_SVA} ?? ''),
			'dateFrom' => $this->dateService->tryCreateFromDb((string) ($row->{TurnusTableMap::COL_DATE_FROM} ?? '')),
			'dateTo' => $this->dateService->tryCreateFromDb((string) ($row->{TurnusTableMap::COL_DATE_TO} ?? '')),
			'travelCostsArrival' => (string) ($row->{TurnusTableMap::COL_TRAVEL_COSTS_ARRIVAL} ?? ''),
			'travelCostsDeparture' => (string) ($row->{TurnusTableMap::COL_TRAVEL_COSTS_DEPARTURE} ?? ''),
			'fee' => (string) ($row->{TurnusTableMap::COL_FEE} ?? ''),
			'feeAg' => (string) ($row->{TurnusTableMap::COL_FEE_AG} ?? ''),
			'feeBk' => (string) ($row->{TurnusTableMap::COL_FEE_BK} ?? ''),
			'notice' => (string) ($row->{TurnusTableMap::COL_NOTICE} ?? ''),
			'statusA1' => (int) ($row->{TurnusTableMap::COL_STATUS_A1} ?? 0),
			'workPositionId' => (int) ($row->{TurnusTableMap::COL_WORK_POSITION_ID} ?? 0),
			'workerType' => $this->findBabysitterType($babysitterId),
		];
	}

	public function createEmptyTurnus(int $userId): int
	{
		$row = $this->insert([
			TurnusTableMap::COL_DATE_CREATED => date('Y-m-d'),
			TurnusTableMap::COL_USER_CREATED => $userId,
			TurnusTableMap::COL_USER_ID => $userId,
			TurnusTableMap::COL_ACTIVE => 1,
			TurnusTableMap::COL_DELETED => 0,
		]);

		if (!$row instanceof ActiveRow) {
			throw new \RuntimeException('Turnus row was not created.');
		}

		return (int) $row->{TurnusTableMap::COL_ID};
	}

	public function updateFromForm(TurnusUpdateForm $form): void
	{
		$this->update($form->id, [
			TurnusTableMap::COL_STATUS => $form->status,
			TurnusTableMap::COL_FAMILY_ID => $form->familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $form->babysitterId,
			TurnusTableMap::COL_DATE_FROM => $form->dateFrom?->format('Y-m-d'),
			TurnusTableMap::COL_DATE_TO => $form->dateTo?->format('Y-m-d'),
			TurnusTableMap::COL_USER_ID => $form->userId,
			TurnusTableMap::COL_AGENCY_ID => $form->agencyId,
			TurnusTableMap::COL_PARTNER_ID => $form->partnerId,
			TurnusTableMap::COL_WORKING_STATUS => $form->workingStatus,
			TurnusTableMap::COL_WORK_POSITION_ID => $form->workPositionId,
			TurnusTableMap::COL_PREINVOICE_NUMBER => $form->preinvoiceNumber,
			TurnusTableMap::COL_INVOICE_NUMBER => $form->invoiceNumber,
			TurnusTableMap::COL_INVOICE_STATUS => $form->invoiceStatus,
			TurnusTableMap::COL_FEE => $form->fee,
			TurnusTableMap::COL_FEE_AG => $form->feeAg,
			TurnusTableMap::COL_FEE_BK => $form->feeBk,
			TurnusTableMap::COL_TRAVEL_COSTS_ARRIVAL => $form->travelCostsArrival,
			TurnusTableMap::COL_TRAVEL_COSTS_DEPARTURE => $form->travelCostsDeparture,
			TurnusTableMap::COL_TRAVEL_EXPENSES => $form->travelExpenses,
			TurnusTableMap::COL_BONUS => $form->bonus,
			TurnusTableMap::COL_HOLIDAY => $form->holiday,
			TurnusTableMap::COL_SVA => $form->sva,
			TurnusTableMap::COL_COMMISSION_COMPLET => $form->commissionComplet,
			TurnusTableMap::COL_COMMISSION_PARTNERS => $form->commissionPartners,
			TurnusTableMap::COL_PAYMENT_PERIOD_PARTNER => $form->paymentPeriodPartner,
			TurnusTableMap::COL_COMMISSION_4MS => $form->commission4ms,
			TurnusTableMap::COL_PAYMENT_PERIOD => $form->paymentPeriod,
			TurnusTableMap::COL_REMAINING_PAYMENT => $form->remainingPayment,
			TurnusTableMap::COL_NOTICE => $form->notice,
			TurnusTableMap::COL_COMPLAINT => $form->complaint,
			TurnusTableMap::COL_COMPLAINT_STATUS => $form->complaintStatus,
		]);
	}

	public function updateStatusA1(int $id, int $statusA1): void
	{
		$this->update($id, [
			TurnusTableMap::COL_STATUS_A1 => $statusA1,
		]);
	}

	public function softDelete(int $id): void
	{
		$this->update($id, [
			TurnusTableMap::COL_DELETED => 1,
		]);
	}

	/**
	 * @return array<int, string>
	 */
	public function findStatusOptions(): array
	{
		return $this->findSelectOptions(StatusTurnusTableMap::TABLE_NAME, StatusTurnusTableMap::COL_ID, StatusTurnusTableMap::COL_STATUS, StatusTurnusTableMap::COL_STATUS);
	}

	/**
	 * @return array<int, string>
	 */
	public function findCountryFilterOptions(int $finish, ?int $statusId, int $order, ?int $agencyId): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		[$whereSql, $params] = $this->createTurnusListWhere($finish, $statusId, $order, null, $agencyId);

		$sql = "
			SELECT DISTINCT
				$c." . CountryTableMap::COL_ID . " AS id,
				$c." . CountryTableMap::COL_NAME . " AS name
			FROM $t
			INNER JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_FAMILY_ID . "
			INNER JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			WHERE $whereSql
			ORDER BY $c." . CountryTableMap::COL_NAME . " ASC
		";

		return $this->mapIdNameOptions($this->database->queryArgs($sql, $params)->fetchAll());
	}

	/**
	 * @return array<int, string>
	 */
	public function findAgencyFilterOptions(int $finish, ?int $statusId, int $order, ?int $countryId): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$a = AgencyTableMap::TABLE_NAME;
		[$whereSql, $params] = $this->createTurnusListWhere($finish, $statusId, $order, $countryId, null);

		$sql = "
			SELECT DISTINCT
				$a." . AgencyTableMap::COL_ID . " AS id,
				$a." . AgencyTableMap::COL_NAME . " AS name
			FROM $t
			INNER JOIN $a ON $a." . AgencyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_AGENCY_ID . "
			WHERE $whereSql
			ORDER BY $a." . AgencyTableMap::COL_NAME . " ASC
		";

		return $this->mapIdNameOptions($this->database->queryArgs($sql, $params)->fetchAll());
	}

	/**
	 * @return array<int, string>
	 */
	public function findFamilySelectOptions(): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table(FamilyTableMap::TABLE_NAME)
			->order(FamilyTableMap::COL_SURNAME . ' ASC, ' . FamilyTableMap::COL_NAME . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{FamilyTableMap::COL_ID}] = trim(
				(string) $row->{FamilyTableMap::COL_SURNAME} . ' '
				. (string) $row->{FamilyTableMap::COL_NAME}
				. ', '
				. (string) $row->{FamilyTableMap::COL_CLIENT_NUMBER},
			);
		}

		return $options;
	}

	/**
	 * @return array<int, string>
	 */
	public function findBabysitterSelectOptions(): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table(OpatrovatelkaTableMap::TABLE_NAME)
			->order(OpatrovatelkaTableMap::COL_SURNAME . ' ASC, ' . OpatrovatelkaTableMap::COL_NAME . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{OpatrovatelkaTableMap::COL_ID}] = trim(
				(string) $row->{OpatrovatelkaTableMap::COL_SURNAME} . ' '
				. (string) $row->{OpatrovatelkaTableMap::COL_NAME}
				. ', '
				. (string) $row->{OpatrovatelkaTableMap::COL_CLIENT_NUMBER},
			);
		}

		return $options;
	}

	/**
	 * @return array<int, string>
	 */
	public function findAgencySelectOptions(): array
	{
		return $this->findSelectOptions(AgencyTableMap::TABLE_NAME, AgencyTableMap::COL_ID, AgencyTableMap::COL_NAME, AgencyTableMap::COL_NAME);
	}

	/**
	 * @return array<int, string>
	 */
	public function findPartnerSelectOptions(): array
	{
		return $this->findSelectOptions(PartnerTableMap::TABLE_NAME, PartnerTableMap::COL_ID, PartnerTableMap::COL_NAME, PartnerTableMap::COL_NAME);
	}

	/**
	 * @return array<int, string>
	 */
	public function findWorkingStatusSelectOptions(): array
	{
		return $this->findSelectOptions(SelectWorkingStatusTableMap::TABLE_NAME, SelectWorkingStatusTableMap::COL_ID, SelectWorkingStatusTableMap::COL_SLOVAK, SelectWorkingStatusTableMap::COL_SLOVAK);
	}

	/**
	 * @return array<int, string>
	 */
	public function findUserSelectOptions(): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table(UserTableMap::TABLE_NAME)
			->where(UserTableMap::COL_PERMISSION . ' < ?', 10)
			->order(UserTableMap::COL_SECOND_NAME . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{UserTableMap::COL_ID}] = trim((string) $row->{UserTableMap::COL_NAME} . ' ' . (string) $row->{UserTableMap::COL_SECOND_NAME});
		}

		return $options;
	}

	/**
	 * @return array<int, string>
	 */
	public function findInvoiceStatusSelectOptions(): array
	{
		return $this->findSelectOptions(StatusFaTableMap::TABLE_NAME, StatusFaTableMap::COL_ID, StatusFaTableMap::COL_STATUS, StatusFaTableMap::COL_STATUS);
	}

	/**
	 * @return array<int, string>
	 */
	public function findComplaintStatusSelectOptions(): array
	{
		return $this->findSelectOptions(StatusComplaintTableMap::TABLE_NAME, StatusComplaintTableMap::COL_ID, StatusComplaintTableMap::COL_STATUS, StatusComplaintTableMap::COL_STATUS);
	}

	/**
	 * @return array<int, string>
	 */
	public function findStatusA1SelectOptions(): array
	{
		return $this->findSelectOptions(StatusDocumentA1TableMap::TABLE_NAME, StatusDocumentA1TableMap::COL_ID, StatusDocumentA1TableMap::COL_STATUS, StatusDocumentA1TableMap::COL_STATUS);
	}

	/**
	 * @return array<int, string>
	 */
	public function findPaymentPeriodSelectOptions(): array
	{
		return $this->findSelectOptions(SelectPaymentPeriodTableMap::TABLE_NAME, SelectPaymentPeriodTableMap::COL_ID, SelectPaymentPeriodTableMap::COL_STATUS, SelectPaymentPeriodTableMap::COL_STATUS);
	}

	/**
	 * @return array<int, string>
	 */
	public function findWorkPositionSelectOptions(): array
	{
		return $this->findSelectOptions(SelectWorkPositionTableMap::TABLE_NAME, SelectWorkPositionTableMap::COL_ID, SelectWorkPositionTableMap::COL_POSITION, SelectWorkPositionTableMap::COL_POSITION);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findForMonth(int $year, int $month): array
	{
		$monthDate = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
		$firstDay = $monthDate->modify('first day of this month')->format('Y-m-d');
		$lastDay = $monthDate->modify('last day of this month')->format('Y-m-d');
		$monthPrefix = $monthDate->format('Y-m');

		$t = TurnusTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$st = StatusTurnusTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_STATUS . " AS status_id,
				$t." . TurnusTableMap::COL_DATE_FROM . " AS date_from,
				$t." . TurnusTableMap::COL_DATE_TO . " AS date_to,
				$t." . TurnusTableMap::COL_FAMILY_ID . " AS family_id,
				$t." . TurnusTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$t." . TurnusTableMap::COL_FEE . " AS fee,
				$t." . TurnusTableMap::COL_TRAVEL_COSTS_ARRIVAL . " AS travel_costs_arrival,
				$t." . TurnusTableMap::COL_TRAVEL_COSTS_DEPARTURE . " AS travel_costs_departure,
				$t." . TurnusTableMap::COL_HOLIDAY . " AS holiday,
				$f." . FamilyTableMap::COL_NAME . " AS family_name,
				$f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$st." . StatusTurnusTableMap::COL_STATUS . " AS status,
				$st." . StatusTurnusTableMap::COL_COLOR . " AS status_color
			FROM $t
			INNER JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $st ON $st." . StatusTurnusTableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS . "
			WHERE $t." . TurnusTableMap::COL_DELETED . " = 0
				AND $f." . FamilyTableMap::COL_STATE . " = 2
				AND (
					$t." . TurnusTableMap::COL_DATE_FROM . " LIKE ?
					OR $t." . TurnusTableMap::COL_DATE_TO . " LIKE ?
					OR ($t." . TurnusTableMap::COL_DATE_FROM . " < ? AND $t." . TurnusTableMap::COL_DATE_TO . " > ?)
					OR ($t." . TurnusTableMap::COL_DATE_FROM . " < ? AND $t." . TurnusTableMap::COL_DATE_TO . " IS NULL)
					OR ($t." . TurnusTableMap::COL_DATE_FROM . " < ? AND $t." . TurnusTableMap::COL_DATE_TO . " = '0000-00-00')
				)
			ORDER BY $t." . TurnusTableMap::COL_ID . " DESC
		";

		$rows = $this->database->query(
			$sql,
			$monthPrefix . '%',
			$monthPrefix . '%',
			$firstDay,
			$lastDay,
			$firstDay,
			$firstDay,
		)->fetchAll();

		return array_map(
			fn (Row $row): array => [
				'id' => (int) $row->id,
				'statusId' => (int) $row->status_id,
				'status' => (string) ($row->status ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				'dateFrom' => $this->dateService->tryCreateFromDb((string) ($row->date_from ?? '')),
				'dateTo' => $this->dateService->tryCreateFromDb((string) ($row->date_to ?? '')),
				'familyId' => (int) $row->family_id,
				'familyName' => trim((string) ($row->family_name ?? '') . ' ' . (string) ($row->family_surname ?? '')),
				'babysitterId' => (int) $row->babysitter_id,
				'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
				'fee' => (string) ($row->fee ?? ''),
				'travelCostsArrival' => (string) ($row->travel_costs_arrival ?? ''),
				'travelCostsDeparture' => (string) ($row->travel_costs_departure ?? ''),
				'holiday' => (string) ($row->holiday ?? ''),
			],
			$rows,
		);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findUpcomingStartsForHomepage(int $days = 20): array
	{
		// @phpstan-ignore-next-line argument.type
		$rows = array_values($this->database->query($this->createHomepageTurnusSql(TurnusTableMap::COL_DATE_FROM), $days)->fetchAll());

		return $this->mapHomepageTurnusRows($rows);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findUpcomingEndsForHomepage(int $days = 20): array
	{
		// @phpstan-ignore-next-line argument.type
		$rows = array_values($this->database->query($this->createHomepageTurnusSql(TurnusTableMap::COL_DATE_TO), $days)->fetchAll());

		return $this->mapHomepageTurnusRows($rows);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function findUnpaidInvoicesForHomepage(): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$fa = StatusFaTableMap::TABLE_NAME;

		/** @var literal-string $sql */
		$sql = "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_PREINVOICE_NUMBER . " AS preinvoice_number,
				$t." . TurnusTableMap::COL_INVOICE_STATUS . " AS invoice_status_id,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$fa." . StatusFaTableMap::COL_STATUS . " AS invoice_status,
				$fa." . StatusFaTableMap::COL_COLOR . " AS invoice_status_color
			FROM $t
			INNER JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $fa ON $fa." . StatusFaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_INVOICE_STATUS . "
			WHERE $t." . TurnusTableMap::COL_INVOICE_STATUS . " <> 3
				AND $t." . TurnusTableMap::COL_INVOICE_STATUS . " <> 5
				AND $t." . TurnusTableMap::COL_WORKING_STATUS . " = 2
				AND $t." . TurnusTableMap::COL_STATUS . " < 30
			ORDER BY $t." . TurnusTableMap::COL_ID . " DESC
		";
		$rows = $this->database->query($sql)->fetchAll();

		return array_map(
			static fn (Row $row): array => [
				'id' => (int) $row->id,
				'preinvoiceNumber' => (string) ($row->preinvoice_number ?? ''),
				'babysitterName' => self::truncate((string) (($row->babysitter_surname ?? '') ?: ($row->babysitter_name ?? '')), 20),
				'invoiceStatus' => (string) ($row->invoice_status ?? ''),
				'invoiceStatusColor' => (string) ($row->invoice_status_color ?? ''),
			],
			$rows,
		);
	}

	/**
	 * @return array{0:literal-string,1:list<mixed>}
	 */
	private function createTurnusListWhere(int $finish, ?int $statusId, int $order, ?int $countryId = null, ?int $agencyId = null): array
	{
		$t = TurnusTableMap::TABLE_NAME;
		$statusId = $statusId !== null && $statusId > 0 ? $statusId : null;
		$today = date('Y-m-d');
		$extraWhere = '';
		$extraParams = [];
		if ($countryId !== null && $countryId > 0) {
			$extraWhere .= " AND $t." . TurnusTableMap::COL_FAMILY_ID . ' IN (SELECT ' . FamilyTableMap::COL_ID . ' FROM ' . FamilyTableMap::TABLE_NAME . ' WHERE ' . FamilyTableMap::COL_STATE . ' = ?)';
			$extraParams[] = $countryId;
		}
		if ($agencyId !== null && $agencyId > 0) {
			$extraWhere .= " AND $t." . TurnusTableMap::COL_AGENCY_ID . ' = ?';
			$extraParams[] = $agencyId;
		}

		if (isset(self::SORT_MAP[$order])) {
			if ($finish === 1) {
				return [
					"$t." . TurnusTableMap::COL_DELETED . ' = ?'
					. " AND $t." . TurnusTableMap::COL_STATUS . ' = ?'
					. " AND $t." . TurnusTableMap::COL_DATE_TO . ' <= ?'
					. $extraWhere,
					[0, self::FINISHED_STATUS_ID, $today, ...$extraParams],
				];
			}

			if ($statusId !== null) {
				return [
					"$t." . TurnusTableMap::COL_DELETED . ' = ?'
					. " AND $t." . TurnusTableMap::COL_STATUS . ' = ?'
					. $extraWhere,
					[0, $statusId, ...$extraParams],
				];
			}

			return [
				"$t." . TurnusTableMap::COL_DELETED . ' = ?'
				. " AND $t." . TurnusTableMap::COL_STATUS . ' <> ?'
				. " AND $t." . TurnusTableMap::COL_STATUS . ' <> ?'
				. " AND ($t." . TurnusTableMap::COL_DATE_TO . ' >= ?'
				. " OR $t." . TurnusTableMap::COL_DATE_TO . ' IS NULL)'
				. $extraWhere,
				[0, self::FINISHED_STATUS_ID, self::CANCELLED_STATUS_ID, $today, ...$extraParams],
			];
		}

		if ($statusId !== null) {
			return [
				"$t." . TurnusTableMap::COL_DELETED . ' = ?'
				. " AND $t." . TurnusTableMap::COL_STATUS . ' = ?'
				. $extraWhere,
				[0, $statusId, ...$extraParams],
			];
		}

		if ($finish === 1) {
			return [
				"$t." . TurnusTableMap::COL_DELETED . ' = ?'
				. " AND $t." . TurnusTableMap::COL_STATUS . ' = ?'
				. " AND $t." . TurnusTableMap::COL_DATE_TO . ' <= ?'
				. $extraWhere,
				[0, self::FINISHED_STATUS_ID, $today, ...$extraParams],
			];
		}

		return [
			"$t." . TurnusTableMap::COL_DELETED . ' = ?'
			. " AND $t." . TurnusTableMap::COL_STATUS . ' <> ?'
			. " AND $t." . TurnusTableMap::COL_STATUS . ' <> ?'
			. $extraWhere,
			[0, self::FINISHED_STATUS_ID, self::CANCELLED_STATUS_ID, ...$extraParams],
		];
	}

	/**
	 * @return literal-string
	 */
	private function createTurnusListOrder(int $finish, ?int $statusId, int $order): string
	{
		$t = TurnusTableMap::TABLE_NAME;

		if (isset(self::SORT_MAP[$order])) {
			[$column, $direction] = self::SORT_MAP[$order];

			return "$t.$column $direction, $t." . TurnusTableMap::COL_ID . ' DESC';
		}

		if ($statusId !== null && $statusId > 0) {
			return "$t." . TurnusTableMap::COL_ID . ' DESC';
		}

		if ($finish === 1) {
			return "$t." . TurnusTableMap::COL_DATE_TO . ' DESC, ' . "$t." . TurnusTableMap::COL_ID . ' DESC';
		}

		return "$t." . TurnusTableMap::COL_ID . ' DESC';
	}

	/**
	 * @return array<int, string>
	 */
	private function findSelectOptions(string $table, string $idColumn, string $labelColumn, string $orderColumn): array
	{
		$options = [0 => '---'];
		$rows = $this->database->table($table)->order($orderColumn . ' ASC');

		foreach ($rows as $row) {
			$options[(int) $row->{$idColumn}] = (string) ($row->{$labelColumn} ?? '');
		}

		return $options;
	}

	/**
	 * @param list<Row> $rows
	 * @return array<int, string>
	 */
	private function mapIdNameOptions(array $rows): array
	{
		$options = [];
		foreach ($rows as $row) {
			$id = (int) ($row->id ?? 0);
			$name = trim((string) ($row->name ?? ''));
			if ($id > 0 && $name !== '') {
				$options[$id] = $name;
			}
		}

		return $options;
	}

	private function findUserName(int $id): string
	{
		if ($id <= 0) {
			return '';
		}

		$row = $this->database->table(UserTableMap::TABLE_NAME)->get($id);
		if ($row === null) {
			return '';
		}

		return trim((string) $row->{UserTableMap::COL_NAME} . ' ' . (string) $row->{UserTableMap::COL_SECOND_NAME});
	}

	private function findBabysitterType(int $id): ?int
	{
		if ($id <= 0) {
			return null;
		}

		$row = $this->database->table(OpatrovatelkaTableMap::TABLE_NAME)->get($id);

		return $row === null ? null : (int) ($row->{OpatrovatelkaTableMap::COL_TYPE} ?? 0);
	}

	private function createHomepageTurnusSql(string $dateColumn): string
	{
		if (!in_array($dateColumn, [TurnusTableMap::COL_DATE_FROM, TurnusTableMap::COL_DATE_TO], true)) {
			throw new \InvalidArgumentException('Unsupported homepage turnus date column.');
		}

		$t = TurnusTableMap::TABLE_NAME;
		$f = FamilyTableMap::TABLE_NAME;
		$b = OpatrovatelkaTableMap::TABLE_NAME;
		$c = CountryTableMap::TABLE_NAME;
		$st = StatusTurnusTableMap::TABLE_NAME;

		return "
			SELECT
				$t." . TurnusTableMap::COL_ID . " AS id,
				$t." . TurnusTableMap::COL_BABYSITTER_ID . " AS babysitter_id,
				$t." . TurnusTableMap::COL_FAMILY_ID . " AS family_id,
				$t." . TurnusTableMap::COL_STATUS . " AS status_id,
				$t." . TurnusTableMap::COL_INVOICE_STATUS . " AS invoice_status_id,
				$t." . TurnusTableMap::COL_DATE_FROM . " AS date_from,
				$t." . TurnusTableMap::COL_DATE_TO . " AS date_to,
				$f." . FamilyTableMap::COL_NAME . " AS family_name,
				$f." . FamilyTableMap::COL_SURNAME . " AS family_surname,
				$f." . FamilyTableMap::COL_STATE . " AS family_state,
				$b." . OpatrovatelkaTableMap::COL_NAME . " AS babysitter_name,
				$b." . OpatrovatelkaTableMap::COL_SURNAME . " AS babysitter_surname,
				$c." . CountryTableMap::COL_IMAGE . " AS country_image,
				$st." . StatusTurnusTableMap::COL_STATUS . " AS status,
				$st." . StatusTurnusTableMap::COL_COLOR . " AS status_color
			FROM $t
			LEFT JOIN $f ON $f." . FamilyTableMap::COL_ID . " = $t." . TurnusTableMap::COL_FAMILY_ID . "
			LEFT JOIN $b ON $b." . OpatrovatelkaTableMap::COL_ID . " = $t." . TurnusTableMap::COL_BABYSITTER_ID . "
			LEFT JOIN $c ON $c." . CountryTableMap::COL_ID . " = $f." . FamilyTableMap::COL_STATE . "
			LEFT JOIN $st ON $st." . StatusTurnusTableMap::COL_ID . " = $t." . TurnusTableMap::COL_STATUS . "
			WHERE $t.$dateColumn < (NOW() + INTERVAL ? DAY)
				AND $t.$dateColumn >= (NOW() - INTERVAL 1 DAY)
				AND $t." . TurnusTableMap::COL_DELETED . " = 0
				AND $t." . TurnusTableMap::COL_STATUS . " < 30
			ORDER BY $t.$dateColumn ASC
		";
	}

	/**
	 * @param list<Row> $rows
	 * @return list<array<string, mixed>>
	 */
	private function mapHomepageTurnusRows(array $rows): array
	{
		return array_map(
			fn (Row $row): array => [
				'id' => (int) $row->id,
				'babysitterId' => (int) $row->babysitter_id,
				'familyId' => (int) $row->family_id,
				'dateFrom' => $this->dateService->tryCreateFromDb((string) ($row->date_from ?? '')),
				'dateTo' => $this->dateService->tryCreateFromDb((string) ($row->date_to ?? '')),
				'familyName' => trim((string) ($row->family_name ?? '') . ' ' . (string) ($row->family_surname ?? '')),
				'babysitterName' => trim((string) ($row->babysitter_name ?? '') . ' ' . (string) ($row->babysitter_surname ?? '')),
				'countryImage' => (string) ($row->country_image ?? ''),
				'status' => (string) ($row->status ?? ''),
				'statusColor' => (string) ($row->status_color ?? ''),
				'isInvoiceUnpaid' => $this->isInvoiceUnpaid($row),
			],
			$rows,
		);
	}

	private function isInvoiceUnpaid(Row $row): bool
	{
		return !in_array((int) $row->babysitter_id, self::UNPAID_INVOICE_EXCLUDED_BABYSITTERS, true)
			&& in_array((int) $row->invoice_status_id, self::UNPAID_INVOICE_STATUSES, true)
			&& !in_array((int) $row->status_id, self::UNPAID_INVOICE_EXCLUDED_TURNUS_STATUSES, true)
			&& (int) $row->family_state === self::GERMANY_COUNTRY_ID;
	}

	private static function truncate(string $value, int $length): string
	{
		return strlen($value) > $length ? substr($value, 0, $length) : $value;
	}
}
