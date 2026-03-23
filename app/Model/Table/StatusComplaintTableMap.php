<?php

declare(strict_types=1);

namespace App\Model\Table;

class StatusComplaintTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_status_complaint';
    public const string TABLE_PREFIX = 'status_complaint_mapper';

    public const string COL_ID = 'id';
    public const string COL_STATUS = 'status';
}
