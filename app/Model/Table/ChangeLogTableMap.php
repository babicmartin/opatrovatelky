<?php declare(strict_types=1);

namespace App\Model\Table;

class ChangeLogTableMap extends BaseTableMap
{
	public const string TABLE_NAME = 'sn_change_log';

	public const string COL_ID = 'id';
	public const string COL_CONTEXT = 'context';
	public const string COL_ENTITY_TABLE = 'entity_table';
	public const string COL_ENTITY_ID = 'entity_id';
	public const string COL_FIELD_NAME = 'field_name';
	public const string COL_FIELD_LABEL = 'field_label';
	public const string COL_COLUMN_NAME = 'column_name';
	public const string COL_VALUE_TYPE = 'value_type';
	public const string COL_OLD_VALUE_ID = 'old_value_id';
	public const string COL_OLD_VALUE_LABEL = 'old_value_label';
	public const string COL_NEW_VALUE_ID = 'new_value_id';
	public const string COL_NEW_VALUE_LABEL = 'new_value_label';
	public const string COL_USER_ID = 'user_id';
	public const string COL_CREATED_AT = 'created_at';
	public const string COL_METADATA = 'metadata';
}
