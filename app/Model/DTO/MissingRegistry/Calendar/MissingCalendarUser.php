<?php declare(strict_types=1);

namespace App\Model\DTO\MissingRegistry\Calendar;

/**
 * A person shown as a column of the absence calendar.
 */
final readonly class MissingCalendarUser
{
	public function __construct(
		public int $id,
		public string $name,
		public string $acronym,
		public string $color,
		public string $imagePath,
		public bool $canManage,
	) {
	}
}
