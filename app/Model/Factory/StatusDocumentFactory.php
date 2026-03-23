<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusDocumentEntity;
use App\Model\Table\StatusDocumentTableMap;

class StatusDocumentFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusDocumentEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusDocumentTableMap::class;
    }
}
