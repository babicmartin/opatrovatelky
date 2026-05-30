SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO `sn_change_log` (
  `context`,
  `entity_table`,
  `entity_id`,
  `field_name`,
  `field_label`,
  `column_name`,
  `value_type`,
  `old_value_id`,
  `old_value_label`,
  `new_value_id`,
  `new_value_label`,
  `user_id`,
  `created_at`,
  `metadata`
)
SELECT
  'babysitter.video',
  'sn_opatrovatelky',
  v.`babysitter_id`,
  'video',
  'Video',
  NULL,
  'file',
  NULL,
  NULL,
  CAST(v.`id` AS CHAR),
  v.`original_name`,
  v.`uploaded_by_user_id`,
  v.`uploaded_at`,
  JSON_OBJECT(
    'action', 'uploaded',
    'video_id', v.`id`,
    'video_table', 'sn_babysitter_videos',
    'original_name', v.`original_name`,
    'stored_name', v.`stored_name`,
    'extension', v.`extension`,
    'mime_type', v.`mime_type`,
    'size_bytes', v.`size_bytes`,
    'duration_seconds', v.`duration_seconds`,
    'checksum_sha256', v.`checksum_sha256`
  )
FROM `sn_babysitter_videos` v
WHERE NOT EXISTS (
    SELECT 1
    FROM `sn_change_log` c
    WHERE c.`context` = 'babysitter.video' COLLATE utf8mb4_general_ci
      AND c.`field_name` = 'video' COLLATE utf8mb4_general_ci
      AND c.`value_type` = 'file' COLLATE utf8mb4_general_ci
      AND c.`new_value_id` = CAST(v.`id` AS CHAR) COLLATE utf8mb4_general_ci
      AND JSON_UNQUOTE(JSON_EXTRACT(c.`metadata`, '$.action')) COLLATE utf8mb4_general_ci = 'uploaded' COLLATE utf8mb4_general_ci
  );
