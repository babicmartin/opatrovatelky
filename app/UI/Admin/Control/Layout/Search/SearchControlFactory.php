<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Search;

interface SearchControlFactory
{
	public function create(): SearchControl;
}
