<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectFamilyProjectEntity;
use App\Model\Table\SelectFamilyProjectTableMap;

class SelectFamilyProjectFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectFamilyProjectEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectFamilyProjectTableMap::class;
    }
}
