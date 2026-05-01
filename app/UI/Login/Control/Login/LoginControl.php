<?php declare(strict_types=1);

namespace App\UI\Login\Control\Login;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;

class LoginControl extends Control
{

	/**
	 * @var callable
	 */
	private $onSuccess;

    public function __construct(
		private readonly LoginFormFactory $loginFormFactory,
		callable $onSuccess,
	)
	{
		$this->onSuccess = $onSuccess;
	}

    public function render(): void
    {
        $this->getTemplate()->setFile(__DIR__ .'/templates/LoginControl.latte');
        $this->getTemplate()->render();
    }

    protected function createComponentForm(): Form
    {
        $form = $this->loginFormFactory->create();

		$form->onSuccess[] = $this->onSuccess;

        return $form;
    }



}