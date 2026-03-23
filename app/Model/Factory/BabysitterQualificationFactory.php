<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\BabysitterQualificationEntity;
use App\Model\Table\BabysitterQualificationTableMap;

class BabysitterQualificationFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return BabysitterQualificationEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return BabysitterQualificationTableMap::class;
    }
}
