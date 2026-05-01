<?php declare(strict_types = 1);

namespace App\UI\Login\Login;

use App\UI\Admin\AdminPresenter;
use App\UI\Login\Control\Login\LoginPresenterTrait;
use Nette\Application\Attributes\Persistent;

final class LoginPresenter extends AdminPresenter
{
	use LoginPresenterTrait;

	#[Persistent]
	public string $storeRequestId = '';

	public function actionDefault(): void
	{
		if ($this->getUser()->isLoggedIn()) {
			$this->redirect('@home');
		}
	}

	public function actionLogout(): void
	{
		if ($this->getUser()->isLoggedIn()) {
			$this->getUser()->logout();
		}

		$this->redirect('@login');
	}
}