<?php

declare(strict_types=1);

namespace App\Model\Entity;

class BabysitterDiseaseEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $babysitterId {
            get {
                return $this->babysitterId;
            }
            set {
                $this->babysitterId = $value;
            }
        },
        private ?string $diseaseId {
            get {
                return $this->diseaseId;
            }
            set {
                $this->diseaseId = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
