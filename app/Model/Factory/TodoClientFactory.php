<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\TodoClientEntity;
use App\Model\Table\TodoClientTableMap;

class TodoClientFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return TodoClientEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return TodoClientTableMap::class;
    }
}
