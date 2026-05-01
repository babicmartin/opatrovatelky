<?php

declare(strict_types=1);

namespace App\Model\Entity;

class TodoClientEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $familyId,
        public ?string $babysitterId,
        public ?string $todoFromUser,
        public ?string $todoToUser1,
        public ?string $todoToUser2,
        public ?string $todoCreated,
        public ?string $todoDeadline,
        public ?string $title,
        public ?string $description,
        public ?string $answer,
        public ?string $status,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
