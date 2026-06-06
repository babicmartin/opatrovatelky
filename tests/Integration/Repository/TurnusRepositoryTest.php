<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Form\DTO\Admin\Turnus\TurnusUpdate\TurnusUpdateForm;
use App\Model\Repository\TurnusRepository;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\StatusDocumentA1TableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class TurnusRepositoryTest extends DatabaseTestCase
{
	public function testCreateEmptyTurnusStoresCreatorAndActiveFlags(): void
	{
		$repository = $this->repository();
		$userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'agent4.turnus.creator@example.test',
			UserTableMap::COL_PERMISSION => 5,
		]);

		$turnusId = $repository->createEmptyTurnus($userId);

		$row = $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->get($turnusId);

		self::assertNotNull($row);
		self::assertSame($userId, (int) $row->{TurnusTableMap::COL_USER_CREATED});
		self::assertSame($userId, (int) $row->{TurnusTableMap::COL_USER_ID});
		self::assertSame(date('Y-m-d'), $row->{TurnusTableMap::COL_DATE_CREATED}->format('Y-m-d'));
		self::assertSame(1, (int) $row->{TurnusTableMap::COL_ACTIVE});
		self::assertSame(0, (int) $row->{TurnusTableMap::COL_DELETED});
	}

	public function testUpdateFromFormPersistsTurnusFields(): void
	{
		$repository = $this->repository();
		$userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'agent4.turnus.update@example.test',
			UserTableMap::COL_PERMISSION => 5,
		]);
		$agencyId = TestDatabase::createAgency([
			AgencyTableMap::COL_NAME => 'Turnus update agency',
		]);
		$partnerId = TestDatabase::createPartner([
			PartnerTableMap::COL_NAME => 'Turnus update partner',
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-TU',
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-AG4-TU',
		]);
		$turnusId = TestDatabase::createTurnus();

		$repository->updateFromForm(new TurnusUpdateForm(
			$turnusId,
			1,
			$familyId,
			$babysitterId,
			new \DateTimeImmutable('2026-06-15'),
			new \DateTimeImmutable('2026-07-15'),
			$userId,
			$agencyId,
			$partnerId,
			1,
			1,
			'PRE-AG4',
			'INV-AG4',
			1,
			1000.5,
			100.25,
			50.75,
			12.5,
			13.5,
			'bus',
			20.0,
			1.5,
			'SVA text',
			30.0,
			40.0,
			1,
			4.5,
			2,
			123.45,
			'Turnus notice',
			'Complaint text',
			1,
		));

		$row = $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->get($turnusId);

		self::assertNotNull($row);
		self::assertSame(1, (int) $row->{TurnusTableMap::COL_STATUS});
		self::assertSame($familyId, (int) $row->{TurnusTableMap::COL_FAMILY_ID});
		self::assertSame($babysitterId, (int) $row->{TurnusTableMap::COL_BABYSITTER_ID});
		self::assertSame('2026-06-15', $row->{TurnusTableMap::COL_DATE_FROM}->format('Y-m-d'));
		self::assertSame('2026-07-15', $row->{TurnusTableMap::COL_DATE_TO}->format('Y-m-d'));
		self::assertSame($userId, (int) $row->{TurnusTableMap::COL_USER_ID});
		self::assertSame($agencyId, (int) $row->{TurnusTableMap::COL_AGENCY_ID});
		self::assertSame($partnerId, (int) $row->{TurnusTableMap::COL_PARTNER_ID});
		self::assertSame('PRE-AG4', $row->{TurnusTableMap::COL_PREINVOICE_NUMBER});
		self::assertSame('INV-AG4', $row->{TurnusTableMap::COL_INVOICE_NUMBER});
		self::assertSame(1000.5, (float) $row->{TurnusTableMap::COL_FEE});
		self::assertSame(123.45, (float) $row->{TurnusTableMap::COL_REMAINING_PAYMENT});
		self::assertSame('Turnus notice', $row->{TurnusTableMap::COL_NOTICE});
		self::assertSame('Complaint text', $row->{TurnusTableMap::COL_COMPLAINT});
	}

	public function testFindTurnusRowsAppliesFiltersAndSelectOptionMethods(): void
	{
		$repository = $this->repository();
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Agent',
			UserTableMap::COL_SECOND_NAME => 'Four',
			UserTableMap::COL_ACRONYM => 'A4',
			UserTableMap::COL_EMAIL => 'agent4.turnus.filter@example.test',
			UserTableMap::COL_PERMISSION => 5,
			UserTableMap::COL_COLOR => '#456789',
		]);
		$agencyId = TestDatabase::createAgency([
			AgencyTableMap::COL_NAME => 'Filter turnus agency',
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-TR1',
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Turnusova',
			FamilyTableMap::COL_STATE => 2,
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-AG4-TR1',
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Care',
		]);
		$matchingId = TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_AGENCY_ID => $agencyId,
			TurnusTableMap::COL_USER_ID => $userId,
			TurnusTableMap::COL_STATUS => 1,
			TurnusTableMap::COL_STATUS_A1 => 1,
			TurnusTableMap::COL_INVOICE_STATUS => 1,
			TurnusTableMap::COL_DATE_FROM => '2026-06-01',
			TurnusTableMap::COL_DATE_TO => '2026-06-30',
		]);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_AGENCY_ID => $agencyId,
			TurnusTableMap::COL_STATUS => 30,
		]);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_AGENCY_ID => $agencyId,
			TurnusTableMap::COL_STATUS => 1,
			TurnusTableMap::COL_DELETED => 1,
		]);

		$pageCount = 0;
		$totalCount = 0;
		$rows = $repository->findTurnusRows(1, 10, 0, 1, 2, $agencyId, 0, $pageCount, $totalCount);

		self::assertSame(1, $pageCount);
		self::assertSame(1, $totalCount);
		self::assertCount(1, $rows);
		self::assertSame($matchingId, $rows[0]['id']);
		self::assertSame('Maria Turnusova', $rows[0]['familyName']);
		self::assertSame('Anna Care', $rows[0]['babysitterName']);
		self::assertSame('Filter turnus agency', $rows[0]['agencyName']);
		self::assertSame('A4', $rows[0]['userAcronym']);
		self::assertSame('Aktívny', $rows[0]['status']);
		self::assertSame('Vybavené', $repository->findStatusA1SelectOptions()[1]);
		self::assertSame('Aktívny', $repository->findStatusOptions()[1]);
		self::assertSame('Filter turnus agency', $repository->findAgencyFilterOptions(0, 1, 0, 2)[$agencyId]);
		self::assertSame('Rakúsko', $repository->findCountryFilterOptions(0, 1, 0, $agencyId)[2]);
	}

	public function testFindForMonthReturnsOnlyAustriaTurnusOverlappingMonth(): void
	{
		$repository = $this->repository();
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-MONTH',
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Monthly',
			FamilyTableMap::COL_STATE => 2,
		]);
		$excludedFamilyId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-SK',
			FamilyTableMap::COL_NAME => 'Zuzana',
			FamilyTableMap::COL_SURNAME => 'Outside',
			FamilyTableMap::COL_STATE => 1,
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-AG4-MONTH',
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Monthly',
		]);
		$matchingId = TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_STATUS => 1,
			TurnusTableMap::COL_DATE_FROM => '2026-05-20',
			TurnusTableMap::COL_DATE_TO => '2026-06-10',
			TurnusTableMap::COL_FEE => 1200.0,
			TurnusTableMap::COL_TRAVEL_COSTS_ARRIVAL => 15.0,
			TurnusTableMap::COL_TRAVEL_COSTS_DEPARTURE => 20.0,
			TurnusTableMap::COL_HOLIDAY => 2.0,
		]);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_DATE_FROM => '2026-07-01',
			TurnusTableMap::COL_DATE_TO => '2026-07-31',
		]);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $excludedFamilyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_DATE_FROM => '2026-06-05',
			TurnusTableMap::COL_DATE_TO => '2026-06-15',
		]);

		$rows = $repository->findForMonth(2026, 6);

		self::assertCount(1, $rows);
		self::assertSame($matchingId, $rows[0]['id']);
		self::assertSame('Maria Monthly', $rows[0]['familyName']);
		self::assertSame('Anna Monthly', $rows[0]['babysitterName']);
		self::assertSame('Aktívny', $rows[0]['status']);
		self::assertSame('1200', $rows[0]['fee']);
		self::assertSame('15', $rows[0]['travelCostsArrival']);
		self::assertSame('20', $rows[0]['travelCostsDeparture']);
		self::assertSame('2', $rows[0]['holiday']);
	}

	public function testUpdateStatusA1SoftDeleteAndSelectOptions(): void
	{
		$repository = $this->repository();
		$turnusId = TestDatabase::createTurnus();

		$repository->updateStatusA1($turnusId, 1);
		$repository->softDelete($turnusId);

		$row = $this->getDatabase()->table(TurnusTableMap::TABLE_NAME)->get($turnusId);

		self::assertNotNull($row);
		self::assertSame(1, (int) $row->{TurnusTableMap::COL_STATUS_A1});
		self::assertSame(1, (int) $row->{TurnusTableMap::COL_DELETED});
		self::assertSame('Vybavené', $this->getDatabase()->table(StatusDocumentA1TableMap::TABLE_NAME)->get(1)?->{StatusDocumentA1TableMap::COL_STATUS});
		self::assertSame('Mesačne', $repository->findPaymentPeriodSelectOptions()[1]);
		self::assertSame('Opatrovanie', $repository->findWorkPositionSelectOptions()[1]);
	}

	private function repository(): TurnusRepository
	{
		return $this->getContainer()->getByType(TurnusRepository::class);
	}
}
