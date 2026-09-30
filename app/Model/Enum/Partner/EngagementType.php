<?php declare(strict_types=1);

namespace App\Model\Enum\Partner;

/**
 * How a babysitter is engaged in a family, derived from the care records (sn_turnus).
 */
enum EngagementType: string
{
	case ALL = 'all';
	case ACTIVE = 'active';
	case FINISHED = 'finished';

	public function label(): string
	{
		return match ($this) {
			self::ALL => 'Celkom',
			self::ACTIVE => 'Aktívne',
			self::FINISHED => 'Ukončené',
		};
	}

	public function forcesLabel(): string
	{
		return match ($this) {
			self::ALL => 'Celkové sily',
			self::ACTIVE => 'Aktívne sily',
			self::FINISHED => 'Ukončené sily',
		};
	}

	public function babysitterLabel(): string
	{
		return match ($this) {
			self::ALL => 'Všetky',
			self::ACTIVE => 'Aktívna',
			self::FINISHED => 'Ukončená',
		};
	}

	public function matches(self $engagement): bool
	{
		return $this === self::ALL || $this === $engagement;
	}
}
