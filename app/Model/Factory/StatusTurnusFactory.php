<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusTurnusEntity;
use App\Model\Table\StatusTurnusTableMap;

class StatusTurnusFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusTurnusEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusTurnusTableMap::class;
    }
}
