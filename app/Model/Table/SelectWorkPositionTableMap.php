<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectWorkPositionTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_work_position';
    public const string TABLE_PREFIX = 'select_work_position_mapper';

    public const string COL_ID = 'id';
    public const string COL_POSITION = 'position';
}
