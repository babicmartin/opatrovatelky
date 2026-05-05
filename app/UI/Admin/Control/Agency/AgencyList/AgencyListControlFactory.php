<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Agency\AgencyList;

interface AgencyListControlFactory
{
	public function create(): AgencyListControl;
}
