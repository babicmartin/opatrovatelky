<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Todo\TodoActiveList;

use App\Model\Repository\TodoClientRepository;
use Nette\Application\UI\Control;

class TodoActiveListControl extends Control
{
	private ?int $userId = null;

	private bool $canViewAll = false;

	private bool $canManage = false;

	private ?int $statusId = null;

	public function __construct(
		private readonly TodoClientRepository $todoClientRepository,
	) {
	}

	public function setContext(?int $userId, bool $canViewAll, bool $canManage): static
	{
		$this->userId = $userId;
		$this->canViewAll = $canViewAll;
		$this->canManage = $canManage;

		return $this;
	}

	public function setStatusFilter(?int $statusId): static
	{
		$this->statusId = $statusId;

		return $this;
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/TodoActiveListControl.latte');
		$template->rows = $this->todoClientRepository->findActiveTodoRows(
			$this->userId,
			$this->canViewAll,
			$this->statusId,
		);
		$template->canManage = $this->canManage;
		$template->render();
	}
}
