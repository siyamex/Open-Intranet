-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: openintranet
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` varchar(64) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_audit_user` (`user_id`),
  KEY `idx_audit_created` (`created_at`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,NULL,'auth.login_failed','user',NULL,'{\"email\":\"admin@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:04'),(2,2,'auth.login','user','2',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:04'),(3,2,'auth.login','user','2',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:46'),(4,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:46'),(5,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:46'),(6,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:46'),(7,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:46'),(8,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:47'),(9,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-09 23:59:47'),(10,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim2@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-10 00:01:31'),(11,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim2@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-10 00:01:32'),(12,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim2@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-10 00:01:32'),(13,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim2@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-10 00:01:32'),(14,NULL,'auth.login_failed','user',NULL,'{\"email\":\"victim2@example.com\"}','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-10 00:01:32'),(15,NULL,'auth.password_reset_requested','user','2',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-10 00:01:34'),(16,2,'auth.password_reset','user','2',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-10 00:01:50'),(17,2,'auth.login','user','2',NULL,'::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8655','2026-07-10 00:01:51'),(18,2,'auth.login','user','2',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-10 00:02:40'),(19,2,'auth.logout','user','2',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-10 00:11:12'),(20,2,'auth.login','user','2',NULL,'::1','curl/8.19.0','2026-07-10 00:11:58'),(21,2,'auth.login','user','2',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-10 00:12:15'),(22,2,'auth.login','user','2',NULL,'::1','curl/8.19.0','2026-07-10 00:18:50'),(23,2,'user.created','user','3','{\"email\":\"eva@example.com\",\"invited\":false}','::1','curl/8.19.0','2026-07-10 00:18:50'),(24,2,'user.bulk_imported','user',NULL,'{\"created\":2}','::1','curl/8.19.0','2026-07-10 00:19:12'),(25,3,'auth.login','user','3',NULL,'::1','curl/8.19.0','2026-07-10 00:19:48'),(26,2,'user.impersonation_started','user','3',NULL,'::1','curl/8.19.0','2026-07-10 00:20:02'),(27,2,'user.impersonation_ended','user','3',NULL,'::1','curl/8.19.0','2026-07-10 00:20:02'),(28,2,'user.impersonation_started','user','3',NULL,'::1','curl/8.19.0','2026-07-10 00:20:18'),(29,2,'user.impersonation_ended','user','3',NULL,'::1','curl/8.19.0','2026-07-10 00:20:18'),(30,2,'auth.login','user','2',NULL,'::1','curl/8.19.0','2026-07-10 07:19:04'),(31,2,'menu.created','menu_item','7','{\"label\":\"Test Item\"}','::1','curl/8.19.0','2026-07-10 07:19:33'),(32,2,'menu.reordered','menu_item',NULL,NULL,'::1','curl/8.19.0','2026-07-10 07:19:34'),(33,2,'menu.deleted','menu_item','7','{\"label\":\"Test Item\"}','::1','curl/8.19.0','2026-07-10 07:19:34'),(34,2,'menu.created','menu_item','8','{\"label\":\"Nest Test\"}','::1','curl/8.19.0','2026-07-10 07:20:06'),(35,2,'menu.reordered','menu_item',NULL,NULL,'::1','curl/8.19.0','2026-07-10 07:20:06'),(36,2,'menu.deleted','menu_item','8','{\"label\":\"Nest Test\"}','::1','curl/8.19.0','2026-07-10 07:20:06'),(37,2,'menu.created','menu_item','9','{\"label\":\"Nest Test2\"}','::1','curl/8.19.0','2026-07-10 07:20:33'),(38,2,'menu.reordered','menu_item',NULL,NULL,'::1','curl/8.19.0','2026-07-10 07:20:33'),(39,2,'menu.deleted','menu_item','9','{\"label\":\"Nest Test2\"}','::1','curl/8.19.0','2026-07-10 07:20:33'),(40,2,'news.created','news','4','{\"title\":\"Sanitizer Test Post\",\"status\":\"published\"}','::1','curl/8.19.0','2026-07-10 07:33:15'),(41,2,'news.created','news','5','{\"title\":\"Scheduled Post\",\"status\":\"scheduled\"}','::1','curl/8.19.0','2026-07-10 07:33:37'),(42,2,'news.pin_toggled','news','1',NULL,'::1','curl/8.19.0','2026-07-10 07:33:38'),(43,2,'news.pin_toggled','news','2',NULL,'::1','curl/8.19.0','2026-07-10 07:33:38'),(44,2,'news.pin_toggled','news','3',NULL,'::1','curl/8.19.0','2026-07-10 07:33:39'),(45,2,'news.pin_toggled','news','4',NULL,'::1','curl/8.19.0','2026-07-10 07:33:39'),(46,NULL,'news.auto_published','news','5','{\"title\":\"Scheduled Post\"}',NULL,NULL,'2026-07-10 07:33:57'),(47,2,'news.created','news','6','{\"title\":\"Secret Draft\",\"status\":\"draft\"}','::1','curl/8.19.0','2026-07-10 07:34:22'),(48,2,'document.uploaded','document','1','{\"title\":\"Official Gazette 2026\",\"name\":\"gazette-2026.pdf\"}','::1','curl/8.19.0','2026-07-10 13:08:09'),(49,2,'document.new_version','document','1','{\"version\":2,\"note\":\"Fixed typos\"}','::1','curl/8.19.0','2026-07-10 13:09:04'),(50,2,'document.version_restored','document','1','{\"restored_version\":1}','::1','curl/8.19.0','2026-07-10 13:09:05'),(51,2,'settings.updated','settings','modules',NULL,'::1','curl/8.19.0','2026-07-10 13:15:27'),(52,2,'settings.updated','settings','modules',NULL,'::1','curl/8.19.0','2026-07-10 13:15:27'),(53,2,'settings.updated','settings','advanced',NULL,'::1','curl/8.19.0','2026-07-10 13:15:27'),(54,2,'settings.updated','settings','advanced',NULL,'::1','curl/8.19.0','2026-07-10 13:15:27'),(55,2,'theme.exported','theme','1','{\"name\":\"Aurora\"}','::1','curl/8.19.0','2026-07-10 13:24:47'),(56,2,'theme.updated','theme','1','{\"name\":\"Aurora\"}','::1','curl/8.19.0','2026-07-10 13:25:49'),(57,2,'theme.exported','theme','1','{\"name\":\"Aurora\"}','::1','curl/8.19.0','2026-07-10 13:25:49'),(58,2,'auth.login','user','2',NULL,'::1','curl/8.19.0','2026-07-10 18:38:57'),(59,2,'theme.installed','theme','5','{\"slug\":\"ocean-test\",\"warnings\":1}','::1','curl/8.19.0','2026-07-10 18:38:58'),(60,2,'theme.install_rejected','theme',NULL,'{\"errors\":[\"Banned file type rejected: assets/shell.php\"]}','::1','curl/8.19.0','2026-07-10 18:38:58'),(61,2,'theme.install_rejected','theme',NULL,'{\"errors\":[\"Path traversal rejected: ../../public/pwned.txt\"]}','::1','curl/8.19.0','2026-07-10 18:38:58'),(62,2,'theme.install_rejected','theme',NULL,'{\"errors\":[\"Banned file type rejected: assets/logo.php.png\"]}','::1','curl/8.19.0','2026-07-10 18:38:59'),(63,2,'theme.installed','theme','5','{\"slug\":\"ocean-test\",\"warnings\":3}','::1','curl/8.19.0','2026-07-10 18:39:25'),(64,2,'theme.rolled_back','theme','5',NULL,'::1','curl/8.19.0','2026-07-10 18:39:25');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `head_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_departments_parent` (`parent_id`),
  KEY `fk_departments_head` (`head_user_id`),
  CONSTRAINT `fk_departments_head` FOREIGN KEY (`head_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_departments_parent` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Executive',NULL,NULL,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(2,'Human Resources',NULL,NULL,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(3,'Finance',NULL,NULL,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(4,'Information Technology',NULL,NULL,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(5,'Infrastructure',4,NULL,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(6,'Software Development',4,NULL,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(7,'Operations',NULL,NULL,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(8,'Engineering',NULL,NULL,'2026-07-10 00:19:12','2026-07-10 00:19:12');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_categories`
--

DROP TABLE IF EXISTS `doc_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `visible_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visible_to`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_doc_categories_parent` (`parent_id`),
  CONSTRAINT `fk_doc_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `doc_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_categories`
--

LOCK TABLES `doc_categories` WRITE;
/*!40000 ALTER TABLE `doc_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(150) NOT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `version_note` varchar(255) DEFAULT NULL,
  `parent_doc_id` int(10) unsigned DEFAULT NULL,
  `visible_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visible_to`)),
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `download_count` int(10) unsigned NOT NULL DEFAULT 0,
  `is_gazette` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `fk_documents_parent` (`parent_doc_id`),
  KEY `fk_documents_uploader` (`uploaded_by`),
  KEY `idx_documents_gazette` (`is_gazette`,`published_at`),
  KEY `idx_documents_category` (`category_id`),
  CONSTRAINT `fk_documents_category` FOREIGN KEY (`category_id`) REFERENCES `doc_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_documents_parent` FOREIGN KEY (`parent_doc_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_documents_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (1,'35ae5575-42b3-4fbf-965e-f9586deee025','Official Gazette 2026',NULL,NULL,'docs/22309da808fec6cc3f43f5f8f0e4def8ab34786b.bin','gazette-2026.pdf','application/pdf',199,3,'Restored v1',NULL,NULL,2,3,1,'2026-07-10 13:08:09','2026-07-10 13:08:09','2026-07-10 14:16:51'),(2,'ec0a8185-7718-4424-a08f-9232e2979525','Official Gazette 2026',NULL,NULL,'docs/22309da808fec6cc3f43f5f8f0e4def8ab34786b.bin','gazette-2026.pdf','application/pdf',199,1,NULL,1,NULL,2,0,0,NULL,'2026-07-10 13:08:09','2026-07-10 13:09:04'),(3,'6d01ca47-9d8e-44b3-87b8-da01a3b65a49','Official Gazette 2026',NULL,NULL,'docs/8fc8d4d8b9f443d16c6bd287df0079d870e534ec.bin','gazette-v2.pdf','application/pdf',49,2,'Fixed typos',1,NULL,2,0,0,NULL,'2026-07-10 13:08:09','2026-07-10 13:09:05');
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `succeeded` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_attempts_email_ip` (`email`,`ip`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (2,'admin@example.com','::1',1,'2026-07-09 23:59:04'),(3,'admin@example.com','::1',1,'2026-07-09 23:59:46'),(4,'victim@example.com','::1',0,'2026-07-09 23:59:46'),(5,'victim@example.com','::1',0,'2026-07-09 23:59:46'),(6,'victim@example.com','::1',0,'2026-07-09 23:59:46'),(7,'victim@example.com','::1',0,'2026-07-09 23:59:46'),(8,'victim@example.com','::1',0,'2026-07-09 23:59:47'),(9,'victim@example.com','::1',0,'2026-07-09 23:59:47'),(10,'victim2@example.com','::1',0,'2026-07-10 00:01:31'),(11,'victim2@example.com','::1',0,'2026-07-10 00:01:32'),(12,'victim2@example.com','::1',0,'2026-07-10 00:01:32'),(13,'victim2@example.com','::1',0,'2026-07-10 00:01:32'),(14,'victim2@example.com','::1',0,'2026-07-10 00:01:32'),(15,'admin@example.com','::1',1,'2026-07-10 00:01:51'),(16,'admin@example.com','::1',1,'2026-07-10 00:02:40'),(17,'admin@example.com','::1',1,'2026-07-10 00:11:58'),(18,'admin@example.com','::1',1,'2026-07-10 00:12:14'),(19,'admin@example.com','::1',1,'2026-07-10 00:18:50'),(20,'eva@example.com','::1',1,'2026-07-10 00:19:48'),(21,'admin@example.com','::1',1,'2026-07-10 07:19:04'),(22,'admin@example.com','::1',1,'2026-07-10 18:38:57');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `location` enum('sidebar','navbar','footer') NOT NULL DEFAULT 'sidebar',
  `label` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `route_name` varchar(100) DEFAULT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `target` enum('_self','_blank') NOT NULL DEFAULT '_self',
  `visible_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visible_to`)),
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_menu_items_parent` (`parent_id`),
  KEY `idx_menu_location` (`location`,`enabled`,`sort_order`),
  CONSTRAINT `fk_menu_items_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
INSERT INTO `menu_items` VALUES (1,'sidebar','Home','home',NULL,'home',NULL,10,'_self',NULL,1,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(2,'sidebar','Profile','user','/profile',NULL,NULL,20,'_self',NULL,1,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(3,'sidebar','Directory','users','/directory',NULL,NULL,30,'_self',NULL,1,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(4,'sidebar','Org Chart','hierarchy','/org-chart',NULL,NULL,40,'_self',NULL,1,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(5,'sidebar','News','news','/news',NULL,NULL,50,'_self',NULL,1,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(6,'sidebar','Documents','files','/documents',NULL,NULL,60,'_self',NULL,1,'2026-07-09 23:53:12','2026-07-09 23:53:12');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(10) unsigned NOT NULL,
  `ran_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'001_users_roles_departments.sql',1,'2026-07-09 23:53:11'),(2,'002_auth_sso.sql',1,'2026-07-09 23:53:11'),(3,'003_navigation_quick_links.sql',1,'2026-07-09 23:53:11'),(4,'004_news.sql',1,'2026-07-09 23:53:11'),(5,'005_documents.sql',1,'2026-07-09 23:53:12'),(6,'006_settings_themes_audit_notifications.sql',1,'2026-07-09 23:53:12'),(7,'007_quick_link_extras.sql',2,'2026-07-10 07:22:07'),(8,'008_news_reactions.sql',3,'2026-07-10 07:32:43'),(9,'009_documents_version_note.sql',4,'2026-07-10 13:08:01'),(10,'010_modules.sql',5,'2026-07-10 13:14:56'),(11,'011_user_skills.sql',6,'2026-07-10 18:43:09');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `slug` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES ('comments',1,'2026-07-10 13:14:56'),('directory',1,'2026-07-10 13:14:56'),('documents',1,'2026-07-10 13:14:56'),('news',1,'2026-07-10 13:15:27'),('org_chart',1,'2026-07-10 13:14:56'),('reactions',1,'2026-07-10 13:14:56');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `cover_path` varchar(255) DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `author_id` int(10) unsigned DEFAULT NULL,
  `status` enum('draft','scheduled','published','archived') NOT NULL DEFAULT 'draft',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_news_category` (`category_id`),
  KEY `fk_news_author` (`author_id`),
  KEY `idx_news_status_published` (`status`,`published_at`),
  KEY `idx_news_pinned` (`is_pinned`),
  CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_news_category` FOREIGN KEY (`category_id`) REFERENCES `news_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'Welcome to OpenIntranet','welcome-to-openintranet','Your new company portal is live — here is what you can do with it.','<p>Welcome aboard! This portal brings together company news, documents, the employee directory and your everyday tools in one place.</p><h2>Getting started</h2><ul><li>Update your profile and photo</li><li>Browse the document center</li><li>Check the latest announcements right here</li></ul>',NULL,1,1,'published',1,1,'2026-07-06 23:53:12',2,'2026-07-09 23:53:12','2026-07-10 14:18:11'),(2,'Quarterly all-hands meeting','quarterly-all-hands-meeting','Join us for the quarterly all-hands — agenda and logistics inside.','<p>Our next all-hands takes place at the end of the month. We will cover business updates, team highlights and the roadmap ahead.</p><p>Submit your questions in advance to the leadership team.</p>',NULL,2,1,'published',0,1,'2026-07-07 23:53:12',0,'2026-07-09 23:53:12','2026-07-10 12:34:22'),(3,'New document center is open','new-document-center-is-open','Policies, forms and the official gazette are now available online.','<p>The document center is now the single source of truth for company policies, HR forms and official gazette publications.</p><p>Documents are versioned, so you will always find the latest copy.</p>',NULL,1,1,'published',0,1,'2026-07-08 23:53:12',0,'2026-07-09 23:53:12','2026-07-10 12:34:22');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_categories`
--

DROP TABLE IF EXISTS `news_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_categories`
--

LOCK TABLES `news_categories` WRITE;
/*!40000 ALTER TABLE `news_categories` DISABLE KEYS */;
INSERT INTO `news_categories` VALUES (1,'Announcements','announcements','#4f46e5','2026-07-10 04:53:12','2026-07-10 04:53:12'),(2,'Events','events','#0ea5e9','2026-07-10 04:53:12','2026-07-10 04:53:12');
/*!40000 ALTER TABLE `news_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_comments`
--

DROP TABLE IF EXISTS `news_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `news_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_news_comments_user` (`user_id`),
  KEY `idx_news_comments_news` (`news_id`,`created_at`),
  CONSTRAINT `fk_news_comments_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_news_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_comments`
--

LOCK TABLES `news_comments` WRITE;
/*!40000 ALTER TABLE `news_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_reactions`
--

DROP TABLE IF EXISTS `news_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_reactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `news_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `emoji` varchar(16) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reaction` (`news_id`,`user_id`,`emoji`),
  KEY `fk_news_reactions_user` (`user_id`),
  CONSTRAINT `fk_news_reactions_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_news_reactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_reactions`
--

LOCK TABLES `news_reactions` WRITE;
/*!40000 ALTER TABLE `news_reactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`,`read_at`,`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'news','News: Sanitizer Test Post',NULL,'http://localhost/intra/news/sanitizer-test-post',NULL,'2026-07-10 07:33:15'),(2,3,'news','News: Sanitizer Test Post',NULL,'http://localhost/intra/news/sanitizer-test-post',NULL,'2026-07-10 07:33:15'),(3,4,'news','News: Sanitizer Test Post',NULL,'http://localhost/intra/news/sanitizer-test-post',NULL,'2026-07-10 07:33:15'),(4,5,'news','News: Sanitizer Test Post',NULL,'http://localhost/intra/news/sanitizer-test-post',NULL,'2026-07-10 07:33:15'),(5,1,'news','News: Scheduled Post',NULL,'http://localhost/intra/news/scheduled-post',NULL,'2026-07-10 07:33:57'),(6,3,'news','News: Scheduled Post',NULL,'http://localhost/intra/news/scheduled-post',NULL,'2026-07-10 07:33:57'),(7,4,'news','News: Scheduled Post',NULL,'http://localhost/intra/news/scheduled-post',NULL,'2026-07-10 07:33:57'),(8,5,'news','News: Scheduled Post',NULL,'http://localhost/intra/news/scheduled-post',NULL,'2026-07-10 07:33:57');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(190) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `label` varchar(150) NOT NULL,
  `group_name` varchar(100) NOT NULL DEFAULT 'General',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'users.manage','Manage users','Users','2026-07-10 04:53:12','2026-07-10 04:53:12'),(2,'roles.manage','Manage roles & permissions','Users','2026-07-10 04:53:12','2026-07-10 04:53:12'),(3,'news.create','Create news drafts','News','2026-07-10 04:53:12','2026-07-10 04:53:12'),(4,'news.publish','Publish news','News','2026-07-10 04:53:12','2026-07-10 04:53:12'),(5,'docs.upload','Upload documents','Documents','2026-07-10 04:53:12','2026-07-10 04:53:12'),(6,'docs.manage','Manage documents & categories','Documents','2026-07-10 04:53:12','2026-07-10 04:53:12'),(7,'themes.manage','Manage themes','Appearance','2026-07-10 04:53:12','2026-07-10 04:53:12'),(8,'settings.manage','Manage settings','System','2026-07-10 04:53:12','2026-07-10 04:53:12'),(9,'menus.manage','Manage menus','Appearance','2026-07-10 04:53:12','2026-07-10 04:53:12'),(10,'sso.manage','Manage SSO providers','System','2026-07-10 04:53:12','2026-07-10 04:53:12'),(11,'links.manage','Manage quick links','Content','2026-07-10 04:53:12','2026-07-10 04:53:12'),(12,'audit.view','View audit log','System','2026-07-10 04:53:12','2026-07-10 04:53:12');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quick_link_clicks`
--

DROP TABLE IF EXISTS `quick_link_clicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quick_link_clicks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `quick_link_id` int(10) unsigned NOT NULL,
  `day` date NOT NULL,
  `clicks` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_link_day` (`quick_link_id`,`day`),
  CONSTRAINT `fk_ql_clicks_link` FOREIGN KEY (`quick_link_id`) REFERENCES `quick_links` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quick_link_clicks`
--

LOCK TABLES `quick_link_clicks` WRITE;
/*!40000 ALTER TABLE `quick_link_clicks` DISABLE KEYS */;
INSERT INTO `quick_link_clicks` VALUES (1,1,'2026-07-10',2);
/*!40000 ALTER TABLE `quick_link_clicks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quick_links`
--

DROP TABLE IF EXISTS `quick_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quick_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `url` varchar(500) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon_type` enum('library','upload') NOT NULL DEFAULT 'library',
  `icon_value` varchar(255) DEFAULT NULL,
  `bg_color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `visible_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visible_to`)),
  `open_new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `click_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quick_links_active` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quick_links`
--

LOCK TABLES `quick_links` WRITE;
/*!40000 ALTER TABLE `quick_links` DISABLE KEYS */;
INSERT INTO `quick_links` VALUES (1,'Webmail','https://mail.example.com','Company email','library','mail','#4f46e5',10,NULL,1,1,2,'2026-07-09 23:53:12','2026-07-10 07:25:30'),(2,'HR Portal','https://hr.example.com','Leave, payslips & policies','library','users','#0ea5e9',20,NULL,1,1,0,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(3,'Payroll','https://payroll.example.com','Salary & reimbursements','library','cash','#16a34a',30,NULL,1,1,0,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(4,'Helpdesk','https://helpdesk.example.com','IT support tickets','library','lifebuoy','#d97706',40,NULL,1,1,0,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(5,'Calendar','https://calendar.example.com','Shared company calendar','library','calendar','#dc2626',50,NULL,1,1,0,'2026-07-09 23:53:12','2026-07-09 23:53:12'),(6,'Company Wiki','https://wiki.example.com','Knowledge base','library','book','#7c3aed',60,NULL,1,1,0,'2026-07-09 23:53:12','2026-07-09 23:53:12');
/*!40000 ALTER TABLE `quick_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remember_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `selector` varchar(24) NOT NULL,
  `validator_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `fk_remember_user` (`user_id`),
  CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remember_tokens`
--

LOCK TABLES `remember_tokens` WRITE;
/*!40000 ALTER TABLE `remember_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permission`
--

DROP TABLE IF EXISTS `role_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permission` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_role_permission_permission` (`permission_id`),
  CONSTRAINT `fk_role_permission_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permission_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permission`
--

LOCK TABLES `role_permission` WRITE;
/*!40000 ALTER TABLE `role_permission` DISABLE KEYS */;
INSERT INTO `role_permission` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,12),(3,3),(3,4),(3,5);
/*!40000 ALTER TABLE `role_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','super_admin','Full access to everything',1,'2026-07-10 04:53:12','2026-07-10 04:53:12'),(2,'Administrator','admin','Manages users, content and settings',1,'2026-07-10 04:53:12','2026-07-10 04:53:12'),(3,'Editor','editor','Creates and publishes content',1,'2026-07-10 04:53:12','2026-07-10 04:53:12'),(4,'Employee','employee','Standard portal access',1,'2026-07-10 04:53:12','2026-07-10 04:53:12');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'string',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('allow_local_login','1','bool','2026-07-10 04:53:12'),('allowed_doc_types','[\"pdf\",\"docx\",\"xlsx\",\"pptx\",\"png\",\"jpg\",\"zip\"]','json','2026-07-10 04:53:12'),('audit_retention_days','365','int','2026-07-10 04:53:12'),('comments_enabled','1','bool','2026-07-10 04:53:12'),('date_format','j M Y','string','2026-07-10 04:53:12'),('favicon_path',NULL,'string','2026-07-10 04:53:12'),('gazette_dashboard_count','5','int','2026-07-10 04:53:12'),('homepage_sections','[\"quick_links\",\"news\",\"gazette\"]','json','2026-07-10 04:53:12'),('logo_path',NULL,'string','2026-07-10 04:53:12'),('maintenance_message','','string','2026-07-10 13:15:27'),('maintenance_mode','0','bool','2026-07-10 13:15:27'),('news_dashboard_count','6','int','2026-07-10 04:53:12'),('password_min_length','10','int','2026-07-10 04:53:12'),('reactions_enabled','1','bool','2026-07-10 04:53:12'),('session_lifetime_minutes','120','int','2026-07-10 04:53:12'),('site_name','OpenIntranet','string','2026-07-10 04:53:12'),('site_tagline','Your company portal','string','2026-07-10 04:53:12'),('timezone','UTC','string','2026-07-10 04:53:12'),('upload_max_mb','20','int','2026-07-10 04:53:12');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sso_providers`
--

DROP TABLE IF EXISTS `sso_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sso_providers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `type` enum('google','microsoft','oidc') NOT NULL DEFAULT 'oidc',
  `client_id` varchar(255) NOT NULL DEFAULT '',
  `client_secret_encrypted` text DEFAULT NULL,
  `tenant_or_issuer` varchar(255) DEFAULT NULL,
  `discovery_url` varchar(500) DEFAULT NULL,
  `scopes` varchar(255) NOT NULL DEFAULT 'openid profile email',
  `icon` varchar(100) DEFAULT NULL,
  `button_color` varchar(20) DEFAULT NULL,
  `allowed_domains` text DEFAULT NULL,
  `auto_provision` tinyint(1) NOT NULL DEFAULT 0,
  `default_role_id` int(10) unsigned DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_sso_providers_role` (`default_role_id`),
  CONSTRAINT `fk_sso_providers_role` FOREIGN KEY (`default_role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sso_providers`
--

LOCK TABLES `sso_providers` WRITE;
/*!40000 ALTER TABLE `sso_providers` DISABLE KEYS */;
INSERT INTO `sso_providers` VALUES (1,'Google','google','google','test-client-id.apps.googleusercontent.com',NULL,NULL,NULL,'openid profile email',NULL,NULL,NULL,1,NULL,0,10,'2026-07-10 05:11:02','2026-07-10 05:12:28');
/*!40000 ALTER TABLE `sso_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `themes`
--

DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `themes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `source` enum('builtin','editor','uploaded') NOT NULL DEFAULT 'editor',
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `dark_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dark_variables`)),
  `custom_css` mediumtext DEFAULT NULL,
  `dir_path` varchar(255) DEFAULT NULL,
  `preview_path` varchar(255) DEFAULT NULL,
  `supports_dark` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `version` varchar(20) NOT NULL DEFAULT '1.0.0',
  `author` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `themes`
--

LOCK TABLES `themes` WRITE;
/*!40000 ALTER TABLE `themes` DISABLE KEYS */;
INSERT INTO `themes` VALUES (1,'Aurora','aurora','editor','{\"color-primary\":\"#4f46e5\",\"color-primary-contrast\":\"#ffffff\",\"color-accent\":\"#0ea5e9\",\"color-bg\":\"#f3f4f6\",\"color-surface\":\"#ffffff\",\"color-surface-2\":\"#f9fafb\",\"color-text\":\"#111827\",\"color-text-muted\":\"#6b7280\",\"color-border\":\"#e5e7eb\",\"color-success\":\"#16a34a\",\"color-warning\":\"#d97706\",\"color-danger\":\"#dc2626\",\"font-family\":\"system-ui, -apple-system, \\\"Segoe UI\\\", Roboto, \\\"Helvetica Neue\\\", Arial, sans-serif\",\"font-size-base\":\"16px\",\"radius-sm\":\"6px\",\"radius-md\":\"10px\",\"radius-lg\":\"16px\"}',NULL,NULL,NULL,NULL,1,1,'1.0.0','OpenIntranet','2026-07-09 23:53:12','2026-07-10 13:26:21'),(2,'Slate','slate','builtin','{\"color-primary\":\"#475569\",\"color-primary-contrast\":\"#ffffff\",\"color-accent\":\"#0284c7\",\"color-bg\":\"#f8fafc\",\"color-surface\":\"#ffffff\",\"color-surface-2\":\"#f9fafb\",\"color-text\":\"#111827\",\"color-text-muted\":\"#6b7280\",\"color-border\":\"#e5e7eb\",\"color-success\":\"#16a34a\",\"color-warning\":\"#d97706\",\"color-danger\":\"#dc2626\",\"font-family\":\"system-ui, -apple-system, \\\"Segoe UI\\\", Roboto, \\\"Helvetica Neue\\\", Arial, sans-serif\",\"font-size-base\":\"16px\",\"radius-sm\":\"4px\",\"radius-md\":\"6px\",\"radius-lg\":\"10px\"}','{\"color-bg\":\"#0f172a\",\"color-surface\":\"#1e293b\",\"color-surface-2\":\"#273449\",\"color-text\":\"#e2e8f0\",\"color-text-muted\":\"#94a3b8\",\"color-border\":\"#334155\",\"navbar-bg\":\"#1e293b\",\"sidebar-bg\":\"#1e293b\",\"color-primary\":\"#94a3b8\"}',NULL,NULL,NULL,1,0,'1.0.0','OpenIntranet','2026-07-10 13:18:38','2026-07-10 13:18:38'),(3,'Forest','forest','builtin','{\"color-primary\":\"#15803d\",\"color-primary-contrast\":\"#ffffff\",\"color-accent\":\"#ca8a04\",\"color-bg\":\"#f4f8f4\",\"color-surface\":\"#ffffff\",\"color-surface-2\":\"#f0f5f0\",\"color-text\":\"#111827\",\"color-text-muted\":\"#6b7280\",\"color-border\":\"#e5e7eb\",\"color-success\":\"#16a34a\",\"color-warning\":\"#d97706\",\"color-danger\":\"#dc2626\",\"font-family\":\"system-ui, -apple-system, \\\"Segoe UI\\\", Roboto, \\\"Helvetica Neue\\\", Arial, sans-serif\",\"font-size-base\":\"16px\",\"radius-sm\":\"6px\",\"radius-md\":\"10px\",\"radius-lg\":\"16px\"}','{\"color-bg\":\"#0c1512\",\"color-surface\":\"#16241e\",\"color-surface-2\":\"#1d2f27\",\"color-text\":\"#e2e8f0\",\"color-text-muted\":\"#94a3b8\",\"color-border\":\"#2d4438\",\"navbar-bg\":\"#16241e\",\"sidebar-bg\":\"#16241e\",\"color-primary\":\"#4ade80\",\"color-primary-contrast\":\"#052e16\"}',NULL,NULL,NULL,1,0,'1.0.0','OpenIntranet','2026-07-10 13:18:38','2026-07-10 13:18:38'),(4,'Midnight','midnight','builtin','{\"color-primary\":\"#818cf8\",\"color-primary-contrast\":\"#111827\",\"color-accent\":\"#0ea5e9\",\"color-bg\":\"#0b1220\",\"color-surface\":\"#131c2e\",\"color-surface-2\":\"#1b2740\",\"color-text\":\"#dbe4f3\",\"color-text-muted\":\"#8b9bb8\",\"color-border\":\"#26334d\",\"color-success\":\"#16a34a\",\"color-warning\":\"#d97706\",\"color-danger\":\"#dc2626\",\"font-family\":\"system-ui, -apple-system, \\\"Segoe UI\\\", Roboto, \\\"Helvetica Neue\\\", Arial, sans-serif\",\"font-size-base\":\"16px\",\"radius-sm\":\"6px\",\"radius-md\":\"10px\",\"radius-lg\":\"16px\",\"navbar-bg\":\"#131c2e\",\"sidebar-bg\":\"#131c2e\"}','{\"color-bg\":\"#070c16\",\"color-surface\":\"#0e1524\",\"color-surface-2\":\"#151f33\",\"color-border\":\"#1f2a41\"}',NULL,NULL,NULL,1,0,'1.0.0','OpenIntranet','2026-07-10 13:18:38','2026-07-10 13:19:55'),(5,'Ocean Test','ocean-test','uploaded','[]',NULL,'.card { border-top: 3px solid var(--color-primary); }\n/*import*/ url();','uploaded/ocean-test','uploaded/ocean-test/preview.png',1,0,'1.0.0','Tester','2026-07-10 18:38:58','2026-07-10 18:39:25');
/*!40000 ALTER TABLE `themes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_identities`
--

DROP TABLE IF EXISTS `user_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_identities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `provider_id` int(10) unsigned NOT NULL,
  `provider_subject` varchar(190) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `raw_profile` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_profile`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_identity` (`provider_id`,`provider_subject`),
  KEY `fk_identities_user` (`user_id`),
  CONSTRAINT `fk_identities_provider` FOREIGN KEY (`provider_id`) REFERENCES `sso_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_identities_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_identities`
--

LOCK TABLES `user_identities` WRITE;
/*!40000 ALTER TABLE `user_identities` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_identities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_prefs`
--

DROP TABLE IF EXISTS `user_prefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_prefs` (
  `user_id` int(10) unsigned NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`,`key`),
  CONSTRAINT `fk_user_prefs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_prefs`
--

LOCK TABLES `user_prefs` WRITE;
/*!40000 ALTER TABLE `user_prefs` DISABLE KEYS */;
INSERT INTO `user_prefs` VALUES (2,'quick_links_order','[1,2,3,4,6,5]','2026-07-10 08:15:20'),(2,'theme_mode','light','2026-07-10 14:22:51');
/*!40000 ALTER TABLE `user_prefs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_quick_link_pins`
--

DROP TABLE IF EXISTS `user_quick_link_pins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_quick_link_pins` (
  `user_id` int(10) unsigned NOT NULL,
  `quick_link_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`quick_link_id`),
  KEY `fk_ql_pins_link` (`quick_link_id`),
  CONSTRAINT `fk_ql_pins_link` FOREIGN KEY (`quick_link_id`) REFERENCES `quick_links` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ql_pins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_quick_link_pins`
--

LOCK TABLES `user_quick_link_pins` WRITE;
/*!40000 ALTER TABLE `user_quick_link_pins` DISABLE KEYS */;
INSERT INTO `user_quick_link_pins` VALUES (2,1,'2026-07-10 07:25:30');
/*!40000 ALTER TABLE `user_quick_link_pins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_role`
--

DROP TABLE IF EXISTS `user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_role` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_user_role_role` (`role_id`),
  CONSTRAINT `fk_user_role_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_role_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_role`
--

LOCK TABLES `user_role` WRITE;
/*!40000 ALTER TABLE `user_role` DISABLE KEYS */;
INSERT INTO `user_role` VALUES (2,1),(3,4),(4,3),(4,4),(5,4);
/*!40000 ALTER TABLE `user_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_skills`
--

DROP TABLE IF EXISTS `user_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_skills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `skill` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_skill` (`user_id`,`skill`),
  CONSTRAINT `fk_user_skills_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_skills`
--

LOCK TABLES `user_skills` WRITE;
/*!40000 ALTER TABLE `user_skills` DISABLE KEYS */;
INSERT INTO `user_skills` VALUES (1,3,'Accounting','2026-07-10 23:43:19');
/*!40000 ALTER TABLE `user_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `job_title` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `manager_id` int(10) unsigned DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_manager` (`manager_id`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_department` (`department_id`),
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'OpenIntranet Bot','bot@openintranet.local',NULL,NULL,'System',NULL,NULL,NULL,NULL,NULL,NULL,'active',0,NULL,'2026-07-09 23:53:12','2026-07-09 23:53:12','2026-07-10 23:47:09'),(2,'Site Admin','admin@example.com','$argon2id$v=19$m=65536,t=4,p=1$SFcwVEw0eGVST2dqUUdmVQ$BXbd3hooYxHo1Hnhi+lg1ptz0jrTJXF06NobYD7p59Y',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',0,'2026-07-10 18:38:57','2026-07-09 23:58:43','2026-07-09 23:58:43','2026-07-10 18:38:57'),(3,'Eva Employee','eva@example.com','$argon2id$v=19$m=65536,t=4,p=1$Y2ZFNGZ3b3pSWndrNWFzbw$kHoUaalMKPyZrcK98kKayKJnDzFiz56vXAJQiRTwev0',NULL,'Accountant',NULL,NULL,2,NULL,NULL,NULL,'active',0,'2026-07-10 00:19:48','2026-07-10 00:18:50','2026-07-10 00:18:50','2026-07-10 23:47:09'),(4,'Carl CSV','carl@example.com',NULL,NULL,'Engineer',NULL,8,3,'HQ','UTC',NULL,'active',1,NULL,NULL,'2026-07-10 00:19:12','2026-07-10 00:19:12'),(5,'Dora CSV','dora@example.com',NULL,NULL,'Designer',NULL,8,4,NULL,NULL,NULL,'active',1,NULL,NULL,'2026-07-10 00:19:12','2026-07-10 23:47:09');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'openintranet'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-10 23:52:15
