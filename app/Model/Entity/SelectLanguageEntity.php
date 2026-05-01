<?php

declare(strict_types=1);

namespace App\Model\Entity;

class SelectLanguageEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $slovak,
        public ?string $german,
        public ?string $stars,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
