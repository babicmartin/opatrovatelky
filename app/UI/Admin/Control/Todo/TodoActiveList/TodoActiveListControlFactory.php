<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Todo\TodoActiveList;

interface TodoActiveListControlFactory
{
	public function create(): TodoActiveListControl;
}
