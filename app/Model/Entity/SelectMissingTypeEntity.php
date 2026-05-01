<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectMissingTypeEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $action,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
