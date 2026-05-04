<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Babysitter\BabysitterList;

use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\BabysitterRepository;
use App\Model\Utils\Paginator\Paginator;
use Nette\Application\UI\Control;

class BabysitterListControl extends Control
{
	private const int ITEMS_PER_PAGE = 50;

	private int $page = 1;

	private ?string $firstLetter = null;

	private int $pageCount = 1;

	/** @var list<array<string, mixed>>|null */
	private ?array $rows = null;

	public function __construct(
		private readonly BabysitterRepository $babysitterRepository,
		private readonly PaginatorFactory $paginatorFactory,
	) {
	}

	public function setPage(int $page): static
	{
		$this->page = max(1, $page);

		return $this;
	}

	public function setFirstLetter(?string $firstLetter): static
	{
		$this->firstLetter = $firstLetter;

		return $this;
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/BabysitterListControl.latte');
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
			$pageCount = 1;
			$this->rows = $this->babysitterRepository->findBabysitterRows(
				$this->page,
				self::ITEMS_PER_PAGE,
				$this->firstLetter,
				$pageCount,
			);
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
			array_filter(
				[
					'first-letter' => $this->firstLetter,
				],
				static fn (mixed $value): bool => $value !== null && $value !== '',
			),
		);
	}
}
