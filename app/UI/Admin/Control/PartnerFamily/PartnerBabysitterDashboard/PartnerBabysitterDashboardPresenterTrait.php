<?php declare(strict_types=1);

namespace App\UI\Admin\Control\PartnerFamily\PartnerBabysitterDashboard;

trait PartnerBabysitterDashboardPresenterTrait
{
	private PartnerBabysitterDashboardControlFactory $partnerBabysitterDashboardControlFactory;


	public function injectPartnerBabysitterDashboardControlFactory(
		PartnerBabysitterDashboardControlFactory $partnerBabysitterDashboardControlFactory,
	): void {
		$this->partnerBabysitterDashboardControlFactory = $partnerBabysitterDashboardControlFactory;
	}


	protected function createComponentPartnerBabysitterDashboard(): PartnerBabysitterDashboardControl
	{
		$family = $this->getParameter('family');

		return $this->partnerBabysitterDashboardControlFactory->create(
			(int) $this->getParameter('id'),
			(string) ($this->getParameter('type') ?? 'all'),
			$family !== null && (int) $family > 0 ? (int) $family : null,
		);
	}
}
