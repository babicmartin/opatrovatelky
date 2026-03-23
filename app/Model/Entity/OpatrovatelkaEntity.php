<?php

declare(strict_types=1);

namespace App\Model\Entity;

class OpatrovatelkaEntity extends BaseEntity
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
        private ?string $age {
            get {
                return $this->age;
            }
            set {
                $this->age = $value;
            }
        },
        private ?string $pohlavie {
            get {
                return $this->pohlavie;
            }
            set {
                $this->pohlavie = $value;
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
        private ?string $active {
            get {
                return $this->active;
            }
            set {
                $this->active = $value;
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
        private ?string $birthday {
            get {
                return $this->birthday;
            }
            set {
                $this->birthday = $value;
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
        private ?string $smoker {
            get {
                return $this->smoker;
            }
            set {
                $this->smoker = $value;
            }
        },
        private ?string $height {
            get {
                return $this->height;
            }
            set {
                $this->height = $value;
            }
        },
        private ?string $weight {
            get {
                return $this->weight;
            }
            set {
                $this->weight = $value;
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
        private ?string $phone2 {
            get {
                return $this->phone2;
            }
            set {
                $this->phone2 = $value;
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
        private ?string $drivingLicence {
            get {
                return $this->drivingLicence;
            }
            set {
                $this->drivingLicence = $value;
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
        private ?string $street {
            get {
                return $this->street;
            }
            set {
                $this->street = $value;
            }
        },
        private ?string $postalCode {
            get {
                return $this->postalCode;
            }
            set {
                $this->postalCode = $value;
            }
        },
        private ?string $workingStatus {
            get {
                return $this->workingStatus;
            }
            set {
                $this->workingStatus = $value;
            }
        },
        private ?string $agencyId {
            get {
                return $this->agencyId;
            }
            set {
                $this->agencyId = $value;
            }
        },
        private ?string $contactPersonName {
            get {
                return $this->contactPersonName;
            }
            set {
                $this->contactPersonName = $value;
            }
        },
        private ?string $contactPersonPhone {
            get {
                return $this->contactPersonPhone;
            }
            set {
                $this->contactPersonPhone = $value;
            }
        },
        private ?string $requirements {
            get {
                return $this->requirements;
            }
            set {
                $this->requirements = $value;
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
        private ?string $blacklist {
            get {
                return $this->blacklist;
            }
            set {
                $this->blacklist = $value;
            }
        },
        private ?string $firstContactUserId {
            get {
                return $this->firstContactUserId;
            }
            set {
                $this->firstContactUserId = $value;
            }
        },
        private ?string $about {
            get {
                return $this->about;
            }
            set {
                $this->about = $value;
            }
        },
        private ?string $allergy {
            get {
                return $this->allergy;
            }
            set {
                $this->allergy = $value;
            }
        },
        private ?string $allergyDetail {
            get {
                return $this->allergyDetail;
            }
            set {
                $this->allergyDetail = $value;
            }
        },
        private ?string $education {
            get {
                return $this->education;
            }
            set {
                $this->education = $value;
            }
        },
        private ?string $course {
            get {
                return $this->course;
            }
            set {
                $this->course = $value;
            }
        },
        private ?string $courseDetail {
            get {
                return $this->courseDetail;
            }
            set {
                $this->courseDetail = $value;
            }
        },
        private ?string $readyDrive {
            get {
                return $this->readyDrive;
            }
            set {
                $this->readyDrive = $value;
            }
        },
        private ?string $howLongWork {
            get {
                return $this->howLongWork;
            }
            set {
                $this->howLongWork = $value;
            }
        },
        private ?string $howLongWorkGerman {
            get {
                return $this->howLongWorkGerman;
            }
            set {
                $this->howLongWorkGerman = $value;
            }
        },
        private ?string $languageSkills {
            get {
                return $this->languageSkills;
            }
            set {
                $this->languageSkills = $value;
            }
        },
        private ?string $languageSkillsOther {
            get {
                return $this->languageSkillsOther;
            }
            set {
                $this->languageSkillsOther = $value;
            }
        },
        private ?string $workingArea {
            get {
                return $this->workingArea;
            }
            set {
                $this->workingArea = $value;
            }
        },
        private ?string $dailyCare {
            get {
                return $this->dailyCare;
            }
            set {
                $this->dailyCare = $value;
            }
        },
        private ?string $hourlyCare {
            get {
                return $this->hourlyCare;
            }
            set {
                $this->hourlyCare = $value;
            }
        },
        private ?string $timeScale {
            get {
                return $this->timeScale;
            }
            set {
                $this->timeScale = $value;
            }
        },
        private ?string $workPlace {
            get {
                return $this->workPlace;
            }
            set {
                $this->workPlace = $value;
            }
        },
        private ?string $workDescription {
            get {
                return $this->workDescription;
            }
            set {
                $this->workDescription = $value;
            }
        },
        private ?string $generalActivities {
            get {
                return $this->generalActivities;
            }
            set {
                $this->generalActivities = $value;
            }
        },
        private ?string $ratingAgency {
            get {
                return $this->ratingAgency;
            }
            set {
                $this->ratingAgency = $value;
            }
        },
        private ?string $profilShowContact {
            get {
                return $this->profilShowContact;
            }
            set {
                $this->profilShowContact = $value;
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
        private ?string $jobPositionInterest {
            get {
                return $this->jobPositionInterest;
            }
            set {
                $this->jobPositionInterest = $value;
            }
        },
        private ?string $workShoes {
            get {
                return $this->workShoes;
            }
            set {
                $this->workShoes = $value;
            }
        },
        private ?string $shoeSize {
            get {
                return $this->shoeSize;
            }
            set {
                $this->shoeSize = $value;
            }
        },
        private ?string $germanTaxId {
            get {
                return $this->germanTaxId;
            }
            set {
                $this->germanTaxId = $value;
            }
        },
        private ?string $accommodationType {
            get {
                return $this->accommodationType;
            }
            set {
                $this->accommodationType = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
