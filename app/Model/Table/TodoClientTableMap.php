<?php

declare(strict_types=1);

namespace App\Model\Table;

class TodoClientTableMap extends BaseTableMap
{
    public const string TABLE_NAME = 'sn_todo_client';
    public const string TABLE_PREFIX = 'todo_client_mapper';

    public const string COL_ID = 'id';
    public const string COL_FAMILY_ID = 'family_id';
    public const string COL_BABYSITTER_ID = 'babysitter_id';
    public const string COL_TODO_FROM_USER = 'todo_from_user';
    public const string COL_TODO_TO_USER_1 = 'todo_to_user_1';
    public const string COL_TODO_TO_USER_2 = 'todo_to_user_2';
    public const string COL_TODO_CREATED = 'todo_created';
    public const string COL_TODO_DEADLINE = 'todo_deadline';
    public const string COL_TITLE = 'title';
    public const string COL_DESCRIPTION = 'description';
    public const string COL_ANSWER = 'answer';
    public const string COL_STATUS = 'status';
}
