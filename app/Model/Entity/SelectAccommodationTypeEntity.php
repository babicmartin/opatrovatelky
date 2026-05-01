<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectAccommodationTypeEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $accommodationType,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
