<?php declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Model\Enum\Turnus\TurnusStatus;
use App\Model\Repository\PartnerFamilyRepository;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TurnusTableMap;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class PartnerFamilyRepositoryTest extends DatabaseTestCase
{
	public function testFindFamiliesReturnsOnlyNonDeletedFamiliesOfPartner(): void
	{
		$partnerId = TestDatabase::createPartner();
		$otherPartnerId = TestDatabase::createPartner(['name' => 'Other']);
		$zetaId = TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Zeta', FamilyTableMap::COL_PARTNER_ID => $partnerId, FamilyTableMap::COL_STATE => 2]);
		$alfaId = TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Alfa', FamilyTableMap::COL_PARTNER_ID => $partnerId]);
		TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Deleted', FamilyTableMap::COL_PARTNER_ID => $partnerId, FamilyTableMap::COL_DELETED => 1]);
		TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Foreign', FamilyTableMap::COL_PARTNER_ID => $otherPartnerId]);

		$families = $this->getRepository()->findFamilies($partnerId);

		self::assertSame([$alfaId, $zetaId], array_column($families, 'id'));
		self::assertSame('Zeta', $families[1]['surname']);
		self::assertSame('Maria', $families[1]['name']);
		self::assertSame('Aktívna', $families[1]['statusLabel']);
	}


	public function testFindFamilyNameChecksOwnership(): void
	{
		$partnerId = TestDatabase::createPartner();
		$otherPartnerId = TestDatabase::createPartner();
		$familyId = TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Novakova', FamilyTableMap::COL_NAME => 'Jana', FamilyTableMap::COL_PARTNER_ID => $partnerId]);
		$deletedId = TestDatabase::createFamily([FamilyTableMap::COL_PARTNER_ID => $partnerId, FamilyTableMap::COL_DELETED => 1]);

		$repository = $this->getRepository();

		self::assertSame('Novakova Jana', $repository->findFamilyName($partnerId, $familyId));
		self::assertNull($repository->findFamilyName($otherPartnerId, $familyId));
		self::assertNull($repository->findFamilyName($partnerId, $deletedId));
		self::assertNull($repository->findFamilyName($partnerId, 999999));
	}


	public function testFindTurnusRowsFiltersAndMapsCareRecords(): void
	{
		TestDatabase::insert(StatusTurnusTableMap::TABLE_NAME, ['id' => TurnusStatus::STORNO, 'status' => ' Storno', 'color' => '#dc3545']);
		$partnerId = TestDatabase::createPartner();
		$otherPartnerId = TestDatabase::createPartner();
		$familyId = TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Rodina', FamilyTableMap::COL_NAME => 'Prva', FamilyTableMap::COL_PARTNER_ID => $partnerId]);
		$secondFamilyId = TestDatabase::createFamily([FamilyTableMap::COL_PARTNER_ID => $partnerId]);
		$deletedFamilyId = TestDatabase::createFamily([FamilyTableMap::COL_PARTNER_ID => $partnerId, FamilyTableMap::COL_DELETED => 1]);
		$foreignFamilyId = TestDatabase::createFamily([FamilyTableMap::COL_PARTNER_ID => $otherPartnerId]);
		$babysitterId = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Eva', OpatrovatelkaTableMap::COL_SURNAME => 'Mala']);

		$stornoId = TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_STATUS => TurnusStatus::STORNO,
			TurnusTableMap::COL_DATE_FROM => '2026-05-01',
			TurnusTableMap::COL_DATE_TO => '2026-05-31',
		]);
		$openId = TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_DATE_FROM => null,
			TurnusTableMap::COL_DATE_TO => null,
		]);
		$secondFamilyTurnusId = TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $secondFamilyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_DATE_FROM => '2026-04-01',
			TurnusTableMap::COL_DATE_TO => '2026-04-30',
		]);
		// excluded rows
		TestDatabase::createTurnus([TurnusTableMap::COL_FAMILY_ID => $familyId, TurnusTableMap::COL_BABYSITTER_ID => $babysitterId, TurnusTableMap::COL_DELETED => 1]);
		TestDatabase::createTurnus([TurnusTableMap::COL_FAMILY_ID => $familyId, TurnusTableMap::COL_BABYSITTER_ID => 0]);
		TestDatabase::createTurnus([TurnusTableMap::COL_FAMILY_ID => $deletedFamilyId, TurnusTableMap::COL_BABYSITTER_ID => $babysitterId]);
		TestDatabase::createTurnus([TurnusTableMap::COL_FAMILY_ID => $foreignFamilyId, TurnusTableMap::COL_BABYSITTER_ID => $babysitterId]);

		$repository = $this->getRepository();
		$rows = $repository->findTurnusRows($partnerId);
		$familyRows = $repository->findTurnusRows($partnerId, $familyId);

		self::assertEqualsCanonicalizing([$stornoId, $openId, $secondFamilyTurnusId], array_column($rows, 'id'));
		self::assertEqualsCanonicalizing([$stornoId, $openId], array_column($familyRows, 'id'));

		$storno = array_values(array_filter($rows, static fn (array $row): bool => $row['id'] === $stornoId))[0];
		self::assertSame($familyId, $storno['familyId']);
		self::assertSame('Rodina Prva', $storno['familyName']);
		self::assertSame($babysitterId, $storno['babysitterId']);
		self::assertSame('Eva Mala', $storno['babysitterName']);
		self::assertSame(TurnusStatus::STORNO, $storno['statusId']);
		self::assertSame('Storno', $storno['statusLabel']);
		self::assertSame('#dc3545', $storno['statusColor']);
		self::assertSame('2026-05-01', $storno['dateFrom']?->format('Y-m-d'));
		self::assertSame('2026-05-31', $storno['dateTo']?->format('Y-m-d'));

		$open = array_values(array_filter($rows, static fn (array $row): bool => $row['id'] === $openId))[0];
		self::assertNull($open['dateFrom']);
		self::assertNull($open['dateTo']);
	}


	private function getRepository(): PartnerFamilyRepository
	{
		return $this->getContainer()->getByType(PartnerFamilyRepository::class);
	}
}
