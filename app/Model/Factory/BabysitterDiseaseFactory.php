<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\BabysitterDiseaseEntity;
use App\Model\Table\BabysitterDiseaseTableMap;

class BabysitterDiseaseFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return BabysitterDiseaseEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return BabysitterDiseaseTableMap::class;
    }
}
