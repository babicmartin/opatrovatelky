<?php declare(strict_types=1);

namespace App\UI\Admin\Babysitter;

use App\Model\Enum\Acl\Resource;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Babysitter\BabysitterList\BabysitterListPresenterTrait;
use App\UI\Admin\Control\Filter\AlphabetFilter\AlphabetFilterPresenterTrait;

class BabysitterPresenter extends AdminPresenter
{
	use AlphabetFilterPresenterTrait;
	use BabysitterListPresenterTrait;

	protected function getResource(): string
	{
		return Resource::BABYSITTER->value;
	}

	public function actionDefault(?int $page = null): void
	{
		$firstLetterRaw = $this->getHttpRequest()->getQuery('first-letter');
		$firstLetter = is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null;

		$this->template->page = $page;
		$this->template->firstLetter = $firstLetter;
	}

	public function actionUpdate(int $id): void
	{
		if (!$this->getUser()->isAllowed(Resource::BABYSITTER->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$this->template->id = $id;
	}
}
