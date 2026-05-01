<?php declare(strict_types = 1);

namespace App\Model\Utils\Float;

use Nette\Utils\Floats;

class FloatService
{
	public function areEqual(float $a, float $b): bool
	{
		return Floats::areEqual($a, $b);
	}

	public function isLessThan(float $a, float $b): bool
	{
		return Floats::isLessThan($a, $b);
	}

	public function isLessThanOrEqualTo(float $a, float $b): bool
	{
		return Floats::isLessThanOrEqualTo($a, $b);
	}

	public function isGreaterThan(float $a, float $b): bool
	{
		return Floats::isGreaterThan($a, $b);
	}

	public function isGreaterThanOrEqualTo(float $a, float $b): bool
	{
		return Floats::isGreaterThanOrEqualTo($a, $b);
	}

	public function compare(float $a, float $b): int
	{
		//If $a < $b, it returns -1, if they are equal it returns 0 and if $a > $b it returns 1.
		return Floats::compare($a, $b);
	}

	public function isZero(float $a): bool
	{
		return Floats::isZero($a);
	}

	public function isInteger(float $a): bool
	{
		return Floats::isInteger($a);
	}

}