<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Date;

use App\Model\Utils\Date\DateService;
use App\Model\Utils\Date\HolidayService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PHPUnit\TestCase;

final class HolidayServiceTest extends TestCase
{
	private HolidayService $service;

	protected function setUp(): void
	{
		parent::setUp();

		$this->service = new HolidayService(new DateService());
	}

	/**
	 * @return iterable<string, array{int, string}>
	 */
	public static function easterSundays(): iterable
	{
		yield '2023' => [2023, '2023-04-09'];
		yield '2024' => [2024, '2024-03-31'];
		yield '2025' => [2025, '2025-04-20'];
		yield '2026' => [2026, '2026-04-05'];
		yield '2027' => [2027, '2027-03-28'];
		yield '2038 (latest possible date, 25 April)' => [2038, '2038-04-25'];
	}

	#[DataProvider('easterSundays')]
	public function testEasterSunday(int $year, string $expected): void
	{
		self::assertSame($expected, $this->service->getEasterSunday($year)->format('Y-m-d'));
	}

	public function testDaysOfRestOf2026(): void
	{
		// § 4b of Act 241/1993: in 2026 neither 8 May nor 15 September is a day of rest.
		self::assertSame([
			'2026-01-01' => 'Deň vzniku Slovenskej republiky',
			'2026-01-06' => 'Zjavenie Pána (Traja králi)',
			'2026-04-03' => 'Veľký piatok',
			'2026-04-06' => 'Veľkonočný pondelok',
			'2026-05-01' => 'Sviatok práce',
			'2026-07-05' => 'Sviatok svätého Cyrila a Metoda',
			'2026-08-29' => 'Výročie SNP',
			'2026-11-01' => 'Sviatok všetkých svätých',
			'2026-12-24' => 'Štedrý deň',
			'2026-12-25' => 'Prvý sviatok vianočný',
			'2026-12-26' => 'Druhý sviatok vianočný',
		], $this->service->getHolidays(2026));
	}

	/**
	 * Date and whether it is a day of rest in that year.
	 *
	 * @return iterable<string, array{string, bool}>
	 */
	public static function changedHolidays(): iterable
	{
		yield '1 September, last time in 2023' => ['2023-09-01', true];
		yield '1 September, working day since 2024' => ['2024-09-01', false];
		yield '17 November, last time in 2024' => ['2024-11-17', true];
		yield '17 November, working day since 2025' => ['2025-11-17', false];
		yield '17 November 2026' => ['2026-11-17', false];
		yield '8 May 2025' => ['2025-05-08', true];
		yield '8 May 2026, exception of one year' => ['2026-05-08', false];
		yield '8 May 2027' => ['2027-05-08', true];
		yield '15 September 2025' => ['2025-09-15', true];
		yield '15 September 2026, exception of one year' => ['2026-09-15', false];
		yield '15 September 2027' => ['2027-09-15', true];
		yield '28 October was never a day of rest' => ['2026-10-28', false];
	}

	#[DataProvider('changedHolidays')]
	public function testHolidayStatusFollowsTheYear(string $date, bool $isHoliday): void
	{
		self::assertSame($isHoliday, $this->service->isHoliday(new DateTimeImmutable($date)));
	}

	public function testWorkingDayIsNeitherWeekendNorHoliday(): void
	{
		self::assertTrue($this->service->isWorkingDay(new DateTimeImmutable('2026-11-17')));
		self::assertTrue($this->service->isWorkingDay(new DateTimeImmutable('2026-05-08')));
		self::assertFalse($this->service->isWorkingDay(new DateTimeImmutable('2025-05-08')));
		self::assertFalse($this->service->isWorkingDay(new DateTimeImmutable('2026-04-03')));
		self::assertFalse($this->service->isWorkingDay(new DateTimeImmutable('2026-11-07')));
		self::assertFalse($this->service->isWorkingDay(new DateTimeImmutable('2026-11-08')));
	}

	public function testHolidaysInRangeCrossTheYear(): void
	{
		self::assertSame(
			['2025-12-24', '2025-12-25', '2025-12-26', '2026-01-01', '2026-01-06'],
			array_keys($this->service->getHolidaysInRange(
				new DateTimeImmutable('2025-12-20'),
				new DateTimeImmutable('2026-01-06'),
			)),
		);
		self::assertSame('Veľkonočný pondelok', $this->service->getHolidayName(new DateTimeImmutable('2026-04-06')));
		self::assertNull($this->service->getHolidayName(new DateTimeImmutable('2026-04-07')));
	}
}
