<?php declare(strict_types=1);

namespace Tests\Unit\Model\Service\Partner;

use App\Model\DTO\Partner\BabysitterEngagement;
use App\Model\DTO\Partner\EngagementCounts;
use App\Model\DTO\Partner\FamilyEngagementRow;
use App\Model\Enum\Partner\EngagementType;
use App\Model\Enum\Turnus\TurnusStatus;
use App\Model\Service\Partner\PartnerEngagementCalculator;
use App\Model\Service\Partner\TurnusEngagementClassifier;
use DateTimeImmutable;
use Tests\Support\PHPUnit\TestCase;

final class PartnerEngagementCalculatorTest extends TestCase
{
	private const int FAMILY_A = 100;
	private const int FAMILY_B = 200;
	private const int FAMILY_EMPTY = 300;

	private const int ANNA = 1;
	private const int BEATA = 2;
	private const int CECILIA = 3;
	private const int DANA = 4;


	public function testFamilyOverviewCountsUniqueBabysittersWithActivePrecedence(): void
	{
		$overview = $this->createCalculator()->calculateFamilyOverview(
			[$this->family(self::FAMILY_A), $this->family(self::FAMILY_B), $this->family(self::FAMILY_EMPTY)],
			[
				// Anna: finished and planned again in family A -> active once
				$this->turnus(1, self::FAMILY_A, self::ANNA, 10, '2026-01-01', '2026-01-31'),
				$this->turnus(2, self::FAMILY_A, self::ANNA, 1, '2026-07-01', '2026-07-31'),
				// Beata: two finished turnus in family A -> finished once
				$this->turnus(3, self::FAMILY_A, self::BEATA, 4, '2026-02-01', '2026-02-28'),
				$this->turnus(4, self::FAMILY_A, self::BEATA, 4, '2026-03-01', '2026-03-31'),
				// Cecilia: storno only -> not counted anywhere
				$this->turnus(5, self::FAMILY_A, self::CECILIA, TurnusStatus::STORNO, '2026-06-01', '2026-06-30'),
				// Anna again in family B, finished there
				$this->turnus(6, self::FAMILY_B, self::ANNA, 4, '2025-05-01', '2025-05-31'),
				// unassigned babysitter is ignored
				$this->turnus(7, self::FAMILY_B, 0, 1, '2026-06-01', '2026-06-30'),
			],
			new DateTimeImmutable('2026-06-15'),
		);

		self::assertCount(3, $overview->families);
		$this->assertCounts(2, 1, 1, $this->findFamily($overview->families, self::FAMILY_A)->counts);
		$this->assertCounts(1, 0, 1, $this->findFamily($overview->families, self::FAMILY_B)->counts);
		$this->assertCounts(0, 0, 0, $this->findFamily($overview->families, self::FAMILY_EMPTY)->counts);

		// partner summary: Anna (active in A wins over finished in B) + Beata (finished)
		$this->assertCounts(2, 1, 1, $overview->summary);
	}


	public function testFamilyOverviewKeepsFamilyOrderAndData(): void
	{
		$overview = $this->createCalculator()->calculateFamilyOverview(
			[$this->family(self::FAMILY_B), $this->family(self::FAMILY_A)],
			[],
			new DateTimeImmutable('2026-06-15'),
		);

		self::assertSame([self::FAMILY_B, self::FAMILY_A], array_map(static fn (FamilyEngagementRow $row): int => $row->id, $overview->families));
		self::assertSame('Rodina 200', $overview->families[0]->surname);
		self::assertSame('at.png', $overview->families[0]->countryImage);
		self::assertSame('Aktívny', $overview->families[0]->statusLabel);
		$this->assertCounts(0, 0, 0, $overview->summary);
	}


	public function testDashboardFiltersByTypeButKeepsScopeCounts(): void
	{
		$rows = $this->dashboardRows();
		$today = new DateTimeImmutable('2026-06-15');
		$calculator = $this->createCalculator();

		$all = $calculator->calculateDashboard($rows, EngagementType::ALL, null, $today);
		$active = $calculator->calculateDashboard($rows, EngagementType::ACTIVE, null, $today);
		$finished = $calculator->calculateDashboard($rows, EngagementType::FINISHED, null, $today);

		foreach ([$all, $active, $finished] as $dashboard) {
			$this->assertCounts(3, 2, 1, $dashboard->counts);
		}

		// active first, open-ended turnus counts as the most recent
		self::assertSame([self::DANA, self::ANNA, self::BEATA], $this->babysitterIds($all->babysitters));
		self::assertSame([self::DANA, self::ANNA], $this->babysitterIds($active->babysitters));
		self::assertSame([self::BEATA], $this->babysitterIds($finished->babysitters));
		self::assertSame(EngagementType::ACTIVE, $active->type);
		self::assertNull($active->familyId);
	}


