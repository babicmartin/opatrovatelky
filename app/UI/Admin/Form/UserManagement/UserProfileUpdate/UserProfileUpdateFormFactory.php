<?php declare(strict_types=1);

namespace App\UI\Admin\Form\UserManagement\UserProfileUpdate;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserPasswordUpdateForm;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserAccessUpdateForm;
use App\Model\Form\DTO\Admin\UserManagement\UserProfileUpdate\UserProfileUpdateForm;
use App\Model\Form\Factory\BaseFormFactory;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

final class UserProfileUpdateFormFactory
{
	public function __construct(
		private readonly BaseFormFactory $baseFormFactory,
		private readonly User $user,
	) {
	}

	/**
	 * @param array<string, mixed> $user
	 * @param callable(UserProfileUpdateForm): void $onSuccess
	 */
	public function createProfileForm(array $user, callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'user-profile-update-form js-autosave-form');

		$form->addText('name', 'Meno')
			->setDefaultValue((string) $user['name'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('secondName', 'Priezvisko')
			->setDefaultValue((string) $user['secondName'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('acronym', 'Skratka')
			->setDefaultValue((string) $user['acronym'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addEmail('email', 'Email')
			->setDefaultValue((string) $user['email'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addText('color', 'Farba pozadia skratky')
			->setDefaultValue((string) $user['color'])
			->setHtmlAttribute('class', 'form-control updateInput js-autosave-control');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$onSuccess(new UserProfileUpdateForm(
				(string) $values->name,
				(string) $values->secondName,
				(string) $values->acronym,
				(string) $values->email,
				(string) $values->color,
			));
		};

		return $form;
	}

	/**
	 * @param array<string, mixed> $user
	 * @param array<int, string> $permissionOptions
	 * @param array<int, string> $activeOptions
	 * @param callable(UserAccessUpdateForm): void $onSuccess
	 */
	public function createAccessForm(array $user, array $permissionOptions, array $activeOptions, callable $onSuccess): Form
	{
		if (!$this->user->isAllowed(Resource::USER_MANAGEMENT->value)) {
			throw new ForbiddenRequestException('Prístup zamietnutý.');
		}

		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('class', 'user-access-update-form js-autosave-form');

		$form->addSelect('permission', 'Práva', $permissionOptions)
			->setDefaultValue((int) $user['permission'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSelect('active', 'Status', $activeOptions)
			->setDefaultValue((int) $user['active'])
			->setHtmlAttribute('class', 'form-control updateSelect js-autosave-control');
		$form->addSubmit('save', 'Uložiť')
			->setHtmlAttribute('class', 'd-none');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			if (!$this->user->isAllowed(Resource::USER_MANAGEMENT->value)) {
				$form->addError('Prístup zamietnutý.');
				return;
			}

			$onSuccess(new UserAccessUpdateForm(
				(int) $values->permission,
				(int) $values->active,
			));
		};

		return $form;
	}

	/**
	 * @param callable(UserPasswordUpdateForm): void $onSuccess
	 */
	public function createPasswordForm(callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();

		$form->addPassword('password', 'Heslo')
			->setRequired('Zadajte heslo.')
			->addRule(Form::MinLength, 'Heslo musí mať aspoň %d znakov.', 8)
			->addRule(Form::Pattern, 'Heslo musí obsahovať aspoň jednu číslicu.', '.*[0-9].*')
			->addRule(Form::Pattern, 'Heslo musí obsahovať aspoň jeden špeciálny znak.', '.*[^A-Za-z0-9].*')
			->setHtmlAttribute('class', 'form-control updateInput');
		$form->addPassword('passwordRepeat', 'Zopakovať heslo')
			->setRequired('Zopakujte heslo.')
			->addRule(Form::Equal, 'Heslá sa nezhodujú.', $form['password'])
			->setHtmlAttribute('class', 'form-control updateInput');
		$form->addSubmit('save', 'Uložiť nové heslo')
			->setHtmlAttribute('class', 'btn btn-success btn-sm');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSuccess): void {
			$onSuccess(new UserPasswordUpdateForm((string) $values->password));
		};

		return $form;
	}

	/**
	 * @param callable(Form, ArrayHash): void $onSuccess
	 */
	public function createImageForm(callable $onSuccess): Form
	{
		$form = $this->baseFormFactory->create();
		$form->getElementPrototype()->setAttribute('enctype', 'multipart/form-data');

		$form->addUpload('image', 'Obrázok')
			->setRequired('Vyberte obrázok.')
			->addRule(Form::Image, 'Iba obrázky typu .png, .jpg');
		$form->addSubmit('save', 'Nahrať')
			->setHtmlAttribute('class', 'btn btn-success btn-sm');

		$form->onSuccess[] = $onSuccess;

		return $form;
	}
}
