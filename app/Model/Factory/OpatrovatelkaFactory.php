<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\OpatrovatelkaEntity;
use App\Model\Table\OpatrovatelkaTableMap;

class OpatrovatelkaFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return OpatrovatelkaEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return OpatrovatelkaTableMap::class;
    }
}
