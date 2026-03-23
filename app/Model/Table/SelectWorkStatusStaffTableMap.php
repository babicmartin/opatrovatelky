<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectWorkStatusStaffTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_work_status_staff';
    public const string TABLE_PREFIX = 'select_work_status_staff_mapper';

    public const string COL_ID = 'id';
    public const string COL_CONTRACT = 'contract';
}
