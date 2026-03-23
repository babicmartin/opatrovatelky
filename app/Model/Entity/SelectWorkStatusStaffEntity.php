<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectWorkStatusStaffEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $contract {
            get {
                return $this->contract;
            }
            set {
                $this->contract = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
