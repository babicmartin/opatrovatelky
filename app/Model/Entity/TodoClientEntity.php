<?php

declare(strict_types=1);

namespace App\Model\Entity;

use DateTimeImmutable;

class TodoClientEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?int $familyId,
        public ?int $babysitterId,
        public ?int $todoFromUser,
        public ?int $todoToUser1,
        public ?int $todoToUser2,
        public ?DateTimeImmutable $todoCreated,
        public ?DateTimeImmutable $todoDeadline,
        public ?string $title,
        public ?string $description,
        public ?string $answer,
        public ?int $status,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
