<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Translation\TranslationUpdate;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final class TranslationUpdateFormFactory
{
	public function __construct(
		private readonly BaseFormFactory $baseFormFactory,
		private readonly User $user,
	) {
	}

	/**
	 * @param array{id:int,slovak:string,german:string} $translation
	 * @param callable(int, array{german:mixed}): void $onSuccess
	 */
	public function create(array $translation, callable $onSuccess): Form
	{
		$this->assertAllowed();

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'translation-update-form js-autosave-form');

		$form->addHidden('id', (string) $translation['id']);
		$form->addText('german', $translation['slovak'])
			->setDefaultValue($translation['german'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$this->assertAllowed();
			$onSuccess((int) $values->id, [
				'german' => $values->german,
			]);
		};

		return $form;
	}

	private function assertAllowed(): void
	{
		if (!$this->user->isAllowed(Resource::TRANSLATION->value)) {
			throw new ForbiddenRequestException('Prístup zamietnutý.');
		}
	}
}
