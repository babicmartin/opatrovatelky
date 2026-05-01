<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Stats\StatsOverview;

trait StatsOverviewPresenterTrait
{
	private StatsOverviewControlFactory $statsOverviewControlFactory;

	public function injectStatsOverviewControlFactory(
		StatsOverviewControlFactory $statsOverviewControlFactory,
	): void {
		$this->statsOverviewControlFactory = $statsOverviewControlFactory;
	}

	protected function createComponentStatsOverview(): StatsOverviewControl
	{
		return $this->statsOverviewControlFactory->create();
	}
}
