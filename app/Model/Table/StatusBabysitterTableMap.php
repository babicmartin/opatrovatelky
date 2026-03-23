<?php

declare(strict_types=1);

namespace App\Model\Table;

class StatusBabysitterTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_status_babysitters';
    public const string TABLE_PREFIX = 'status_babysitter_mapper';

    public const string COL_ID = 'id';
    public const string COL_STATUS = 'status';
    public const string COL_COLOR = 'color';
}
