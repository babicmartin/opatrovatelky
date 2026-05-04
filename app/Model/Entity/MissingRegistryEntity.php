<?php

declare(strict_types=1);

namespace App\Model\Entity;

use DateTimeImmutable;

class MissingRegistryEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?int $userId,
        public ?DateTimeImmutable $dateFrom,
        public ?DateTimeImmutable $dateTo,
        public ?int $typePn,
        public ?int $typeOcr,
        public ?int $typeLekar,
        public ?int $typeSviatok,
        public ?int $typeZastup,
        public ?int $typeSluzba,
        public ?int $typeDovolenka,
        public ?string $notice,
        public ?int $active,
        public ?int $deleted,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
