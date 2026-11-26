<?php declare(strict_types=1);

namespace App\Model\DTO\MissingRegistry\Calendar;

use DateTimeImmutable;

/**
 * A row of the absence calendar.
 */
final readonly class MissingCalendarDay
{
	/**
	 * @param array<int, list<MissingCalendarEntry>> $entriesByUser user id => entries of that day
	 */
	public function __construct(
		public DateTimeImmutable $date,
		public string $dayName,
		public bool $isWeekend,
		public ?string $holidayName,
		public bool $isToday,
		public array $entriesByUser,
	) {
	}

	/**
	 * @return list<MissingCalendarEntry>
	 */
	public function getEntries(int $userId): array
	{
		return $this->entriesByUser[$userId] ?? [];
	}
}
