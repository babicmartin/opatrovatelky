<?php declare(strict_types=1);

namespace App\UI\Admin\Todo;

use App\Model\Enum\Acl\Resource;
use App\Model\Repository\TodoClientRepository;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Todo\TodoActiveList\TodoActiveListPresenterTrait;
use App\UI\Admin\Control\Todo\TodoClosedList\TodoClosedListPresenterTrait;

class TodoPresenter extends AdminPresenter
{
	use TodoActiveListPresenterTrait;
	use TodoClosedListPresenterTrait;

	public function __construct(
		private readonly TodoClientRepository $todoClientRepository,
	) {
		parent::__construct();
	}

	protected function getResource(): string
	{
		return Resource::TODO->value;
	}

	public function actionDefault(?int $page = null, ?int $status = null): void
	{
		$this->template->page = $page;
		$this->template->status = $status;
		$this->template->canManage = $this->getUser()->isAllowed(Resource::TODO->value);
	}

	public function actionClosed(?int $page = null, ?int $status = null): void
	{
		$this->template->page = $page;
		$this->template->status = $status;
		$this->template->canManage = $this->getUser()->isAllowed(Resource::TODO->value);
	}

	public function actionUpdate(int $id): void
	{
		if (!$this->getUser()->isAllowed(Resource::TODO->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$user = $this->getUser();
		$userId = $user->isLoggedIn() ? (int) $user->getId() : null;
		$canViewAll = $user->isAllowed(Resource::TODO_VIEW_ALL->value);

		$row = $this->todoClientRepository->getItemForUser($id, $userId, $canViewAll);
		if ($row === null) {
			$this->error('Na úpravu tejto úlohy nemáte oprávnenie alebo neexistuje.', 403);
		}

		$this->template->id = $id;
	}

	public function handleCreate(): void
	{
		if (!$this->getUser()->isAllowed(Resource::TODO->value)) {
			$this->error('Prístup zamietnutý', 403);
		}

		$createdBy = $this->getUser()->isLoggedIn() ? (int) $this->getUser()->getId() : 0;
		$id = $this->todoClientRepository->createEmptyTodo($createdBy);
		$this->redirect('update', $id);
	}
}
