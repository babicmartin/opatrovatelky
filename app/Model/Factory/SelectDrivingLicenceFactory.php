<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectDrivingLicenceEntity;
use App\Model\Table\SelectDrivingLicenceTableMap;

class SelectDrivingLicenceFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectDrivingLicenceEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectDrivingLicenceTableMap::class;
    }
}
