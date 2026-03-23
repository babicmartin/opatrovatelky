<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectYesNoEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $status {
            get {
                return $this->status;
            }
            set {
                $this->status = $value;
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
