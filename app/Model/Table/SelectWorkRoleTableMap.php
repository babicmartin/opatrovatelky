<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectWorkRoleTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_work_role';
    public const string TABLE_PREFIX = 'select_work_role_mapper';

    public const string COL_ID = 'id';
    public const string COL_SLOVAK = 'slovak';
    public const string COL_GERMAN = 'german';
}
