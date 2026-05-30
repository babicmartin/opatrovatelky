<?php declare(strict_types=1);

namespace App\Model\Table;

class BabysitterVideoTableMap extends BaseTableMap
{
	public const string TABLE_NAME = 'sn_babysitter_videos';
	public const string TABLE_PREFIX = 'babysitter_video_mapper';

	public const string COL_ID = 'id';
	public const string COL_BABYSITTER_ID = 'babysitter_id';
	public const string COL_ORIGINAL_NAME = 'original_name';
	public const string COL_STORED_NAME = 'stored_name';
	public const string COL_EXTENSION = 'extension';
	public const string COL_MIME_TYPE = 'mime_type';
	public const string COL_SIZE_BYTES = 'size_bytes';
	public const string COL_DURATION_SECONDS = 'duration_seconds';
	public const string COL_CHECKSUM_SHA256 = 'checksum_sha256';
	public const string COL_UPLOADED_BY_USER_ID = 'uploaded_by_user_id';
	public const string COL_UPLOADED_AT = 'uploaded_at';
	public const string COL_DELETED_BY_USER_ID = 'deleted_by_user_id';
	public const string COL_DELETED_AT = 'deleted_at';
	public const string COL_ACTIVE = 'active';
}
