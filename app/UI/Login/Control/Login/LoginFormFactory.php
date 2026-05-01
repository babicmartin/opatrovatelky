<?php declare(strict_types = 1);

namespace App\UI\Login\Control\Login;

use App\Model\Form\DTO\Login\LoginForm\LoginFormDTO;
use App\Model\Form\Factory\BaseFormFactory;
use App\Model\Security\AdminAuthenticator;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Nette\SmartObject;
use Psr\Log\LoggerInterface;

class LoginFormFactory
{
	use SmartObject;

	public function __construct(
		private readonly User $user,

        private readonly AdminAuthenticator $adminAuthenticator,
		private readonly LoggerInterface $logger,
		private readonly BaseFormFactory $baseFormFactory,
	) {	}

	public function create(): Form
	{
		$form = $this->baseFormFactory->create();

		$form->addText('email', 'Email')
			->setRequired('Email je povinný');
		$form->addPassword('password', 'Heslo')
			->setRequired('Heslo je povinné');
		$form->addSubmit('send', 'Prihlásiť');
		$form->onSuccess[] = [$this, 'onSuccess'];

		return $form;
	}

	public function onSuccess(Form $form, LoginFormDTO $formDTO): void
	{

		try {
			$this->user->setAuthenticator($this->adminAuthenticator);
			$this->user->login($formDTO->getEmail(), $formDTO->getPassword());
		} catch (AuthenticationException $exception) {
			$form->addError('Prihlásenie sa nepodarilo');
			$this->logger->warning('Login failed: ' . $formDTO->getEmail());
		}
	}

}