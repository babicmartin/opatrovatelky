<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Babysitter\BabysitterProfile;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterProfile\BabysitterProfileForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class BabysitterProfileFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $babysitter
	 * @param array<int, string> $smokerOptions
	 * @param array<int, string> $yesNoOptions
	 * @param array<int, string> $accommodationOptions
	 * @param array<int, string> $diseaseOptions
	 * @param list<int> $diseaseIds
	 * @param callable(BabysitterProfileForm): void $onSuccess
	 */
	public function create(
		array $babysitter,
		array $smokerOptions,
		array $yesNoOptions,
		array $accommodationOptions,
		array $diseaseOptions,
		array $diseaseIds,
		callable $onSuccess,
	): Form {
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()
			->setAttribute('class', 'babysitter-profile-form js-autosave-form')
			->setAttribute('style', 'display:contents;');
		$form->addHidden('id', (string) $babysitter['id']);
		$form->addSelect('smoker', 'Fajčiar', $smokerOptions)->setDefaultValue((int) $babysitter['smoker'])->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('allergy', 'Alergie', $yesNoOptions)->setDefaultValue((int) $babysitter['allergy'])->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('dailyCare', 'Denná starostlivosť', $yesNoOptions)->setDefaultValue((int) $babysitter['dailyCare'])->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('hourlyCare', 'Hodinová starostlivosť', $yesNoOptions)->setDefaultValue((int) $babysitter['hourlyCare'])->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('accommodationType', 'Ubytovanie', $accommodationOptions)->setDefaultValue((int) $babysitter['accommodationType'])->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('workShoes', 'Pracovná obuv', $yesNoOptions)->setDefaultValue((int) $babysitter['workShoes'])->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');

		foreach ([
			'allergyDetail' => ['Alergie - detail', 'h100'],
			'howLongWork' => [(int) $babysitter['type'] === 1 ? 'Ako dlho pracuje ako opatrovateľka' : 'Prax v obore', 'h100'],
			'howLongWorkGerman' => ['Ako dlho pracuje v Nemeckých krajinách', 'h100'],
			'timeScale' => ['Časové rozmedzie', 'h100'],
			'workPlace' => ['Miesto práce', 'h100'],
			'jobPositionInterest' => ['Záujem o pracovnú pozíciu', 'h150'],
			'workDescription' => [(int) $babysitter['type'] === 1 ? 'Činnosti pri opatrovanej osobe' : 'Popis pracovných pozícii', 'h300'],
			'generalActivities' => ['Všeobecné činnosti', 'h300'],
			'ratingAgency' => ['Hodnotenie agentúry', 'h100'],
		] as $name => [$label, $heightClass]) {
			$form->addTextArea($name, $label)
				->setDefaultValue((string) $babysitter[$name])
				->setHtmlAttribute('class', 'form-control updateInput js-autosave-control ' . $heightClass);
		}
		$form->addText('shoeSize', 'Veľkosť pracovnej obuvi')
			->setDefaultValue((string) $babysitter['shoeSize'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('germanTaxId', 'Nemecké daňové číslo')
			->setDefaultValue((string) $babysitter['germanTaxId'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addCheckboxList('diseaseIds', 'Skúsenosti s chorobami', $diseaseOptions)
			->setDefaultValue($diseaseIds)
			->setHtmlAttribute('class', 'js-autosave-control');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new BabysitterProfileForm(
				(int) $values->id,
				(int) $values->smoker,
				(int) $values->allergy,
				(string) $values->allergyDetail,
				(string) $values->howLongWork,
				(string) $values->howLongWorkGerman,
				(int) $values->dailyCare,
				(int) $values->hourlyCare,
				(int) $values->accommodationType,
				(string) $values->timeScale,
				(string) $values->workPlace,
				(string) $values->jobPositionInterest,
				(string) $values->workDescription,
				(string) $values->generalActivities,
				(string) $values->ratingAgency,
				(int) $values->workShoes,
				(string) $values->shoeSize,
				(string) $values->germanTaxId,
				array_values(array_map('intval', (array) $values->diseaseIds)),
			));
		};

		return $form;
	}
}
