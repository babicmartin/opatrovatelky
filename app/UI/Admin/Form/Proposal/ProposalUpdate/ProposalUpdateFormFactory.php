<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Proposal\ProposalUpdate;

use App\Model\Form\DTO\Admin\Proposal\ProposalUpdate\ProposalUpdateForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class ProposalUpdateFormFactory
{
	public function __construct(
		private readonly BaseFormFactory $baseFormFactory,
	) {
	}

	/**
	 * @param array<string, mixed> $proposal
	 * @param array<int, string> $statusOptions
	 * @param array<int, string> $babysitterOptions
	 * @param callable(ProposalUpdateForm): void $onSuccess
	 */
	public function create(array $proposal, array $statusOptions, array $babysitterOptions, callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'proposal-update-form js-autosave-form');

		$form->addHidden('id', (string) $proposal['id']);
		$form->addSelect('status', 'Status', $statusOptions)
			->setDefaultValue((int) $proposal['status'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('babysitterId', 'Opatrovateľka', $babysitterOptions)
			->setDefaultValue((int) $proposal['babysitterId'])
			->setHtmlAttribute('class', 'form-control updateSelectReload js-autosave-control');
		$form->addText('dateStartingWork', 'Kedy môže nastúpiť')
			->setDefaultValue((string) $proposal['dateStartingWork'])
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addText('dateProposalSended', 'Odoslané klientovi')
			->setDefaultValue((string) $proposal['dateProposalSended'])
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addTextArea('notice', 'Poznámka')
			->setDefaultValue((string) $proposal['notice'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h150');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$onSuccess(new ProposalUpdateForm(
				(int) $values->id,
				(int) $values->status,
				(int) $values->babysitterId,
				(string) $values->dateStartingWork,
				(string) $values->dateProposalSended,
				(string) $values->notice,
			));
		};

		return $form;
	}
}
