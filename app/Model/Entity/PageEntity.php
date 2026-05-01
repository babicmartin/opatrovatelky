<?php

declare(strict_types=1);

namespace App\Model\Entity;

class PageEntity extends BaseEntity
{
    public function __construct(
        public readonly int $id,
        public ?string $name,
        public ?string $url,
        public ?int $parent,
        public ?int $permission,
        public ?int $active,
        public ?int $inMenu,
        public ?int $showParents,
        public ?int $showSameLevel,
        public ?int $header,
        public ?int $sidebarRight,
        public ?int $position,
        public ?string $templateFolder,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
