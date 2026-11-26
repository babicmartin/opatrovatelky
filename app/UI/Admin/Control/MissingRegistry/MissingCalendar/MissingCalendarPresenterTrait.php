<?php declare(strict_types=1);

namespace App\UI\Admin\Control\MissingRegistry\MissingCalendar;

use Psr\Clock\ClockInterface;

/**
 * Adds the team absence calendar to a presenter. The shown month comes from the `year`
 * and `month` parameters of the request; without them it is the current month.
 */
trait MissingCalendarPresenterTrait
{
	private const int MISSING_CALENDAR_MIN_YEAR = 2000;
	private const int MISSING_CALENDAR_MAX_YEAR = 2100;

	private MissingCalendarControlFactory $missingCalendarControlFactory;
	private ClockInterface $missingCalendarClock;

	public function injectMissingCalendar(
		MissingCalendarControlFactory $missingCalendarControlFactory,
		ClockInterface $clock,
	): void {
		$this->missingCalendarControlFactory = $missingCalendarControlFactory;
		$this->missingCalendarClock = $clock;
	}

	/**
	 * Call from the action that shows the calendar: refuses a period that does not exist and,
	 * when the page is asked for through fetch (month navigation), sends back only the calendar.
	 */
	protected function prepareMissingCalendar(): void
	{
		$this->getMissingCalendarPeriod();

		if ($this->isAjax() && $this->getSignal() === null) {
			$this->getComponent('missingCalendar')->redrawControl();
		}
	}

	protected function createComponentMissingCalendar(): MissingCalendarControl
	{
		[$year, $month] = $this->getMissingCalendarPeriod();

		return $this->missingCalendarControlFactory->create($year, $month);
	}

	/**
	 * @return array{int, int} year and month
	 */
	private function getMissingCalendarPeriod(): array
	{
		$now = $this->missingCalendarClock->now();
		$year = $this->readMissingCalendarNumber('year', (int) $now->format('Y'));
		$month = $this->readMissingCalendarNumber('month', (int) $now->format('n'));

		if (
			$year < self::MISSING_CALENDAR_MIN_YEAR
			|| $year > self::MISSING_CALENDAR_MAX_YEAR
			|| $month < 1
			|| $month > 12
		) {
			$this->error('Kalendár pre zadané obdobie neexistuje.');
		}

		return [$year, $month];
	}

	private function readMissingCalendarNumber(string $name, int $default): int
	{
		$value = $this->getParameter($name);
		if ($value === null || $value === '') {
			return $default;
		}

		$number = filter_var($value, FILTER_VALIDATE_INT);
		if ($number === false) {
			$this->error('Kalendár pre zadané obdobie neexistuje.');
		}

		return $number;
	}
}
