<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Form\DTO\Admin\Family\FamilyAddress\FamilyAddressForm;
use App\Model\Form\DTO\Admin\Family\FamilyInfo\FamilyInfoForm;
use App\Model\Form\DTO\Admin\Family\FamilyShortInfo\FamilyShortInfoForm;
use App\Model\Repository\FamilyRepository;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\PartnerTableMap;
use App\Model\Table\TurnusTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class FamilyRepositoryTest extends DatabaseTestCase
{
	public function testCreateEmptyFamilyAndProjectSetExpectedTypes(): void
	{
		$repository = $this->repository();

		$familyId = $repository->createEmptyFamily();
		$projectId = $repository->createEmptyProject();

		$family = $this->getDatabase()->table(FamilyTableMap::TABLE_NAME)->get($familyId);
		$project = $this->getDatabase()->table(FamilyTableMap::TABLE_NAME)->get($projectId);

		self::assertNotNull($family);
		self::assertNotNull($project);
		self::assertSame(1, (int) $family->{FamilyTableMap::COL_TYPE});
		self::assertSame(2, (int) $project->{FamilyTableMap::COL_TYPE});
		self::assertSame(0, (int) $family->{FamilyTableMap::COL_DELETED});
		self::assertSame(0, (int) $project->{FamilyTableMap::COL_DELETED});
	}

	public function testUpdateFromFormDtosPersistsFamilyAndProjectFields(): void
	{
		$repository = $this->repository();
		$userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'agent4.family@example.test',
		]);
		$partnerId = TestDatabase::createPartner([
			PartnerTableMap::COL_NAME => 'Agent 4 partner',
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-001',
		]);

		$repository->updateInfoFromForm(new FamilyInfoForm(
			$familyId,
			2,
			$partnerId,
			$userId,
			$userId,
			1,
			'+431111111',
			new \DateTimeImmutable('2026-06-01'),
			new \DateTimeImmutable('2026-12-31'),
			1,
			1,
			1,
			'Project description',
			'2 positions',
			'1 available',
		));
		$repository->updateAddressFromForm(new FamilyAddressForm(
			$familyId,
			'Agent 4 GmbH',
			'Maria',
			'Familia',
			'Hauptstrasse',
			'10',
			'1010',
			'Wien',
			'Billing text',
			'Employer text',
			'Accommodation address',
			'Notice text',
			'Kontaktova',
			'Eva',
			'+431222222',
			'eva.kontaktova@example.test',
			'+431333333',
		));
		$repository->updateShortInfoFromForm(new FamilyShortInfoForm(
			$familyId,
			'F-AG4-001',
			'DE-AG4-77',
			2,
		));

		$row = $this->getDatabase()->table(FamilyTableMap::TABLE_NAME)->get($familyId);

		self::assertNotNull($row);
		self::assertSame(2, (int) $row->{FamilyTableMap::COL_TYPE});
		self::assertSame($partnerId, (int) $row->{FamilyTableMap::COL_PARTNER_ID});
		self::assertSame($userId, (int) $row->{FamilyTableMap::COL_USER_ID});
		self::assertSame('+431111111', $row->{FamilyTableMap::COL_PHONE});
		self::assertSame('2026-06-01', $row->{FamilyTableMap::COL_DATE_START}->format('Y-m-d'));
		self::assertSame('2026-12-31', $row->{FamilyTableMap::COL_DATE_TO}->format('Y-m-d'));
		self::assertSame('Agent 4 GmbH', $row->{FamilyTableMap::COL_COMPANY_NAME});
		self::assertSame('Maria', $row->{FamilyTableMap::COL_NAME});
		self::assertSame('Familia', $row->{FamilyTableMap::COL_SURNAME});
		self::assertSame('Wien', $row->{FamilyTableMap::COL_CITY});
		self::assertSame('DE-AG4-77', $row->{FamilyTableMap::COL_DE_PROJECT_NUMBER});
		self::assertSame(2, (int) $row->{FamilyTableMap::COL_STATE});
		self::assertSame('Project description', $row->{FamilyTableMap::COL_PROJECT_DESCRIPTION});
	}

	public function testFindFamilyRowsAppliesTypeFiltersAndJoins(): void
	{
		$repository = $this->repository();
		$userId = TestDatabase::createUser([
			UserTableMap::COL_NAME => 'Agent',
			UserTableMap::COL_SECOND_NAME => 'Four',
			UserTableMap::COL_ACRONYM => 'A4',
			UserTableMap::COL_EMAIL => 'agent4.family.filter@example.test',
			UserTableMap::COL_COLOR => '#123456',
		]);
		$partnerId = TestDatabase::createPartner([
			PartnerTableMap::COL_NAME => 'Filter partner',
		]);
		$matchingId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-101',
			FamilyTableMap::COL_NAME => 'Greta',
			FamilyTableMap::COL_SURNAME => 'Kaiser',
			FamilyTableMap::COL_STATE => 2,
			FamilyTableMap::COL_STATUS => 1,
			FamilyTableMap::COL_PARTNER_ID => $partnerId,
			FamilyTableMap::COL_CITY => 'Wien',
			FamilyTableMap::COL_USER_ID => $userId,
			FamilyTableMap::COL_PERSON_EMAIL => 'greta.kaiser@example.test',
			FamilyTableMap::COL_TYPE => 1,
		]);
		TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-102',
			FamilyTableMap::COL_SURNAME => 'Novak',
			FamilyTableMap::COL_STATE => 2,
			FamilyTableMap::COL_CITY => 'Wien',
			FamilyTableMap::COL_TYPE => 1,
		]);
		TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'P-AG4-103',
			FamilyTableMap::COL_SURNAME => 'Kaiser',
			FamilyTableMap::COL_STATE => 2,
			FamilyTableMap::COL_CITY => 'Wien',
			FamilyTableMap::COL_TYPE => 2,
		]);
		TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-104',
			FamilyTableMap::COL_SURNAME => 'Kovac',
			FamilyTableMap::COL_STATE => 2,
			FamilyTableMap::COL_CITY => 'Wien',
			FamilyTableMap::COL_TYPE => 1,
			FamilyTableMap::COL_DELETED => 1,
		]);

		$pageCount = 0;
		$totalCount = 0;
		$rows = $repository->findFamilyRows(1, 10, 2, 1, $partnerId, 'K', 'Wien', $userId, $pageCount, $totalCount);

		self::assertSame(1, $pageCount);
		self::assertSame(1, $totalCount);
		self::assertCount(1, $rows);
		self::assertSame($matchingId, $rows[0]['id']);
		self::assertSame('Greta', $rows[0]['name']);
		self::assertSame('Kaiser', $rows[0]['surname']);
		self::assertSame('Filter partner', $rows[0]['partnerName']);
		self::assertSame('A4', $rows[0]['userAcronym']);
		self::assertSame('#123456', $rows[0]['userColor']);
		self::assertSame('Aktívna', $rows[0]['statusLabel']);
	}

	public function testFindOptionsAndTurnusRowsForFamily(): void
	{
		$repository = $this->repository();
		$partnerId = TestDatabase::createPartner([
			PartnerTableMap::COL_NAME => 'AAA Family partner',
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-AG4-201',
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Turnusova',
			FamilyTableMap::COL_STATE => 2,
			FamilyTableMap::COL_PARTNER_ID => $partnerId,
			FamilyTableMap::COL_CITY => 'Graz',
			FamilyTableMap::COL_DE_PROJECT_NUMBER => 'DE-TURNUS',
		]);
		$babysitterId = TestDatabase::createBabysitter([
			'client_number' => 'B-AG4-201',
			'name' => 'Anna',
			'surname' => 'Care',
		]);
		$turnusId = TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_STATUS => 1,
			TurnusTableMap::COL_INVOICE_STATUS => 1,
			TurnusTableMap::COL_WORK_POSITION_ID => 1,
			TurnusTableMap::COL_DATE_FROM => '2026-06-10',
			TurnusTableMap::COL_DATE_TO => '2026-06-20',
		]);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_DELETED => 1,
		]);

		$turnusRows = $repository->findTurnusRowsForFamily($familyId);

		self::assertSame('AAA Family partner', $repository->findPartnerSelectOptions()[$partnerId]);
		self::assertSame('Rakúsko', $repository->findCountrySelectOptions()[2]);
		self::assertSame('Aktívna', $repository->findStatusSelectOptions()[1]);
		self::assertSame(['Graz'], $repository->findCityOptions());
		self::assertCount(1, $turnusRows);
		self::assertSame($turnusId, $turnusRows[0]['id']);
		self::assertSame('Maria Turnusova', $turnusRows[0]['familyName']);
		self::assertSame('Anna Care', $turnusRows[0]['babysitterName']);
		self::assertSame('DE-TURNUS', $turnusRows[0]['familyProjectNumber']);
		self::assertSame('Opatrovanie', $turnusRows[0]['workPosition']);
		self::assertFalse($turnusRows[0]['isInvoiceUnpaid']);
	}

	private function repository(): FamilyRepository
	{
		return $this->getContainer()->getByType(FamilyRepository::class);
	}
}
