<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusPartnerEntity;
use App\Model\Table\StatusPartnerTableMap;

class StatusPartnerFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusPartnerEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusPartnerTableMap::class;
    }
}
