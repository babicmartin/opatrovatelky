<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\PageEntity;
use App\Model\Table\PageTableMap;

class PageFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return PageEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return PageTableMap::class;
    }
}
