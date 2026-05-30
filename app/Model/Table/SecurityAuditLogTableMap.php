<?php declare(strict_types=1);

namespace App\Model\Table;

final class SecurityAuditLogTableMap extends BaseTableMap
{
	public const string TABLE_NAME = 'security_audit_log';

	public const string COL_ID = 'id';
	public const string COL_USER_ID = 'user_id';
	public const string COL_EVENT_TYPE = 'event_type';
	public const string COL_EMAIL = 'email';
	public const string COL_IP_ADDRESS = 'ip_address';
	public const string COL_USER_AGENT = 'user_agent';
	public const string COL_METADATA = 'metadata';
	public const string COL_CREATED_AT = 'created_at';
}
