<?php

declare(strict_types=1);

namespace App\Model\Table;

class FileTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_files';
    public const string TABLE_PREFIX = 'file_mapper';

    public const string COL_ID = 'id';
    public const string COL_PERMISSION = 'permission';
    public const string COL_DIR = 'dir';
    public const string COL_NAME = 'name';
    public const string COL_USER = 'user';
    public const string COL_UPLOAD = 'upload';
    public const string COL_ACTIVE = 'active';
    public const string COL_TYPE = 'type';
    public const string COL_VALID_FROM = 'valid_from';
    public const string COL_VALID_TO = 'valid_to';
    public const string COL_NOTICE = 'notice';
    public const string COL_STATUS = 'status';
    public const string COL_STATUS2 = 'status2';
}
