<?php declare(strict_types=1);

namespace App\Model\Table;

final class SecurityLoginAttemptTableMap extends BaseTableMap
{
	public const string TABLE_NAME = 'security_login_attempts';

	public const string COL_ID = 'id';
	public const string COL_EMAIL = 'email';
	public const string COL_IP_ADDRESS = 'ip_address';
	public const string COL_SUCCESS = 'success';
	public const string COL_FAILURE_REASON = 'failure_reason';
	public const string COL_CREATED_AT = 'created_at';
}
