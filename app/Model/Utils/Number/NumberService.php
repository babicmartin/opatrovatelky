<?php declare(strict_types = 1);

namespace App\Model\Utils\Number;

class NumberService
{
	public function formatNumber(float|int|string $number, int $decimals = 2, string $decimalSeparator = ',', string $thousandsSeparator = ' '): string
	{
		return number_format((float) $number, $decimals, $decimalSeparator, $thousandsSeparator);
	}

	public function round(float $number, int $precision = 2, int $mode = PHP_ROUND_HALF_UP): float
	{
		/** @var 1|2|3|4 $mode */
		return round($number, $precision, $mode);
	}

	public function roundUp(float|int|string $number): int
	{
		return (int) ceil((float) $number);
	}
}
