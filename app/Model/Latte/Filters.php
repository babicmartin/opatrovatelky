<?php declare(strict_types = 1);

namespace App\Model\Latte;


class Filters
{
	public function __construct(
	)
	{
	}


	public function load(string $filter): ?callable
	{
		$callable = [$this, $filter];
		if (in_array($filter, get_class_methods($this)) && is_callable($callable)) {
			return $callable;
		}
		return null;
	}




}