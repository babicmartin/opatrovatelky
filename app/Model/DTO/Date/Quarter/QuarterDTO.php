<?php declare(strict_types = 1);

namespace App\Model\DTO\Date\Quarter;

use DateTimeImmutable;

final readonly class QuarterDTO
{
	public function __construct(
		private int $id,
		private DateTimeImmutable $from,
		private DateTimeImmutable $to,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getFrom(): DateTimeImmutable
	{
		return $this->from;
	}

	public function getTo(): DateTimeImmutable
	{
		return $this->to;
	}
}