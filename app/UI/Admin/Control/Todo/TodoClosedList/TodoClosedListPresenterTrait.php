<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Todo\TodoClosedList;

use App\Model\Enum\Acl\Resource;

trait TodoClosedListPresenterTrait
{
	private TodoClosedListControlFactory $todoClosedListControlFactory;

	public function injectTodoClosedListControlFactory(
		TodoClosedListControlFactory $todoClosedListControlFactory,
	): void {
		$this->todoClosedListControlFactory = $todoClosedListControlFactory;
	}

	protected function createComponentTodoClosedList(): TodoClosedListControl
	{
		$user = $this->getUser();
		$userId = $user->isLoggedIn() ? (int) $user->getId() : null;
		$canViewAll = $user->isAllowed(Resource::TODO_VIEW_ALL->value);
		$canManage = $user->isAllowed(Resource::TODO->value);

		$control = $this->todoClosedListControlFactory->create();
		$control->setPage($this->getParameter('page') !== null ? (int) $this->getParameter('page') : 1);
		$control->setContext($userId, $canViewAll, $canManage);

		$statusParam = $this->getParameter('status');
		$control->setStatusFilter($statusParam !== null ? (int) $statusParam : null);

		return $control;
	}
}
