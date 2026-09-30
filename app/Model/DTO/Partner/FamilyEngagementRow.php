<?php declare(strict_types=1);

namespace App\Model\DTO\Partner;

final readonly class FamilyEngagementRow
{
	public function __construct(
		public int $id,
		public string $name,
		public string $surname,
		public string $countryImage,
		public string $statusLabel,
		public string $statusColor,
		public EngagementCounts $counts,
	) {
	}
}
