<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\PermissionEntity;
use App\Model\Table\PermissionTableMap;

class PermissionFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return PermissionEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return PermissionTableMap::class;
    }
}
