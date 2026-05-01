<?php declare(strict_types = 1);

namespace App\Model\Factory;

use App\Model\Utils\Paginator\Paginator;

class PaginatorFactory
{
	/**
	 * @param array<string, mixed> $routeParams
	 */
	public function create(
		int $currentPage,
		int $totalCount,
		int $itemsPerPage,
		string $route,
		array $routeParams = [],
	): Paginator
	{
		return new Paginator($currentPage, $totalCount, $itemsPerPage, $route, $routeParams);
	}

	/**
	 * @param array<string, mixed> $routeParams
	 */
	public function createFromPageCount(
		int $currentPage,
		int $pageCount,
		int $itemsPerPage,
		string $route,
		array $routeParams = [],
	): Paginator
	{
		return Paginator::fromPageCount($currentPage, $pageCount, $itemsPerPage, $route, $routeParams);
	}
}
