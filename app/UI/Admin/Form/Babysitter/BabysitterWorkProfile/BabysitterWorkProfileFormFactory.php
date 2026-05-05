<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Babysitter\BabysitterWorkProfile;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterWorkProfile\BabysitterWorkProfileForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class BabysitterWorkProfileFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $babysitter
	 * @param array<int, string> $workPositionOptions
	 * @param list<int> $qualificationIds
	 * @param list<int> $preferenceIds
	 * @param callable(BabysitterWorkProfileForm): void $onSuccess
	 */
	public function create(
		array $babysitter,
		array $workPositionOptions,
		array $qualificationIds,
		array $preferenceIds,
		callable $onSuccess,
	): Form {
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()
			->setAttribute('class', 'babysitter-work-profile-form js-autosave-form')
			->setAttribute('style', 'display:contents;');
		$form->addHidden('id', (string) $babysitter['id']);
		$form->addCheckboxList('qualificationIds', 'Kvalifikácia', $workPositionOptions)
			->setDefaultValue($qualificationIds)
			->setHtmlAttribute('class', 'js-autosave-control');
		$form->addCheckboxList('preferenceIds', 'Má záujem o', $workPositionOptions)
			->setDefaultValue($preferenceIds)
			->setHtmlAttribute('class', 'js-autosave-control');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new BabysitterWorkProfileForm(
				(int) $values->id,
				array_values(array_map('intval', (array) $values->qualificationIds)),
				array_values(array_map('intval', (array) $values->preferenceIds)),
			));
		};

		return $form;
	}
}
