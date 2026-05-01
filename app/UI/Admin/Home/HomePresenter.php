<?php declare(strict_types=1);

namespace App\UI\Admin\Home;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Turnus\HomepageTurnusList\HomepageTurnusListPresenterTrait;
use App\UI\Admin\Control\Turnus\UnpaidInvoicesWidget\UnpaidInvoicesWidgetPresenterTrait;

class HomePresenter extends AdminPresenter
{
	use HomepageTurnusListPresenterTrait;
	use UnpaidInvoicesWidgetPresenterTrait;

	protected function getResource(): string
	{
		return Resource::HOME->value;
	}

	public function actionDefault(): void
	{
	}
}
