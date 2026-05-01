<?php declare(strict_types = 1);

namespace App\Model\Utils\Random;

use Nette\Utils\Random;

class RandomService
{
	public function randomNumber(int $min = 0, int $max = PHP_INT_MAX): int
	{
		return rand($min, $max);
	}

	public function randomString(int $length = 10, string $charList = '0-9a-z'): string
	{
		/** @var int<1, max> $length */
		/** @var non-empty-string $charList */
		return Random::generate($length, $charList);
	}

}
