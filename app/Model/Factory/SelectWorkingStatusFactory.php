<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectWorkingStatusEntity;
use App\Model\Table\SelectWorkingStatusTableMap;

class SelectWorkingStatusFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectWorkingStatusEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectWorkingStatusTableMap::class;
    }
}
