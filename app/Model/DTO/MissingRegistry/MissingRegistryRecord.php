<?php declare(strict_types=1);

namespace App\Model\DTO\MissingRegistry;

use App\Model\Enum\MissingRegistry\MissingType;
use DateTimeImmutable;

/**
 * One row of the absence registry.
 */
final readonly class MissingRegistryRecord
{
	/**
	 * @param list<MissingType> $types
	 */
	public function __construct(
		public int $id,
		public int $userId,
		public ?DateTimeImmutable $dateFrom,
		public ?DateTimeImmutable $dateTo,
		public array $types,
		public string $notice,
	) {
	}

	public function hasType(MissingType $type): bool
	{
		return in_array($type, $this->types, true);
	}

	/**
	 * Last day of the record. A missing end, or an end before the start, means a one-day record.
	 */
	public function getLastDay(): ?DateTimeImmutable
	{
		if ($this->dateFrom === null) {
			return null;
		}

		return $this->dateTo !== null && $this->dateTo >= $this->dateFrom ? $this->dateTo : $this->dateFrom;
	}

	public function covers(DateTimeImmutable $day): bool
	{
		$lastDay = $this->getLastDay();
		$key = $day->format('Y-m-d');

		return $this->dateFrom !== null
			&& $lastDay !== null
			&& $this->dateFrom->format('Y-m-d') <= $key
			&& $key <= $lastDay->format('Y-m-d');
	}

	/**
	 * @return list<string>
	 */
	public function getTypeValues(): array
	{
		return array_map(static fn (MissingType $type): string => $type->value, $this->types);
	}

	public function getTypeLabel(): string
	{
		return $this->types === []
			? 'Nezaradené'
			: implode(' + ', array_map(static fn (MissingType $type): string => $type->label(), $this->types));
	}
}
