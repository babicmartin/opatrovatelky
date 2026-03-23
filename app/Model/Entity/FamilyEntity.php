<?php

declare(strict_types=1);

namespace App\Model\Entity;

class FamilyEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $clientNumber {
            get {
                return $this->clientNumber;
            }
            set {
                $this->clientNumber = $value;
            }
        },
        private ?string $name {
            get {
                return $this->name;
            }
            set {
                $this->name = $value;
            }
        },
        private ?string $surname {
            get {
                return $this->surname;
            }
            set {
                $this->surname = $value;
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
        private ?string $phone {
            get {
                return $this->phone;
            }
            set {
                $this->phone = $value;
            }
        },
        private ?string $personEmail {
            get {
                return $this->personEmail;
            }
            set {
                $this->personEmail = $value;
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
        private ?string $dateTo {
            get {
                return $this->dateTo;
            }
            set {
                $this->dateTo = $value;
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
        private ?string $personPhone {
            get {
                return $this->personPhone;
            }
            set {
                $this->personPhone = $value;
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
        private ?string $billing {
            get {
                return $this->billing;
            }
            set {
                $this->billing = $value;
            }
        },
        private ?string $partnerId {
            get {
                return $this->partnerId;
            }
            set {
                $this->partnerId = $value;
            }
        },
        private ?string $userId {
            get {
                return $this->userId;
            }
            set {
                $this->userId = $value;
            }
        },
        private ?string $acquiredByUserId {
            get {
                return $this->acquiredByUserId;
            }
            set {
                $this->acquiredByUserId = $value;
            }
        },
        private ?string $orderStatus {
            get {
                return $this->orderStatus;
            }
            set {
                $this->orderStatus = $value;
            }
        },
        private ?string $contractStatus {
            get {
                return $this->contractStatus;
            }
            set {
                $this->contractStatus = $value;
            }
        },
        private ?string $patientPhone {
            get {
                return $this->patientPhone;
            }
            set {
                $this->patientPhone = $value;
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
        private ?string $type {
            get {
                return $this->type;
            }
            set {
                $this->type = $value;
            }
        },
        private ?string $companyName {
            get {
                return $this->companyName;
            }
            set {
                $this->companyName = $value;
            }
        },
        private ?string $employer {
            get {
                return $this->employer;
            }
            set {
                $this->employer = $value;
            }
        },
        private ?string $accommodationAddress {
            get {
                return $this->accommodationAddress;
            }
            set {
                $this->accommodationAddress = $value;
            }
        },
        private ?string $deProjectNumber {
            get {
                return $this->deProjectNumber;
            }
            set {
                $this->deProjectNumber = $value;
            }
        },
        private ?string $projectDescription {
            get {
                return $this->projectDescription;
            }
            set {
                $this->projectDescription = $value;
            }
        },
        private ?string $projectPositions {
            get {
                return $this->projectPositions;
            }
            set {
                $this->projectPositions = $value;
            }
        },
        private ?string $projectAvailablePositions {
            get {
                return $this->projectAvailablePositions;
            }
            set {
                $this->projectAvailablePositions = $value;
            }
        },
        private ?string $workStatusStaff {
            get {
                return $this->workStatusStaff;
            }
            set {
                $this->workStatusStaff = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
