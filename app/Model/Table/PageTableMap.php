<?php

declare(strict_types=1);

namespace App\Model\Table;

class PageTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sany_pages';
    public const string TABLE_PREFIX = 'page_mapper';

    public const string COL_ID = 'id';
    public const string COL_NAME = 'name';
    public const string COL_URL = 'url';
    public const string COL_PARENT = 'parent';
    public const string COL_PERMISSION = 'permission';
    public const string COL_ACTIVE = 'active';
    public const string COL_IN_MENU = 'in_menu';
    public const string COL_SHOW_PARENTS = 'show_parents';
    public const string COL_SHOW_SAME_LEVEL = 'show_same_level';
    public const string COL_HEADER = 'header';
    public const string COL_SIDEBAR_RIGHT = 'sidebar_right';
    public const string COL_POSITION = 'position';
    public const string COL_TEMPLATE_FOLDER = 'template_folder';
}
