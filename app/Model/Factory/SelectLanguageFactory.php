<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectLanguageEntity;
use App\Model\Table\SelectLanguageTableMap;

class SelectLanguageFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectLanguageEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectLanguageTableMap::class;
    }
}
