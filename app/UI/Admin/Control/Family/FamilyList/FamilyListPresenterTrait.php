<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Family\FamilyList;

trait FamilyListPresenterTrait
{
	private FamilyListControlFactory $familyListControlFactory;

	public function injectFamilyListControlFactory(
		FamilyListControlFactory $familyListControlFactory,
	): void {
		$this->familyListControlFactory = $familyListControlFactory;
	}

	protected function createComponentFamilyList(): FamilyListControl
	{
		$control = $this->familyListControlFactory->create();
		$control->setPage($this->getParameter('page') !== null ? (int) $this->getParameter('page') : 1);
		$firstLetterRaw = $this->getHttpRequest()->getQuery('first-letter');
		$firstLetter = is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null;
		$control->setFilters(
			$this->getParameter('country') !== null ? (int) $this->getParameter('country') : null,
			$this->getParameter('status') !== null ? (int) $this->getParameter('status') : null,
			$this->getParameter('partner') !== null ? (int) $this->getParameter('partner') : null,
			$firstLetter,
			$this->getParameter('city') !== null ? (string) $this->getParameter('city') : null,
			$this->getParameter('user') !== null ? (int) $this->getParameter('user') : null,
		);

		return $control;
	}
}
