<?php declare(strict_types = 1);

namespace App\Model\Utils\Date;

use App\Model\DTO\Date\Month\MonthDTO;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class MonthService
{

	/** @var array<int, array{name: string, days: int}> */
	private array $slovakMonths = [
		1 => ['name' => 'Január', 'days' => 31],
		2 => ['name' => 'Február', 'days' => 28],
		3 => ['name' => 'Marec', 'days' => 31],
		4 => ['name' => 'Apríl', 'days' => 30],
		5 => ['name' => 'Máj', 'days' => 31],
		6 => ['name' => 'Jún', 'days' => 30],
		7 => ['name' => 'Júl', 'days' => 31],
		8 => ['name' => 'August', 'days' => 31],
		9 => ['name' => 'September', 'days' => 30],
		10 => ['name' => 'Október', 'days' => 31],
		11 => ['name' => 'November', 'days' => 30],
		12 => ['name' => 'December', 'days' => 31],
	];



	public function __construct(
		private readonly ClockInterface $clock,
		private readonly DateService $dateService,
		private readonly YearService $yearService,
	)
	{
	}


	public function getCurrentMonthDTO(): ?MonthDTO
	{
		$month = $this->getCurrentMonth();
		$year = $this->yearService->getCurrentYear();

		return $this->getMonthDTOByNumber($month, $year);
	}



	public function getMonthDTOByNumber(int $month, ?int $year = null): ?MonthDTO
	{
		if (!isset($this->slovakMonths[$month])) {
			return null;
		}

		$monthData = $this->slovakMonths[$month];
		$year = $year ?? $this->yearService->getCurrentYear();
		$fistDay = $this->dateService->getFirstDayOfMonthByYearDate($year, $month);
		$lastDay = $this->dateService->getLastDayOfMontByYearDate($year, $month);

		$dayList = $this->getDayListInMonth($fistDay, $lastDay);

		return new MonthDTO($month, $monthData['name'], $monthData['days'], $fistDay, $lastDay, $dayList);
	}

	public function getCurrentMonth(): int
	{
		$now = $this->clock->now();

		return (int) $now->format('m');
	}




	/**
	 * @return array<int, string>
	 */
	public function getMonths(): array
	{
		$months =  [];

		$months[1] = 'Január';
		$months[2] = 'Február';
		$months[3] = 'Marec';
		$months[4] = 'Apríl';
		$months[5] = 'Máj';
		$months[6] = 'Jún';
		$months[7] = 'Júl';
		$months[8] = 'August';
		$months[9] = 'September';
		$months[10] = 'Október';
		$months[11] = 'November';
		$months[12] = 'December';

		return $months;
	}

	/**
	 * @return array<int, MonthDTO|null>
	 */
	public function getMonthDTOs(?int $year): array
	{
		$months =  [];

		for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++) {
			$months[$monthNumber] = $this->getMonthDTOByNumber($monthNumber, $year);
		}

		return $months;
	}


	/**
	 * @return array<int, DateTimeImmutable>
	 */
	private function getDayListInMonth(DateTimeImmutable $firstDay, DateTimeImmutable $lastDay): array
	{
		$days = [];

		$day = $firstDay;

		while ($day <= $lastDay) {
			$days[] = $day;
			$day = $this->dateService->addDay($day);
		}

		return $days;
	}
}
