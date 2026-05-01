<?php

declare(strict_types=1);

namespace App\Model\Entity;

class FamilyProposalEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $familyId,
        public ?string $babysitterId,
        public ?string $dateStartingWork,
        public ?string $dateProposalSended,
        public ?string $status,
        public ?string $notice,
        public ?string $userCreated,
        public ?string $dateCreated,
        public ?string $deleted,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
