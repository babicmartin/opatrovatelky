<?php declare(strict_types=1);

namespace App\Model\DTO\Partner;

final readonly class PartnerFamilyOverview
{
	/**
	 * @param list<FamilyEngagementRow> $families
	 * @param EngagementCounts $summary unique babysitters across all families of the partner
	 */
	public function __construct(
		public array $families,
		public EngagementCounts $summary,
	) {
	}
}
