<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Form\DTO\Admin\Agency\AgencyUpdate\AgencyUpdateForm;
use App\Model\Repository\AgencyRepository;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\TurnusTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class AgencyRepositoryTest extends DatabaseTestCase
{
	public function testAgencyRepositoryCreatesUpdatesFiltersAndFindsRelatedRows(): void
	{
		$repository = $this->getContainer()->getByType(AgencyRepository::class);
		$agencyId = $repository->createEmptyAgency();

		$repository->updateFromForm(new AgencyUpdateForm(
			$agencyId,
			'Agent 5 agency',
			'Hlavna',
			'12',
			'81101',
			'Bratislava',
			2,
			new \DateTimeImmutable('2026-01-15'),
			'Kontaktova',
			'Eva',
			'12345678',
			'SK12345678',
			'example.test',
			'+421900111222',
			'<b>agency@example.test</b>',
			1,
			'Agency notice',
		));
		TestDatabase::createAgency([
			AgencyTableMap::COL_NAME => 'Inactive agency',
			AgencyTableMap::COL_STATE => 2,
			AgencyTableMap::COL_STATUS => 1,
			AgencyTableMap::COL_ACTIVE => 0,
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Agency',
			OpatrovatelkaTableMap::COL_AGENCY_ID => $agencyId,
			OpatrovatelkaTableMap::COL_COUNTRY => 2,
		]);
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Agency',
			FamilyTableMap::COL_STATE => 2,
		]);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_AGENCY_ID => $agencyId,
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_STATUS => 1,
		]);
		TestDatabase::createTurnus([
			TurnusTableMap::COL_AGENCY_ID => $agencyId,
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_STATUS => 30,
		]);

		$updateRow = $repository->findUpdateRow($agencyId);
		$rows = $repository->findAgencyRows(2, 1);
		$babysitters = $repository->findBabysittersForAgency($agencyId);
		$families = $repository->findFamiliesForAgency($agencyId);

		self::assertNotNull($updateRow);
		self::assertSame('Agent 5 agency', $updateRow['name']);
		self::assertSame('2026-01-15', $updateRow['dateStart']->format('Y-m-d'));
		self::assertSame('Agency notice', $updateRow['notice']);
		self::assertCount(1, $rows);
		self::assertSame($agencyId, $rows[0]['id']);
		self::assertSame('http://example.test', $rows[0]['websiteUrl']);
		self::assertSame('agency@example.test', $rows[0]['emailLabel']);
		self::assertSame('Rakúsko', $repository->findCountrySelectOptions()[2]);
		self::assertSame('Aktívny', $repository->findStatusSelectOptions()[1]);
		self::assertCount(1, $babysitters);
		self::assertSame($babysitterId, $babysitters[0]['id']);
		self::assertSame('Anna', $babysitters[0]['name']);
		self::assertCount(1, $families);
		self::assertSame($familyId, $families[0]['id']);
		self::assertSame('Agency', $families[0]['surname']);
	}
}
