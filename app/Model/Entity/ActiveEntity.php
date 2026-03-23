<?php

declare(strict_types=1);

namespace App\Model\Entity;

class ActiveEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private string $status {
            get {
                return $this->status;
            }
            set {
                $this->status = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
