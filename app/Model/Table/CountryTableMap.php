<?php

declare(strict_types=1);

namespace App\Model\Table;

class CountryTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_country';
    public const string TABLE_PREFIX = 'country_mapper';

    public const string COL_ID = 'id';
    public const string COL_NAME = 'name';
    public const string COL_COUNTRY = 'country';
    public const string COL_GERMAN = 'german';
    public const string COL_IMAGE = 'image';
    public const string COL_ACTIVE = 'active';
}
