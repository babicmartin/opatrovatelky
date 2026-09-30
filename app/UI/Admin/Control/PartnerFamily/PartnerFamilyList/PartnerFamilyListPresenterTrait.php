<?php declare(strict_types=1);

namespace App\UI\Admin\Control\PartnerFamily\PartnerFamilyList;

trait PartnerFamilyListPresenterTrait
{
	private PartnerFamilyListControlFactory $partnerFamilyListControlFactory;


	public function injectPartnerFamilyListControlFactory(
		PartnerFamilyListControlFactory $partnerFamilyListControlFactory,
	): void {
		$this->partnerFamilyListControlFactory = $partnerFamilyListControlFactory;
	}


	protected function createComponentPartnerFamilyList(): PartnerFamilyListControl
	{
		return $this->partnerFamilyListControlFactory->create((int) $this->getParameter('id'));
	}
}
