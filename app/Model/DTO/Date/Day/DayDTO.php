<?php declare(strict_types = 1);

namespace App\Model\DTO\Date\Day;

use DateTimeImmutable;

final readonly class DayDTO
{
	public function __construct(
		private DateTimeImmutable $date,
		private string            $dayName,
		private bool              $isWeekendDay,
		private bool              $isToday,
	)
	{
	}

	public function getDate(): DateTimeImmutable
	{
		return $this->date;
	}

	public function getDayName(): string
	{
		return $this->dayName;
	}

	public function isWeekendDay(): bool
	{
		return $this->isWeekendDay;
	}

	public function isWorkday(): bool
	{
		return !$this->isWeekendDay;
	}

	public function isToday(): bool
	{
		return $this->isToday;
	}

	public function getDayNumber(): int
	{
		return (int)$this->date->format('j');
	}

	public function getWeekNumber(): int
	{
		return (int)$this->date->format('W');
	}

	public function isFirstDayOfMonth(): bool
	{
		return $this->getDayNumber() === 1;
	}

	public function isLastDayOfMonth(): bool
	{
		return $this->date->format('j') === $this->date->format('t');
	}

	public function isPast(): bool
	{
		return $this->date->format('Y-m-d') < (new DateTimeImmutable())->format('Y-m-d');
	}

	public function isFuture(): bool
	{
		return $this->date->format('Y-m-d') > (new DateTimeImmutable())->format('Y-m-d');
	}


}