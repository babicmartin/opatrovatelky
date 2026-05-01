<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Proposal\ProposalList;

trait ProposalListPresenterTrait
{
	private ProposalListControlFactory $proposalListControlFactory;

	public function injectProposalListControlFactory(
		ProposalListControlFactory $proposalListControlFactory,
	): void {
		$this->proposalListControlFactory = $proposalListControlFactory;
	}

	protected function createComponentProposalList(): ProposalListControl
	{
		$control = $this->proposalListControlFactory->create();
		$control->setPage($this->getParameter('page') !== null ? (int) $this->getParameter('page') : 1);

		return $control;
	}
}
