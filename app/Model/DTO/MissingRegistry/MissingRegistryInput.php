<?php declare(strict_types=1);

namespace App\Model\DTO\MissingRegistry;

use App\Model\Enum\MissingRegistry\MissingType;
use DateTimeImmutable;

/**
 * Values a user asks to store in a registry record. `userId` is only a request:
 * MissingRegistryAccessPolicy decides whose record it really becomes.
 */
final readonly class MissingRegistryInput
{
	/**
	 * @param list<MissingType> $types
	 */
	public function __construct(
		public ?int $userId,
		public ?DateTimeImmutable $dateFrom,
		public ?DateTimeImmutable $dateTo,
		public array $types,
		public string $notice,
	) {
	}
}
