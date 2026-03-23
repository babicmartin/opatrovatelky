<?php

declare(strict_types=1);

namespace App\Model\Entity;

class PermissionEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $permission {
            get {
                return $this->permission;
            }
            set {
                $this->permission = $value;
            }
        },
        private ?string $name {
            get {
                return $this->name;
            }
            set {
                $this->name = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
