<?php declare(strict_types = 1);

namespace App\Model\Utils\Date;

use App\Model\Utils\ArrayService\ArrayService;
use Psr\Clock\ClockInterface;

final class YearService
{
	public function __construct(
		private readonly ClockInterface $clock,
		private readonly ArrayService $arrayService,
	)
	{
	}

	public function getCurrentYear(): int
	{
		$now = $this->clock->now();

		return (int) $now->format('Y');
	}


	/**
	 * @return array<int>
	 */
	public function getLastYears(int $countYears, bool $reverse = false): array
	{
		$currentYear = $this->getCurrentYear();
		$startYear = $currentYear - $countYears + 1;

		$years = $this->arrayService->range($startYear, $currentYear);

		if ($reverse === false) {
			return $years;
		}

		return $this->arrayService->arrayReverse($years);

	}


	/**
	 * @return array<int>
	 */
	public function getYearsFromYear(int $fromYear, bool $reverse = false): array
	{
		$currentYear = $this->getCurrentYear();

		$years = $this->arrayService->range($fromYear, $currentYear);

		if ($reverse === false) {
			return $years;
		}

		return $this->arrayService->arrayReverse($years);

	}

}