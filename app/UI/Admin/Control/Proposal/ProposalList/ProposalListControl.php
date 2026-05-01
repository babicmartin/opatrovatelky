<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Proposal\ProposalList;

use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\FamilyProposalRepository;
use App\Model\Utils\Paginator\Paginator;
use Nette\Application\UI\Control;

class ProposalListControl extends Control
{
	private const int ITEMS_PER_PAGE = 50;

	private int $page = 1;

	private int $pageCount = 1;

	/** @var list<array<string, mixed>>|null */
	private ?array $rows = null;

	public function __construct(
		private readonly FamilyProposalRepository $familyProposalRepository,
		private readonly PaginatorFactory $paginatorFactory,
	) {
	}

	public function setPage(int $page): static
	{
		$this->page = max(1, $page);

		return $this;
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/ProposalListControl.latte');
		$template->rows = $this->getRows();
		$template->paginator = $this->createPaginator();
		$template->render();
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function getRows(): array
	{
		if ($this->rows === null) {
			/** @var int<1, max> $itemsPerPage */
			$itemsPerPage = self::ITEMS_PER_PAGE;
			/** @var int<1, max> $page */
			$page = max(1, $this->page);
			$pageCount = 1;
			$this->rows = $this->familyProposalRepository->findVisibleRows($page, $itemsPerPage, $pageCount);
			$this->pageCount = max(1, $pageCount);
		}

		return $this->rows;
	}

	private function createPaginator(): Paginator
	{
		$this->getRows();

		return $this->paginatorFactory->createFromPageCount(
			$this->page,
			$this->pageCount,
			self::ITEMS_PER_PAGE,
			'this',
		);
	}
}
