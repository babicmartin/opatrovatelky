<?php declare(strict_types=1);

namespace App\UI\Admin\Todo;

use App\Model\Enum\Acl\Resource;
use App\Model\Form\DTO\Admin\Todo\TodoUpdate\TodoUpdateForm;
use App\Model\Repository\TodoClientRepository;
use App\UI\Admin\AdminPresenter;
use App\UI\Admin\Control\Todo\TodoActiveList\TodoActiveListPresenterTrait;
use App\UI\Admin\Control\Todo\TodoClosedList\TodoClosedListPresenterTrait;
use App\UI\Admin\Form\Todo\TodoUpdate\TodoUpdateFormFactory;
use Nette\Application\UI\Form;

class TodoPresenter extends AdminPresenter
{
	use TodoActiveListPresenterTrait;
	use TodoClosedListPresenterTrait;

	private int $todoId = 0;

	/** @var array<string, mixed>|null */
	private ?array $todo = null;

	public function __construct(
		private readonly TodoClientRepository $todoClientRepository,
		private readonly TodoUpdateFormFactory $todoUpdateFormFactory,
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

		$this->todoId = $id;
		$this->todo = $this->getAllowedTodo($id);
		if ($this->todo === null) {
			$this->error('Na úpravu tejto úlohy nemáte oprávnenie alebo neexistuje.', 403);
		}

		$this->template->id = $id;
		$this->template->todo = $this->todo;
		$this->template->canOpenFamily = $this->getUser()->isAllowed(Resource::FAMILY->value);
		$this->template->canOpenBabysitter = $this->getUser()->isAllowed(Resource::BABYSITTER->value);
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

	protected function createComponentTodoUpdateForm(): Form
	{
		$todo = $this->getTodo();

		return $this->todoUpdateFormFactory->create(
			$todo,
			$this->todoClientRepository->findFamilyOptions((int) $todo['familyId']),
			$this->todoClientRepository->findBabysitterOptions((int) $todo['babysitterId']),
			$this->todoClientRepository->findUserOptions([
				(int) $todo['todoFromUser'],
				(int) $todo['todoToUser1'],
				(int) $todo['todoToUser2'],
			]),
			$this->todoClientRepository->findStatusOptions(),
			$this->todoUpdateFormSucceeded(...),
		);
	}

	private function todoUpdateFormSucceeded(TodoUpdateForm $form): void
	{
		if ($this->getAllowedTodo($form->id) === null) {
			$this->error('Na úpravu tejto úlohy nemáte oprávnenie alebo neexistuje.', 403);
		}

		$this->todoClientRepository->updateFromForm($form);

		if ($this->isAjax()) {
			$this->sendJson(['success' => true]);
		}

		$this->flashMessage('Úloha bola uložená.', 'success');
		$this->redirect('this');
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getTodo(): array
	{
		if ($this->todo === null) {
			$this->todo = $this->getAllowedTodo($this->todoId);
			if ($this->todo === null) {
				$this->error('Na úpravu tejto úlohy nemáte oprávnenie alebo neexistuje.', 403);
			}
		}

		return $this->todo;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function getAllowedTodo(int $id): ?array
	{
		$user = $this->getUser();
		$userId = $user->isLoggedIn() ? (int) $user->getId() : null;

		return $this->todoClientRepository->findUpdateRowForUser(
			$id,
			$userId,
			$user->isAllowed(Resource::TODO_VIEW_ALL->value),
		);
	}
}
