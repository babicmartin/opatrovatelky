<?php declare(strict_types=1);

namespace App\Model\Service\Partner;

use App\Model\DTO\Partner\BabysitterEngagement;
use App\Model\DTO\Partner\EngagementCounts;
use App\Model\DTO\Partner\EngagementTurnus;
use App\Model\DTO\Partner\FamilyEngagementRow;
use App\Model\DTO\Partner\PartnerBabysitterDashboard;
use App\Model\DTO\Partner\PartnerFamilyOverview;
use App\Model\Enum\Partner\EngagementType;
use App\Model\Repository\PartnerFamilyRepository;
use DateTimeImmutable;

/**
 * Pure aggregation of care records into babysitter engagement numbers.
 *
 * A babysitter is counted once per scope (family or whole partner): active when at least one
 * counted turnus is active, otherwise finished. Babysitters with storno turnus only are skipped,
 * so total = active + finished.
 *
 * @phpstan-import-type FamilyRow from PartnerFamilyRepository
 * @phpstan-import-type TurnusRow from PartnerFamilyRepository
 */
class PartnerEngagementCalculator
{
	public function __construct(
		private readonly TurnusEngagementClassifier $classifier,
	) {
	}


	/**
	 * @param list<FamilyRow> $families
	 * @param list<TurnusRow> $turnusRows
	 */
	public function calculateFamilyOverview(array $families, array $turnusRows, DateTimeImmutable $today): PartnerFamilyOverview
	{
		$turnusList = $this->createTurnusList($turnusRows, $today);

		$byFamily = [];
		foreach ($turnusList as $turnus) {
			$byFamily[$turnus->familyId][] = $turnus;
		}

		$rows = [];
		foreach ($families as $family) {
			$rows[] = new FamilyEngagementRow(
				$family['id'],
				$family['name'],
				$family['surname'],
				$family['countryImage'],
				$family['statusLabel'],
				$family['statusColor'],
				EngagementCounts::fromEngagements($this->resolveBabysitterEngagements($byFamily[$family['id']] ?? [])),
			);
		}

		return new PartnerFamilyOverview(
			$rows,
			EngagementCounts::fromEngagements($this->resolveBabysitterEngagements($turnusList)),
		);
	}


	/**
	 * @param list<TurnusRow> $turnusRows rows already limited to the requested scope (partner or one family)
	 */
	public function calculateDashboard(
		array $turnusRows,
		EngagementType $type,
		?int $familyId,
		DateTimeImmutable $today,
	): PartnerBabysitterDashboard {
		$byBabysitter = [];
		foreach ($this->createTurnusList($turnusRows, $today) as $turnus) {
			$byBabysitter[$turnus->babysitterId][] = $turnus;
		}

		$all = [];
		foreach ($byBabysitter as $rows) {
			$babysitter = $this->createBabysitterEngagement($rows);
			if ($babysitter !== null) {
				$all[] = $babysitter;
			}
		}

		$babysitters = array_values(array_filter(
			$all,
			static fn (BabysitterEngagement $babysitter): bool => $type->matches($babysitter->engagement),
		));
		usort($babysitters, $this->compareBabysitters(...));

		$countedTurnusCount = 0;
		$stornoTurnusCount = 0;
		$workedDays = 0;
		$turnusWithDays = 0;
		foreach ($babysitters as $babysitter) {
			$countedTurnusCount += $babysitter->countedTurnusCount;
			$stornoTurnusCount += $babysitter->stornoTurnusCount;
			$workedDays += $babysitter->workedDays;
			foreach ($babysitter->turnusRows as $turnus) {
				if ($turnus->isCounted() && $turnus->workedDays > 0) {
					$turnusWithDays++;
				}
			}
		}

		return new PartnerBabysitterDashboard(
			$type,
			$familyId,
			EngagementCounts::fromEngagements(array_map(
				static fn (BabysitterEngagement $babysitter): EngagementType => $babysitter->engagement,
				$all,
			)),
			$babysitters,
			$countedTurnusCount,
			$stornoTurnusCount,
			$workedDays,
			$turnusWithDays > 0 ? round($workedDays / $turnusWithDays, 1) : 0.0,
		);
	}


