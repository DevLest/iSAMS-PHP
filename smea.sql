-- Dumping database structure for isams
CREATE DATABASE IF NOT EXISTS `smea`;
USE `smea`;

-- Dumping structure for table isams.als
CREATE TABLE IF NOT EXISTS `als` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table isams.als: ~0 rows (approximately)

-- Dumping structure for table isams.attendance_summary
CREATE TABLE IF NOT EXISTS `attendance_summary` (
  `summary_id` int NOT NULL AUTO_INCREMENT,
  `school_id` int DEFAULT NULL,
  `grade_level_id` int DEFAULT NULL,
  `gender` tinyint DEFAULT NULL COMMENT '1 - male, 2 - female',
  `type` varchar(50) DEFAULT NULL,
  `count` int DEFAULT NULL,
  `quarter` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `last_user_save` int NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`summary_id`),
  KEY `type` (`type`) USING BTREE,
  KEY `quarter` (`quarter`) USING BTREE,
  KEY `year` (`year`) USING BTREE,
  KEY `school_id` (`school_id`) USING BTREE,
  KEY `grade_level_id` (`grade_level_id`) USING BTREE,
  KEY `FK_attendance_summary_users` (`last_user_save`),
  CONSTRAINT `FK_attendance_summary_grade_level` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`id`),
  CONSTRAINT `FK_attendance_summary_schools` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `FK_attendance_summary_users` FOREIGN KEY (`last_user_save`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

-- Dumping structure for table isams.grade_level
CREATE TABLE IF NOT EXISTS `grade_level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table isams.grade_level: ~12 rows (approximately)
INSERT INTO `grade_level` (`id`, `name`, `updated_at`, `created_at`) VALUES
	(1, 'Kinder', '2024-04-04 04:30:22', '2024-04-04 04:30:22'),
	(2, 'Grade 1', '2024-04-04 04:30:22', '2024-04-04 04:30:22'),
	(3, 'Grade 2', '2024-04-04 04:30:22', '2024-04-04 04:30:22'),
	(4, 'Grade 3', '2024-04-04 04:30:22', '2024-04-04 04:30:22'),
	(5, 'Grade 4', '2024-04-04 04:30:22', '2024-04-04 04:30:22'),
	(6, 'Grade 5', '2024-04-04 04:30:22', '2024-04-04 04:30:22'),
	(7, 'Grade 6', '2024-04-04 04:30:22', '2024-04-04 04:30:22'),
	(8, 'Grade 7', '2024-04-04 05:52:57', '2024-04-04 04:30:22'),
	(9, 'Grade 8', '2024-04-04 05:52:59', '2024-04-04 04:30:22'),
	(10, 'Grade 9', '2024-04-04 05:53:12', '2024-04-04 04:30:22'),
	(11, 'Grade 10', '2024-04-04 05:53:15', '2024-04-04 04:30:22'),
	(12, 'Grade 11', '2024-04-04 05:53:18', '2024-04-04 04:30:22'),
	(13, 'Grade 12', '2024-04-04 05:53:18', '2024-04-04 04:30:22');

-- Dumping structure for table isams.quarters
CREATE TABLE IF NOT EXISTS `quarters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table isams.quarters: ~0 rows (approximately)

-- Dumping structure for table isams.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(50) NOT NULL DEFAULT '0',
  `permission` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table isams.roles: ~2 rows (approximately)
INSERT INTO `roles` (`id`, `description`, `permission`, `updated_at`, `created_at`) VALUES
	(1, 'Admin', '[\'1\',\'2\']', '2024-03-19 03:43:00', '2024-03-18 10:58:50'),
	(2, 'Teacher', '[\'1\', \'2\']', '2024-03-18 10:59:09', '2024-03-18 10:59:09');

-- Dumping structure for table isams.schools
CREATE TABLE IF NOT EXISTS `schools` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `address` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table isams.schools: ~20 rows (approximately)
INSERT INTO `schools` (`id`, `name`, `address`, `updated_at`, `created_at`) VALUES
	(1, 'Amontay Elementary School', 'Brgy. Amontay, Binalbagan, Negros Occidental', '2024-05-27 09:02:04', '2024-04-04 05:24:26'),
	(2, 'Binalbagan Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(3, 'Binalbagan South Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(4, 'Canmoros Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(5, 'Don A.y. Locsin Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(6, 'Don Santiago Lazarte Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(7, 'Doña Concepcion Y. Yusay Memorial School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(8, 'Mabunga Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(9, 'Marina Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(10, 'Nabu-ac Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(11, 'Nabuswang Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(12, 'Pedro Y. Ditching, Sr. - Bagroy Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(13, 'Porferio S. Porillo Sr. Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(14, 'Progreso Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(15, 'Santol Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(16, 'Tambu Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(17, 'Torres Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(18, 'Binalbagan National High School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(19, 'Augurio Marañon Abeto National High School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(20, 'Binalbagan National High School-santol Extension', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26');

-- Dumping structure for table isams.subjects
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `current_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table isams.subjects: ~8 rows (approximately)
INSERT INTO `subjects` (`id`, `name`, `updated_at`, `current_timestamp`) VALUES
	(1, 'Math', '2024-05-23 12:32:57', '2024-05-23 12:32:57'),
	(2, 'Filipino', '2024-05-23 12:33:04', '2024-05-23 12:33:04'),
	(3, 'English', '2024-05-23 12:33:17', '2024-05-23 12:33:17'),
	(4, 'A.P.', '2024-05-23 12:33:34', '2024-05-23 12:33:20'),
	(5, 'E.S.P.', '2024-05-23 12:33:30', '2024-05-23 12:33:30'),
	(6, 'MTB-MLE', '2024-05-23 12:33:44', '2024-05-23 12:33:44'),
	(7, 'MAPEH', '2024-05-23 12:33:55', '2024-05-23 12:33:55'),
	(8, 'EPP', '2024-05-23 12:34:00', '2024-05-23 12:34:00'),
	(9, 'Science', '2024-05-23 12:34:05', '2024-05-23 12:34:05');

-- Dumping structure for table isams.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` text NOT NULL,
  `first_name` text NOT NULL,
  `last_name` text NOT NULL,
  `role` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_users_roles` (`role`),
  CONSTRAINT `FK_users_roles` FOREIGN KEY (`role`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table isams.users: ~0 rows (approximately)
INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `role`, `updated_at`, `created_at`) VALUES
	(1, 'bon', '5f4dcc3b5aa765d61d8327deb882cf99', 'Frederson', 'Ebra', 1, '2024-03-18 10:59:36', '2024-05-27 08:46:29'),
	(2, 'bea', '5f4dcc3b5aa765d61d8327deb882cf99', 'Bea', 'Sasi', 2, '2024-03-27 08:31:14', '2024-05-27 08:46:18');
	(3, 'carlo', '5f4dcc3b5aa765d61d8327deb882cf99', 'Carlo', 'Carlo', 2, '2024-03-27 08:31:14', '2024-05-27 08:46:18');