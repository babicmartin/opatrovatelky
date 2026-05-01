<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectYesNoEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $status,
        public ?string $german,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
