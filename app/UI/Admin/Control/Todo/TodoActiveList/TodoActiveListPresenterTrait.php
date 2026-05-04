<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Todo\TodoActiveList;

use App\Model\Enum\Acl\Resource;

trait TodoActiveListPresenterTrait
{
	private TodoActiveListControlFactory $todoActiveListControlFactory;

	public function injectTodoActiveListControlFactory(
		TodoActiveListControlFactory $todoActiveListControlFactory,
	): void {
		$this->todoActiveListControlFactory = $todoActiveListControlFactory;
	}

	protected function createComponentTodoActiveList(): TodoActiveListControl
	{
		$user = $this->getUser();
		$userId = $user->isLoggedIn() ? (int) $user->getId() : null;
		$canViewAll = $user->isAllowed(Resource::TODO_VIEW_ALL->value);
		$canManage = $user->isAllowed(Resource::TODO->value);

		$control = $this->todoActiveListControlFactory->create();
		$control->setContext($userId, $canViewAll, $canManage);

		$statusParam = $this->getParameter('status');
		$control->setStatusFilter($statusParam !== null ? (int) $statusParam : null);

		return $control;
	}
}
