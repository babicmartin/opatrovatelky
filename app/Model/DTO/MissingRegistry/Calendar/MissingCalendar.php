<?php declare(strict_types=1);

namespace App\Model\DTO\MissingRegistry\Calendar;

/**
 * One month of the team absence calendar with the day counts around it.
 */
final readonly class MissingCalendar
{
	/**
	 * @param list<MissingCalendarUser> $users
	 * @param list<MissingCalendarDay> $days
	 * @param array<string, string> $holidays days of rest on Monday to Friday, date `Y-m-d` => name
	 * @param list<string> $absentToday names of the people absent today
	 * @param array<int, array<string, int>> $monthTotals user id => MissingType value => days
	 * @param array<int, array<string, int>> $yearTotals user id => MissingType value => days
	 */
	public function __construct(
		public int $year,
		public int $month,
		public string $monthName,
		public array $users,
		public array $days,
		public int $workingDays,
		public int $weekendDays,
		public array $holidays,
		public array $absentToday,
		public array $monthTotals,
		public array $yearTotals,
	) {
	}

	public function getMonthTotal(int $userId, string $type): int
	{
		return $this->monthTotals[$userId][$type] ?? 0;
	}

	public function getYearTotal(int $userId, string $type): int
	{
		return $this->yearTotals[$userId][$type] ?? 0;
	}
}
