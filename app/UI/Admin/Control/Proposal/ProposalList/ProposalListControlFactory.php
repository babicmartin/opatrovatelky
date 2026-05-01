<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Proposal\ProposalList;

interface ProposalListControlFactory
{
	public function create(): ProposalListControl;
}
