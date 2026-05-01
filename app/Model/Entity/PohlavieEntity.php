<?php

declare(strict_types=1);

namespace App\Model\Entity;

class PohlavieEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $pohlavie,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
