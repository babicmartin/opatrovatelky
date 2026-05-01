<?php declare(strict_types=1);

namespace App\UI\Admin\MissingRegistry;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\MissingRegistry\MissingRegistryList\MissingRegistryListPresenterTrait;

class MissingRegistryPresenter extends AdminPresenter
{
	use MissingRegistryListPresenterTrait;

	protected function getResource(): string
	{
		return Resource::MISSING->value;
	}

	public function actionDefault(): void
	{
	}
}
