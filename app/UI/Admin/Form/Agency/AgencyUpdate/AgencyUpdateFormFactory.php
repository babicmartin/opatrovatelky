<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Agency\AgencyUpdate;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Agency\AgencyUpdate\AgencyUpdateForm;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Utils\Date\DateService;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class AgencyUpdateFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
		private DateService $dateService,
	) {
	}

	/**
	 * @param array<string, mixed> $agency
	 * @param array<int, string> $countryOptions
	 * @param array<int, string> $statusOptions
	 * @param callable(AgencyUpdateForm): void $onSuccess
	 */
	public function create(
		array $agency,
		array $countryOptions,
		array $statusOptions,
		callable $onSuccess,
	): Form {
		if (!$this->user->isAllowed(Resource::AGENCY->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'agency-update-form js-autosave-form');
		$form->addHidden('id', (string) $agency['id']);
		$form->addText('name', 'Názov firmy')
			->setDefaultValue((string) $agency['name'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('street', 'Ulica')
			->setDefaultValue((string) $agency['street'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('streetNumber', 'Číslo ulice')
			->setDefaultValue((string) $agency['streetNumber'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('psc', 'PSČ')
			->setDefaultValue((string) $agency['psc'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('city', 'Mesto')
			->setDefaultValue((string) $agency['city'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addSelect('state', 'Krajina', $countryOptions)
			->setDefaultValue((int) $agency['state'])
			->setHtmlAttribute('class', 'form-select updateSelect js-autosave-control');
		$form->addText('dateStart', 'Začiatok spolupráce')
			->setDefaultValue($agency['dateStart'] instanceof \DateTimeImmutable ? $agency['dateStart']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addText('personSurname', 'Kontaktná osoba - priezvisko')
			->setDefaultValue((string) $agency['personSurname'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('personName', 'Kontaktná osoba - meno')
			->setDefaultValue((string) $agency['personName'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('ico', 'IČO')
			->setDefaultValue((string) $agency['ico'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('icDph', 'IČ DPH')
			->setDefaultValue((string) $agency['icDph'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('web', 'Web')
			->setDefaultValue((string) $agency['web'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('phone', 'Telefónne číslo')
			->setDefaultValue((string) $agency['phone'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('email', 'Email')
			->setDefaultValue((string) $agency['email'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addSelect('status', 'Status', $statusOptions)
			->setDefaultValue((int) $agency['status'])
			->setHtmlAttribute('class', 'form-select updateSelect js-autosave-control');
		$form->addTextArea('notice', 'Poznámka')
			->setDefaultValue((string) $agency['notice'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h300');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::AGENCY->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new AgencyUpdateForm(
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
