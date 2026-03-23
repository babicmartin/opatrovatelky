<?php

declare(strict_types=1);

namespace App\Model\Table;

class SelectLanguageTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_select_language';
    public const string TABLE_PREFIX = 'select_language_mapper';

    public const string COL_ID = 'id';
    public const string COL_SLOVAK = 'slovak';
    public const string COL_GERMAN = 'german';
    public const string COL_STARS = 'stars';
}
