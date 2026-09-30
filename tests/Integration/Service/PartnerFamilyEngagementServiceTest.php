<?php declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Model\Enum\Partner\EngagementType;
use App\Model\Enum\Turnus\TurnusStatus;
use App\Model\Repository\PartnerFamilyRepository;
use App\Model\Service\Partner\PartnerEngagementCalculator;
use App\Model\Service\Partner\PartnerFamilyEngagementService;
use App\Model\Table\FamilyTableMap;
use App\Model\Table\OpatrovatelkaTableMap;
use App\Model\Table\StatusTurnusTableMap;
use App\Model\Table\TurnusTableMap;
use DateTimeImmutable;
use Mockery;
use Psr\Clock\ClockInterface;
use RuntimeException;
use Tests\Support\Database\TestDatabase;
use Tests\Support\PHPUnit\DatabaseTestCase;

final class PartnerFamilyEngagementServiceTest extends DatabaseTestCase
{
	public function testServiceIsRegisteredInContainer(): void
	{
		self::assertInstanceOf(
			PartnerFamilyEngagementService::class,
			$this->getContainer()->getByType(PartnerFamilyEngagementService::class),
		);
	}


	public function testFamilyOverviewAndDashboardFromDatabase(): void
	{
		TestDatabase::insert(StatusTurnusTableMap::TABLE_NAME, ['id' => TurnusStatus::FINISHED, 'status' => 'Vybavený', 'color' => '#6c757d']);
		TestDatabase::insert(StatusTurnusTableMap::TABLE_NAME, ['id' => TurnusStatus::STORNO, 'status' => 'Storno', 'color' => '#dc3545']);

		$partnerId = TestDatabase::createPartner();
		$familyId = TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Alfa', FamilyTableMap::COL_PARTNER_ID => $partnerId]);
		$quietFamilyId = TestDatabase::createFamily([FamilyTableMap::COL_SURNAME => 'Beta', FamilyTableMap::COL_PARTNER_ID => $partnerId]);
		$active = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Aktivna']);
		$finished = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Ukoncena']);
		$storno = TestDatabase::createBabysitter([OpatrovatelkaTableMap::COL_NAME => 'Stornovana']);

		// running turnus -> active
		$this->createTurnus($familyId, $active, 1, '2026-06-01', '2026-06-30');
		// finished turnus in the past + finished status
		$this->createTurnus($familyId, $finished, 4, '2026-03-01', '2026-03-31');
		$this->createTurnus($familyId, $finished, TurnusStatus::FINISHED, '2026-04-01', '2026-04-30');
		// storno only -> not counted
		$this->createTurnus($familyId, $storno, TurnusStatus::STORNO, '2026-07-01', '2026-07-31');
		// the quiet family has only a storno of the active babysitter
		$this->createTurnus($quietFamilyId, $active, TurnusStatus::STORNO, '2026-08-01', '2026-08-31');

		$service = $this->createService(new DateTimeImmutable('2026-06-15 10:30:00'));

		$overview = $service->getFamilyOverview($partnerId);
		self::assertSame([$familyId, $quietFamilyId], array_map(static fn ($row): int => $row->id, $overview->families));
		self::assertSame([2, 1, 1], [$overview->families[0]->counts->total, $overview->families[0]->counts->active, $overview->families[0]->counts->finished]);
		self::assertSame([0, 0, 0], [$overview->families[1]->counts->total, $overview->families[1]->counts->active, $overview->families[1]->counts->finished]);
		self::assertSame([2, 1, 1], [$overview->summary->total, $overview->summary->active, $overview->summary->finished]);

		$dashboard = $service->getBabysitterDashboard($partnerId, EngagementType::FINISHED, $familyId);
		self::assertCount(1, $dashboard->babysitters);
		self::assertSame($finished, $dashboard->babysitters[0]->babysitterId);
		self::assertSame(2, $dashboard->countedTurnusCount);
		self::assertSame(61, $dashboard->workedDays);

		$activeDashboard = $service->getBabysitterDashboard($partnerId, EngagementType::ACTIVE);
		self::assertCount(1, $activeDashboard->babysitters);
		self::assertSame($active, $activeDashboard->babysitters[0]->babysitterId);
		self::assertSame(1, $activeDashboard->stornoTurnusCount);
		self::assertSame(15, $activeDashboard->workedDays);

		self::assertSame('Alfa Maria', $service->findFamilyName($partnerId, $familyId));
		self::assertNull($service->findFamilyName($partnerId + 1000, $familyId));
	}


	private function createTurnus(int $familyId, int $babysitterId, int $status, string $dateFrom, string $dateTo): void
	{
		TestDatabase::createTurnus([
			TurnusTableMap::COL_FAMILY_ID => $familyId,
			TurnusTableMap::COL_BABYSITTER_ID => $babysitterId,
			TurnusTableMap::COL_STATUS => $status,
			TurnusTableMap::COL_DATE_FROM => $dateFrom,
			TurnusTableMap::COL_DATE_TO => $dateTo,
		]);
	}


	private function createService(DateTimeImmutable $now): PartnerFamilyEngagementService
	{
		$clock = Mockery::mock(ClockInterface::class, ['now' => $now]);
		if (!$clock instanceof ClockInterface) {
			throw new RuntimeException('Clock mock must implement ClockInterface.');
		}

		return new PartnerFamilyEngagementService(
			$this->getContainer()->getByType(PartnerFamilyRepository::class),
			$this->getContainer()->getByType(PartnerEngagementCalculator::class),
			$clock,
		);
	}
}
