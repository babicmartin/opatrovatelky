<?php declare(strict_types = 1);

namespace App\Model\Utils\Paginator;

class Paginator
{
	private int $currentPage;
	private int $totalPages;
	private int $totalCount;
	/** @var int<1, max> */
	private int $itemsPerPage;
	/** @var int<0, max> */
	private int $offset;
	private string $route;
	/** @var array<string, mixed> */
	private array $routeParams;

	/**
	 * @param array<string, mixed> $routeParams
	 */
	public function __construct(
		int $currentPage,
		int $totalCount,
		int $itemsPerPage,
		string $route,
		array $routeParams = [],
	)
	{
		$this->currentPage = max(1, $currentPage);
		$this->totalCount = max(0, $totalCount);
		$this->itemsPerPage = max(1, $itemsPerPage);
		$this->totalPages = $this->totalCount > 0 ? (int) ceil($this->totalCount / $this->itemsPerPage) : 1;
		$this->currentPage = min($this->currentPage, $this->totalPages);
		/** @var int<0, max> $offset */
		$offset = max(0, ($this->currentPage - 1) * $this->itemsPerPage);
		$this->offset = $offset;
		$this->route = $route;
		$this->routeParams = $routeParams;
	}

	/**
	 * @param array<string, mixed> $routeParams
	 */
	public static function fromPageCount(
		int $currentPage,
		int $pageCount,
		int $itemsPerPage,
		string $route,
		array $routeParams = [],
	): self
	{
		$pageCount = max(1, $pageCount);
		$itemsPerPage = max(1, $itemsPerPage);
		$totalCount = $pageCount * $itemsPerPage;

		return new self($currentPage, $totalCount, $itemsPerPage, $route, $routeParams);
	}

	public function getCurrentPage(): int
	{
		return $this->currentPage;
	}

	public function getTotalPages(): int
	{
		return $this->totalPages;
	}

	public function getTotalCount(): int
	{
		return $this->totalCount;
	}

	/**
	 * @return int<1, max>
	 */
	public function getItemsPerPage(): int
	{
		return $this->itemsPerPage;
	}

	/**
	 * @return int<0, max>
	 */
	public function getOffset(): int
	{
		return $this->offset;
	}

	public function getRoute(): string
	{
		return $this->route;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getRouteParams(): array
	{
		return $this->routeParams;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getRouteParamsForPage(int $page): array
	{
		return array_merge($this->routeParams, ['page' => $page]);
	}

	public function getQueryForPage(int $page): string
	{
		return '?' . http_build_query($this->getRouteParamsForPage($page));
	}

	/**
	 * @return list<int|string>
	 */
	public function getVisiblePageItems(): array
	{
		if ($this->totalPages <= 7) {
			return range(1, $this->totalPages);
		}

		if ($this->currentPage <= 4) {
			return [1, 2, 3, 4, 5, 'ellipsis-right', $this->totalPages];
		}

		if ($this->currentPage >= $this->totalPages - 3) {
			return [1, 'ellipsis-left', $this->totalPages - 4, $this->totalPages - 3, $this->totalPages - 2, $this->totalPages - 1, $this->totalPages];
		}

		return [
			1,
			'ellipsis-left',
			$this->currentPage - 1,
			$this->currentPage,
			$this->currentPage + 1,
			'ellipsis-right',
			$this->totalPages,
		];
	}

	public function hasMultiplePages(): bool
	{
		return $this->totalPages > 1;
	}
}
