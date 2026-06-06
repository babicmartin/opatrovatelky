<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Form\DTO\Admin\Babysitter\BabysitterAddress\BabysitterAddressForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterMain\BabysitterMainForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterProfile\BabysitterProfileForm;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterWorkProfile\BabysitterWorkProfileForm;
use App\Model\Repository\BabysitterRepository;
use App\Model\Table\AgencyTableMap;
use App\Model\Table\BabysitterDiseaseTableMap;
use App\Model\Table\BabysitterPositionPreferenceTableMap;
use App\Model\Table\BabysitterQualificationTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\UserTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class BabysitterRepositoryTest extends DatabaseTestCase
{
	public function testCreateEmptyBabysitterAndWorkerSetTypeActiveAndClientNumber(): void
	{
		$repository = $this->repository();

		$babysitterId = $repository->createEmptyBabysitter();
		$workerId = $repository->createEmptyWorker();

		$babysitter = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($babysitterId);
		$worker = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($workerId);

		self::assertNotNull($babysitter);
		self::assertNotNull($worker);
		self::assertSame(1, (int) $babysitter->{OpatrovatelkaTableMap::COL_TYPE});
		self::assertSame(2, (int) $worker->{OpatrovatelkaTableMap::COL_TYPE});
		self::assertSame(1, (int) $babysitter->{OpatrovatelkaTableMap::COL_ACTIVE});
		self::assertSame(1, (int) $worker->{OpatrovatelkaTableMap::COL_ACTIVE});
		self::assertSame('OP' . date('y') . '001', $babysitter->{OpatrovatelkaTableMap::COL_CLIENT_NUMBER});
		self::assertSame('OP' . date('y') . '002', $worker->{OpatrovatelkaTableMap::COL_CLIENT_NUMBER});
	}

	public function testUpdateFromFormDtosPersistsScalarAndJunctionValues(): void
	{
		$repository = $this->repository();
		$userId = TestDatabase::createUser([
			UserTableMap::COL_EMAIL => 'agent4.babysitter@example.test',
		]);
		$agencyId = TestDatabase::createAgency([
			AgencyTableMap::COL_NAME => 'Agent 4 agency',
		]);
		$babysitterId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-AG4-001',
		]);

		$repository->updateMainFromForm(new BabysitterMainForm(
			$babysitterId,
			1,
			$agencyId,
			1,
			1,
			$userId,
			2,
			'Agent 4 notice',
		));
		$repository->updateAddressFromForm(new BabysitterAddressForm(
			$babysitterId,
			'Klara',
			'Novakova',
			new \DateTimeImmutable('1981-02-03'),
			1,
			2,
			'Wien',
			'Ringstrasse',
			'1010',
			'+421900111222',
			'+4312345',
			'klara.novakova@example.test',
			'172',
			'65',
			'About Klara',
			'Requirements text',
			'Contact Person',
			'+421900333444',
		));
		$repository->updateProfileFromForm(new BabysitterProfileForm(
			$babysitterId,
			1,
			1,
			2,
			'pollen',
			'5 years',
			'5 Jahre',
			2,
			1,
			0,
			'full time',
			'Vienna',
			'',
			'care work',
			'housekeeping',
			'excellent',
			0,
			'',
			'',
			[2, 1, 2],
		));
		$repository->updateWorkProfileFromForm(new BabysitterWorkProfileForm(
			$babysitterId,
			[2, 1, 2],
			[1],
		));

		$row = $this->getDatabase()->table(OpatrovatelkaTableMap::TABLE_NAME)->get($babysitterId);

		self::assertNotNull($row);
		self::assertSame('Klara', $row->{OpatrovatelkaTableMap::COL_NAME});
		self::assertSame('Novakova', $row->{OpatrovatelkaTableMap::COL_SURNAME});
		self::assertSame('1981-02-03', $row->{OpatrovatelkaTableMap::COL_BIRTHDAY}->format('Y-m-d'));
		self::assertSame($agencyId, (int) $row->{OpatrovatelkaTableMap::COL_AGENCY_ID});
		self::assertSame($userId, (int) $row->{OpatrovatelkaTableMap::COL_FIRST_CONTACT_USER_ID});
		self::assertSame(2, (int) $row->{OpatrovatelkaTableMap::COL_BLACKLIST});
		self::assertSame('Agent 4 notice', $row->{OpatrovatelkaTableMap::COL_NOTICE});
		self::assertSame(2, (int) $row->{OpatrovatelkaTableMap::COL_DAILY_CARE});
		self::assertSame(1, (int) $row->{OpatrovatelkaTableMap::COL_HOURLY_CARE});
		self::assertSame([2, 1], $repository->findDiseaseIds($babysitterId));
		self::assertSame([2, 1], $repository->findQualificationIds($babysitterId));
		self::assertSame([1], $repository->findPreferenceIds($babysitterId));
		self::assertSame(2, $this->getDatabase()->table(BabysitterDiseaseTableMap::TABLE_NAME)->where(BabysitterDiseaseTableMap::COL_BABYSITTER_ID, $babysitterId)->count('*'));
		self::assertSame(2, $this->getDatabase()->table(BabysitterQualificationTableMap::TABLE_NAME)->where(BabysitterQualificationTableMap::COL_BABYSITTER_ID, $babysitterId)->count('*'));
		self::assertSame(1, $this->getDatabase()->table(BabysitterPositionPreferenceTableMap::TABLE_NAME)->where(BabysitterPositionPreferenceTableMap::COL_BABYSITTER_ID, $babysitterId)->count('*'));
	}

	public function testFindBabysitterRowsAppliesTypeAndFiltersWithPagination(): void
	{
		$repository = $this->repository();
		$agencyId = TestDatabase::createAgency([
			AgencyTableMap::COL_NAME => 'Filter agency',
		]);
		$matchingId = TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-AG4-101',
			OpatrovatelkaTableMap::COL_NAME => 'Anna',
			OpatrovatelkaTableMap::COL_SURNAME => 'Kovacova',
			OpatrovatelkaTableMap::COL_COUNTRY => 2,
			OpatrovatelkaTableMap::COL_LANGUAGE_SKILLS => 1,
			OpatrovatelkaTableMap::COL_WORKING_STATUS => 1,
			OpatrovatelkaTableMap::COL_POHLAVIE => 1,
			OpatrovatelkaTableMap::COL_DRIVING_LICENCE => 1,
			OpatrovatelkaTableMap::COL_SMOKER => 1,
			OpatrovatelkaTableMap::COL_AGENCY_ID => $agencyId,
			OpatrovatelkaTableMap::COL_STATUS => 1,
			OpatrovatelkaTableMap::COL_BIRTHDAY => '1985-05-06',
		]);
		TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'B-AG4-102',
			OpatrovatelkaTableMap::COL_SURNAME => 'Novakova',
			OpatrovatelkaTableMap::COL_COUNTRY => 2,
		]);
		TestDatabase::createBabysitter([
			OpatrovatelkaTableMap::COL_CLIENT_NUMBER => 'W-AG4-103',
			OpatrovatelkaTableMap::COL_SURNAME => 'Kralova',
			OpatrovatelkaTableMap::COL_COUNTRY => 2,
			OpatrovatelkaTableMap::COL_TYPE => 2,
		]);

		$pageCount = 0;
		$totalCount = 0;
		$rows = $repository->findBabysitterRows(1, 10, 2, 1, 1, 1, 1, 1, $agencyId, 1, 'K', $pageCount, $totalCount);

		self::assertSame(1, $pageCount);
		self::assertSame(1, $totalCount);
		self::assertCount(1, $rows);
		self::assertSame($matchingId, $rows[0]['id']);
		self::assertSame('Anna', $rows[0]['name']);
		self::assertSame('Kovacova', $rows[0]['surname']);
		self::assertSame('Filter agency', $rows[0]['agencyName']);
		self::assertSame('Aktívna', $rows[0]['statusLabel']);
		self::assertNotNull($rows[0]['birthday']);
		self::assertIsInt($rows[0]['age']);
	}

	public function testFindSelectOptionsReturnSeededAndFixtureRows(): void
	{
		$repository = $this->repository();
		$agencyId = TestDatabase::createAgency([
			AgencyTableMap::COL_NAME => 'AAA Agent 4',
		]);

		self::assertSame('AAA Agent 4', $repository->findAgencySelectOptions()[$agencyId]);
		self::assertSame('Slovensko', $repository->findCountrySelectOptions()[1]);
		self::assertSame('Žena', $repository->findGenderSelectOptions()[1]);
		self::assertSame('Aktívna', $repository->findStatusSelectOptions()[1]);
		self::assertSame('Nemčina', $repository->findLanguageSelectOptions()[1]);
	}

	private function repository(): BabysitterRepository
	{
		return $this->getContainer()->getByType(BabysitterRepository::class);
	}
}
