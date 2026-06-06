<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\ArrayService;

use App\Model\Utils\ArrayService\ArrayService;
use Tests\Support\PHPUnit\TestCase;

final class ArrayServiceTest extends TestCase
{
	private ArrayService $service;

	protected function setUp(): void
	{
		parent::setUp();
		$this->service = new ArrayService();
	}

	public function testPaginateArrayReturnsRequestedSlice(): void
	{
		$input = ['a', 'b', 'c', 'd', 'e'];

		self::assertSame(['c', 'd'], $this->service->paginateArray($input, 2, 2));
		self::assertSame([], $this->service->paginateArray($input, 2, 9));
	}

	public function testGetKeyByValueUsesStrictComparison(): void
	{
		$input = ['x' => 1, 'y' => '1', 'z' => 2];

		self::assertSame('x', $this->service->getKeyByValue(1, $input));
		self::assertFalse($this->service->getKeyByValue(99, $input));
	}

	public function testAddArrayToArrayMutatesReferenceAndAppends(): void
	{
		$base = ['a'];
		$result = $this->service->addArrayToArray($base, ['b', 'c']);

		self::assertSame(['a', 'b', 'c'], $result);
		self::assertSame(['a', 'b', 'c'], $base);
	}

	public function testAddArrayToArrayUniqueValuesRemovesDuplicates(): void
	{
		$base = [1, 2];
		$result = $this->service->addArrayToArrayUniqueValues($base, [2, 3]);

		self::assertSame([1, 2, 3], array_values($result));
	}

	public function testJoinArraysWithKeysKeepsFirstOccurrence(): void
	{
		$result = $this->service->joinArraysWithKeys(['a' => 1], ['a' => 2, 'b' => 3]);

		self::assertSame(['a' => 1, 'b' => 3], $result);
	}

	public function testSplitArrayAtIndex(): void
	{
		[$first, $second] = $this->service->splitArrayAtIndex([1, 2, 3, 4], 2);

		self::assertSame([1, 2], $first);
		self::assertSame([3, 4], $second);
	}

	public function testGetItemsByKeyAndValueMatching(): void
	{
		$byKey = ['type_a' => 1, 'type_b' => 2, 'other' => 3];
		self::assertSame(['type_a' => 1, 'type_b' => 2], $this->service->getItemsKeyStartsWith($byKey, 'type_'));
		self::assertSame(['other' => 3], $this->service->getItemsKeyContains($byKey, 'oth'));

		$byValue = ['a' => 'apple', 'b' => 'apricot', 'c' => 'banana'];
		self::assertSame(['a' => 'apple', 'b' => 'apricot'], $this->service->getItemsValueStartsWith($byValue, 'ap'));
		self::assertSame(['c' => 'banana'], $this->service->getItemsValueContains($byValue, 'nan'));
	}

	public function testAddIfNotExistsValueSkipsDuplicates(): void
	{
		self::assertSame([1, 2], $this->service->addIfNotExistsValue(2, [1, 2]));
		self::assertSame([1, 2, 3], $this->service->addIfNotExistsValue(3, [1, 2]));
	}

	public function testGetMaxValueFallsBackToTypedZeroWhenEmpty(): void
	{
		self::assertSame(5, $this->service->getMaxValue([1, 5, 3], false));
		self::assertSame(0, $this->service->getMaxValue([], false));
		self::assertSame(0.0, $this->service->getMaxValue([]));
	}

	public function testFirstAndRangeHelpers(): void
	{
		self::assertSame('a', $this->service->getFirstKey(['a' => 1, 'b' => 2]));
		self::assertSame(1, $this->service->getFirstElement([1, 2]));
		self::assertSame([2, 3, 4], $this->service->range(2, 4));
	}
}
