<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectPaymentPeriodEntity;
use App\Model\Table\SelectPaymentPeriodTableMap;

class SelectPaymentPeriodFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectPaymentPeriodEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectPaymentPeriodTableMap::class;
    }
}