	/**
	 * @param list<TurnusRow> $turnusRows
	 * @return list<EngagementTurnus>
	 */
	private function createTurnusList(array $turnusRows, DateTimeImmutable $today): array
	{
		$list = [];
		foreach ($turnusRows as $row) {
			if ($row['babysitterId'] <= 0) {
				continue;
			}

			$engagement = $this->classifier->classify($row['statusId'], $row['dateTo'], $today);
			$list[] = new EngagementTurnus(
				$row['id'],
				$row['familyId'],
				$row['familyName'],
				$row['babysitterId'],
				$row['babysitterName'],
				$row['statusId'],
				$row['statusLabel'],
				$row['statusColor'],
				$row['dateFrom'],
				$row['dateTo'],
				$row['workPosition'],
				$engagement,
				$engagement === null ? 0 : $this->classifier->countWorkedDays($row['dateFrom'], $row['dateTo'], $today),
			);
		}

		return $list;
	}


	/**
	 * @param list<EngagementTurnus> $turnusList
	 * @return array<int, EngagementType> babysitterId => resolved engagement
	 */
	private function resolveBabysitterEngagements(array $turnusList): array
	{
		$engagements = [];
		foreach ($turnusList as $turnus) {
			if ($turnus->engagement === null) {
				continue;
			}

			$engagements[$turnus->babysitterId] = $this->mergeEngagement(
				$engagements[$turnus->babysitterId] ?? null,
				$turnus->engagement,
			);
		}

		return $engagements;
	}


	private function mergeEngagement(?EngagementType $current, EngagementType $next): EngagementType
	{
		return $current === EngagementType::ACTIVE || $next === EngagementType::ACTIVE
			? EngagementType::ACTIVE
			: EngagementType::FINISHED;
	}


	/**
	 * @param non-empty-list<EngagementTurnus> $rows turnus rows of one babysitter
	 */
	private function createBabysitterEngagement(array $rows): ?BabysitterEngagement
	{
		$engagement = null;
		$families = [];
		$countedTurnusCount = 0;
		$stornoTurnusCount = 0;
		$workedDays = 0;
		$firstDateFrom = null;
		$lastDateTo = null;
		$hasOpenTurnus = false;

		foreach ($rows as $turnus) {
			$families[$turnus->familyId] = $turnus->familyName;
			if ($turnus->engagement === null) {
				$stornoTurnusCount++;
				continue;
			}

			$engagement = $this->mergeEngagement($engagement, $turnus->engagement);
			$countedTurnusCount++;
			$workedDays += $turnus->workedDays;

			if ($turnus->dateFrom !== null && ($firstDateFrom === null || $turnus->dateFrom < $firstDateFrom)) {
				$firstDateFrom = $turnus->dateFrom;
			}

			if ($turnus->dateTo === null) {
				$hasOpenTurnus = true;
			} elseif ($lastDateTo === null || $turnus->dateTo > $lastDateTo) {
				$lastDateTo = $turnus->dateTo;
			}
		}

		if ($engagement === null) {
			return null;
		}

		// undated (planned) turnus first, then newest
		usort(
			$rows,
			static fn (EngagementTurnus $a, EngagementTurnus $b): int => [$b->dateFrom === null, $b->dateFrom, $b->id] <=> [$a->dateFrom === null, $a->dateFrom, $a->id],
		);
		ksort($families);

		return new BabysitterEngagement(
			$rows[0]->babysitterId,
			$rows[0]->babysitterName,
			$engagement,
			$families,
			$rows,
			$countedTurnusCount,
			$stornoTurnusCount,
			$firstDateFrom,
			$lastDateTo,
			$hasOpenTurnus,
			$workedDays,
		);
	}


	/**
	 * Active first, then the most recent (open-ended turnus counts as the most recent), then by name.
	 */
	private function compareBabysitters(BabysitterEngagement $a, BabysitterEngagement $b): int
	{
		return [
			$b->engagement === EngagementType::ACTIVE,
			$b->hasOpenTurnus,
			$b->lastDateTo,
			$a->name,
			$a->babysitterId,
		] <=> [
			$a->engagement === EngagementType::ACTIVE,
			$a->hasOpenTurnus,
			$a->lastDateTo,
			$b->name,
			$b->babysitterId,
		];
	}
}
