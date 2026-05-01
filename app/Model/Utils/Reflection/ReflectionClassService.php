<?php declare(strict_types = 1);

namespace App\Model\Utils\Reflection;

use ReflectionClass;

class ReflectionClassService
{
	/**
	 * @return array<string, mixed>
	 */
	public function getClassConstants(string $className): array
	{
		/** @var class-string $className */
		$reflectionClass = new ReflectionClass($className);
		return $reflectionClass->getConstants();
	}

}
