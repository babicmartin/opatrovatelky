<?php

declare(strict_types=1);

namespace App\Model\Entity;

class TodoClientEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $familyId {
            get {
                return $this->familyId;
            }
            set {
                $this->familyId = $value;
            }
        },
        private ?string $babysitterId {
            get {
                return $this->babysitterId;
            }
            set {
                $this->babysitterId = $value;
            }
        },
        private ?string $todoFromUser {
            get {
                return $this->todoFromUser;
            }
            set {
                $this->todoFromUser = $value;
            }
        },
        private ?string $todoToUser1 {
            get {
                return $this->todoToUser1;
            }
            set {
                $this->todoToUser1 = $value;
            }
        },
        private ?string $todoToUser2 {
            get {
                return $this->todoToUser2;
            }
            set {
                $this->todoToUser2 = $value;
            }
        },
        private ?string $todoCreated {
            get {
                return $this->todoCreated;
            }
            set {
                $this->todoCreated = $value;
            }
        },
        private ?string $todoDeadline {
            get {
                return $this->todoDeadline;
            }
            set {
                $this->todoDeadline = $value;
            }
        },
        private ?string $title {
            get {
                return $this->title;
            }
            set {
                $this->title = $value;
            }
        },
        private ?string $description {
            get {
                return $this->description;
            }
            set {
                $this->description = $value;
            }
        },
        private ?string $answer {
            get {
                return $this->answer;
            }
            set {
                $this->answer = $value;
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
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
