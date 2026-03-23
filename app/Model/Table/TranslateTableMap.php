<?php

declare(strict_types=1);

namespace App\Model\Table;

class TranslateTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_translate';
    public const string TABLE_PREFIX = 'translate_mapper';

    public const string COL_ID = 'id';
    public const string COL_SLOVAK = 'slovak';
    public const string COL_GERMAN = 'german';
}
