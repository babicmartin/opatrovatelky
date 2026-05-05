<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Family\FamilyShortInfo;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Family\FamilyShortInfo\FamilyShortInfoForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class FamilyShortInfoFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $family
	 * @param array<int, string> $countryOptions
	 * @param callable(FamilyShortInfoForm): void $onSuccess
	 */
	public function create(array $family, array $countryOptions, callable $onSuccess): Form
	{
		if (!$this->user->isAllowed(Resource::FAMILY->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'family-short-info-form js-autosave-form');

		$form->addHidden('id', (string) $family['id']);
		$form->addText('deProjectNumber', 'Číslo DE projektu')
			->setDefaultValue((string) $family['deProjectNumber'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addSelect('state', 'Krajina', $countryOptions)
			->setDefaultValue((int) $family['state'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess, $family): void {
			if (!$this->user->isAllowed(Resource::FAMILY->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new FamilyShortInfoForm(
				(int) $values->id,
				(string) $family['clientNumber'],
				(string) $values->deProjectNumber,
				(int) $values->state,
			));
		};

		return $form;
	}
}
