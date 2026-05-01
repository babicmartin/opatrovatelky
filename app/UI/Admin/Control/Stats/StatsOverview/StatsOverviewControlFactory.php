<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Stats\StatsOverview;

interface StatsOverviewControlFactory
{
	public function create(): StatsOverviewControl;
}
