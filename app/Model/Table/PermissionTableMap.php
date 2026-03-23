<?php

declare(strict_types=1);

namespace App\Model\Table;

class PermissionTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_permission';
    public const string TABLE_PREFIX = 'permission_mapper';

    public const string COL_PERMISSION = 'permission';
    public const string COL_NAME = 'name';
    public const string COL_ID = 'id';
}
