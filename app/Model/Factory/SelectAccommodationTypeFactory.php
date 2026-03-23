<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectAccommodationTypeEntity;
use App\Model\Table\SelectAccommodationTypeTableMap;

class SelectAccommodationTypeFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectAccommodationTypeEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectAccommodationTypeTableMap::class;
    }
}
