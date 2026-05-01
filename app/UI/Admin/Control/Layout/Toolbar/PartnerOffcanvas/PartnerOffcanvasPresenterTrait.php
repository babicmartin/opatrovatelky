<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Layout\Toolbar\PartnerOffcanvas;

trait PartnerOffcanvasPresenterTrait
{
	private PartnerOffcanvasControlFactory $partnerOffcanvasControlFactory;

	public function injectPartnerOffcanvasControl(
		PartnerOffcanvasControlFactory $partnerOffcanvasControlFactory,
	): void {
		$this->partnerOffcanvasControlFactory = $partnerOffcanvasControlFactory;
	}

	protected function createComponentPartnerOffcanvas(): PartnerOffcanvasControl
	{
		return $this->partnerOffcanvasControlFactory->create();
	}
}
