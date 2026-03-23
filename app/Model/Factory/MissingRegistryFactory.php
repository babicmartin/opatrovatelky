<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\MissingRegistryEntity;
use App\Model\Table\MissingRegistryTableMap;

class MissingRegistryFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return MissingRegistryEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return MissingRegistryTableMap::class;
    }
}
