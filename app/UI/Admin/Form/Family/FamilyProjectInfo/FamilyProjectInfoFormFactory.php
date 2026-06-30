<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Family\FamilyProjectInfo;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Family\FamilyProjectInfo\FamilyProjectInfoForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class FamilyProjectInfoFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $family
	 * @param callable(FamilyProjectInfoForm): void $onSuccess
	 */
	public function create(array $family, callable $onSuccess): Form
	{
		if (!$this->user->isAllowed(Resource::FAMILY->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()
			->setAttribute('class', 'family-info-form js-autosave-form')
			->setAttribute('style', 'display:contents;');

		$form->addHidden('id', (string) $family['id']);
		$form->addTextArea('projectDescription', 'Popis projektu')
			->setDefaultValue((string) $family['projectDescription'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h100');
		$form->addTextArea('projectPositions', 'Pozície na obsadenie')
			->setDefaultValue((string) $family['projectPositions'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h100');
		$form->addTextArea('projectAvailablePositions', 'Počet voľných pracovných miest')
			->setDefaultValue((string) $family['projectAvailablePositions'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h100');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::FAMILY->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new FamilyProjectInfoForm(
				(int) $values->id,
				(string) $values->projectDescription,
				(string) $values->projectPositions,
				(string) $values->projectAvailablePositions,
			));
		};

		return $form;
	}
}
