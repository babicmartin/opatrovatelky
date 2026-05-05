<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Babysitter\BabysitterEducation;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterEducation\BabysitterEducationForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class BabysitterEducationFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $babysitter
	 * @param array<int, string> $educationOptions
	 * @param array<int, string> $drivingOptions
	 * @param array<int, string> $yesNoOptions
	 * @param array<int, string> $languageOptions
	 * @param callable(BabysitterEducationForm): void $onSuccess
	 */
	public function create(
		array $babysitter,
		array $educationOptions,
		array $drivingOptions,
		array $yesNoOptions,
		array $languageOptions,
		callable $onSuccess,
	): Form {
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()
			->setAttribute('class', 'babysitter-education-form js-autosave-form')
			->setAttribute('style', 'display:contents;');
		$form->addHidden('id', (string) $babysitter['id']);
		$form->addSelect('education', 'Vzdelanie', $educationOptions)
			->setDefaultValue((int) $babysitter['education'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('drivingLicence', 'Vodičský preukaz', $drivingOptions)
			->setDefaultValue((int) $babysitter['drivingLicence'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('readyDrive', 'Pripravený riadiť', $yesNoOptions)
			->setDefaultValue((int) $babysitter['readyDrive'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('languageSkills', 'Jazykové znalosti', $languageOptions)
			->setDefaultValue((int) $babysitter['languageSkills'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addTextArea('languageSkillsOther', 'Iné jazyky')
			->setDefaultValue((string) $babysitter['languageSkillsOther'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h100');
		$form->addSelect('course', 'Kurzy', $yesNoOptions)
			->setDefaultValue((int) $babysitter['course'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addTextArea('courseDetail', 'Kurzy - detail')
			->setDefaultValue((string) $babysitter['courseDetail'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control h200');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new BabysitterEducationForm(
				(int) $values->id,
				(int) $values->education,
				(int) $values->drivingLicence,
				(int) $values->readyDrive,
				(int) $values->languageSkills,
				(string) $values->languageSkillsOther,
				(int) $values->course,
				(string) $values->courseDetail,
			));
		};

		return $form;
	}
}
