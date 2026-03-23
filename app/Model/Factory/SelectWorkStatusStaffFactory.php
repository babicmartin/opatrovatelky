<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectWorkStatusStaffEntity;
use App\Model\Table\SelectWorkStatusStaffTableMap;

class SelectWorkStatusStaffFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectWorkStatusStaffEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectWorkStatusStaffTableMap::class;
    }
}
