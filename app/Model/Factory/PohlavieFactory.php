<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\PohlavieEntity;
use App\Model\Table\PohlavieTableMap;

class PohlavieFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return PohlavieEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return PohlavieTableMap::class;
    }
}
