<?php declare(strict_types=1);

namespace App\Model\DTO\Partner;

use App\Model\Enum\Partner\EngagementType;

final readonly class EngagementCounts
{
	public int $total;


	public function __construct(
		public int $active = 0,
		public int $finished = 0,
	) {
		$this->total = $active + $finished;
	}


	/**
	 * @param iterable<EngagementType> $engagements one resolved engagement per babysitter
	 */
	public static function fromEngagements(iterable $engagements): self
	{
		$active = 0;
		$finished = 0;
		foreach ($engagements as $engagement) {
			match ($engagement) {
				EngagementType::ACTIVE => $active++,
				EngagementType::FINISHED => $finished++,
				EngagementType::ALL => throw new \InvalidArgumentException('Babysitter engagement must be active or finished.'),
			};
		}

		return new self($active, $finished);
	}


	public function get(EngagementType $type): int
	{
		return match ($type) {
			EngagementType::ALL => $this->total,
			EngagementType::ACTIVE => $this->active,
			EngagementType::FINISHED => $this->finished,
		};
	}
}
