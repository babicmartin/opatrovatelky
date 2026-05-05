<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Agency\AgencyList;

trait AgencyListPresenterTrait
{
	private AgencyListControlFactory $agencyListControlFactory;

	public function injectAgencyListControlFactory(
		AgencyListControlFactory $agencyListControlFactory,
	): void {
		$this->agencyListControlFactory = $agencyListControlFactory;
	}

	protected function createComponentAgencyList(): AgencyListControl
	{
		$control = $this->agencyListControlFactory->create();
		$country = $this->getParameter('country') !== null ? (int) $this->getParameter('country') : null;
		$status = $this->getParameter('status') !== null ? (int) $this->getParameter('status') : null;
		$control->setFilters(
			$country !== null && $country > 0 ? $country : null,
			$status !== null && $status > 0 ? $status : null,
		);

		return $control;
	}
}
