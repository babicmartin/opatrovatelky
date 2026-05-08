<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Turnus\TurnusList;

trait TurnusListPresenterTrait
{
	private TurnusListControlFactory $turnusListControlFactory;

	public function injectTurnusListControlFactory(TurnusListControlFactory $turnusListControlFactory): void
	{
		$this->turnusListControlFactory = $turnusListControlFactory;
	}

	protected function createComponentTurnusList(): TurnusListControl
	{
		$control = $this->turnusListControlFactory->create();
		$control->setPage($this->getParameter('page') !== null ? (int) $this->getParameter('page') : 1);
		$control->setFilters(
			$this->getParameter('finish') !== null ? (int) $this->getParameter('finish') : 0,
			$this->getParameter('status') !== null ? (int) $this->getParameter('status') : null,
			$this->getParameter('country') !== null ? (int) $this->getParameter('country') : null,
			$this->getParameter('agency') !== null ? (int) $this->getParameter('agency') : null,
			$this->getParameter('order') !== null ? (int) $this->getParameter('order') : 0,
		);

		return $control;
	}
}
