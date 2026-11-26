<?php declare(strict_types=1);

namespace App\Model\Enum\MissingRegistry;

use App\Model\Table\MissingRegistryTableMap;

/**
 * Types of a record in the absence registry. Each type is one 0/1 column of `sn_missing_registry`,
 * so a record may carry several types or none.
 *
 * The order of the cases is the order of the checkboxes, the legend and the summary columns.
 */
enum MissingType: string
{
	case PN = 'pn';
	case OCR = 'ocr';
	case LEKAR = 'lekar';
	case SVIATOK = 'sviatok';
	case ZASTUP = 'zastup';
	case SLUZBA = 'sluzba';
	case DOVOLENKA = 'dovolenka';

	public function column(): string
	{
		return match ($this) {
			self::PN => MissingRegistryTableMap::COL_TYPE_PN,
			self::OCR => MissingRegistryTableMap::COL_TYPE_OCR,
			self::LEKAR => MissingRegistryTableMap::COL_TYPE_LEKAR,
			self::SVIATOK => MissingRegistryTableMap::COL_TYPE_SVIATOK,
			self::ZASTUP => MissingRegistryTableMap::COL_TYPE_ZASTUP,
			self::SLUZBA => MissingRegistryTableMap::COL_TYPE_SLUZBA,
			self::DOVOLENKA => MissingRegistryTableMap::COL_TYPE_DOVOLENKA,
		};
	}

	/**
	 * Name of the form control and of the autosave field (`typePn`, `typeDovolenka`, …).
	 */
	public function field(): string
	{
		return 'type' . ucfirst($this->value);
	}

	public function label(): string
	{
		return match ($this) {
			self::PN => 'PN',
			self::OCR => 'OČR',
			self::LEKAR => 'Lekár',
			self::SVIATOK => 'Sviatok',
			self::ZASTUP => 'Zástup',
			self::SLUZBA => 'Služba',
			self::DOVOLENKA => 'Dovolenka',
		};
	}

	public function color(): string
	{
		return match ($this) {
			self::PN => '#dc3545',
			self::OCR => '#fd7e14',
			self::LEKAR => '#e83e8c',
			self::SVIATOK => '#6c757d',
			self::ZASTUP => '#6f42c1',
			self::SLUZBA => '#0d6efd',
			self::DOVOLENKA => '#198754',
		};
	}

	/**
	 * Absences from work are counted in working days. A duty or a substitution is work done,
	 * usually over a weekend, so every calendar day counts.
	 */
	public function countsWorkingDaysOnly(): bool
	{
		return match ($this) {
			self::ZASTUP, self::SLUZBA => false,
			self::PN, self::OCR, self::LEKAR, self::SVIATOK, self::DOVOLENKA => true,
		};
	}

	/**
	 * @return array<string, string> value => label
	 */
	public static function options(): array
	{
		$options = [];
		foreach (self::cases() as $type) {
			$options[$type->value] = $type->label();
		}

		return $options;
	}
}
