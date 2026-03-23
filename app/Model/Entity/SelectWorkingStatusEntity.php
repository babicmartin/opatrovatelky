<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectWorkingStatusEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $slovak {
            get {
                return $this->slovak;
            }
            set {
                $this->slovak = $value;
            }
        },
        private ?string $german {
            get {
                return $this->german;
            }
            set {
                $this->german = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
