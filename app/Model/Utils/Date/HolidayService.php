<?php declare(strict_types=1);

namespace App\Model\Utils\Date;

use DateTimeImmutable;

/**
 * Slovak days of rest (dni pracovného pokoja) under Act No. 241/1993 Coll., without Sundays.
 *
 * A state holiday that is not a day of rest (28 October; 1 September since 2024; 17 November since 2025)
 * is a normal working day here. Wording checked against Slov-lex, version effective 29 October 2026.
 */
class HolidayService
{
	/**
	 * `until`: last year in which the day was a day of rest. `except`: single years in which it was not (§ 4b).
	 *
	 * @var list<array{month: int, day: int, name: string, until?: int, except?: list<int>}>
	 */
	private const array FIXED_HOLIDAYS = [
		['month' => 1, 'day' => 1, 'name' => 'Deň vzniku Slovenskej republiky'],
		['month' => 1, 'day' => 6, 'name' => 'Zjavenie Pána (Traja králi)'],
		['month' => 5, 'day' => 1, 'name' => 'Sviatok práce'],
		['month' => 5, 'day' => 8, 'name' => 'Deň víťazstva nad fašizmom', 'except' => [2026]],
		['month' => 7, 'day' => 5, 'name' => 'Sviatok svätého Cyrila a Metoda'],
		['month' => 8, 'day' => 29, 'name' => 'Výročie SNP'],
		['month' => 9, 'day' => 1, 'name' => 'Deň Ústavy Slovenskej republiky', 'until' => 2023],
		['month' => 9, 'day' => 15, 'name' => 'Sedembolestná Panna Mária', 'except' => [2026]],
		['month' => 11, 'day' => 1, 'name' => 'Sviatok všetkých svätých'],
		['month' => 11, 'day' => 17, 'name' => 'Deň boja za slobodu a demokraciu', 'until' => 2024],
		['month' => 12, 'day' => 24, 'name' => 'Štedrý deň'],
		['month' => 12, 'day' => 25, 'name' => 'Prvý sviatok vianočný'],
		['month' => 12, 'day' => 26, 'name' => 'Druhý sviatok vianočný'],
	];

	/** @var array<int, array<string, string>> */
	private array $holidaysByYear = [];

	public function __construct(
		private readonly DateService $dateService,
	) {
	}

	/**
	 * @return array<string, string> date `Y-m-d` => name, in calendar order
	 */
	public function getHolidays(int $year): array
	{
		if (!isset($this->holidaysByYear[$year])) {
			$holidays = [];
			foreach (self::FIXED_HOLIDAYS as $holiday) {
				if (isset($holiday['until']) && $year > $holiday['until']) {
					continue;
				}
				if (in_array($year, $holiday['except'] ?? [], true)) {
					continue;
				}

				$holidays[sprintf('%04d-%02d-%02d', $year, $holiday['month'], $holiday['day'])] = $holiday['name'];
			}

			$easterSunday = $this->getEasterSunday($year);
			$holidays[$this->dateService->subtractDays($easterSunday, 2)->format('Y-m-d')] = 'Veľký piatok';
			$holidays[$this->dateService->addDay($easterSunday)->format('Y-m-d')] = 'Veľkonočný pondelok';

			ksort($holidays);
			$this->holidaysByYear[$year] = $holidays;
		}

		return $this->holidaysByYear[$year];
	}

	/**
	 * @return array<string, string> date `Y-m-d` => name
	 */
	public function getHolidaysInRange(DateTimeImmutable $from, DateTimeImmutable $to): array
	{
		$fromKey = $from->format('Y-m-d');
		$toKey = $to->format('Y-m-d');
		$holidays = [];

		for ($year = (int) $from->format('Y'); $year <= (int) $to->format('Y'); $year++) {
			foreach ($this->getHolidays($year) as $date => $name) {
				if ($date >= $fromKey && $date <= $toKey) {
					$holidays[$date] = $name;
				}
			}
		}

		return $holidays;
	}

	public function getHolidayName(DateTimeImmutable $date): ?string
	{
		return $this->getHolidays((int) $date->format('Y'))[$date->format('Y-m-d')] ?? null;
	}

	public function isHoliday(DateTimeImmutable $date): bool
	{
		return $this->getHolidayName($date) !== null;
	}

	public function isWorkingDay(DateTimeImmutable $date): bool
	{
		return !$this->dateService->isWeekendDay($date) && !$this->isHoliday($date);
	}

	/**
	 * Gregorian Easter Sunday (anonymous Meeus/Jones/Butcher algorithm).
	 */
	public function getEasterSunday(int $year): DateTimeImmutable
	{
		$a = $year % 19;
		$b = intdiv($year, 100);
		$c = $year % 100;
		$d = intdiv($b, 4);
		$e = $b % 4;
		$f = intdiv($b + 8, 25);
		$g = intdiv($b - $f + 1, 3);
		$h = (19 * $a + $b - $d - $g + 15) % 30;
		$i = intdiv($c, 4);
		$k = $c % 4;
		$l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
		$m = intdiv($a + 11 * $h + 22 * $l, 451);
		$month = intdiv($h + $l - 7 * $m + 114, 31);
		$day = (($h + $l - 7 * $m + 114) % 31) + 1;

		return $this->dateService->createDateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
	}
}
