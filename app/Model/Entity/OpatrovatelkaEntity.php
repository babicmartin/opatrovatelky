<?php

declare(strict_types=1);

namespace App\Model\Entity;

class OpatrovatelkaEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $clientNumber,
        public ?string $name,
        public ?string $surname,
        public ?string $age,
        public ?string $pohlavie,
        public ?string $country,
        public ?string $active,
        public ?string $status,
        public ?string $birthday,
        public ?string $image,
        public ?string $smoker,
        public ?string $height,
        public ?string $weight,
        public ?string $phone,
        public ?string $phone2,
        public ?string $email,
        public ?string $drivingLicence,
        public ?string $city,
        public ?string $street,
        public ?string $postalCode,
        public ?string $workingStatus,
        public ?string $agencyId,
        public ?string $contactPersonName,
        public ?string $contactPersonPhone,
        public ?string $requirements,
        public ?string $notice,
        public ?string $blacklist,
        public ?string $firstContactUserId,
        public ?string $about,
        public ?string $allergy,
        public ?string $allergyDetail,
        public ?string $education,
        public ?string $course,
        public ?string $courseDetail,
        public ?string $readyDrive,
        public ?string $howLongWork,
        public ?string $howLongWorkGerman,
        public ?string $languageSkills,
        public ?string $languageSkillsOther,
        public ?string $workingArea,
        public ?string $dailyCare,
        public ?string $hourlyCare,
        public ?string $timeScale,
        public ?string $workPlace,
        public ?string $workDescription,
        public ?string $generalActivities,
        public ?string $ratingAgency,
        public ?string $profilShowContact,
        public ?string $type,
        public ?string $jobPositionInterest,
        public ?string $workShoes,
        public ?string $shoeSize,
        public ?string $germanTaxId,
        public ?string $accommodationType,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
