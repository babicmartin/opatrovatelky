<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectWorkStatusStaffEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $contract,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
