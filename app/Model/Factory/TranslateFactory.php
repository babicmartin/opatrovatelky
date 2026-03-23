<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\TranslateEntity;
use App\Model\Table\TranslateTableMap;


class TranslateFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return TranslateEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return TranslateTableMap::class;
    }
}
