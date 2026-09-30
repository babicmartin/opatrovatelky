<?php declare(strict_types=1);

namespace App\Model\Service\Partner;

use App\Model\DTO\Partner\PartnerBabysitterDashboard;
use App\Model\DTO\Partner\PartnerFamilyOverview;
use App\Model\Enum\Partner\EngagementType;
use App\Model\Repository\PartnerFamilyRepository;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class PartnerFamilyEngagementService
{
	public function __construct(
		private readonly PartnerFamilyRepository $partnerFamilyRepository,
		private readonly PartnerEngagementCalculator $calculator,
		private readonly ClockInterface $clock,
	) {
	}


	public function getFamilyOverview(int $partnerId): PartnerFamilyOverview
	{
		return $this->calculator->calculateFamilyOverview(
			$this->partnerFamilyRepository->findFamilies($partnerId),
			$this->partnerFamilyRepository->findTurnusRows($partnerId),
			$this->getToday(),
		);
	}


	public function getBabysitterDashboard(int $partnerId, EngagementType $type, ?int $familyId = null): PartnerBabysitterDashboard
	{
		return $this->calculator->calculateDashboard(
			$this->partnerFamilyRepository->findTurnusRows($partnerId, $familyId),
			$type,
			$familyId,
			$this->getToday(),
		);
	}


	/**
	 * @return string|null null when the family does not belong to the partner
	 */
	public function findFamilyName(int $partnerId, int $familyId): ?string
	{
		return $this->partnerFamilyRepository->findFamilyName($partnerId, $familyId);
	}


	private function getToday(): DateTimeImmutable
	{
		return $this->clock->now()->setTime(0, 0);
	}
}
