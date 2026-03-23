<?php

declare(strict_types=1);

namespace App\Model\Table;

class UserTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sany_users';
    public const string TABLE_PREFIX = 'user_mapper';

    public const string COL_ID = 'id';
    public const string COL_NAME = 'name';
    public const string COL_SECOND_NAME = 'second_name';
    public const string COL_ACRONYM = 'acronym';
    public const string COL_EMAIL = 'email';
    public const string COL_PASSWORD = 'password';
    public const string COL_PERMISSION = 'permission';
    public const string COL_COLOR = 'color';
    public const string COL_ACTIVE = 'active';
    public const string COL_IMAGE = 'image';
}
