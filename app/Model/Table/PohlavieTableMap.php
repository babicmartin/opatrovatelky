<?php

declare(strict_types=1);

namespace App\Model\Table;

class PohlavieTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_pohlavie';
    public const string TABLE_PREFIX = 'pohlavie_mapper';

    public const string COL_ID = 'id';
    public const string COL_POHLAVIE = 'pohlavie';
}
