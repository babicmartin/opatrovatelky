<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectLanguageEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $slovak,
        public ?string $german,
        public ?int $stars,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
