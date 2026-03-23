<?php

declare(strict_types=1);

namespace App\Model\Factory;

use App\Model\Entity\FileEntity;
use App\Model\Table\FileTableMap;

class FileFactory extends BaseFactory
{
    protected function getEntityClass(): string
    {
        return FileEntity::class;
    }

    protected function getTableMapClass(): string
    {
        return FileTableMap::class;
    }
}
