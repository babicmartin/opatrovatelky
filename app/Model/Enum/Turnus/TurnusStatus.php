<?php declare(strict_types=1);

namespace App\Model\Enum\Turnus;

/**
 * Known IDs from sn_status_turnus that carry business meaning.
 */
final class TurnusStatus
{
	public const int FINISHED = 10;
	public const int STORNO = 30;


	private function __construct()
	{
	}
}
