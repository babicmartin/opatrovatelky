<?php

declare(strict_types=1);

namespace App\Model\Entity;

class CountryEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $name,
        public ?string $country,
        public ?string $german,
        public ?string $image,
        public ?string $active,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
