<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Search;

trait SearchPresenterTrait
{
	private SearchControlFactory $searchControlFactory;

	public function injectSearchControl(
		SearchControlFactory $searchControlFactory,
	): void {
		$this->searchControlFactory = $searchControlFactory;
	}

	protected function createComponentSearch(): SearchControl
	{
		return $this->searchControlFactory->create();
	}
}
