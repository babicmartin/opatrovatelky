<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Partner\PartnerUpdate;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Partner\PartnerUpdate\PartnerUpdateForm;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Utils\Date\DateService;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class PartnerUpdateFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
		private DateService $dateService,
	) {
	}

	/**
	 * @param array<string, mixed> $partner
	 * @param array<int, string> $countryOptions
	 * @param array<int, string> $statusOptions
	 * @param callable(PartnerUpdateForm): void $onSuccess
	 */
	public function create(
		array $partner,
		array $countryOptions,
		array $statusOptions,
		callable $onSuccess,
	): Form {
		if (!$this->user->isAllowed(Resource::PARTNER->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'partner-update-form js-autosave-form');
		$form->addHidden('id', (string) $partner['id']);
		$form->addText('name', 'Názov firmy')
			->setDefaultValue((string) $partner['name'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('street', 'Ulica')
			->setDefaultValue((string) $partner['street'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('streetNumber', 'Číslo ulice')
			->setDefaultValue((string) $partner['streetNumber'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('psc', 'PSČ')
			->setDefaultValue((string) $partner['psc'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('city', 'Mesto')
			->setDefaultValue((string) $partner['city'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addSelect('state', 'Krajina', $countryOptions)
			->setDefaultValue((int) $partner['state'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addText('dateStart', 'Začiatok spolupráce')
			->setDefaultValue($partner['dateStart'] instanceof \DateTimeImmutable ? $partner['dateStart']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addText('personSurname', 'Kontaktná osoba - priezvisko')
			->setDefaultValue((string) $partner['personSurname'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('personName', 'Kontaktná osoba - meno')
			->setDefaultValue((string) $partner['personName'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('ico', 'IČO')
			->setDefaultValue((string) $partner['ico'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('icDph', 'IČ DPH')
			->setDefaultValue((string) $partner['icDph'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('web', 'Web')
			->setDefaultValue((string) $partner['web'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('phone', 'Telefónne číslo')
			->setDefaultValue((string) $partner['phone'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('email', 'Email')
			->setDefaultValue((string) $partner['email'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addSelect('status', 'Status', $statusOptions)
			->setDefaultValue((int) $partner['status'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addTextArea('notice', 'Poznámka')
			->setDefaultValue((string) $partner['notice'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h100');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::PARTNER->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new PartnerUpdateForm(
				(int) $values->id,
				(string) $values->name,
				(string) $values->street,
				(string) $values->streetNumber,
				(string) $values->psc,
				(string) $values->city,
				(int) $values->state,
				$this->dateService->tryCreateFromUserInput((string) $values->dateStart),
				(string) $values->personSurname,
				(string) $values->personName,
				(string) $values->ico,
				(string) $values->icDph,
				(string) $values->web,
				(string) $values->phone,
				(string) $values->email,
				(int) $values->status,
				(string) $values->notice,
			));
		};

		return $form;
	}
}
