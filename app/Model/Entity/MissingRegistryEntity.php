<?php

declare(strict_types=1);

namespace App\Model\Entity;

class MissingRegistryEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $userId,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?string $typePn,
        public ?string $typeOcr,
        public ?string $typeLekar,
        public ?string $typeSviatok,
        public ?string $typeZastup,
        public ?string $typeSluzba,
        public ?string $typeDovolenka,
        public ?string $notice,
        public ?string $active,
        public ?string $deleted,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
