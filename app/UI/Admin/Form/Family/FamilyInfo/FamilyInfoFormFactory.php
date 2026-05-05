<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Family\FamilyInfo;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Family\FamilyInfo\FamilyInfoForm;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Utils\Date\DateService;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class FamilyInfoFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
		private DateService $dateService,
	) {
	}

	/**
	 * @param array<string, mixed> $family
	 * @param array<int, string> $typeOptions
	 * @param array<int, string> $partnerOptions
	 * @param array<int, string> $userOptions
	 * @param array<int, string> $statusOptions
	 * @param array<int, string> $documentStatusOptions
	 * @param array<int, string> $workStatusOptions
	 * @param callable(FamilyInfoForm): void $onSuccess
	 */
	public function create(
		array $family,
		array $typeOptions,
		array $partnerOptions,
		array $userOptions,
		array $statusOptions,
		array $documentStatusOptions,
		array $workStatusOptions,
		callable $onSuccess,
	): Form {
		if (!$this->user->isAllowed(Resource::FAMILY->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'family-info-form js-autosave-form');

		$form->addHidden('id', (string) $family['id']);
		$form->addSelect('type', 'Rodina / Projekt', $typeOptions)
			->setDefaultValue((int) $family['type'])
			->setHtmlAttribute('class', 'form-control updateSelectReload js-autosave-control');
		$form->addSelect('partnerId', 'Partner', $partnerOptions)
			->setDefaultValue((int) $family['partnerId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('acquiredByUserId', 'Rodinu získal', $userOptions)
			->setDefaultValue((int) $family['acquiredByUserId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('userId', 'Spravuje', $userOptions)
			->setDefaultValue((int) $family['userId'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('status', 'Status', $statusOptions)
			->setDefaultValue((int) $family['status'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addText('phone', 'Telefónne číslo')
			->setDefaultValue((string) $family['phone'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('dateStart', 'Začiatok spolupráce')
			->setDefaultValue($family['dateStart'] instanceof \DateTimeImmutable ? $family['dateStart']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addText('dateTo', 'Koniec spolupráce')
			->setDefaultValue($family['dateTo'] instanceof \DateTimeImmutable ? $family['dateTo']->format('d.m.Y') : '')
			->setHtmlAttribute('class', 'form-control updateDate datepicker js-autosave-control')
			->setHtmlAttribute('autocomplete', 'off');
		$form->addSelect('orderStatus', 'Status objednávky', $documentStatusOptions)
			->setDefaultValue((int) $family['orderStatus'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('contractStatus', 'Status zmluvy', $documentStatusOptions)
			->setDefaultValue((int) $family['contractStatus'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('workStatusStaff', 'Pracovný status personálu', $workStatusOptions)
			->setDefaultValue((int) $family['workStatusStaff'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
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

			$onSuccess(new FamilyInfoForm(
				(int) $values->id,
				(int) $values->type,
				(int) $values->partnerId,
				(int) $values->acquiredByUserId,
				(int) $values->userId,
				(int) $values->status,
				(string) $values->phone,
				$this->dateService->tryCreateFromUserInput((string) $values->dateStart),
				$this->dateService->tryCreateFromUserInput((string) $values->dateTo),
				(int) $values->orderStatus,
				(int) $values->contractStatus,
				(int) $values->workStatusStaff,
				(string) $values->projectDescription,
				(string) $values->projectPositions,
				(string) $values->projectAvailablePositions,
			));
		};

		return $form;
	}
}
