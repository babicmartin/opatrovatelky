<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\UserEntity;
use App\Model\Table\UserTableMap;

class UserFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return UserEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return UserTableMap::class;
    }
}
