<?php declare(strict_types=1);

namespace App\UI\Admin\Proposal;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Proposal\ProposalUpdate\ProposalUpdateForm;
use App\Model\Repository\FamilyProposalRepository;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Proposal\ProposalList\ProposalListPresenterTrait;
use App\UI\Admin\Form\Proposal\ProposalUpdate\ProposalUpdateFormFactory;
use Nette\Application\UI\Form;

class ProposalPresenter extends AdminPresenter
{
	use ProposalListPresenterTrait;

	private int $proposalId = 0;

	/** @var array<string, mixed>|null */
	private ?array $proposal = null;

	public function __construct(
		private readonly FamilyProposalRepository $familyProposalRepository,
		private readonly ProposalUpdateFormFactory $proposalUpdateFormFactory,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::PROPOSAL->value;
	}

	public function actionDefault(?int $page = null): void
	{
	}

	public function actionUpdate(int $id): void
	{
		$this->proposalId = $id;
		$this->proposal = $this->familyProposalRepository->findUpdateRow($id);
		if ($this->proposal === null) {
			$this->error('Návrh neexistuje.');
		}

		$this->template->proposal = $this->proposal;
	}

	protected function createComponentProposalUpdateForm(): Form
	{
		$proposal = $this->proposal ?? $this->familyProposalRepository->findUpdateRow($this->proposalId);
		if ($proposal === null) {
			$this->error('Návrh neexistuje.');
		}

		return $this->proposalUpdateFormFactory->create(
			$proposal,
			$this->familyProposalRepository->findStatusOptions(),
			$this->familyProposalRepository->findBabysitterOptions((int) $proposal['babysitterId']),
			$this->proposalUpdateFormSucceeded(...),
		);
	}

	private function proposalUpdateFormSucceeded(ProposalUpdateForm $form): void
	{
		$this->familyProposalRepository->updateFromForm($form);

		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->flashMessage('Návrh bol uložený.', 'success');
		$this->redirect('this');
	}
}
