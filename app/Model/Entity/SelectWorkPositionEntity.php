<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectWorkPositionEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $position,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
