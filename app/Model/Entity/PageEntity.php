<?php

declare(strict_types=1);

namespace App\Model\Entity;

class PageEntity extends BaseEntity
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
        private ?string $url {
            get {
                return $this->url;
            }
            set {
                $this->url = $value;
            }
        },
        private ?string $parent {
            get {
                return $this->parent;
            }
            set {
                $this->parent = $value;
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
        private ?string $active {
            get {
                return $this->active;
            }
            set {
                $this->active = $value;
            }
        },
        private ?string $inMenu {
            get {
                return $this->inMenu;
            }
            set {
                $this->inMenu = $value;
            }
        },
        private ?string $showParents {
            get {
                return $this->showParents;
            }
            set {
                $this->showParents = $value;
            }
        },
        private ?string $showSameLevel {
            get {
                return $this->showSameLevel;
            }
            set {
                $this->showSameLevel = $value;
            }
        },
        private ?string $header {
            get {
                return $this->header;
            }
            set {
                $this->header = $value;
            }
        },
        private ?string $sidebarRight {
            get {
                return $this->sidebarRight;
            }
            set {
                $this->sidebarRight = $value;
            }
        },
        private ?string $position {
            get {
                return $this->position;
            }
            set {
                $this->position = $value;
            }
        },
        private ?string $templateFolder {
            get {
                return $this->templateFolder;
            }
            set {
                $this->templateFolder = $value;
            }
        },
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
