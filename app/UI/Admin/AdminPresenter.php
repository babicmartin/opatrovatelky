<?php declare(strict_types=1);

namespace App\UI\Admin;

use App\UI\Admin\Control\Layout\Menu\MenuPresenterTrait;
use App\UI\Admin\Control\Layout\Search\SearchPresenterTrait;
use App\UI\Admin\Control\Layout\Toolbar\FamilyOffcanvas\FamilyOffcanvasPresenterTrait;
use App\UI\Admin\Control\Layout\Toolbar\PartnerOffcanvas\PartnerOffcanvasPresenterTrait;
use App\UI\Admin\Control\Layout\Toolbar\ProjectOffcanvas\ProjectOffcanvasPresenterTrait;
use App\UI\Admin\Control\Layout\Toolbar\ToolbarPresenterTrait;
use App\UI\Admin\Control\User\UserProfileImage\UserProfileImagePresenterTrait;
use Nette\Application\UI\Presenter;

abstract class AdminPresenter extends Presenter
{
	use FamilyOffcanvasPresenterTrait;
	use MenuPresenterTrait;
	use PartnerOffcanvasPresenterTrait;
	use ProjectOffcanvasPresenterTrait;
	use SearchPresenterTrait;
	use ToolbarPresenterTrait;
	use UserProfileImagePresenterTrait;

	protected function getResource(): ?string
	{
		return null;
	}

	protected function getPrivilege(): string
	{
		return 'default';
	}

	protected function startup(): void
	{
		parent::startup();

		$this->getSession()->start();


		// TODO: uncomment after development
		// if (!$this->getUser()->isLoggedIn() && $this->getName() !== 'Login:Login') {
		// 	$this->redirect('@login');
		// }


		$resource = $this->getResource();

		if ($resource !== null && !$this->getUser()->isAllowed($resource, $this->getPrivilege())) {
			$this->error('Prístup zamietnutý', 403);
		}
	}

	protected function beforeRender(): void
	{
	}
}
