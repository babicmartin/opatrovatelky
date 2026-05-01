<?php

declare(strict_types=1);

namespace App\Model\Entity;

class PartnerEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $name,
        public ?string $street,
        public ?string $streetNumber,
        public ?string $psc,
        public ?string $city,
        public ?string $state,
        public ?string $ico,
        public ?string $icDph,
        public ?string $web,
        public ?string $phone,
        public ?string $email,
        public ?string $dateStart,
        public ?string $status,
        public ?string $active,
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
