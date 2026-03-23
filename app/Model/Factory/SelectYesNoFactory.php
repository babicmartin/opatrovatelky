<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\SelectYesNoEntity;
use App\Model\Table\SelectYesNoTableMap;

class SelectYesNoFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return SelectYesNoEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return SelectYesNoTableMap::class;
    }
}
