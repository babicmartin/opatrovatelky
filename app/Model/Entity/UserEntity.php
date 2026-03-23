<?php

declare(strict_types=1);

namespace App\Model\Entity;

class UserEntity extends BaseEntity
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
        private ?string $secondName {
            get {
                return $this->secondName;
            }
            set {
                $this->secondName = $value;
            }
        },
        private ?string $acronym {
            get {
                return $this->acronym;
            }
            set {
                $this->acronym = $value;
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
        private ?string $password {
            get {
                return $this->password;
            }
            set {
                $this->password = $value;
            }
        },
        private ?string $permission {
            get {
                return $this->permission;
            }
            set {
                $this->permission = $value;
            }
        },
        private ?string $color {
            get {
                return $this->color;
            }
            set {
                $this->color = $value;
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
        private ?string $image {
            get {
                return $this->image;
            }
            set {
                $this->image = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
