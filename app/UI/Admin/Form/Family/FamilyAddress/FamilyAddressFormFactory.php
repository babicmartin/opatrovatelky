<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Family\FamilyAddress;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Family\FamilyAddress\FamilyAddressForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class FamilyAddressFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $family
	 * @param callable(FamilyAddressForm): void $onSuccess
	 */
	public function create(array $family, callable $onSuccess): Form
	{
		if (!$this->user->isAllowed(Resource::FAMILY->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()
			->setAttribute('class', 'family-address-form js-autosave-form')
			->setAttribute('style', 'display:contents;');

		$form->addHidden('id', (string) $family['id']);
		foreach ([
			'companyName' => 'Meno spoločnosti',
			'name' => 'Meno',
			'surname' => 'Priezvisko',
			'street' => 'Ulica',
			'streetNumber' => 'Číslo ulice',
			'psc' => 'PSČ',
			'city' => 'Mesto',
			'personSurname' => 'Kontaktná osoba - priezvisko',
			'personName' => 'Kontaktná osoba - meno',
			'personPhone' => 'Kontaktná osoba - telefónne číslo',
			'personEmail' => 'Kontaktná osoba - email',
			'patientPhone' => 'Pacient - telefónne číslo',
		] as $name => $label) {
			$form->addText($name, $label)
				->setDefaultValue((string) $family[$name])
				->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		}

		foreach ([
			'billing' => ['Fakturačné údaje', 'h100'],
			'employer' => ['Zamestnávateľ', 'h100'],
			'accommodationAddress' => ['Adresa ubytovania', 'h100'],
			'notice' => ['Poznámka', 'h150'],
		] as $name => [$label, $heightClass]) {
			$form->addTextArea($name, $label)
				->setDefaultValue((string) $family[$name])
				->setHtmlAttribute('class', 'form-control updateInput js-autosave-control ' . $heightClass);
		}

		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::FAMILY->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new FamilyAddressForm(
				(int) $values->id,
				(string) $values->companyName,
				(string) $values->name,
				(string) $values->surname,
				(string) $values->street,
				(string) $values->streetNumber,
				(string) $values->psc,
				(string) $values->city,
				(string) $values->billing,
				(string) $values->employer,
				(string) $values->accommodationAddress,
				(string) $values->notice,
				(string) $values->personSurname,
				(string) $values->personName,
				(string) $values->personPhone,
				(string) $values->personEmail,
				(string) $values->patientPhone,
			));
		};

		return $form;
	}
}
