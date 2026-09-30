<?php declare(strict_types=1);

namespace App\Model\DTO\Partner;

use App\Model\Enum\Partner\EngagementType;
use DateTimeImmutable;

final readonly class EngagementTurnus
{
	public function __construct(
		public int $id,
		public int $familyId,
		public string $familyName,
		public int $babysitterId,
		public string $babysitterName,
		public int $statusId,
		public string $statusLabel,
		public string $statusColor,
		public ?DateTimeImmutable $dateFrom,
		public ?DateTimeImmutable $dateTo,
		public string $workPosition,
		public ?EngagementType $engagement,
		public int $workedDays,
	) {
	}


	public function isCounted(): bool
	{
		return $this->engagement !== null;
	}
}
