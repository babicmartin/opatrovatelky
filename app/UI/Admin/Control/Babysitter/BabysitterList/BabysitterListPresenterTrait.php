<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Babysitter\BabysitterList;

trait BabysitterListPresenterTrait
{
	private BabysitterListControlFactory $babysitterListControlFactory;

	public function injectBabysitterListControlFactory(
		BabysitterListControlFactory $babysitterListControlFactory,
	): void {
		$this->babysitterListControlFactory = $babysitterListControlFactory;
	}

	protected function createComponentBabysitterList(): BabysitterListControl
	{
		$control = $this->babysitterListControlFactory->create();
		$control->setPage($this->getParameter('page') !== null ? (int) $this->getParameter('page') : 1);
		$firstLetterRaw = $this->getHttpRequest()->getQuery('first-letter');
		$control->setFirstLetter(is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null);

		return $control;
	}
}
