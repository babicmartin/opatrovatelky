<?php declare(strict_types=1);

namespace App\UI\Login\Control\Login;

use App\Model\Form\DTO\Login\LoginForm\LoginFormDTO;
use Nette\Application\UI\Form;

trait LoginPresenterTrait
{
	private LoginControlFactory $loginControlFactory;

	public function injectLoginControlTrait(
		LoginControlFactory $loginControlFactory,
	): void
	{
		$this->loginControlFactory = $loginControlFactory;
	}

	protected function createComponentLogin(): LoginControl
	{
		return $this->loginControlFactory->create([$this, 'loginFormSucceeded']);
	}

	public function loginFormSucceeded(Form $form, LoginFormDTO $formDTO): void
	{
		$this->restoreRequest($this->storeRequestId);
		$this->redirect('@home');
	}
}