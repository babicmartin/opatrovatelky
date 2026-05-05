<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Babysitter\BabysitterPdf;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Babysitter\BabysitterPdf\BabysitterPdfForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final readonly class BabysitterPdfFormFactory
{
	public function __construct(
		private BaseFormFactory $baseFormFactory,
		private User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $babysitter
	 * @param array<int, string> $yesNoOptions
	 * @param callable(BabysitterPdfForm): void $onSuccess
	 */
	public function create(array $babysitter, array $yesNoOptions, callable $onSuccess): Form
	{
		if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
			throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'babysitter-pdf-form js-autosave-form');
		$form->addHidden('id', (string) $babysitter['id']);
		$form->addSelect('profilShowContact', 'Zobraziť kontakt', $yesNoOptions)
			->setDefaultValue((int) $babysitter['profilShowContact'])
			->setHtmlAttribute('class', 'form-control updateSelectReload js-autosave-control');
		$form->addSubmit('save', 'Uložiť')->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::BABYSITTER->value)) {
				throw new \Nette\Application\ForbiddenRequestException('Prístup zamietnutý');
			}

			$onSuccess(new BabysitterPdfForm(
				(int) $values->id,
				(int) $values->profilShowContact,
			));
		};

		return $form;
	}
}
