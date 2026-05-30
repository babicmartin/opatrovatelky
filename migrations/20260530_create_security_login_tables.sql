CREATE TABLE IF NOT EXISTS `security_login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(190) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `failure_reason` VARCHAR(80) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_security_login_attempts_email_created_at` (`email`, `created_at`),
  KEY `idx_security_login_attempts_ip_created_at` (`ip_address`, `created_at`),
  KEY `idx_security_login_attempts_success_created_at` (`success`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `security_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED DEFAULT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `email` VARCHAR(190) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(512) DEFAULT NULL,
  `metadata` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_security_audit_log_user_created_at` (`user_id`, `created_at`),
  KEY `idx_security_audit_log_event_created_at` (`event_type`, `created_at`),
  KEY `idx_security_audit_log_email_created_at` (`email`, `created_at`),
  CONSTRAINT `chk_security_audit_log_metadata_json`
    CHECK (`metadata` IS NULL OR JSON_VALID(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
