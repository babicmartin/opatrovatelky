<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusComplaintEntity;
use App\Model\Table\StatusComplaintTableMap;

class StatusComplaintFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusComplaintEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusComplaintTableMap::class;
    }
}
