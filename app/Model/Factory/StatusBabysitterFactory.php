<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusBabysitterEntity;
use App\Model\Table\StatusBabysitterTableMap;

class StatusBabysitterFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusBabysitterEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusBabysitterTableMap::class;
    }
}
