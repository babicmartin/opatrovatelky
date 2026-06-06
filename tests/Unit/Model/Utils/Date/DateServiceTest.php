<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Date;

use App\Model\Utils\Date\DateService;
use InvalidArgumentException;
use Tests\Support\PHPUnit\TestCase;

final class DateServiceTest extends TestCase
{
	private DateService $service;

	protected function setUp(): void
	{
		parent::setUp();

		$this->service = new DateService();
	}

	public function testTryCreateFromDbIgnoresEmptyAndZeroDates(): void
	{
		self::assertNull($this->service->tryCreateFromDb(null));
		self::assertNull($this->service->tryCreateFromDb(''));
		self::assertNull($this->service->tryCreateFromDb('0000-00-00'));
	}

	public function testTryCreateFromDbParsesDateAndDatetime(): void
	{
		self::assertSame('2026-06-06', $this->service->tryCreateFromDb('2026-06-06')?->format('Y-m-d'));
		self::assertSame('2026-06-06', $this->service->tryCreateFromDb('2026-06-06 14:30:00')?->format('Y-m-d'));
	}

	public function testTryCreateFromUserInputParsesSupportedFormats(): void
	{
		self::assertSame('2026-06-06', $this->service->tryCreateFromUserInput('6.6.2026')?->format('Y-m-d'));
		self::assertSame('2026-06-06', $this->service->tryCreateFromUserInput('2026-6-6')?->format('Y-m-d'));
		self::assertNull($this->service->tryCreateFromUserInput('31.2.2026'));
	}

	public function testMonthBoundaries(): void
	{
		self::assertSame('2026-02-01', $this->service->getFirstDayOfMonth('2026-02-18'));
		self::assertSame('2026-02-28', $this->service->getLastDayOfMonth('2026-02-18'));
		self::assertSame('2024-02-29', $this->service->getLastDayOfMontByYearDate(2024, 2)->format('Y-m-d'));
	}

	public function testCreateDateTimeImmutableFromYearMonthRejectsInvalidMonth(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->service->createDateTimeImmutableFromYearMonth(2026, 13);
	}
}
