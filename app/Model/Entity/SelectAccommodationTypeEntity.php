<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectAccommodationTypeEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $accommodationType {
            get {
                return $this->accommodationType;
            }
            set {
                $this->accommodationType = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
