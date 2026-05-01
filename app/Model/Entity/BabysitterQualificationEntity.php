<?php

declare(strict_types=1);

namespace App\Model\Entity;

class BabysitterQualificationEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $babysitterId,
        public ?string $workPositionId,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
