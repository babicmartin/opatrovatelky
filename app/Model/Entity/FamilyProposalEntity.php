<?php

declare(strict_types=1);

namespace App\Model\Entity;

use DateTimeImmutable;

class FamilyProposalEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?int $familyId,
        public ?int $babysitterId,
        public ?DateTimeImmutable $dateStartingWork,
        public ?DateTimeImmutable $dateProposalSended,
        public ?int $status,
        public ?string $notice,
        public ?int $userCreated,
        public ?DateTimeImmutable $dateCreated,
        public ?int $deleted,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
