-- CVIMap 内核版数据表结构
-- 三张表：fans_groups（粉丝群）、events（活动）、settings（站点配置，k/v 两列）
-- 页面与接口只读取 status = 'approved' 的记录。

CREATE TABLE `fans_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qq_group` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `backup_qq_group` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `qq_owner` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `province` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `city` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `level` enum('province','city') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'province',
  `member_count` int unsigned NOT NULL DEFAULT 0,
  `activity_level` tinyint NOT NULL DEFAULT 4,
  `description` text COLLATE utf8mb4_unicode_ci,
  `public_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `review_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `lat` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `lng` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `proof_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reject_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_qq_group` (`qq_group`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('offline','online','birthday','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `province` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `city` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `description` text COLLATE utf8mb4_unicode_ci,
  `qq_group` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `backup_qq_group` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `cover_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `public_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `review_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `lat` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `lng` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reject_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `k` varchar(64) NOT NULL,
  `v` text,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
