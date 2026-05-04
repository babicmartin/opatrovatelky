<?php declare(strict_types=1);

namespace App\UI\Admin\Settings;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\AdminPresenter;

final class SettingsPresenter extends AdminPresenter
{
	protected function getResource(): string
	{
		return Resource::SETTINGS->value;
	}

	public function actionDefault(): void
	{
	}
}
