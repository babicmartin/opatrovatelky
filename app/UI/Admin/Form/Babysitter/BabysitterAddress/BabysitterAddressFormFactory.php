<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Babysitter\BabysitterAddress;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterAddress\BabysitterAddressForm;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Utils\Date\DateService;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class BabysitterAddressFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
		private DateService $dateService,
	) {
	}

	/**
	 * @param array<string, mixed> $babysitter
	 * @param array<int, string> $genderOptions
	 * @param array<int, string> $countryOptions
	 * @param callable(BabysitterAddressForm): void $onSuccess
	 */
	public function create(array $babysitter, array $genderOptions, array $countryOptions, callable $onSuccess): Form
	{
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()
			->setAttribute('class', 'babysitter-address-form js-autosave-form')
			->setAttribute('style', 'display:contents;');
		$form->addHidden('id', (string) $babysitter['id']);
		foreach ([
			'name' => 'Meno',
			'surname' => 'Priezvisko',
			'city' => 'Mesto',
			'street' => 'Ulica',
			'postalCode' => 'PSČ',
			'phone' => 'Telefón',
			'phone2' => 'Telefón č.2',
			'email' => 'Email',
			'height' => 'Výška',
			'weight' => 'Váha',
			'contactPersonName' => 'Kont. osoba - meno',
			'contactPersonPhone' => 'Kont. osoba - telefón',
		] as $name => $label) {
			$form->addText($name, $label)
				->setDefaultValue((string) $babysitter[$name])
				->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		}
		$form->addText('birthday', 'Dátum narodenia')
			->setDefaultValue($babysitter['birthday'] instanceof \DateTimeImmutable ? $babysitter['birthday']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addSelect('pohlavie', 'Pohlavie', $genderOptions)
			->setDefaultValue((int) $babysitter['pohlavie'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('country', 'Národnosť', $countryOptions)
			->setDefaultValue((int) $babysitter['country'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		foreach ([
			'about' => ['O sebe', 'h200'],
			'requirements' => ['Požiadavky', 'h200'],
		] as $name => [$label, $heightClass]) {
			$form->addTextArea($name, $label)
				->setDefaultValue((string) $babysitter[$name])
				->setHtmlAttribute('class', 'form-control updateInput js-autosave-control ' . $heightClass);
		}
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new BabysitterAddressForm(
				(int) $values->id,
				(string) $values->name,
				(string) $values->surname,
				$this->dateService->tryCreateFromUserInput((string) $values->birthday),
				(int) $values->pohlavie,
				(int) $values->country,
				(string) $values->city,
				(string) $values->street,
				(string) $values->postalCode,
				(string) $values->phone,
				(string) $values->phone2,
				(string) $values->email,
				(string) $values->height,
				(string) $values->weight,
				(string) $values->about,
				(string) $values->requirements,
				(string) $values->contactPersonName,
				(string) $values->contactPersonPhone,
			));
		};

		return $form;
	}
}
