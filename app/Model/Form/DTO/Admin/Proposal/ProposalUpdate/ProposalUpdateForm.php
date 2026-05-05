<?php declare(strict_types=1);

namespace App\Model\Form\DTO\Admin\Proposal\ProposalUpdate;

class ProposalUpdateForm
{
	public function __construct(
		public readonly int $id,
		public readonly int $status,
		public readonly int $babysitterId,
		public readonly ?\DateTimeImmutable $dateStartingWork,
		public readonly ?\DateTimeImmutable $dateProposalSended,
		public readonly string $notice,
	) {
	}
}
