<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusFamilyEntity;
use App\Model\Table\StatusFamilyTableMap;

class StatusFamilyFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusFamilyEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusFamilyTableMap::class;
    }
}
