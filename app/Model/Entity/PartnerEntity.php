<?php

declare(strict_types=1);

namespace App\Model\Entity;

class PartnerEntity extends BaseEntity
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
        private ?string $street {
            get {
                return $this->street;
            }
            set {
                $this->street = $value;
            }
        },
        private ?string $streetNumber {
            get {
                return $this->streetNumber;
            }
            set {
                $this->streetNumber = $value;
            }
        },
        private ?string $psc {
            get {
                return $this->psc;
            }
            set {
                $this->psc = $value;
            }
        },
        private ?string $city {
            get {
                return $this->city;
            }
            set {
                $this->city = $value;
            }
        },
        private ?string $state {
            get {
                return $this->state;
            }
            set {
                $this->state = $value;
            }
        },
        private ?string $ico {
            get {
                return $this->ico;
            }
            set {
                $this->ico = $value;
            }
        },
        private ?string $icDph {
            get {
                return $this->icDph;
            }
            set {
                $this->icDph = $value;
            }
        },
        private ?string $web {
            get {
                return $this->web;
            }
            set {
                $this->web = $value;
            }
        },
        private ?string $phone {
            get {
                return $this->phone;
            }
            set {
                $this->phone = $value;
            }
        },
        private ?string $email {
            get {
                return $this->email;
            }
            set {
                $this->email = $value;
            }
        },
        private ?string $dateStart {
            get {
                return $this->dateStart;
            }
            set {
                $this->dateStart = $value;
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
        private ?string $active {
            get {
                return $this->active;
            }
            set {
                $this->active = $value;
            }
        },
        private ?string $personName {
            get {
                return $this->personName;
            }
            set {
                $this->personName = $value;
            }
        },
        private ?string $personSurname {
            get {
                return $this->personSurname;
            }
            set {
                $this->personSurname = $value;
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
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
