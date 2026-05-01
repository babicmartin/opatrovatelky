<?php

declare(strict_types=1);

namespace App\Model\Entity;

class PermissionEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $permission,
        public ?string $name,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
