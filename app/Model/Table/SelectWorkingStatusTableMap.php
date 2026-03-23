<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectWorkingStatusTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_working_status';
    public const string TABLE_PREFIX = 'select_working_status_mapper';

    public const string COL_ID = 'id';
    public const string COL_SLOVAK = 'slovak';
    public const string COL_GERMAN = 'german';
}