	public function testDashboardBabysitterDetail(): void
	{
		$dashboard = $this->createCalculator()->calculateDashboard(
			$this->dashboardRows(),
			EngagementType::ALL,
			null,
			new DateTimeImmutable('2026-06-15'),
		);

		$anna = $this->findBabysitter($dashboard->babysitters, self::ANNA);
		self::assertSame('Anna Testova', $anna->name);
		self::assertSame(EngagementType::ACTIVE, $anna->engagement);
		self::assertSame([self::FAMILY_A => 'Rodina 100', self::FAMILY_B => 'Rodina 200'], $anna->families);
		self::assertSame(3, $anna->countedTurnusCount);
		self::assertSame(1, $anna->stornoTurnusCount);
		self::assertSame('2025-05-01', $anna->firstDateFrom?->format('Y-m-d'));
		self::assertSame('2026-07-31', $anna->lastDateTo?->format('Y-m-d'));
		self::assertFalse($anna->hasOpenTurnus);
		// 31 (May 2025) + 31 (Jan 2026) + 0 (future) ; storno does not add days
		self::assertSame(62, $anna->workedDays);
		// storno row is still listed, newest first
		self::assertSame([2, 5, 1, 6], array_map(static fn ($turnus): int => $turnus->id, $anna->turnusRows));
		self::assertFalse($anna->turnusRows[1]->isCounted());
		self::assertSame(0, $anna->turnusRows[1]->workedDays);

		$dana = $this->findBabysitter($dashboard->babysitters, self::DANA);
		self::assertTrue($dana->hasOpenTurnus);
		self::assertNull($dana->lastDateTo);
		self::assertSame(0, $dana->workedDays);
	}


	public function testDashboardKpisFollowDisplayedBabysitters(): void
	{
		$calculator = $this->createCalculator();
		$today = new DateTimeImmutable('2026-06-15');

		$all = $calculator->calculateDashboard($this->dashboardRows(), EngagementType::ALL, null, $today);
		// Anna 3 + Beata 2 + Dana 1 counted; Anna 1 storno (Cecilia is storno-only and not listed)
		self::assertSame(6, $all->countedTurnusCount);
		self::assertSame(1, $all->stornoTurnusCount);
		// Anna 62 + Beata 28 + 31
		self::assertSame(121, $all->workedDays);
		self::assertSame(round(121 / 4, 1), $all->averageTurnusDays);

		$finished = $calculator->calculateDashboard($this->dashboardRows(), EngagementType::FINISHED, null, $today);
		self::assertSame(2, $finished->countedTurnusCount);
		self::assertSame(0, $finished->stornoTurnusCount);
		self::assertSame(59, $finished->workedDays);
		self::assertSame(29.5, $finished->averageTurnusDays);
	}


	public function testDashboardWithoutBabysitters(): void
	{
		$dashboard = $this->createCalculator()->calculateDashboard(
			[$this->turnus(1, self::FAMILY_A, self::CECILIA, TurnusStatus::STORNO, '2026-06-01', '2026-06-30')],
			EngagementType::ALL,
			self::FAMILY_A,
			new DateTimeImmutable('2026-06-15'),
		);

		self::assertSame([], $dashboard->babysitters);
		$this->assertCounts(0, 0, 0, $dashboard->counts);
		self::assertSame(0, $dashboard->stornoTurnusCount);
		self::assertSame(0.0, $dashboard->averageTurnusDays);
		self::assertSame(self::FAMILY_A, $dashboard->familyId);
	}


	public function testEngagementCounts(): void
	{
		$counts = EngagementCounts::fromEngagements([EngagementType::ACTIVE, EngagementType::FINISHED, EngagementType::ACTIVE]);

		self::assertSame(3, $counts->get(EngagementType::ALL));
		self::assertSame(2, $counts->get(EngagementType::ACTIVE));
		self::assertSame(1, $counts->get(EngagementType::FINISHED));

		$this->expectException(\InvalidArgumentException::class);
		EngagementCounts::fromEngagements([EngagementType::ALL]);
	}


	public function testEngagementTypeMatchesAndLabels(): void
	{
		self::assertTrue(EngagementType::ALL->matches(EngagementType::ACTIVE));
		self::assertTrue(EngagementType::ALL->matches(EngagementType::FINISHED));
		self::assertTrue(EngagementType::ACTIVE->matches(EngagementType::ACTIVE));
		self::assertFalse(EngagementType::ACTIVE->matches(EngagementType::FINISHED));
		self::assertFalse(EngagementType::FINISHED->matches(EngagementType::ACTIVE));
		self::assertSame('Celkom', EngagementType::ALL->label());
		self::assertSame('Aktívne', EngagementType::ACTIVE->label());
		self::assertSame('Ukončené', EngagementType::FINISHED->label());
		self::assertSame('Celkové sily', EngagementType::ALL->forcesLabel());
		self::assertSame('Aktívne sily', EngagementType::ACTIVE->forcesLabel());
		self::assertSame('Ukončené sily', EngagementType::FINISHED->forcesLabel());
	}


