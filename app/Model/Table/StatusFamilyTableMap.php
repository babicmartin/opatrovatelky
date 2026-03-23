<?php

declare(strict_types=1);

namespace App\Model\Table;

class StatusFamilyTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_status_families';
    public const string TABLE_PREFIX = 'status_family_mapper';

    public const string COL_ID = 'id';
    public const string COL_STATUS = 'status';
    public const string COL_COLOR = 'color';
}
