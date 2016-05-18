CREATE TABLE IF NOT EXISTS `nc_taskslaunch` (
  `tala_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tala_token` varchar(40) NOT NULL,
  `tala_task` varchar(255) NOT NULL,
  `tala_pid` int(10) unsigned NOT NULL,
  `tala_status` enum('RUNNING','SUCCESS','NOTHING_TO_DO','PHP_FATAL','USER_ERROR','TASK_ERROR','SKIPPED') NOT NULL,
  `tala_launch_from` varchar(255) NOT NULL,
  `tala_exclusive` tinyint(4) NOT NULL DEFAULT '0',
  `tala_message` varchar(255) NOT NULL DEFAULT '',
  `tala_no_error` tinyint(4) NOT NULL DEFAULT '1',
  `tala_no_output` tinyint(4) NOT NULL DEFAULT '1',
  `tala_infos` mediumtext NOT NULL,
  `tala_created_at` datetime NOT NULL,
  `tala_updated_at` datetime NOT NULL,
  PRIMARY KEY (`tala_id`),
  UNIQUE KEY `tala_token` (`tala_token`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;