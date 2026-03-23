<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectDiseaseEntity;
use App\Model\Table\SelectDiseaseTableMap;

class SelectDiseaseFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectDiseaseEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectDiseaseTableMap::class;
    }
}
