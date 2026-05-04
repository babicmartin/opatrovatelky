<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Todo\TodoClosedList;

interface TodoClosedListControlFactory
{
	public function create(): TodoClosedListControl;
}
