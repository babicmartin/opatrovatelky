<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Babysitter\BabysitterMain;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterMain\BabysitterMainForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class BabysitterMainFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $babysitter
	 * @param array<int, string> $typeOptions
	 * @param array<int, string> $agencyOptions
	 * @param array<int, string> $workingStatusOptions
	 * @param array<int, string> $statusOptions
	 * @param array<int, string> $userOptions
	 * @param array<int, string> $blacklistOptions
	 * @param callable(BabysitterMainForm): void $onSuccess
	 */
	public function create(
		array $babysitter,
		array $typeOptions,
		array $agencyOptions,
		array $workingStatusOptions,
		array $statusOptions,
		array $userOptions,
		array $blacklistOptions,
		callable $onSuccess,
	): Form {
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'babysitter-main-form js-autosave-form');
		$form->addHidden('id', (string) $babysitter['id']);
		$form->addSelect('type', 'Typ pracovnej pozície', $typeOptions)
			->setDefaultValue((int) $babysitter['type'])
			->setHtmlAttribute('class', 'form-control updateSelectReload js-autosave-control');
		$form->addSelect('agencyId', 'Agentúra', $agencyOptions)
			->setDefaultValue((int) $babysitter['agencyId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('workingStatus', 'Pracovný status', $workingStatusOptions)
			->setDefaultValue((int) $babysitter['workingStatus'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('status', 'Status', $statusOptions)
			->setDefaultValue((int) $babysitter['status'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('firstContactUserId', 'Prvý kontakt vytvoril', $userOptions)
			->setDefaultValue((int) $babysitter['firstContactUserId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('blacklist', 'Blacklist', $blacklistOptions)
			->setDefaultValue((int) $babysitter['blacklist'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addTextArea('notice', 'Poznámka')
			->setDefaultValue((string) $babysitter['notice'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h200');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new BabysitterMainForm(
				(int) $values->id,
				(int) $values->type,
				(int) $values->agencyId,
				(int) $values->workingStatus,
				(int) $values->status,
				(int) $values->firstContactUserId,
				(int) $values->blacklist,
				(string) $values->notice,
			));
		};

		return $form;
	}
}
