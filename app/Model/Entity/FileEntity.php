<?php

declare(strict_types=1);

namespace App\Model\Entity;

class FileEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $permission,
        public ?string $dir,
        public ?string $name,
        public ?string $user,
        public ?string $upload,
        public ?string $active,
        public ?string $type,
        public ?string $validFrom,
        public ?string $validTo,
        public ?string $notice,
        public ?string $status,
        public ?string $status2,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
