<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectWorkPositionEntity;
use App\Model\Table\SelectWorkPositionTableMap;

class SelectWorkPositionFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectWorkPositionEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectWorkPositionTableMap::class;
    }
}
