<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Date;

use App\Model\Utils\ArrayService\ArrayService;
use App\Model\Utils\Date\YearService;
use DateTimeImmutable;
use Mockery;
use Psr\Clock\ClockInterface;
use RuntimeException;
use Tests\Support\PHPUnit\TestCase;

final class YearServiceTest extends TestCase
{
	public function testCurrentYearComesFromClock(): void
	{
		self::assertSame(2026, $this->service('2026-06-06 12:00:00')->getCurrentYear());
	}

	public function testGetLastYearsCountsBackFromCurrentYear(): void
	{
		$service = $this->service('2026-06-06 12:00:00');

		self::assertSame([2024, 2025, 2026], $service->getLastYears(3));
		self::assertSame([2026, 2025, 2024], $service->getLastYears(3, true));
	}

	public function testGetYearsFromYearListsInclusiveRange(): void
	{
		$service = $this->service('2026-06-06 12:00:00');

		self::assertSame([2024, 2025, 2026], $service->getYearsFromYear(2024));
		self::assertSame([2026, 2025, 2024], $service->getYearsFromYear(2024, true));
	}

	private function service(string $now): YearService
	{
		$clock = Mockery::mock(ClockInterface::class, ['now' => new DateTimeImmutable($now)]);
		if (!$clock instanceof ClockInterface) {
			throw new RuntimeException('Clock mock must implement ClockInterface.');
		}

		return new YearService($clock, new ArrayService());
	}
}
