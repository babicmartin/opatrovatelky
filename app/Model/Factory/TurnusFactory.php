<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\TurnusEntity;
use App\Model\Table\TurnusTableMap;

class TurnusFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return TurnusEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return TurnusTableMap::class;
    }
}
