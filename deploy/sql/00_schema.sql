
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AuthAssignment` (
  `itemname` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `userid` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bizrule` mediumtext COLLATE utf8mb4_unicode_ci,
  `data` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`itemname`,`userid`),
  CONSTRAINT `AuthAssignment_ibfk_1` FOREIGN KEY (`itemname`) REFERENCES `AuthItem` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AuthItem` (
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` int NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `bizrule` mediumtext COLLATE utf8mb4_unicode_ci,
  `data` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AuthItemChild` (
  `parent` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `child` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`parent`,`child`),
  KEY `child` (`child`),
  CONSTRAINT `AuthItemChild_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `AuthItem` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `AuthItemChild_ibfk_2` FOREIGN KEY (`child`) REFERENCES `AuthItem` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Rights` (
  `itemname` varchar(64) NOT NULL,
  `type` int NOT NULL,
  `weight` int NOT NULL,
  PRIMARY KEY (`itemname`),
  CONSTRAINT `Rights_ibfk_1` FOREIGN KEY (`itemname`) REFERENCES `AuthItem` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_course` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `lesson_time` time NOT NULL,
  `venue` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_course_attendance` (
  `course_id` int NOT NULL,
  `member_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `lesson_number` int NOT NULL,
  `state` tinyint NOT NULL,
  PRIMARY KEY (`course_id`,`member_id`,`attendance_date`),
  KEY `course_id` (`course_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `tbl_course_attendance_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `tbl_course` (`id`),
  CONSTRAINT `tbl_course_attendance_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `tbl_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_course_member` (
  `course_id` int NOT NULL,
  `member_id` int NOT NULL,
  `remarks` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`course_id`,`member_id`),
  KEY `course_id` (`course_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `tbl_course_member_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `tbl_course` (`id`),
  CONSTRAINT `tbl_course_member_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `tbl_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `period_id` (`period_id`),
  CONSTRAINT `tbl_group_ibfk_1` FOREIGN KEY (`period_id`) REFERENCES `tbl_group_period` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_group_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `member_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `tbl_group_attendance_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `tbl_group` (`id`),
  CONSTRAINT `tbl_group_attendance_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `tbl_member` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67605 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_group_member` (
  `group_id` int NOT NULL,
  `member_id` int NOT NULL,
  `member_type` tinyint NOT NULL,
  PRIMARY KEY (`group_id`,`member_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `tbl_group_member_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `tbl_group` (`id`),
  CONSTRAINT `tbl_group_member_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `tbl_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_group_period` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_hymn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` tinyint NOT NULL,
  `language` tinyint NOT NULL,
  `lyric` mediumtext COLLATE utf8mb4_unicode_ci,
  `composer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lyricist` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `producer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guitar_notation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `midi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `powerpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1094 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_hymn_tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hymn_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hymn_id` (`hymn_id`),
  CONSTRAINT `tbl_hymn_tags_ibfk_1` FOREIGN KEY (`hymn_id`) REFERENCES `tbl_hymn` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_issues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL,
  `priority` int NOT NULL,
  `creator` int NOT NULL,
  `modify_date` datetime NOT NULL,
  `create_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_issues_reply` (
  `id` int NOT NULL AUTO_INCREMENT,
  `issues_id` int NOT NULL,
  `reply` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `creator` int NOT NULL,
  `create_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `issues_id` (`issues_id`),
  CONSTRAINT `tbl_issues_reply_ibfk_1` FOREIGN KEY (`issues_id`) REFERENCES `tbl_issues` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_member` (
  `id` int NOT NULL AUTO_INCREMENT,
  `state` tinyint NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '會友編號',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` mediumtext COLLATE utf8mb4_unicode_ci,
  `english_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` tinyint NOT NULL COMMENT 'FEMALE = 1; MALE = 2; UNKNOWN = 3;',
  `birthday` date DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `believe` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `believe_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baptized` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baptized_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` int NOT NULL COMMENT '0: Temp. member, 1: Member, 2: Passed away',
  `new_card` int NOT NULL,
  `arrived_date` date NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modify_date` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  `creator_id` int NOT NULL,
  `modifier_id` int NOT NULL,
  `address_district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_estate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_house` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_flat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_home` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_mobile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_office` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_others` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15619 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_member_relationship` (
  `id` int NOT NULL AUTO_INCREMENT,
  `relationship` varchar(255) NOT NULL,
  `related1_id` int NOT NULL,
  `related2_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `related1_id` (`related1_id`),
  KEY `related2_id` (`related2_id`),
  CONSTRAINT `tbl_member_relationship_ibfk_1` FOREIGN KEY (`related1_id`) REFERENCES `tbl_member` (`id`),
  CONSTRAINT `tbl_member_relationship_ibfk_2` FOREIGN KEY (`related2_id`) REFERENCES `tbl_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_pledge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_pledge_member` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pledge_id` int NOT NULL,
  `member_id` int NOT NULL,
  `state` tinyint NOT NULL,
  `remarks` mediumtext COLLATE utf8mb4_unicode_ci,
  `pledge_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pledge_id` (`pledge_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `tbl_pledge_member_ibfk_1` FOREIGN KEY (`pledge_id`) REFERENCES `tbl_pledge` (`id`),
  CONSTRAINT `tbl_pledge_member_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `tbl_member` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_summer_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `activity_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `person_in_charge` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_summer_activity_participant` (
  `activity_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age` int NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  KEY `activity_id` (`activity_id`),
  CONSTRAINT `tbl_summer_activity_participant_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `tbl_summer_activity` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `email` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `member_code` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1000002 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_worship` (
  `id` int NOT NULL AUTO_INCREMENT,
  `state` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `weekly` int NOT NULL,
  `remarks` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_worship_attendance` (
  `worship_id` int NOT NULL,
  `member_id` int NOT NULL,
  `attendance_date` datetime NOT NULL,
  PRIMARY KEY (`worship_id`,`member_id`,`attendance_date`),
  KEY `worship_id` (`worship_id`),
  KEY `member_id` (`member_id`),
  KEY `idx_attendance_date` (`attendance_date`),
  CONSTRAINT `tbl_worship_attendance_ibfk_1` FOREIGN KEY (`worship_id`) REFERENCES `tbl_worship` (`id`),
  CONSTRAINT `tbl_worship_attendance_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `tbl_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_worship_greeting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `message` text NOT NULL,
  `expiry_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `tbl_worship_greeting_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `tbl_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_worship_remarks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `worship_id` int NOT NULL,
  `worship_date` date NOT NULL,
  `remarks` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `worship_id` (`worship_id`),
  CONSTRAINT `tbl_worship_remarks_ibfk_1` FOREIGN KEY (`worship_id`) REFERENCES `tbl_worship` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

