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
		if (!$this->getHttpRequest()->isMethod('POST')) {
			$this->redirect($this->getUser()->isLoggedIn() ? '@home' : '@login');
		}

		if ($this->getHttpRequest()->getPost('_do') === null) {
			$this->error('Metóda nie je povolená.', 405);
		}
	}
}
