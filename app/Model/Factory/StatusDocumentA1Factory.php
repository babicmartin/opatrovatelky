<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\StatusDocumentA1Entity;
use App\Model\Table\StatusDocumentA1TableMap;

class StatusDocumentA1Factory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return StatusDocumentA1Entity::class;
    }

    protected function getTableMapClass(): string
    {
        return StatusDocumentA1TableMap::class;
    }
}