	/**
	 * Anna: active (planned in A, finished in A and B, one storno)
	 * Beata: finished (two finished turnus in A)
	 * Cecilia: storno only
	 * Dana: active with an open-ended turnus
	 *
	 * @return list<array{id:int,familyId:int,familyName:string,babysitterId:int,babysitterName:string,statusId:int,statusLabel:string,statusColor:string,dateFrom:?DateTimeImmutable,dateTo:?DateTimeImmutable,workPosition:string}>
	 */
	private function dashboardRows(): array
	{
		return [
			$this->turnus(1, self::FAMILY_A, self::ANNA, 10, '2026-01-01', '2026-01-31'),
			$this->turnus(2, self::FAMILY_A, self::ANNA, 1, '2026-07-01', '2026-07-31'),
			$this->turnus(5, self::FAMILY_A, self::ANNA, TurnusStatus::STORNO, '2026-03-01', '2026-03-31'),
			$this->turnus(6, self::FAMILY_B, self::ANNA, 4, '2025-05-01', '2025-05-31'),
			$this->turnus(3, self::FAMILY_A, self::BEATA, 4, '2026-02-01', '2026-02-28'),
			$this->turnus(4, self::FAMILY_A, self::BEATA, 4, '2026-03-01', '2026-03-31'),
			$this->turnus(7, self::FAMILY_A, self::CECILIA, TurnusStatus::STORNO, '2026-06-01', '2026-06-30'),
			$this->turnus(8, self::FAMILY_B, self::DANA, 1, null, null),
		];
	}


	private function createCalculator(): PartnerEngagementCalculator
	{
		return new PartnerEngagementCalculator(new TurnusEngagementClassifier());
	}


	/**
	 * @return array{id:int,name:string,surname:string,countryImage:string,statusLabel:string,statusColor:string}
	 */
	private function family(int $id): array
	{
		return [
			'id' => $id,
			'name' => 'Maria',
			'surname' => 'Rodina ' . $id,
			'countryImage' => 'at.png',
			'statusLabel' => 'Aktívny',
			'statusColor' => '#198754',
		];
	}


	/**
	 * @return array{id:int,familyId:int,familyName:string,babysitterId:int,babysitterName:string,statusId:int,statusLabel:string,statusColor:string,dateFrom:?DateTimeImmutable,dateTo:?DateTimeImmutable,workPosition:string}
	 */
	private function turnus(int $id, int $familyId, int $babysitterId, int $statusId, ?string $dateFrom, ?string $dateTo): array
	{
		$names = [self::ANNA => 'Anna Testova', self::BEATA => 'Beata Testova', self::CECILIA => 'Cecilia Testova', self::DANA => 'Dana Testova'];

		return [
			'id' => $id,
			'familyId' => $familyId,
			'familyName' => 'Rodina ' . $familyId,
			'babysitterId' => $babysitterId,
			'babysitterName' => $names[$babysitterId] ?? '',
			'statusId' => $statusId,
			'statusLabel' => 'Status ' . $statusId,
			'statusColor' => '#000000',
			'dateFrom' => $dateFrom === null ? null : new DateTimeImmutable($dateFrom),
			'dateTo' => $dateTo === null ? null : new DateTimeImmutable($dateTo),
			'workPosition' => '',
		];
	}


	private function assertCounts(int $total, int $active, int $finished, EngagementCounts $counts): void
	{
		self::assertSame(
			['total' => $total, 'active' => $active, 'finished' => $finished],
			['total' => $counts->total, 'active' => $counts->active, 'finished' => $counts->finished],
		);
	}


	/**
	 * @param list<FamilyEngagementRow> $families
	 */
	private function findFamily(array $families, int $familyId): FamilyEngagementRow
	{
		foreach ($families as $family) {
			if ($family->id === $familyId) {
				return $family;
			}
		}

		self::fail('Family ' . $familyId . ' not found.');
	}


	/**
	 * @param list<BabysitterEngagement> $babysitters
	 */
	private function findBabysitter(array $babysitters, int $babysitterId): BabysitterEngagement
	{
		foreach ($babysitters as $babysitter) {
			if ($babysitter->babysitterId === $babysitterId) {
				return $babysitter;
			}
		}

		self::fail('Babysitter ' . $babysitterId . ' not found.');
	}


	/**
	 * @param list<BabysitterEngagement> $babysitters
	 * @return list<int>
	 */
	private function babysitterIds(array $babysitters): array
	{
		return array_map(static fn (BabysitterEngagement $babysitter): int => $babysitter->babysitterId, $babysitters);
	}
}
