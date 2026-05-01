<?php

declare(strict_types=1);

namespace App\Model\Entity;

class StatusTodoEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $status,
        public ?string $color,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
