<?php

declare(strict_types=1);

namespace App\Model\Table;

class ActiveTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_active';
    public const string TABLE_PREFIX = 'active_mapper';

    public const string COL_ID = 'id';
    public const string COL_STATUS = 'status';
}
