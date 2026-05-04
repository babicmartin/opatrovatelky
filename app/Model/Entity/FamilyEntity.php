<?php

declare(strict_types=1);

namespace App\Model\Entity;

use DateTimeImmutable;

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
        public ?int $state,
        public ?string $phone,
        public ?string $personEmail,
        public ?DateTimeImmutable $dateStart,
        public ?DateTimeImmutable $dateTo,
        public ?int $status,
        public ?int $active,
        public ?string $personName,
        public ?string $personSurname,
        public ?string $personPhone,
        public ?string $notice,
        public ?string $billing,
        public ?int $partnerId,
        public ?int $userId,
        public ?int $acquiredByUserId,
        public ?int $orderStatus,
        public ?int $contractStatus,
        public ?string $patientPhone,
        public ?int $deleted,
        public ?int $type,
        public ?string $companyName,
        public ?string $employer,
        public ?string $accommodationAddress,
        public ?string $deProjectNumber,
        public ?string $projectDescription,
        public ?string $projectPositions,
        public ?string $projectAvailablePositions,
        public ?int $workStatusStaff,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
