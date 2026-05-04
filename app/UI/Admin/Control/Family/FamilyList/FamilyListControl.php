<?php declare(strict_types=1);

namespace App\UI\Admin\Control\Family\FamilyList;

use App\Model\Factory\PaginatorFactory;
use App\Model\Repository\FamilyRepository;
use App\Model\Utils\Paginator\Paginator;
use Nette\Application\UI\Control;

class FamilyListControl extends Control
{
	private const int ITEMS_PER_PAGE = 50;

	private int $page = 1;

	private ?int $countryId = null;

	private ?int $statusId = null;

	private ?int $partnerId = null;

	private ?string $firstLetter = null;

	private ?string $city = null;

	private ?int $userId = null;

	private int $pageCount = 1;

	/** @var list<array<string, mixed>>|null */
	private ?array $rows = null;

	public function __construct(
		private readonly FamilyRepository $familyRepository,
		private readonly PaginatorFactory $paginatorFactory,
	) {
	}

	public function setPage(int $page): static
	{
		$this->page = max(1, $page);

		return $this;
	}

	public function setFilters(
		?int $countryId,
		?int $statusId,
		?int $partnerId,
		?string $firstLetter,
		?string $city,
		?int $userId,
	): static
	{
		$this->countryId = $countryId;
		$this->statusId = $statusId;
		$this->partnerId = $partnerId;
		$this->firstLetter = $firstLetter;
		$this->city = $city;
		$this->userId = $userId;

		return $this;
	}

	public function render(): void
	{
		$template = $this->getTemplate();
		$template->setFile(__DIR__ . '/templates/FamilyListControl.latte');
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
			$this->rows = $this->familyRepository->findFamilyRows(
				$this->page,
				self::ITEMS_PER_PAGE,
				$this->countryId,
				$this->statusId,
				$this->partnerId,
				$this->firstLetter,
				$this->city,
				$this->userId,
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
					'country' => $this->countryId,
					'partner' => $this->partnerId,
					'city' => $this->city,
					'status' => $this->statusId,
					'user' => $this->userId,
					'first-letter' => $this->firstLetter,
				],
				static fn (mixed $value): bool => $value !== null && $value !== '',
			),
		);
	}
}
