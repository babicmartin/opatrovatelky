<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Float;

use App\Model\Utils\Float\FloatService;
use Tests\Support\PHPUnit\TestCase;

final class FloatServiceTest extends TestCase
{
	private FloatService $service;

	protected function setUp(): void
	{
		parent::setUp();
		$this->service = new FloatService();
	}

	public function testEqualityAndComparison(): void
	{
		self::assertTrue($this->service->areEqual(0.1 + 0.2, 0.3));
		self::assertTrue($this->service->isLessThan(1.0, 2.0));
		self::assertTrue($this->service->isLessThanOrEqualTo(2.0, 2.0));
		self::assertTrue($this->service->isGreaterThan(2.0, 1.0));
		self::assertTrue($this->service->isGreaterThanOrEqualTo(2.0, 2.0));
	}

	public function testCompareReturnsSign(): void
	{
		self::assertSame(-1, $this->service->compare(1.0, 2.0));
		self::assertSame(0, $this->service->compare(2.0, 2.0));
		self::assertSame(1, $this->service->compare(3.0, 2.0));
	}

	public function testZeroAndInteger(): void
	{
		self::assertTrue($this->service->isZero(0.0));
		self::assertFalse($this->service->isZero(0.5));
		self::assertTrue($this->service->isInteger(4.0));
		self::assertFalse($this->service->isInteger(4.5));
	}
}
