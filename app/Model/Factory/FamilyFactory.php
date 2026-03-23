<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\FamilyEntity;
use App\Model\Table\FamilyTableMap;

class FamilyFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return FamilyEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return FamilyTableMap::class;
    }
}
