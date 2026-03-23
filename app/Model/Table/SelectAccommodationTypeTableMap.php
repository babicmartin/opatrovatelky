<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectAccommodationTypeTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_accommodation_type';
    public const string TABLE_PREFIX = 'select_accommodation_type_mapper';

    public const string COL_ID = 'id';
    public const string COL_ACCOMMODATION_TYPE = 'accommodation_type';
}
