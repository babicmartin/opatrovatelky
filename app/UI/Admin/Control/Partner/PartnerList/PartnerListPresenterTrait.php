<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Partner\PartnerList;

trait PartnerListPresenterTrait
{
	private PartnerListControlFactory $partnerListControlFactory;

	public function injectPartnerListControlFactory(
		PartnerListControlFactory $partnerListControlFactory,
	): void {
		$this->partnerListControlFactory = $partnerListControlFactory;
	}

	protected function createComponentPartnerList(): PartnerListControl
	{
		$control = $this->partnerListControlFactory->create();
		$country = $this->getParameter('country') !== null ? (int) $this->getParameter('country') : null;
		$status = $this->getParameter('status') !== null ? (int) $this->getParameter('status') : null;
		$control->setFilters(
			$country !== null && $country > 0 ? $country : null,
			$status !== null && $status > 0 ? $status : null,
		);

		return $control;
	}
}
