<?php

declare(strict_types=1);

namespace App\Model\Entity;

class CountryEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $name {
            get {
                return $this->name;
            }
            set {
                $this->name = $value;
            }
        },
        private ?string $country {
            get {
                return $this->country;
            }
            set {
                $this->country = $value;
            }
        },
        private ?string $german {
            get {
                return $this->german;
            }
            set {
                $this->german = $value;
            }
        },
        private ?string $image {
            get {
                return $this->image;
            }
            set {
                $this->image = $value;
            }
        },
        private ?string $active {
            get {
                return $this->active;
            }
            set {
                $this->active = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
