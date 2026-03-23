<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\BabysitterPositionPreferenceEntity;
use App\Model\Table\BabysitterPositionPreferenceTableMap;

class BabysitterPositionPreferenceFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return BabysitterPositionPreferenceEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return BabysitterPositionPreferenceTableMap::class;
    }
}
