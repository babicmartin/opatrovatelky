<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Number;

use App\Model\Utils\Number\NumberService;
use Tests\Support\PHPUnit\TestCase;

final class NumberServiceTest extends TestCase
{
	private NumberService $service;

	protected function setUp(): void
	{
		parent::setUp();
		$this->service = new NumberService();
	}

	public function testFormatNumberUsesSlovakSeparatorsByDefault(): void
	{
		self::assertSame('1 234,50', $this->service->formatNumber(1234.5));
		self::assertSame('1234.50', $this->service->formatNumber('1234.5', 2, '.', ''));
	}

	public function testRoundRespectsPrecision(): void
	{
		self::assertSame(1.24, $this->service->round(1.235, 2));
		self::assertSame(2.0, $this->service->round(1.5, 0));
	}

	public function testRoundUpAlwaysCeilsToInt(): void
	{
		self::assertSame(2, $this->service->roundUp(1.01));
		self::assertSame(5, $this->service->roundUp('4.2'));
		self::assertSame(4, $this->service->roundUp('4.0'));
		self::assertSame(3, $this->service->roundUp(3));
	}
}
