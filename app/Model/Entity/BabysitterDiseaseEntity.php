<?php

declare(strict_types=1);

namespace App\Model\Entity;

class BabysitterDiseaseEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $babysitterId,
        public ?string $diseaseId,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
