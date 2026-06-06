<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Repository\SearchRepository;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\PartnerTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class SearchRepositoryTest extends DatabaseTestCase
{
	public function testSearchReturnsEmptyForShortTermsAndUnknownTypes(): void
	{
		$repository = $this->repository();
		TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_SURNAME => 'Vyhladavana']);

		self::assertSame([], $repository->search(SearchRepository::TYPE_BABYSITTER, 'ab'));
		self::assertSame([], $repository->search(SearchRepository::TYPE_BABYSITTER, '   '));
		self::assertSame([], $repository->search(999, 'Vyhladavana'));
	}

	public function testSearchBabysittersMapsJoinedRowAndResolvesAge(): void
	{
		$agencyId = TestDatabase::createAgency([AgencyTableMap::COL_NAME => 'Agentura Vyhlad']);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-SEARCH',
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Vyhladavana',
			OpatrovatelkaTableMap::COL_POHLAVIE => 1,
			OpatrovatelkaTableMap::COL_COUNTRY => 1,
			OpatrovatelkaTableMap::COL_AGENCY_ID => $agencyId,
			OpatrovatelkaTableMap::COL_STATUS => 1,
		]);

		$rows = $this->repository()->search(SearchRepository::TYPE_BABYSITTER, 'Vyhlad');

		self::assertCount(1, $rows);
		self::assertSame($babysitterId, $rows[0]['id']);
		self::assertSame('B-SEARCH', $rows[0]['clientNumber']);
		self::assertSame('Anna', $rows[0]['name']);
		self::assertSame('Vyhladavana', $rows[0]['surname']);
		self::assertSame('-', $rows[0]['age']);
		self::assertSame(1, $rows[0]['genderId']);
		self::assertSame('Žena', $rows[0]['genderLabel']);
		self::assertSame(1, $rows[0]['countryId']);
		self::assertSame($agencyId, $rows[0]['agencyId']);
		self::assertSame('Agentura Vyhlad', $rows[0]['agencyName']);
		self::assertSame(1, $rows[0]['statusId']);
		self::assertSame('Aktívna', $rows[0]['statusLabel']);
		self::assertSame('#198754', $rows[0]['statusColor']);
	}

	public function testSearchFamiliesByNameAndByContactPerson(): void
	{
		$familyId = TestDatabase::createFamily([
			FamilyTableMap::COL_CLIENT_NUMBER => 'F-SRCH',
			FamilyTableMap::COL_NAME => 'Maria',
			FamilyTableMap::COL_SURNAME => 'Hladana',
			FamilyTableMap::COL_PERSON_NAME => 'Jan',
			FamilyTableMap::COL_PERSON_SURNAME => 'Kontaktova',
			FamilyTableMap::COL_PERSON_EMAIL => 'jan.kontaktova@example.test',
		]);

		$byName = $this->repository()->search(SearchRepository::TYPE_FAMILY, 'Hladana');
		$byContact = $this->repository()->search(SearchRepository::TYPE_FAMILY_CONTACT, 'Kontaktova');

		self::assertCount(1, $byName);
		self::assertSame($familyId, $byName[0]['id']);
		self::assertSame('Hladana', $byName[0]['surname']);
		self::assertSame('jan.kontaktova@example.test', $byName[0]['personEmail']);

		self::assertCount(1, $byContact);
		self::assertSame($familyId, $byContact[0]['id']);
		self::assertSame('Kontaktova', $byContact[0]['personSurname']);
	}

	public function testSearchCompaniesTagsPartnersAndAgencies(): void
	{
		TestDatabase::createPartner([PartnerTableMap::COL_NAME => 'Partner Hladany']);
		TestDatabase::createAgency([AgencyTableMap::COL_NAME => 'Agentura Hladana']);

		$partners = $this->repository()->search(SearchRepository::TYPE_PARTNER, 'Hladany');
		$agencies = $this->repository()->search(SearchRepository::TYPE_AGENCY, 'Hladana');

		self::assertCount(1, $partners);
		self::assertSame('Partner Hladany', $partners[0]['name']);
		self::assertSame('partner', $partners[0]['type']);

		self::assertCount(1, $agencies);
		self::assertSame('Agentura Hladana', $agencies[0]['name']);
		self::assertSame('agency', $agencies[0]['type']);
	}

	private function repository(): SearchRepository
	{
		return $this->getContainer()->getByType(SearchRepository::class);
	}
}
