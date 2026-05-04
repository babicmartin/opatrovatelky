<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Filter\AlphabetFilter;

trait AlphabetFilterPresenterTrait
{
	private AlphabetFilterControlFactory $alphabetFilterControlFactory;

	public function injectAlphabetFilterControlFactory(
		AlphabetFilterControlFactory $alphabetFilterControlFactory,
	): void {
		$this->alphabetFilterControlFactory = $alphabetFilterControlFactory;
	}

	protected function createComponentAlphabetFilter(): AlphabetFilterControl
	{
		$control = $this->alphabetFilterControlFactory->create();
		$query = $this->getHttpRequest()->getQuery();
		$firstLetterRaw = $query['first-letter'] ?? null;
		$selectedLetter = is_string($firstLetterRaw) && $firstLetterRaw !== '' ? $firstLetterRaw : null;
		unset($query['first-letter'], $query['page']);

		$control->setFilterState($selectedLetter, $query);

		return $control;
	}
}
