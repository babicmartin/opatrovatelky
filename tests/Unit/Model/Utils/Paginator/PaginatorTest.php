<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Paginator;

use App\Model\Utils\Paginator\Paginator;
use Tests\Support\PHPUnit\TestCase;

final class PaginatorTest extends TestCase
{
	public function testClampsPageCountAndComputesOffset(): void
	{
		$paginator = new Paginator(99, 95, 10, ':Admin:Babysitter:default', ['status' => 1]);

		self::assertSame(10, $paginator->getTotalPages());
		self::assertSame(10, $paginator->getCurrentPage());
		self::assertSame(90, $paginator->getOffset());
		self::assertSame(95, $paginator->getTotalCount());
		self::assertTrue($paginator->hasMultiplePages());
	}

	public function testEmptyResultStillHasSinglePage(): void
	{
		$paginator = new Paginator(0, 0, 10, ':Admin:Babysitter:default');

		self::assertSame(1, $paginator->getTotalPages());
		self::assertSame(1, $paginator->getCurrentPage());
		self::assertSame(0, $paginator->getOffset());
		self::assertFalse($paginator->hasMultiplePages());
	}

	public function testRouteParamsAndQueryIncludePage(): void
	{
		$paginator = new Paginator(2, 50, 10, ':Admin:Family:default', ['status' => 3]);

		self::assertSame(['status' => 3], $paginator->getRouteParams());
		self::assertSame(['status' => 3, 'page' => 4], $paginator->getRouteParamsForPage(4));
		self::assertSame('?status=3&page=4', $paginator->getQueryForPage(4));
	}

	public function testFromPageCountDerivesTotalCount(): void
	{
		$paginator = Paginator::fromPageCount(3, 6, 10, ':Admin:Family:default');

		self::assertSame(6, $paginator->getTotalPages());
		self::assertSame(3, $paginator->getCurrentPage());
	}

	public function testVisiblePageItemsListsEveryPageWhenFew(): void
	{
		$paginator = new Paginator(1, 70, 10, ':r');

		self::assertSame([1, 2, 3, 4, 5, 6, 7], $paginator->getVisiblePageItems());
	}

	public function testVisiblePageItemsShowsRightEllipsisNearStart(): void
	{
		$paginator = new Paginator(3, 200, 10, ':r');

		self::assertSame([1, 2, 3, 4, 5, 'ellipsis-right', 20], $paginator->getVisiblePageItems());
	}

	public function testVisiblePageItemsShowsLeftEllipsisNearEnd(): void
	{
		$paginator = new Paginator(19, 200, 10, ':r');

		self::assertSame([1, 'ellipsis-left', 16, 17, 18, 19, 20], $paginator->getVisiblePageItems());
	}

	public function testVisiblePageItemsShowsBothEllipsesInMiddle(): void
	{
		$paginator = new Paginator(10, 200, 10, ':r');

		self::assertSame([1, 'ellipsis-left', 9, 10, 11, 'ellipsis-right', 20], $paginator->getVisiblePageItems());
	}
}
