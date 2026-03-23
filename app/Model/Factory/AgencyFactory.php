<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\AgencyEntity;
use App\Model\Table\AgencyTableMap;

class AgencyFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return AgencyEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return AgencyTableMap::class;
    }
}
