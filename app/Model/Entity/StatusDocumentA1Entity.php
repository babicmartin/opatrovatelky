<?php

declare(strict_types=1);

namespace App\Model\Entity;

class StatusDocumentA1Entity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $status,
        public ?string $color,
        public ?string $icon,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
