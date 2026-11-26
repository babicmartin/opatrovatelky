<?php declare(strict_types=1);

namespace Tests\Unit\Model\Service\MissingRegistry;

use App\Model\DTO\MissingRegistry\MissingRegistryRecord;
use App\Model\Enum\MissingRegistry\MissingType;
use App\Model\Service\MissingRegistry\MissingRegistryDayCounter;
use App\Model\Utils\Date\DateService;
use App\Model\Utils\Date\HolidayService;
use DateTimeImmutable;
use Tests\Support\PHPUnit\TestCase;

final class MissingRegistryDayCounterTest extends TestCase
{
	private const int Karol = 2;
	private const int Sona = 4;

	private MissingRegistryDayCounter $counter;

	private int $lastId = 0;

	protected function setUp(): void
	{
		parent::setUp();

		$dateService = new DateService();
		$this->counter = new MissingRegistryDayCounter(new HolidayService($dateService), $dateService);
	}

	public function testVacationSkipsWeekendsAndDaysOfRest(): void
	{
		// Mon 2 Nov - Sun 8 Nov 2026: five working days.
		// Fri 3 Apr (Good Friday) - Tue 7 Apr 2026: only Tuesday is a working day.
		$totals = $this->totals([
			$this->record(self::Karol, '2026-11-02', '2026-11-08', MissingType::DOVOLENKA),
			$this->record(self::Sona, '2026-04-03', '2026-04-07', MissingType::DOVOLENKA),
		]);

		self::assertSame(5, $totals[self::Karol]['dovolenka']);
		self::assertSame(1, $totals[self::Sona]['dovolenka']);
	}

	public function testDutyAndSubstitutionCountEveryCalendarDay(): void
	{
		$totals = $this->totals([
			$this->record(self::Karol, '2026-11-07', '2026-11-08', MissingType::SLUZBA),
			$this->record(self::Sona, '2026-12-24', '2026-12-27', MissingType::ZASTUP, MissingType::SLUZBA),
		]);

		self::assertSame(['sluzba' => 2], $totals[self::Karol]);
		self::assertSame(['zastup' => 4, 'sluzba' => 4], $totals[self::Sona]);
	}

	public function testRecordIsClippedToThePeriod(): void
	{
		// Mon 29 Dec 2025 - Fri 9 Jan 2026. 2025: 29, 30, 31 Dec. 2026: 2, 5, 7, 8, 9 Jan (1 and 6 Jan are days of rest).
		$record = $this->record(self::Karol, '2025-12-29', '2026-01-09', MissingType::DOVOLENKA);

		self::assertSame(3, $this->totals([$record], '2025-01-01', '2025-12-31')[self::Karol]['dovolenka']);
		self::assertSame(5, $this->totals([$record], '2026-01-01', '2026-12-31')[self::Karol]['dovolenka']);
		self::assertSame([], $this->totals([$record], '2026-02-01', '2026-02-28'));
	}

	public function testDayCoveredTwiceByTheSameTypeIsCountedOnce(): void
	{
		$totals = $this->totals([
			$this->record(self::Karol, '2026-11-02', '2026-11-04', MissingType::DOVOLENKA),
			$this->record(self::Karol, '2026-11-04', '2026-11-06', MissingType::DOVOLENKA),
			$this->record(self::Karol, '2026-11-04', '2026-11-04', MissingType::LEKAR),
			$this->record(self::Sona, '2026-11-04', '2026-11-04', MissingType::DOVOLENKA),
		]);

		self::assertSame(['dovolenka' => 5, 'lekar' => 1], $totals[self::Karol]);
		self::assertSame(['dovolenka' => 1], $totals[self::Sona]);
	}

	public function testMissingOrReversedEndMeansOneDay(): void
	{
		$totals = $this->totals([
			$this->record(self::Karol, '2026-11-10', null, MissingType::PN),
			$this->record(self::Sona, '2026-11-10', '2026-11-03', MissingType::OCR),
		]);

		self::assertSame(['pn' => 1], $totals[self::Karol]);
		self::assertSame(['ocr' => 1], $totals[self::Sona]);
	}

	public function testRecordWithoutTypeOrDateCountsNothing(): void
	{
		$untyped = $this->record(self::Karol, '2026-11-10', '2026-11-12');
		$undated = new MissingRegistryRecord(9, self::Sona, null, null, [MissingType::DOVOLENKA], '');

		self::assertSame([], $this->totals([$untyped, $undated]));
		self::assertFalse($this->counter->isCountedRecordDay($untyped, new DateTimeImmutable('2026-11-10')));
	}

	public function testCountedDayDependsOnTheType(): void
	{
		$saturday = new DateTimeImmutable('2026-11-07');
		$vacation = $this->record(self::Karol, '2026-11-06', '2026-11-09', MissingType::DOVOLENKA);
		$duty = $this->record(self::Karol, '2026-11-06', '2026-11-09', MissingType::SLUZBA);

		self::assertFalse($this->counter->isCountedDay(MissingType::DOVOLENKA, $saturday));
		self::assertTrue($this->counter->isCountedDay(MissingType::SLUZBA, $saturday));
		self::assertFalse($this->counter->isCountedRecordDay($vacation, $saturday));
		self::assertTrue($this->counter->isCountedRecordDay($duty, $saturday));
	}

	public function testRangeOfNovember2026(): void
	{
		// 1 November is a Sunday and 17 November is a working day since 2025.
		self::assertSame(
			['calendarDays' => 30, 'workingDays' => 21, 'weekendDays' => 9, 'holidays' => []],
			$this->counter->countRange(new DateTimeImmutable('2026-11-01'), new DateTimeImmutable('2026-11-30')),
		);
	}

	public function testRangeNamesTheDaysOfRestOnWeekdays(): void
	{
		self::assertSame(
			[
				'calendarDays' => 5,
				'workingDays' => 1,
				'weekendDays' => 2,
				'holidays' => ['2026-04-03' => 'Veľký piatok', '2026-04-06' => 'Veľkonočný pondelok'],
			],
			$this->counter->countRange(new DateTimeImmutable('2026-04-03'), new DateTimeImmutable('2026-04-07')),
		);
	}

	/**
	 * @param list<MissingRegistryRecord> $records
	 * @return array<int, array<string, int>>
	 */
	private function totals(array $records, string $from = '2026-01-01', string $to = '2026-12-31'): array
	{
		return $this->counter->countByUserAndType($records, new DateTimeImmutable($from), new DateTimeImmutable($to));
	}

	private function record(int $userId, string $from, ?string $to, MissingType ...$types): MissingRegistryRecord
	{
		return new MissingRegistryRecord(
			++$this->lastId,
			$userId,
			new DateTimeImmutable($from),
			$to === null ? null : new DateTimeImmutable($to),
			array_values($types),
			'',
		);
	}
}
