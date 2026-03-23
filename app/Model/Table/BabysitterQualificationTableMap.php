<?php

declare(strict_types=1);

namespace App\Model\Table;

class BabysitterQualificationTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_babysitter_qualification';
    public const string TABLE_PREFIX = 'babysitter_qualification_mapper';

    public const string COL_ID = 'id';
    public const string COL_BABYSITTER_ID = 'babysitter_id';
    public const string COL_WORK_POSITION_ID = 'work_position_id';
}
