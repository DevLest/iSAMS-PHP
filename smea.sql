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
CREATE DATABASE IF NOT EXISTS `smea` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `smea`;

-- Dumping structure for table smea.schools
CREATE TABLE IF NOT EXISTS `schools` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `address` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table smea.schools: ~20 rows (approximately)
INSERT INTO `schools` (`id`, `name`, `address`, `updated_at`, `created_at`) VALUES
	(1, 'Amontay Elementary School', NULL, '2024-04-04 05:24:26', '2024-04-04 05:24:26'),
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
  
-- Dumping structure for table smea.grade_level
CREATE TABLE IF NOT EXISTS `grade_level` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table smea.grade_level: ~13 rows (approximately)
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

-- Dumping structure for table smea.attendance_summary
CREATE TABLE IF NOT EXISTS `attendance_summary` (
  `summary_id` int NOT NULL AUTO_INCREMENT,
  `school_id` int DEFAULT NULL,
  `grade_level_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `gender` tinyint DEFAULT NULL COMMENT '1 - male, 2 - female',
  `type` varchar(50) DEFAULT NULL,
  `count` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`summary_id`),
  KEY `FK_attendance_summary_schools` (`school_id`),
  KEY `FK_attendance_summary_grade_level` (`grade_level_id`),
  CONSTRAINT `FK_attendance_summary_grade_level` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`id`),
  CONSTRAINT `FK_attendance_summary_schools` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table smea.attendance_summary: ~0 rows (approximately)

-- Dumping structure for table smea.quarters
CREATE TABLE IF NOT EXISTS `quarters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table smea.quarters: ~0 rows (approximately)

-- Dumping structure for table smea.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(50) NOT NULL DEFAULT '0',
  `permission` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table smea.roles: ~2 rows (approximately)
INSERT INTO `roles` (`id`, `description`, `permission`, `updated_at`, `created_at`) VALUES
	(1, 'Admin', '[\'1\',\'2\']', '2024-03-19 03:43:00', '2024-03-18 10:58:50'),
	(2, 'Teacher', '[\'1\', \'2\']', '2024-03-18 10:59:09', '2024-03-18 10:59:09');

-- Dumping structure for table smea.subjects
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `current_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table smea.subjects: ~0 rows (approximately)

-- Dumping structure for table smea.users
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

-- Dumping data for table smea.users: ~2 rows (approximately)
INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `role`, `updated_at`, `created_at`) VALUES
	(5, 'bonbon', '5f4dcc3b5aa765d61d8327deb882cf99', 'Frederson', 'Ebra', 1, '2024-03-18 10:59:36', '2024-03-18 10:59:36'),
	(6, 'bea', '25d55ad283aa400af464c76d713c07ad', 'Bea', 'Sasi', 2, '2024-03-27 08:31:14', '2024-03-27 08:31:14');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
