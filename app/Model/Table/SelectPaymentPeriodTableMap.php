<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectPaymentPeriodTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_payment_period';
    public const string TABLE_PREFIX = 'select_payment_period_mapper';

    public const string COL_ID = 'id';
    public const string COL_STATUS = 'status';
}
