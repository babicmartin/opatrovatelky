<?php declare(strict_types=1);

namespace App\UI\Admin\Form\Country\CountryUpdate;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final class CountryUpdateFormFactory
{
	public function __construct(
		private readonly BaseFormFactory $baseFormFactory,
		private readonly User $user,
	) {
	}

	/**
	 * @param array{id:int,name:string,german:string} $country
	 * @param callable(int, array{name:mixed,german:mixed}): void $onSuccess
	 */
	public function create(array $country, callable $onSuccess): Form
	{
		$this->assertAllowed();

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'country-update-form js-autosave-form');

		$form->addHidden('id', (string) $country['id']);
		$form->addText('name', 'Názov krajiny')
			->setDefaultValue($country['name'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('german', 'Preklad')
			->setDefaultValue($country['german'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$this->assertAllowed();
			$onSuccess((int) $values->id, [
				'name' => $values->name,
				'german' => $values->german,
			]);
		};

		return $form;
	}

	/**
	 * @param callable(Form, ArrayHash): void $onSuccess
	 */
	public function createImageForm(callable $onSuccess): Form
	{
		$this->assertAllowed();

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('enctype', 'multipart/form-data');

		$form->addUpload('image', 'Obrázok')
			->setRequired('Vyberte obrázok.')
			->addRule(Form::Image, 'Iba obrázky typu .png, .jpg');
		$form->addSubmit('save', 'Nahrať')
			->setHtmlAttribute('class', 'btn btn-success btn-sm');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$this->assertAllowed();
			$onSuccess($form, $values);
		};

		return $form;
	}

	private function assertAllowed(): void
	{
		if (!$this->user->isAllowed(Resource::COUNTRY->value)) {
			throw new ForbiddenRequestException('Prístup zamietnutý.');
		}
	}
}
