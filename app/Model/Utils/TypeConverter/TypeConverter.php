<?php declare(strict_types = 1);

namespace App\Model\Utils\TypeConverter;

use App\Model\Utils\String\StringService;

class TypeConverter
{
	private StringService $stringService;

	public function __construct(
		StringService $stringService
	)
	{

		$this->stringService = $stringService;
	}

	public function intToBool(int $int): bool
	{
		return $int === 1;
	}

	public function boolToInt(bool $bool): int
	{
		return $bool ? 1 : 0;
	}
	public function stringToInt(string $string): int
	{
		return (int) $string;
	}

	public function stringToFloat(string $string): float
	{
		$string = $this->stringService->replace($string, ',', '.');

		return (float) $string;
	}

	public function intToString(int $int): string
	{
		return (string) $int;
	}

}