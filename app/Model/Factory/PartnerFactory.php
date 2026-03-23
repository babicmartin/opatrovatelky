<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\PartnerEntity;
use App\Model\Table\PartnerTableMap;

class PartnerFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return PartnerEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return PartnerTableMap::class;
    }
}
