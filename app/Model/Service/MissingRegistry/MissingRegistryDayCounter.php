<?php declare(strict_types=1);

namespace App\Model\Service\MissingRegistry;

use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Utils\Date\DateService;
use App\Model\Utils\Date\HolidayService;
use DateTimeImmutable;

/**
 * Counts the days of registry records.
 *
 * A type that counts working days only (see MissingType::countsWorkingDaysOnly()) skips weekends
 * and days of rest; the other types count every calendar day. A day covered by two records
 * of the same person and type is counted once.
 */
final readonly class MissingRegistryDayCounter
{
	public function __construct(
		private HolidayService $holidayService,
		private DateService $dateService,
	) {
	}

	public function isCountedDay(MissingType $type, DateTimeImmutable $day): bool
	{
		return !$type->countsWorkingDaysOnly() || $this->holidayService->isWorkingDay($day);
	}

	/**
	 * Is the day counted for at least one type of the record? A record without a type counts nothing.
	 */
	public function isCountedRecordDay(MissingRegistryRecord $record, DateTimeImmutable $day): bool
	{
		return array_any($record->types, fn (MissingType $type): bool => $this->isCountedDay($type, $day));
	}

	/**
	 * Days per person and type inside the period; the records are clipped to it.
	 *
	 * @param list<MissingRegistryRecord> $records
	 * @return array<int, array<string, int>> user id => MissingType value => days
	 */
	public function countByUserAndType(array $records, DateTimeImmutable $from, DateTimeImmutable $to): array
	{
		$days = [];
		foreach ($records as $record) {
			foreach ($this->getDaysInPeriod($record, $from, $to) as $key => $day) {
				foreach ($record->types as $type) {
					if ($this->isCountedDay($type, $day)) {
						$days[$record->userId][$type->value][$key] = true;
					}
				}
			}
		}

		return array_map(
			static fn (array $types): array => array_map(count(...), $types),
			$days,
		);
	}

	/**
	 * @return array{calendarDays: int, workingDays: int, weekendDays: int, holidays: array<string, string>}
	 *         `holidays` holds only the days of rest that fall on Monday to Friday, date => name
	 */
	public function countRange(DateTimeImmutable $from, DateTimeImmutable $to): array
	{
		$result = ['calendarDays' => 0, 'workingDays' => 0, 'weekendDays' => 0, 'holidays' => []];

		for ($day = $from; $day <= $to; $day = $this->dateService->addDay($day)) {
			$result['calendarDays']++;
			$holiday = $this->holidayService->getHolidayName($day);

			if ($this->dateService->isWeekendDay($day)) {
				$result['weekendDays']++;
			} elseif ($holiday !== null) {
				$result['holidays'][$day->format('Y-m-d')] = $holiday;
			} else {
				$result['workingDays']++;
			}
		}

		return $result;
	}

	/**
	 * @return array<string, DateTimeImmutable> date `Y-m-d` => day
	 */
	private function getDaysInPeriod(MissingRegistryRecord $record, DateTimeImmutable $from, DateTimeImmutable $to): array
	{
		$lastDay = $record->getLastDay();
		if ($record->dateFrom === null || $lastDay === null) {
			return [];
		}

		$days = [];
		for ($day = max($record->dateFrom, $from); $day <= min($lastDay, $to); $day = $this->dateService->addDay($day)) {
			$days[$day->format('Y-m-d')] = $day;
		}

		return $days;
	}
}
