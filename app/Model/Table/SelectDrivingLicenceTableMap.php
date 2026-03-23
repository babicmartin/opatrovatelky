<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectDrivingLicenceTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_driving_licence';
    public const string TABLE_PREFIX = 'select_driving_licence_mapper';

    public const string COL_ID = 'id';
    public const string COL_SLOVAK = 'slovak';
    public const string COL_GERMAN = 'german';
}
