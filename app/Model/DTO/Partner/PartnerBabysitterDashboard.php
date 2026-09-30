<?php declare(strict_types=1);

namespace App\Model\DTO\Partner;

use App\Model\Enum\Partner\EngagementType;

final readonly class PartnerBabysitterDashboard
{
	/**
	 * @param EngagementCounts $counts babysitters in the whole scope, independent of the type filter
	 * @param list<BabysitterEngagement> $babysitters babysitters matching the type filter
	 */
	public function __construct(
		public EngagementType $type,
		public ?int $familyId,
		public EngagementCounts $counts,
		public array $babysitters,
		public int $countedTurnusCount,
		public int $stornoTurnusCount,
		public int $workedDays,
		public float $averageTurnusDays,
	) {
	}
}
