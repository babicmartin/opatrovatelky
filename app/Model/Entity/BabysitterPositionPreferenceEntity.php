<?php

declare(strict_types=1);

namespace App\Model\Entity;

class BabysitterPositionPreferenceEntity extends BaseEntity
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
        private ?string $workPositionId {
            get {
                return $this->workPositionId;
            }
            set {
                $this->workPositionId = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
