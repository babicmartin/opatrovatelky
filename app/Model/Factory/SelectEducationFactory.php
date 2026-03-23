<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectEducationEntity;
use App\Model\Table\SelectEducationTableMap;

class SelectEducationFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectEducationEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectEducationTableMap::class;
    }
}
