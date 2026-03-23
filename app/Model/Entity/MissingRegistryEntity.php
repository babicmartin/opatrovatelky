<?php

declare(strict_types=1);

namespace App\Model\Entity;

class MissingRegistryEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $userId {
            get {
                return $this->userId;
            }
            set {
                $this->userId = $value;
            }
        },
        private ?string $dateFrom {
            get {
                return $this->dateFrom;
            }
            set {
                $this->dateFrom = $value;
            }
        },
        private ?string $dateTo {
            get {
                return $this->dateTo;
            }
            set {
                $this->dateTo = $value;
            }
        },
        private ?string $typePn {
            get {
                return $this->typePn;
            }
            set {
                $this->typePn = $value;
            }
        },
        private ?string $typeOcr {
            get {
                return $this->typeOcr;
            }
            set {
                $this->typeOcr = $value;
            }
        },
        private ?string $typeLekar {
            get {
                return $this->typeLekar;
            }
            set {
                $this->typeLekar = $value;
            }
        },
        private ?string $typeSviatok {
            get {
                return $this->typeSviatok;
            }
            set {
                $this->typeSviatok = $value;
            }
        },
        private ?string $typeZastup {
            get {
                return $this->typeZastup;
            }
            set {
                $this->typeZastup = $value;
            }
        },
        private ?string $typeSluzba {
            get {
                return $this->typeSluzba;
            }
            set {
                $this->typeSluzba = $value;
            }
        },
        private ?string $typeDovolenka {
            get {
                return $this->typeDovolenka;
            }
            set {
                $this->typeDovolenka = $value;
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
        private ?string $active {
            get {
                return $this->active;
            }
            set {
                $this->active = $value;
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
