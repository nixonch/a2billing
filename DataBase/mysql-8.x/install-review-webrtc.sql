-- Optional personal review_webrtc installation; run separately from install-db.sh.
-- Usage from DataBase/mysql-8.x/:
-- mysql --default-character-set=utf8 -u root -p mya2billing < install-review-webrtc.sql

-- Application-level rate-limit state for MySQL 5.5 and MySQL 8.x.
-- Deploy this table before the revised review_webrtc.php.

CREATE TABLE IF NOT EXISTS `cc_rate_limit` (
  `scope` varchar(40) NOT NULL,
  `key_hash` char(64) NOT NULL,
  `window_start` int unsigned NOT NULL,
  `hits` int unsigned NOT NULL DEFAULT 0,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`scope`, `key_hash`),
  KEY `idx_rate_limit_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

CREATE TABLE IF NOT EXISTS `cc_feedback` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `country` varchar(40) COLLATE utf8_bin NOT NULL DEFAULT '',
  `city` char(40) COLLATE utf8_bin NOT NULL DEFAULT '',
  `timezone` char(40) NOT NULL DEFAULT '',
  `letter` text COLLATE utf8_bin NOT NULL,
  `email` varchar(80) COLLATE utf8_bin NOT NULL DEFAULT '',
  `reply` smallint(6) NOT NULL DEFAULT 1,
  `notify` smallint(6) NOT NULL DEFAULT 1,
  `fromip` varchar(45) COLLATE utf8_bin NOT NULL DEFAULT '',
  `operation` varchar(17) COLLATE utf8_bin NOT NULL DEFAULT '',
  `verprev` varchar(40) COLLATE utf8_bin NOT NULL DEFAULT '',
  `vercurr` varchar(40) COLLATE utf8_bin NOT NULL DEFAULT '',
  `fromagent` varchar(120) COLLATE utf8_bin NOT NULL DEFAULT '',
  `uuid` varchar(70) COLLATE utf8_bin NOT NULL DEFAULT '',
  `cookie` varchar(70) COLLATE utf8_bin NOT NULL DEFAULT '',
  `extension` varchar(80) COLLATE utf8_bin NOT NULL DEFAULT '',
  `apilink` varchar(120) COLLATE utf8_bin NOT NULL DEFAULT '',
  `messtext` varchar(120) COLLATE utf8_bin NOT NULL DEFAULT '',
  `createtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatetime` timestamp NULL DEFAULT NULL,
  `removetime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_uuid_id` (`uuid`, `id`),
  KEY `idx_feedback_uuid_event` (`uuid`, `operation`, `verprev`, `vercurr`, `createtime`),
  KEY `idx_feedback_cookie_id` (`cookie`, `id`),
  KEY `idx_feedback_cookie_operation_id` (`cookie`, `operation`, `id`),
  KEY `idx_feedback_fromip_createtime` (`fromip`, `createtime`),
  KEY `idx_feedback_fromip_updatetime` (`fromip`, `updatetime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
