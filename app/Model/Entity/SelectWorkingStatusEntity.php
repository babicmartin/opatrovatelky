<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectWorkingStatusEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $slovak,
        public ?string $german,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
