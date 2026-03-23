<?php

declare(strict_types=1);

namespace App\Model\Entity;

class FileEntity extends BaseEntity
{
    public function __construct(
        private readonly int $id,
        private ?string $permission {
            get {
                return $this->permission;
            }
            set {
                $this->permission = $value;
            }
        },
        private ?string $dir {
            get {
                return $this->dir;
            }
            set {
                $this->dir = $value;
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
        private ?string $user {
            get {
                return $this->user;
            }
            set {
                $this->user = $value;
            }
        },
        private ?string $upload {
            get {
                return $this->upload;
            }
            set {
                $this->upload = $value;
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
        private ?string $type {
            get {
                return $this->type;
            }
            set {
                $this->type = $value;
            }
        },
        private ?string $validFrom {
            get {
                return $this->validFrom;
            }
            set {
                $this->validFrom = $value;
            }
        },
        private ?string $validTo {
            get {
                return $this->validTo;
            }
            set {
                $this->validTo = $value;
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
        private ?string $status {
            get {
                return $this->status;
            }
            set {
                $this->status = $value;
            }
        },
        private ?string $status2 {
            get {
                return $this->status2;
            }
            set {
                $this->status2 = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
