<?php

declare(strict_types=1);

namespace App\Model\Entity;

use DateTimeImmutable;

class PartnerEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $name,
        public ?string $street,
        public ?string $streetNumber,
        public ?string $psc,
        public ?string $city,
        public ?int $state,
        public ?string $ico,
        public ?string $icDph,
        public ?string $web,
        public ?string $phone,
        public ?string $email,
        public ?DateTimeImmutable $dateStart,
        public ?int $status,
        public ?int $active,
        public ?string $personName,
        public ?string $personSurname,
        public ?string $notice,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
