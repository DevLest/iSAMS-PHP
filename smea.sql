-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for smea
CREATE DATABASE IF NOT EXISTS `smea` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `smea`;

-- Dumping structure for table smea.als
CREATE TABLE IF NOT EXISTS `als` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.als: ~0 rows (approximately)

-- Dumping structure for table smea.attendance_summary
CREATE TABLE IF NOT EXISTS `attendance_summary` (
  `summary_id` int NOT NULL AUTO_INCREMENT,
  `school_id` int DEFAULT NULL,
  `grade_level_id` int DEFAULT NULL,
  `gender` tinyint DEFAULT NULL COMMENT '1 - male, 2 - female',
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.attendance_summary: ~2 rows (approximately)
INSERT INTO `attendance_summary` (`summary_id`, `school_id`, `grade_level_id`, `gender`, `type`, `count`, `quarter`, `year`, `last_user_save`, `updated_at`, `created_at`) VALUES
	(10, 2, 1, 1, 'enrollment', 100, 4, 2024, 5, '2024-12-02 11:21:10', '2024-12-02 10:40:39'),
	(11, 2, 1, 1, 'enrollment', 80, 4, 2023, 5, '2024-12-02 11:21:13', '2024-12-02 10:40:39');

-- Dumping structure for table smea.edit_requests
CREATE TABLE IF NOT EXISTS `edit_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `grade_level` int NOT NULL,
  `gender` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `requested_by` int NOT NULL,
  `processed_by` int DEFAULT NULL,
  `status` enum('pending','approved','denied') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requested_by` (`requested_by`),
  KEY `processed_by` (`processed_by`),
  CONSTRAINT `edit_requests_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `edit_requests_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.edit_requests: ~0 rows (approximately)

-- Dumping structure for table smea.equity_assessment
CREATE TABLE IF NOT EXISTS `equity_assessment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `grade_level` int DEFAULT NULL,
  `gender` tinyint DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `count` int NOT NULL DEFAULT '0',
  `points` int DEFAULT NULL,
  `quarter` int NOT NULL,
  `year` int NOT NULL,
  `last_user_save` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `grade_level` (`grade_level`),
  KEY `last_user_save` (`last_user_save`),
  CONSTRAINT `equity_assessment_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `equity_assessment_ibfk_2` FOREIGN KEY (`grade_level`) REFERENCES `grade_level` (`id`),
  CONSTRAINT `equity_assessment_ibfk_3` FOREIGN KEY (`last_user_save`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.equity_assessment: ~5 rows (approximately)
INSERT INTO `equity_assessment` (`id`, `school_id`, `grade_level`, `gender`, `type`, `count`, `points`, `quarter`, `year`, `last_user_save`, `created_at`, `updated_at`) VALUES
	(1, 1, NULL, NULL, 'cfs', 23, 25, 4, 2024, 5, '2024-11-10 16:31:27', '2024-11-10 16:31:27'),
	(2, 2, 1, 1, 'sbfp', 23, NULL, 1, 2024, 5, '2024-11-10 16:31:36', '2024-11-10 16:31:36'),
	(3, 2, 1, 2, 'sbfp', 54, NULL, 1, 2024, 5, '2024-11-10 16:31:36', '2024-11-10 16:31:36'),
	(4, 1, NULL, NULL, 'cfs', 123, 25, 1, 2025, 5, '2024-11-10 16:31:53', '2024-11-10 16:31:53'),
	(5, 2, NULL, NULL, 'cfs', 21312, 35, 1, 2025, 5, '2024-11-10 16:31:53', '2024-11-10 16:31:53');

-- Dumping structure for table smea.grade_level
CREATE TABLE IF NOT EXISTS `grade_level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.grade_level: ~13 rows (approximately)
INSERT INTO `grade_level` (`id`, `name`, `type`, `updated_at`, `created_at`) VALUES
	(1, 'Kinder', 'elem', '2024-11-10 07:38:53', '2024-04-04 04:30:22'),
	(2, 'Grade 1', 'elem', '2024-11-10 07:38:55', '2024-04-04 04:30:22'),
	(3, 'Grade 2', 'elem', '2024-11-10 07:38:56', '2024-04-04 04:30:22'),
	(4, 'Grade 3', 'elem', '2024-11-10 07:38:55', '2024-04-04 04:30:22'),
	(5, 'Grade 4', 'elem', '2024-11-10 07:38:56', '2024-04-04 04:30:22'),
	(6, 'Grade 5', 'elem', '2024-11-10 07:38:57', '2024-04-04 04:30:22'),
	(7, 'Grade 6', 'elem', '2024-11-10 07:38:57', '2024-04-04 04:30:22'),
	(8, 'Grade 7', 'jhs', '2024-11-10 07:39:33', '2024-04-04 04:30:22'),
	(9, 'Grade 8', 'jhs', '2024-11-10 07:39:35', '2024-04-04 04:30:22'),
	(10, 'Grade 9', 'jhs', '2024-11-10 07:39:35', '2024-04-04 04:30:22'),
	(11, 'Grade 10', 'jhs', '2024-11-10 07:39:36', '2024-04-04 04:30:22'),
	(12, 'Grade 11', 'shs', '2024-11-10 07:39:40', '2024-04-04 04:30:22'),
	(13, 'Grade 12', 'shs', '2024-11-10 07:39:42', '2024-04-04 04:30:22');

-- Dumping structure for table smea.issues_and_concerns
CREATE TABLE IF NOT EXISTS `issues_and_concerns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `quarter` int NOT NULL,
  `year` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issues` text COLLATE utf8mb4_general_ci,
  `facilitating_facts` text COLLATE utf8mb4_general_ci,
  `hindering_factors` text COLLATE utf8mb4_general_ci,
  `actions_taken` text COLLATE utf8mb4_general_ci,
  `last_user_save` int NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_issues_and_concerns_schools` (`school_id`),
  CONSTRAINT `FK_issues_and_concerns_schools` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.issues_and_concerns: ~14 rows (approximately)
INSERT INTO `issues_and_concerns` (`id`, `school_id`, `quarter`, `year`, `type`, `issues`, `facilitating_facts`, `hindering_factors`, `actions_taken`, `last_user_save`, `updated_at`, `created_at`) VALUES
	(73, 2, 4, 2024, 'attendance', 'increasing number of severely wasted and wasted pupils', '', 'intake of unhealthy food due to pandemic', 'School canteen should offer food like arroz caldo with egg, camote, veggie burger, etc. Canteen should minimize and/or not offer junk foods, soft drinks and other unhealthy food for kids.', 5, '2024-11-10 08:47:10', '2024-10-07 05:24:36'),
	(75, 3, 4, 2024, 'attendance', 'Uncontrolled number of malnourished learners', 'Production of modules are equal with the number of learners', '*Economic status of family         *Some parents take for granted their child\'s health', 'Conduct advocacy on health and wellness to parents       *Involve parents to participate in school\'s activities/programs regarding health and wellness', 5, '2024-11-10 08:42:57', '2024-10-07 05:29:23'),
	(76, 2, 4, 2024, 'attendance', 'increasing number of severely wasted and wasted pupils', '', 'intake of unhealthy food due to pandemic', 'School canteen should offer food like arroz caldo with egg, camote, veggie burger, etc. Canteen should minimize and/or not offer junk foods, soft drinks and other unhealthy food for kids.', 5, '2024-11-10 08:42:56', '2024-10-07 05:36:38'),
	(77, 3, 4, 2024, 'attendance', 'Uncontrolled number of malnourished learners', 'Production of modules are equal with the number of learners', '*Economic status of family         *Some parents take for granted their child', 'Conduct advocacy on health and wellness to parents       *Involve parents to participate in school', 5, '2024-11-10 08:42:56', '2024-10-07 05:36:38'),
	(78, 2, 3, 2024, 'attendance', 'increasing number of severely wasted and wasted pupils', '', 'intake of unhealthy food due to pandemic', 'School canteen should offer food like arroz caldo with egg, camote, veggie burger, etc. Canteen should minimize and/or not offer junk foods, soft drinks and other unhealthy food for kids.', 5, '2024-11-10 08:42:55', '2024-10-08 12:54:57'),
	(79, 3, 3, 2024, 'attendance', 'Uncontrolled number of malnourished learners', 'Production of modules are equal with the number of learners', '*Economic status of family         *Some parents take for granted their child', 'Conduct advocacy on health and wellness to parents       *Involve parents to participate in school', 5, '2024-11-10 08:42:55', '2024-10-08 12:54:57'),
	(80, 8, 3, 2024, 'attendance', 'active and effective communication between the teachers, parents and stakeholder of the school.', 'parents notion regarding educating their pupils', 'poverty, parents and pupils participation', 'parent teacher orientation', 5, '2024-11-10 08:42:53', '2024-10-08 12:54:57'),
	(81, 9, 3, 2024, 'attendattendanceace', 'programs will implemented, effective communication between teachers and stakeholders', '', '', '', 5, '2024-11-10 08:42:54', '2024-10-08 12:54:57'),
	(82, 11, 3, 2024, 'attendance', 'active participation from the teachers and stakeholders on the different programs of the school. effective communication between the teachers and parents.', 'parents and pupils negative attitude towards education', 'poverty, pupils and parents participation', 'orientation/PTA meetings', 5, '2024-11-10 08:42:52', '2024-10-08 12:54:57'),
	(83, 12, 3, 2024, 'attendance', 'school coordinator and school are very active and well knowlegeable in implementing the program. teachers are actively helped facilitate and cooperate in doing their tasks. proper timeline in giving support and dessimination of information from division to school level.', 'pupils willingness to fully cooperate and engage in the program lack of technical support and assistance from partners school resources availabilty', 'lack of knowledge in conducting the program school resources availability pupils cooperation lack of support from stakeholders', 'coordinate from division and create a technical working commitee to ensure the smooth and effective implementation of the program. regular monitoring', 5, '2024-11-10 08:42:51', '2024-10-08 12:54:57'),
	(84, 14, 3, 2024, 'attendance', 'the school continue to practice resiliency and provide technical assistance for the safety of the learners', '', '', 'the school continue its program and implementation with appropriate schedule of time', 5, '2024-11-10 08:42:52', '2024-10-08 12:54:57'),
	(85, 16, 3, 2024, 'attendance', 'effective communication between teachers and stakeholders', 'the perspective of parents and students on education', 'poverty and the distance from home to school', 'inspire and encourage the parents to have confidence and positive mindset amidst adversities', 5, '2024-11-10 08:42:50', '2024-10-08 12:54:57'),
	(86, 17, 3, 2024, 'attendance', 'increased number of learners for SY 2023-2024. active participation of teachers and stakeholders', '', '', '', 5, '2024-11-10 08:42:50', '2024-10-08 12:54:57'),
	(87, 20, 3, 2024, 'attendance', 'school coordinator and school are very active and well knowlegeable in implementing the program. teachers are actively helped facilitate and cooperate in doing their tasks. proper timeline in giving support and dessimination of information from division to school level', 'students willingness to fully cooperate and engage in the program lack of technical support and assistance from partners school resources availability students cooperation lack of support from stakeholders', 'coordinate from division and create a technical working commitee to ensure the smooth and effective implementation of the program. regular monitoring', '', 5, '2024-11-10 08:42:49', '2024-10-08 12:54:57');

-- Dumping structure for table smea.quality_assessment
CREATE TABLE IF NOT EXISTS `quality_assessment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `grade_level` int NOT NULL,
  `gender` tinyint NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `count` int NOT NULL DEFAULT '0',
  `quarter` int NOT NULL,
  `year` int NOT NULL,
  `last_user_save` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `grade_level` (`grade_level`),
  KEY `last_user_save` (`last_user_save`),
  CONSTRAINT `quality_assessment_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `quality_assessment_ibfk_2` FOREIGN KEY (`grade_level`) REFERENCES `grade_level` (`id`),
  CONSTRAINT `quality_assessment_ibfk_3` FOREIGN KEY (`last_user_save`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.quality_assessment: ~13 rows (approximately)
INSERT INTO `quality_assessment` (`id`, `school_id`, `grade_level`, `gender`, `type`, `count`, `quarter`, `year`, `last_user_save`, `created_at`, `updated_at`) VALUES
	(2, 1, 1, 1, 'als', 23, 4, 2024, 5, '2024-11-10 13:18:47', '2024-11-10 13:18:47'),
	(3, 1, 2, 1, 'als', 3436, 4, 2024, 5, '2024-11-10 13:55:49', '2024-11-10 13:55:49'),
	(4, 1, 1, 1, 'eng-frustration', 11, 4, 2024, 5, '2024-11-10 14:20:08', '2024-11-10 14:20:08'),
	(5, 2, 1, 1, 'eng-frustration', 33, 4, 2024, 5, '2024-11-10 14:20:08', '2024-11-10 14:20:08'),
	(6, 3, 1, 1, 'eng-frustration', 55, 4, 2024, 5, '2024-11-10 14:20:08', '2024-11-10 14:20:08'),
	(7, 1, 1, 2, 'eng-frustration', 22, 4, 2024, 5, '2024-11-10 14:20:08', '2024-11-10 14:20:08'),
	(8, 2, 1, 2, 'eng-frustration', 44, 4, 2024, 5, '2024-11-10 14:20:08', '2024-11-10 14:20:08'),
	(9, 1, 1, 1, 'fil-frustration', 999, 4, 2024, 5, '2024-11-10 14:26:47', '2024-11-10 14:26:47'),
	(10, 2, 1, 1, 'fil-frustration', 333, 4, 2024, 5, '2024-11-10 14:26:47', '2024-11-10 14:26:47'),
	(11, 3, 1, 1, 'fil-frustration', 88, 4, 2024, 5, '2024-11-10 14:26:47', '2024-11-10 14:26:47'),
	(12, 1, 1, 2, 'fil-frustration', 222, 4, 2024, 5, '2024-11-10 14:26:47', '2024-11-10 14:26:47'),
	(13, 2, 1, 2, 'fil-frustration', 44, 4, 2024, 5, '2024-11-10 14:26:47', '2024-11-10 14:26:47'),
	(14, 4, 1, 2, 'fil-frustration', 78, 4, 2024, 5, '2024-11-10 14:26:47', '2024-11-10 14:26:47');

-- Dumping structure for table smea.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `permission` text COLLATE utf8mb4_general_ci NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.roles: ~2 rows (approximately)
INSERT INTO `roles` (`id`, `description`, `permission`, `updated_at`, `created_at`) VALUES
	(1, 'Admin', '[\'1\',\'2\']', '2024-03-19 03:43:00', '2024-03-18 10:58:50'),
	(2, 'Teacher', '[\'1\', \'2\']', '2024-03-18 10:59:09', '2024-03-18 10:59:09');

-- Dumping structure for table smea.rwb_assessment
CREATE TABLE IF NOT EXISTS `rwb_assessment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `grade_level` int NOT NULL,
  `gender` tinyint NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `count` int NOT NULL DEFAULT '0',
  `quarter` int NOT NULL,
  `year` int NOT NULL,
  `last_user_save` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `grade_level` (`grade_level`),
  KEY `last_user_save` (`last_user_save`),
  CONSTRAINT `rwb_assessment_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `rwb_assessment_ibfk_2` FOREIGN KEY (`grade_level`) REFERENCES `grade_level` (`id`),
  CONSTRAINT `rwb_assessment_ibfk_3` FOREIGN KEY (`last_user_save`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.rwb_assessment: ~1 rows (approximately)
INSERT INTO `rwb_assessment` (`id`, `school_id`, `grade_level`, `gender`, `type`, `count`, `quarter`, `year`, `last_user_save`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 1, 'displaced', 505, 4, 2024, 5, '2024-11-10 16:41:41', '2024-11-10 16:41:41');

-- Dumping structure for table smea.schools
CREATE TABLE IF NOT EXISTS `schools` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_general_ci NOT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.schools: ~20 rows (approximately)
INSERT INTO `schools` (`id`, `name`, `address`, `updated_at`, `created_at`) VALUES
	(1, 'Amontay Elementary School', 'Brgy. Amontay, Binalbagan, Negros Occidental', '2024-05-27 09:02:04', '2024-04-04 05:24:26'),
	(2, 'Binalbagan Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(3, 'Binalbagan South Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(4, 'Canmoros Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(5, 'Don A.y. Locsin Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(6, 'Don Santiago Lazarte Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(7, 'DoÃ±a Concepcion Y. Yusay Memorial School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
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
	(19, 'Augurio MaraÃ±on Abeto National High School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
	(20, 'Binalbagan National High School-santol Extension', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26');

-- Dumping structure for table smea.school_year
CREATE TABLE IF NOT EXISTS `school_year` (
  `id` int NOT NULL AUTO_INCREMENT,
  `start_month` int NOT NULL DEFAULT '0',
  `start_year` int NOT NULL DEFAULT '0',
  `end_month` int NOT NULL DEFAULT '0',
  `end_year` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.school_year: ~4 rows (approximately)
INSERT INTO `school_year` (`id`, `start_month`, `start_year`, `end_month`, `end_year`, `updated_at`, `created_at`) VALUES
	(3, 8, 2021, 7, 2022, '2024-09-10 23:19:14', '2024-09-10 23:19:14'),
	(4, 8, 2022, 7, 2023, '2024-09-10 23:19:19', '2024-09-10 23:18:26'),
	(5, 8, 2023, 7, 2024, '2024-09-10 23:19:42', '2024-05-28 08:44:50'),
	(6, 8, 2024, 7, 2025, '2024-09-10 23:19:47', '2024-05-28 08:46:07');

-- Dumping structure for table smea.subjects
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `current_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.subjects: ~9 rows (approximately)
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

-- Dumping structure for table smea.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` text COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` text COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` text COLLATE utf8mb4_general_ci NOT NULL,
  `role` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `school_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_users_roles` (`role`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `FK_users_roles` FOREIGN KEY (`role`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table smea.users: ~2 rows (approximately)
INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `role`, `updated_at`, `created_at`, `school_id`) VALUES
	(5, 'bon', '5f4dcc3b5aa765d61d8327deb882cf99', 'Frederson', 'Ebra', 1, '2024-03-18 10:59:36', '2024-09-23 09:15:58', NULL),
	(6, 'bea', '5f4dcc3b5aa765d61d8327deb882cf99', 'Bea', 'Sasi', 2, '2024-03-27 08:31:14', '2024-11-20 07:14:06', 19);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
