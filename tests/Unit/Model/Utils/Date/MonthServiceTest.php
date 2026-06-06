<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Date;

use App\Model\Utils\ArrayService\ArrayService;
use App\Model\Utils\Date\DateService;
use App\Model\Utils\Date\MonthService;
use App\Model\Utils\Date\YearService;
use DateTimeImmutable;
use Mockery;
use Psr\Clock\ClockInterface;
use RuntimeException;
use Tests\Support\PHPUnit\TestCase;

final class MonthServiceTest extends TestCase
{
	public function testCurrentMonthComesFromClock(): void
	{
		$clock = $this->createClockReturning(new DateTimeImmutable('2026-06-06 12:00:00'));

		$service = new MonthService($clock, new DateService(), new YearService($clock, new ArrayService()));

		self::assertSame(6, $service->getCurrentMonth());
	}

	public function testMonthDtoContainsSlovakNameAndDays(): void
	{
		$clock = $this->createClockReturning(new DateTimeImmutable('2026-06-06 12:00:00'));

		$service = new MonthService($clock, new DateService(), new YearService($clock, new ArrayService()));
		$month = $service->getMonthDTOByNumber(2, 2024);

		self::assertNotNull($month);
		self::assertSame('Február', $month->getName());
		self::assertSame('2024-02-01', $month->getFirstDay()->format('Y-m-d'));
		self::assertSame('2024-02-29', $month->getLastDay()->format('Y-m-d'));
		self::assertCount(29, $month->getDayList());
	}

	private function createClockReturning(DateTimeImmutable $now): ClockInterface
	{
		$clock = Mockery::mock(ClockInterface::class, ['now' => $now]);
		if (!$clock instanceof ClockInterface) {
			throw new RuntimeException('Clock mock must implement ClockInterface.');
		}

		return $clock;
	}
}
