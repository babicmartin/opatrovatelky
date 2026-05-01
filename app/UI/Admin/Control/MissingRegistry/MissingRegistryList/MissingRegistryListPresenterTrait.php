<?php declare(strict_types=1);

namespace App\UI\Admin\Control\MissingRegistry\MissingRegistryList;

trait MissingRegistryListPresenterTrait
{
	private MissingRegistryListControlFactory $missingRegistryListControlFactory;

	public function injectMissingRegistryListControlFactory(
		MissingRegistryListControlFactory $missingRegistryListControlFactory,
	): void {
		$this->missingRegistryListControlFactory = $missingRegistryListControlFactory;
	}

	protected function createComponentMissingRegistryList(): MissingRegistryListControl
	{
		$control = $this->missingRegistryListControlFactory->create();
		$control->setPage($this->getParameter('page') !== null ? (int) $this->getParameter('page') : 1);

		return $control;
	}
}
