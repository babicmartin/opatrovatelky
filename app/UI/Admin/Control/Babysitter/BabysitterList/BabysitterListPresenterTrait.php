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

		$query = $this->getHttpRequest()->getQuery();
		$firstLetterRaw = $query['first-letter'] ?? null;
		$workingStatusRaw = $query['working-status'] ?? null;

		$control->setFilters(
			$this->getParameter('country') !== null ? (int) $this->getParameter('country') : null,
			$this->getParameter('language') !== null ? (int) $this->getParameter('language') : null,
			is_string($workingStatusRaw) && $workingStatusRaw !== '' ? (int) $workingStatusRaw : null,
			$this->getParameter('gender') !== null ? (int) $this->getParameter('gender') : null,
			$this->getParameter('driver') !== null ? (int) $this->getParameter('driver') : null,
			$this->getParameter('smoker') !== null ? (int) $this->getParameter('smoker') : null,
			$this->getParameter('agency') !== null ? (int) $this->getParameter('agency') : null,
			$this->getParameter('status') !== null ? (int) $this->getParameter('status') : null,
			is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null,
		);

		return $control;
	}
}
