<?php

declare(strict_types=1);

namespace App\Model\Entity;

use DateTimeImmutable;

class OpatrovatelkaEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $clientNumber,
        public ?string $name,
        public ?string $surname,
        public ?int $age,
        public ?int $pohlavie,
        public ?int $country,
        public ?int $active,
        public ?int $status,
        public ?DateTimeImmutable $birthday,
        public ?string $image,
        public ?int $smoker,
        public ?int $height,
        public ?int $weight,
        public ?string $phone,
        public ?string $phone2,
        public ?string $email,
        public ?int $drivingLicence,
        public ?string $city,
        public ?string $street,
        public ?string $postalCode,
        public ?int $workingStatus,
        public ?int $agencyId,
        public ?string $contactPersonName,
        public ?string $contactPersonPhone,
        public ?string $requirements,
        public ?string $notice,
        public ?int $blacklist,
        public ?int $firstContactUserId,
        public ?string $about,
        public ?int $allergy,
        public ?string $allergyDetail,
        public ?int $education,
        public ?int $course,
        public ?string $courseDetail,
        public ?int $readyDrive,
        public ?string $howLongWork,
        public ?string $howLongWorkGerman,
        public ?int $languageSkills,
        public ?string $languageSkillsOther,
        public ?int $workingArea,
        public ?int $dailyCare,
        public ?int $hourlyCare,
        public ?string $timeScale,
        public ?string $workPlace,
        public ?string $workDescription,
        public ?string $generalActivities,
        public ?string $ratingAgency,
        public ?int $profilShowContact,
        public ?int $type,
        public ?string $jobPositionInterest,
        public ?int $workShoes,
        public ?int $shoeSize,
        public ?string $germanTaxId,
        public ?int $accommodationType,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
