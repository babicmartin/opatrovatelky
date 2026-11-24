<?php declare(strict_types = 1);

namespace App\UI\Login\Login;

use App\Model\Latte\LatteDefaultVariables;
use App\Model\Security\MediaAccessCookie;
use App\Model\Security\SecurityAuditLogger;
use App\UI\Login\Control\Login\LoginPresenterTrait;
use App\UI\Login\Control\Logout\LogoutFormFactory;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\SimpleIdentity;

/**
 * The only page open to a guest. It deliberately does not extend AdminPresenter,
 * so no component or signal of the administration is reachable without signing in.
 */
final class LoginPresenter extends Presenter
{
	use LoginPresenterTrait;

	#[Persistent]
	public string $storeRequestId = '';

	private MediaAccessCookie $mediaAccessCookie;
	private SecurityAuditLogger $securityAuditLogger;
	private LatteDefaultVariables $latteDefaultVariables;
	private LogoutFormFactory $logoutFormFactory;

	public function injectLoginPresenterDependencies(
		MediaAccessCookie $mediaAccessCookie,
		SecurityAuditLogger $securityAuditLogger,
		LatteDefaultVariables $latteDefaultVariables,
		LogoutFormFactory $logoutFormFactory,
	): void
	{
		$this->mediaAccessCookie = $mediaAccessCookie;
		$this->securityAuditLogger = $securityAuditLogger;
		$this->latteDefaultVariables = $latteDefaultVariables;
		$this->logoutFormFactory = $logoutFormFactory;
	}

	protected function startup(): void
	{
		parent::startup();

		$this->getSession()->start();
		$this->mediaAccessCookie->sync($this->getUser()->isLoggedIn());
	}

	protected function beforeRender(): void
	{
		$this->template->cssVersion = $this->latteDefaultVariables->getCssVersion();
		$this->template->jsVersion = $this->latteDefaultVariables->getJsVersion();
	}

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

	protected function createComponentLogoutForm(): Form
	{
		$form = $this->logoutFormFactory->create($this->link('logout'));
		$form->onSuccess[] = [$this, 'logoutFormSucceeded'];
		$form->onError[] = function (): void {
			$this->error('Odhlásenie sa nepodarilo.', 403);
		};

		return $form;
	}

	public function logoutFormSucceeded(): void
	{
		if ($this->getUser()->isLoggedIn()) {
			$userId = $this->getUser()->getId();
			$identity = $this->getUser()->getIdentity();
			$identityData = $identity instanceof SimpleIdentity ? $identity->getData() : [];
			$this->securityAuditLogger->log(
				'logout',
				is_int($userId) ? $userId : null,
				isset($identityData['email']) ? (string) $identityData['email'] : null,
			);
			$this->getUser()->logout(true);
			$this->getSession()->regenerateId();
		}

		$this->mediaAccessCookie->clear();
		$this->redirect('@login');
	}
}
