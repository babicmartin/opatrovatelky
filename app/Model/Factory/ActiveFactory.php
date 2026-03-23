<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\ActiveEntity;
use App\Model\Table\ActiveTableMap;

class ActiveFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return ActiveEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return ActiveTableMap::class;
    }
}
