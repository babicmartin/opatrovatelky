<?php

declare(strict_types=1);

namespace App\Model\Entity;

class FamilyProposalEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $familyId {
            get {
                return $this->familyId;
            }
            set {
                $this->familyId = $value;
            }
        },
        private ?string $babysitterId {
            get {
                return $this->babysitterId;
            }
            set {
                $this->babysitterId = $value;
            }
        },
        private ?string $dateStartingWork {
            get {
                return $this->dateStartingWork;
            }
            set {
                $this->dateStartingWork = $value;
            }
        },
        private ?string $dateProposalSended {
            get {
                return $this->dateProposalSended;
            }
            set {
                $this->dateProposalSended = $value;
            }
        },
        private ?string $status {
            get {
                return $this->status;
            }
            set {
                $this->status = $value;
            }
        },
        private ?string $notice {
            get {
                return $this->notice;
            }
            set {
                $this->notice = $value;
            }
        },
        private ?string $userCreated {
            get {
                return $this->userCreated;
            }
            set {
                $this->userCreated = $value;
            }
        },
        private ?string $dateCreated {
            get {
                return $this->dateCreated;
            }
            set {
                $this->dateCreated = $value;
            }
        },
        private ?string $deleted {
            get {
                return $this->deleted;
            }
            set {
                $this->deleted = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
