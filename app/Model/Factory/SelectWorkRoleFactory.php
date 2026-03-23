<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectWorkRoleEntity;
use App\Model\Table\SelectWorkRoleTableMap;

class SelectWorkRoleFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectWorkRoleEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectWorkRoleTableMap::class;
    }
}
