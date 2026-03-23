<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectYesNoTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_yes_no';
    public const string TABLE_PREFIX = 'select_yes_no_mapper';

    public const string COL_ID = 'id';
    public const string COL_STATUS = 'status';
    public const string COL_GERMAN = 'german';
}
