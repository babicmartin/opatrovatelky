<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusFaEntity;
use App\Model\Table\StatusFaTableMap;

class StatusFaFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusFaEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusFaTableMap::class;
    }
}
