<?php declare(strict_types = 1);

namespace App\Model\Service\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

class ClockService implements ClockInterface
{
	public function now(): DateTimeImmutable
	{
		//return new DateTimeImmutable();
		return new DateTimeImmutable('now', new DateTimeZone('Europe/Bratislava'));

	}
}