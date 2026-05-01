<?php declare(strict_types = 1);

namespace App\Model\Utils\Date;

use DateInterval;
use DateTimeImmutable;
use Exception;
use IntlDateFormatter;
use InvalidArgumentException;
use RuntimeException;

class DateService
{

	function joinDateAndTime(DateTimeImmutable $date, DateTimeImmutable $time): DateTimeImmutable
	{
		$combined = DateTimeImmutable::createFromFormat(
			'Y-m-d H:i:s',
			$date->format('Y-m-d') . ' ' . $time->format('H:i:s')
		);

		if ($combined === false) {
			throw new RuntimeException('Failed to combine date and time.');
		}

		return $combined;
	}

	public function getFullTextDayName(DateTimeImmutable $date): string
	{
		$formatter = new IntlDateFormatter('sk_SK', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
		$result = $formatter->format($date);
		return $result === false ? '' : $result;
	}

	public function getDayName(DateTimeImmutable $date): string
	{
		$formatter = new IntlDateFormatter('sk_SK', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'EEEE');
		$result = $formatter->format($date);
		return $result === false ? '' : $result;
	}


	public function createDateTimeImmutable(string $date = ''): DateTimeImmutable
	{

		try {
			return new DateTimeImmutable($date);
		} catch (Exception $e) {
			// Handle the exception or throw a custom exception
			throw new InvalidArgumentException('Invalid date format: ' . $date, 0, $e);
		}
	}

	public function createDateTimeImmutableFromYearMonth(int $year, ?int $month = null): DateTimeImmutable
	{
		$month = $month ?? 1; // Default to January if no month is provided

		if ($month < 1 || $month > 12) {
			throw new InvalidArgumentException('Month must be between 1 and 12.');
		}

		return new DateTimeImmutable("$year-$month-01");
	}

	function getFirstDayOfMonth(string $date = '', bool $returnDateTimeImmutable = false): string|DateTimeImmutable {

		$dateTime = $this->createDateTimeImmutable($date);

		// Modify the DateTimeImmutable object to the first day of the month
		$firstDayOfMonth = $dateTime->modify('first day of this month');

		// If $returnDateTimeImmutable is false, return the formatted date string
		if ($returnDateTimeImmutable === false) {
			return $firstDayOfMonth->format('Y-m-d');
		}

		// Otherwise, return the DateTimeImmutable object itself
		return $firstDayOfMonth;

	}

	function getLastDayOfMonth(string $date = '', bool $returnDateTimeImmutable = false): string|DateTimeImmutable {

		// Create a DateTimeImmutable object using the provided date (or current date if empty)
		$dateTime = $this->createDateTimeImmutable($date);

		// Modify the DateTimeImmutable object to the last day of the month
		$lastDayOfMonth = $dateTime->modify('last day of this month');

		// If $returnDateTimeImmutable is false, return the formatted date string
		if ($returnDateTimeImmutable === false) {
			return $lastDayOfMonth->format('Y-m-d');
		}

		// Otherwise, return the DateTimeImmutable object itself
		return $lastDayOfMonth;
	}

	function getFirstDayOfMonthByYearDate(int $year, int $month): DateTimeImmutable {

		//if month is zero, then I need whole year, so mont is January
		$month = $month === 0 ? 1 : $month;
		return $this->createDateTimeImmutable("$year-$month-01");
	}

	function getLastDayOfMontByYearDate(int $year, int $month): DateTimeImmutable {

		//if month is zero, then I need whole year, so mont is December
		$month = $month === 0 ? 12 : $month;

		$dateTime = $this->createDateTimeImmutable("$year-$month-01");

		return $dateTime->modify('last day of this month');

	}


	public function addDay(DateTimeImmutable $date): DateTimeImmutable
	{
		return $date->modify('+1 day');
	}

	public function addDays(DateTimeImmutable $date, int $days): DateTimeImmutable
	{
		return $date->modify("+$days days");
	}

	public function subtractDay(DateTimeImmutable $date): DateTimeImmutable
	{
		// Subtract one day from the given date
		return $date->modify('-1 day');
	}

	public function subtractDays(DateTimeImmutable $date, int $days): DateTimeImmutable
	{
		// Subtract the specified number of days from the given date
		return $date->modify("-$days days");
	}

	public function addMonth(DateTimeImmutable $date): DateTimeImmutable
	{
		// Add one month to the given date
		return $date->modify('+1 month');
	}

	public function addMonths(DateTimeImmutable $date, int $months): DateTimeImmutable
	{
		// Add the specified number of months to the given date
		return $date->modify("+$months months");
	}

	public function subtractMonth(DateTimeImmutable $date): DateTimeImmutable
	{
		// Subtract one month from the given date
		return $date->modify('-1 month');
	}

	public function subtractMonths(DateTimeImmutable $date, int $months): DateTimeImmutable
	{
		// Subtract the specified number of months from the given date
		return $date->modify("-$months months");
	}

	public function addYear(DateTimeImmutable $date): DateTimeImmutable
	{
		// Add one year to the given date
		return $date->modify('+1 year');
	}

	public function addYears(DateTimeImmutable $date, int $years): DateTimeImmutable
	{
		// Add the specified number of years to the given date
		return $date->modify("+$years years");
	}

	public function subtractYear(DateTimeImmutable $date): DateTimeImmutable
	{
		// Subtract one year from the given date
		return $date->modify('-1 year');
	}

	public function subtractYears(DateTimeImmutable $date, int $years): DateTimeImmutable
	{
		// Subtract the specified number of years from the given date
		return $date->modify("-$years years");
	}

	public function getDateDifference(?DateTimeImmutable $date1 = null, ?DateTimeImmutable $date2 = null): DateInterval
	{
		if ($date1 === null || $date2 === null) {
			throw new InvalidArgumentException('Both dates must be provided.');
		}
		return $date1->diff($date2);
	}

	public function isWeekendDay(DateTimeImmutable $date): bool
	{
		$dayOfWeek = (int) $date->format('N');
		return $dayOfWeek === 6 || $dayOfWeek === 7;
	}

	public function isToday(DateTimeImmutable $date): bool
	{
		$today = new DateTimeImmutable('today');
		return $date->format('Y-m-d') === $today->format('Y-m-d');
	}

	public function isWeekDay(DateTimeImmutable $date): bool
	{
		$dayOfWeek = (int) $date->format('N');
		return $dayOfWeek <= 5;
	}

}
