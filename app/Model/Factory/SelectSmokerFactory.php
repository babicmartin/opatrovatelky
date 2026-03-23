<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectSmokerEntity;
use App\Model\Table\SelectSmokerTableMap;

class SelectSmokerFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectSmokerEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectSmokerTableMap::class;
    }
}
