<?php declare(strict_types=1);

namespace App\Model\DTO\MissingRegistry\Calendar;

use App\Model\DTO\MissingRegistry\MissingRegistryRecord;

/**
 * One day of a registry record inside a calendar cell. Every day carries the name of the type;
 * the note is shown once, on the first day of the record that the month shows ($showNotice).
 */
final readonly class MissingCalendarEntry
{
	public const string UNCLASSIFIED_COLOR = '#a16207';

	public function __construct(
		public MissingRegistryRecord $record,
		public bool $isFirstDay,
		public bool $isLastDay,
		public bool $showNotice,
		public bool $isCounted,
		public bool $isEditable,
	) {
	}

	/**
	 * CSS background: the colour of the type; a record with more types is split into a left and a right half,
	 * so the halves line up when the record runs over several rows.
	 */
	public function getBackground(): string
	{
		$types = $this->record->types;
		if ($types === []) {
			return self::UNCLASSIFIED_COLOR;
		}
		if (count($types) === 1) {
			return $types[0]->color();
		}

		return 'linear-gradient(90deg, ' . $types[0]->color() . ' 50%, ' . $types[1]->color() . ' 50%)';
	}
}
