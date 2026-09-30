<?php declare(strict_types=1);

namespace App\Model\DTO\Partner;

use App\Model\Enum\Partner\EngagementType;
use DateTimeImmutable;

final readonly class BabysitterEngagement
{
	/**
	 * @param array<int, string> $families familyId => family name
	 * @param list<EngagementTurnus> $turnusRows all turnus rows including storno, newest first
	 */
	public function __construct(
		public int $babysitterId,
		public string $name,
		public EngagementType $engagement,
		public array $families,
		public array $turnusRows,
		public int $countedTurnusCount,
		public int $stornoTurnusCount,
		public ?DateTimeImmutable $firstDateFrom,
		public ?DateTimeImmutable $lastDateTo,
		public bool $hasOpenTurnus,
		public int $workedDays,
	) {
	}
}
