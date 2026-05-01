<?php

declare(strict_types=1);

namespace App\Model\Entity;

class FamilyEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $clientNumber,
        public ?string $name,
        public ?string $surname,
        public ?string $street,
        public ?string $streetNumber,
        public ?string $psc,
        public ?string $city,
        public ?string $state,
        public ?string $phone,
        public ?string $personEmail,
        public ?string $dateStart,
        public ?string $dateTo,
        public ?string $status,
        public ?string $active,
        public ?string $personName,
        public ?string $personSurname,
        public ?string $personPhone,
        public ?string $notice,
        public ?string $billing,
        public ?string $partnerId,
        public ?string $userId,
        public ?string $acquiredByUserId,
        public ?string $orderStatus,
        public ?string $contractStatus,
        public ?string $patientPhone,
        public ?string $deleted,
        public ?string $type,
        public ?string $companyName,
        public ?string $employer,
        public ?string $accommodationAddress,
        public ?string $deProjectNumber,
        public ?string $projectDescription,
        public ?string $projectPositions,
        public ?string $projectAvailablePositions,
        public ?string $workStatusStaff,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
