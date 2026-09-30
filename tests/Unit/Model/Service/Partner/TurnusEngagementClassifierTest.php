<?php declare(strict_types=1);

namespace Tests\Unit\Model\Service\Partner;

use App\Model\Enum\Partner\EngagementType;
use App\Model\Enum\Turnus\TurnusStatus;
use App\Model\Service\Partner\TurnusEngagementClassifier;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PHPUnit\TestCase;

final class TurnusEngagementClassifierTest extends TestCase
{
	private const string TODAY = '2026-06-15';


	/**
	 * @return iterable<string, array{int, ?string, ?EngagementType}>
	 */
	public static function classifyCases(): iterable
	{
		yield 'storno in the past is not counted' => [TurnusStatus::STORNO, '2026-01-31', null];
		yield 'storno in the future is not counted' => [TurnusStatus::STORNO, '2026-12-31', null];
		yield 'storno without date is not counted' => [TurnusStatus::STORNO, null, null];
		yield 'finished status with future date is finished' => [TurnusStatus::FINISHED, '2026-12-31', EngagementType::FINISHED];
		yield 'finished status without date is finished' => [TurnusStatus::FINISHED, null, EngagementType::FINISHED];
		yield 'planned turnus in the future is active' => [1, '2026-08-31', EngagementType::ACTIVE];
		yield 'running turnus ending today is active' => [4, self::TODAY, EngagementType::ACTIVE];
		yield 'turnus ended yesterday is finished' => [4, '2026-06-14', EngagementType::FINISHED];
		yield 'turnus without end date is active' => [1, null, EngagementType::ACTIVE];
		yield 'status 0 without end date is active' => [0, null, EngagementType::ACTIVE];
		yield 'status 0 in the past is finished' => [0, '2025-03-01', EngagementType::FINISHED];
		yield 'planned departure in the future is active' => [21, '2026-07-01', EngagementType::ACTIVE];
	}


	#[DataProvider('classifyCases')]
	public function testClassify(int $statusId, ?string $dateTo, ?EngagementType $expected): void
	{
		$classifier = new TurnusEngagementClassifier();

		self::assertSame(
			$expected,
			$classifier->classify($statusId, $dateTo === null ? null : new DateTimeImmutable($dateTo), new DateTimeImmutable(self::TODAY)),
		);
	}


	public function testClassifyIgnoresTimeOfDay(): void
	{
		$classifier = new TurnusEngagementClassifier();

		self::assertSame(
			EngagementType::ACTIVE,
			$classifier->classify(1, new DateTimeImmutable(self::TODAY . ' 00:00:00'), new DateTimeImmutable(self::TODAY . ' 23:59:59')),
		);
	}


	/**
	 * @return iterable<string, array{?string, ?string, int}>
	 */
	public static function workedDaysCases(): iterable
	{
		yield 'finished turnus counts inclusive days' => ['2026-05-01', '2026-05-31', 31];
		yield 'single day turnus' => ['2026-05-10', '2026-05-10', 1];
		yield 'running turnus is capped at today' => ['2026-06-10', '2026-06-30', 6];
		yield 'future turnus has no days yet' => ['2026-07-01', '2026-07-31', 0];
		yield 'missing start date' => [null, '2026-05-31', 0];
		yield 'missing end date' => ['2026-05-01', null, 0];
		yield 'inverted range' => ['2026-05-31', '2026-05-01', 0];
	}


	#[DataProvider('workedDaysCases')]
	public function testCountWorkedDays(?string $dateFrom, ?string $dateTo, int $expected): void
	{
		$classifier = new TurnusEngagementClassifier();

		self::assertSame($expected, $classifier->countWorkedDays(
			$dateFrom === null ? null : new DateTimeImmutable($dateFrom),
			$dateTo === null ? null : new DateTimeImmutable($dateTo),
			new DateTimeImmutable(self::TODAY),
		));
	}
}
