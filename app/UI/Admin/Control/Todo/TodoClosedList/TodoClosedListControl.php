<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Todo\TodoClosedList;

use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\TodoClientRepository;
use App\Model\Utils\Paginator\Paginator;
use Nette\Application\UI\Control;

class TodoClosedListControl extends Control
{
	private const int ITEMS_PER_PAGE = 30;

	private int $page = 1;

	private ?int $userId = null;

	private bool $canViewAll = false;

	private bool $canManage = false;

	private ?int $statusId = null;

	private int $pageCount = 1;

	/** @var list<array<string, mixed>>|null */
	private ?array $rows = null;

	public function __construct(
		private readonly TodoClientRepository $todoClientRepository,
		private readonly PaginatorFactory $paginatorFactory,
	) {
	}

	public function setPage(int $page): static
	{
		$this->page = max(1, $page);

		return $this;
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
		$template->setFile(__DIR__ . '/templates/TodoClosedListControl.latte');
		$template->rows = $this->getRows();
		$template->paginator = $this->createPaginator();
		$template->canManage = $this->canManage;
		$template->render();
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function getRows(): array
	{
		if ($this->rows === null) {
			$pageCount = 1;
			$this->rows = $this->todoClientRepository->findDoneTodoRows(
				$this->page,
				self::ITEMS_PER_PAGE,
				$this->userId,
				$this->canViewAll,
				$this->statusId,
				$pageCount,
			);
			$this->pageCount = max(1, $pageCount);
		}

		return $this->rows;
	}

	private function createPaginator(): Paginator
	{
		$this->getRows();

		$routeParams = [];
		if ($this->statusId !== null) {
			$routeParams['status'] = $this->statusId;
		}

		return $this->paginatorFactory->createFromPageCount(
			$this->page,
			$this->pageCount,
			self::ITEMS_PER_PAGE,
			'this',
			$routeParams,
		);
	}
}
