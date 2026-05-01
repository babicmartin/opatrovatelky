<?php declare(strict_types = 1);

namespace App\Model\Utils\TypeChecker;

class TypeChecker
{
	public function isString(mixed $value): bool
	{
		return is_string($value);
	}

	public function isInt(mixed $value): bool
	{
		return is_int($value);
	}

	public function isBool(mixed $value): bool
	{
		return is_bool($value);
	}
}
