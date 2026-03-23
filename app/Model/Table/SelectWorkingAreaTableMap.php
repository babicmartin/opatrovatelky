<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectWorkingAreaTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_working_area';
    public const string TABLE_PREFIX = 'select_working_area_mapper';

    public const string COL_ID = 'id';
    public const string COL_SLOVAK = 'slovak';
    public const string COL_GERMAN = 'german';
}
