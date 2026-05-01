<?php declare(strict_types = 1);

namespace App\Model\DTO\Date\Month;

use DateTimeImmutable;

class MonthDTO
{
	/**
	 * @param array<DateTimeImmutable> $dayList
	 */
	public function __construct(
		private readonly int $number,
		private readonly string $name,
		private readonly int $days,
		private readonly DateTimeImmutable $firstDay,
		private readonly DateTimeImmutable $lastDay,
		private readonly array $dayList,
	)
	{
	}

	public function getNumber(): int
	{
		return $this->number;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDays(): int
	{
		return $this->days;
	}

	public function getFirstDay(): DateTimeImmutable
	{
		return $this->firstDay;
	}

	public function getLastDay(): DateTimeImmutable
	{
		return $this->lastDay;
	}

	/**
	 * @return array<DateTimeImmutable>
	 */
	public function getDayList(): array
	{
		return $this->dayList;
	}
}
