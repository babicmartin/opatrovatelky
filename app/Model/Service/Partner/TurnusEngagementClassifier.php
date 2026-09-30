<?php declare(strict_types=1);

namespace App\Model\Service\Partner;

use App\Model\Enum\Partner\EngagementType;
use App\Model\Enum\Turnus\TurnusStatus;
use DateTimeImmutable;

/**
 * Decides whether a single care record (turnus) makes the babysitter active or finished.
 */
class TurnusEngagementClassifier
{
	/**
	 * @return EngagementType|null null when the turnus must not be counted at all (storno)
	 */
	public function classify(int $statusId, ?DateTimeImmutable $dateTo, DateTimeImmutable $today): ?EngagementType
	{
		if ($statusId === TurnusStatus::STORNO) {
			return null;
		}

		if ($statusId === TurnusStatus::FINISHED) {
			return EngagementType::FINISHED;
		}

		if ($dateTo === null || $dateTo->format('Y-m-d') >= $today->format('Y-m-d')) {
			return EngagementType::ACTIVE;
		}

		return EngagementType::FINISHED;
	}


	/**
	 * Days spent in the family, counted inclusively and capped at today (future turnus = 0).
	 */
	public function countWorkedDays(?DateTimeImmutable $dateFrom, ?DateTimeImmutable $dateTo, DateTimeImmutable $today): int
	{
		if ($dateFrom === null || $dateTo === null) {
			return 0;
		}

		$from = $dateFrom->setTime(0, 0);
		$to = min($dateTo->setTime(0, 0), $today->setTime(0, 0));
		if ($to < $from) {
			return 0;
		}

		return (int) $from->diff($to)->days + 1;
	}
}
