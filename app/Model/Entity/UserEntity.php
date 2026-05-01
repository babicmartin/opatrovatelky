<?php

declare(strict_types=1);

namespace App\Model\Entity;

class UserEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $name,
        public ?string $secondName,
        public ?string $acronym,
        public ?string $email,
        public ?string $password,
        public ?int $permission,
        public ?string $color,
        public ?int $active,
        public ?string $image,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
