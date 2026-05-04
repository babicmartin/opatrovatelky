<?php

declare(strict_types=1);

namespace App\Model\Entity;

class BabysitterQualificationEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?int $babysitterId,
        public ?int $workPositionId,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
