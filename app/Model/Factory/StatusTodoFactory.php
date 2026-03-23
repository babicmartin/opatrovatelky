<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusTodoEntity;
use App\Model\Table\StatusTodoTableMap;

class StatusTodoFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusTodoEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusTodoTableMap::class;
    }
}
