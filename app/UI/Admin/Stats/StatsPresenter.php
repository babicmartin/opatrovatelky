<?php declare(strict_types=1);

namespace App\UI\Admin\Stats;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Stats\StatsOverview\StatsOverviewPresenterTrait;

class StatsPresenter extends AdminPresenter
{
	use StatsOverviewPresenterTrait;

	protected function getResource(): string
	{
		return Resource::STATS->value;
	}

	public function actionDefault(): void
	{
	}
}
