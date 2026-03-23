<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectMissingTypeTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_missing_type';
    public const string TABLE_PREFIX = 'select_missing_type_mapper';

    public const string COL_ID = 'id';
    public const string COL_ACTION = 'action';
}
