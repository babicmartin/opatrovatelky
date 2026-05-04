<?php

declare(strict_types=1);

namespace App\Model\Entity;

use DateTimeImmutable;

class FileEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?int $permission,
        public ?string $dir,
        public ?string $name,
        public ?int $user,
        public ?DateTimeImmutable $upload,
        public ?int $active,
        public ?string $type,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validTo,
        public ?string $notice,
        public ?int $status,
        public ?int $status2,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
