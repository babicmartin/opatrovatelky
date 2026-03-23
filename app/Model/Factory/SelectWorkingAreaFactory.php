<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectWorkingAreaEntity;
use App\Model\Table\SelectWorkingAreaTableMap;

class SelectWorkingAreaFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectWorkingAreaEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectWorkingAreaTableMap::class;
    }
}
