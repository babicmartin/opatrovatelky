<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectMissingTypeEntity;
use App\Model\Table\SelectMissingTypeTableMap;

class SelectMissingTypeFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectMissingTypeEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectMissingTypeTableMap::class;
    }
}
