<?php declare(strict_types=1);

namespace App\UI\Admin\Babysitter;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\AdminPresenter;

class BabysitterPresenter extends AdminPresenter
{
	protected function getResource(): string
	{
		return Resource::BABYSITTER->value;
	}

	public function actionDefault(?int $status = null, ?int $country = null): void
	{
		$this->template->status = $status;
		$this->template->country = $country;
	}

	public function actionUpdate(int $id): void
	{
		$this->template->id = $id;
	}
}
