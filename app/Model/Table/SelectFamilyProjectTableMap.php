<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectFamilyProjectTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_family_project';
    public const string TABLE_PREFIX = 'select_family_project_mapper';

    public const string COL_ID = 'id';
    public const string COL_SLOVAK = 'slovak';
    public const string COL_GERMAN = 'german';
}
