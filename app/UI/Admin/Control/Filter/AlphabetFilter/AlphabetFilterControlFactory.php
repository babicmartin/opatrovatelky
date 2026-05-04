<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Filter\AlphabetFilter;

interface AlphabetFilterControlFactory
{
	public function create(): AlphabetFilterControl;
}
