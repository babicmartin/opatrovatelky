<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectMissingTypeEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $action {
            get {
                return $this->action;
            }
            set {
                $this->action = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
