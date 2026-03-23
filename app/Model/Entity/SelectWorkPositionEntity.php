<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectWorkPositionEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $position {
            get {
                return $this->position;
            }
            set {
                $this->position = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
