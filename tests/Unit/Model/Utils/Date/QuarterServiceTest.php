<?php declare(strict_types=1);

namespace Tests\Unit\Model\Utils\Date;

use App\Model\Utils\Date\DateService;
use App\Model\Utils\Date\QuarterService;
use Tests\Support\PHPUnit\TestCase;

final class QuarterServiceTest extends TestCase
{
	public function testGetQuartersReturnsFourBoundedQuarters(): void
	{
		$quarters = (new QuarterService(new DateService()))->getQuarters(2024);

		self::assertSame([1, 2, 3, 4], array_keys($quarters));

		self::assertSame(1, $quarters[1]->getId());
		self::assertSame('2024-01-01', $quarters[1]->getFrom()->format('Y-m-d'));
		self::assertSame('2024-03-31', $quarters[1]->getTo()->format('Y-m-d'));

		self::assertSame('2024-04-01', $quarters[2]->getFrom()->format('Y-m-d'));
		self::assertSame('2024-06-30', $quarters[2]->getTo()->format('Y-m-d'));

		self::assertSame('2024-10-01', $quarters[4]->getFrom()->format('Y-m-d'));
		self::assertSame('2024-12-31', $quarters[4]->getTo()->format('Y-m-d'));
	}
}
