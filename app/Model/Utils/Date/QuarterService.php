<?php declare(strict_types = 1);

namespace App\Model\Utils\Date;

use App\Model\DTO\Date\Quarter\QuarterDTO;

class QuarterService
{

	public function __construct(
		private readonly DateService $dateService,
	)
	{
	}

	/**
	 * @return array<int, QuarterDTO>
	 */
	public function getQuarters(int $year): array
	{
		$quarters = [];

		// Define the starting months for each quarter
		/** @var array<int, array{0: int, 1: int}> $quarterMonths */
		$quarterMonths = [
			[1, 3],  // Quarter 1: January - March
			[4, 6],  // Quarter 2: April - June
			[7, 9],  // Quarter 3: July - September
			[10, 12] // Quarter 4: October - December
		];

		foreach ($quarterMonths as $index => [$startMonth, $endMonth]) {
			$from = $this->dateService->getFirstDayOfMonthByYearDate($year, $startMonth);
			$to = $this->dateService->getLastDayOfMontByYearDate($year, $endMonth);

			$quarters[$index + 1] = new QuarterDTO($index + 1, $from, $to);
		}

		return $quarters;
	}
}
