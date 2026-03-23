<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\CountryEntity;
use App\Model\Table\CountryTableMap;

class CountryFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return CountryEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return CountryTableMap::class;
    }
}
