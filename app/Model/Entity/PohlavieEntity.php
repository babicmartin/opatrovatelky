<?php

declare(strict_types=1);

namespace App\Model\Entity;

class PohlavieEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $pohlavie {
            get {
                return $this->pohlavie;
            }
            set {
                $this->pohlavie = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
