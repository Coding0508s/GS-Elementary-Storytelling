-- MySQL dump 10.13  Distrib 9.3.0, for macos15.2 (arm64)
--
-- Host: localhost    Database: storytelling_contest
-- ------------------------------------------------------
-- Server version	9.3.0

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

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','judge') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'judge',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_username_unique` (`username`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$12$oOb/HgRC6EM/xepOZvrbeuOnPaMfoCQSOANCaiIUZlOaLyCLfa8Tu','관리자','admin@gs-education.com','admin',1,'2025-09-01 22:15:47','2025-08-27 02:14:45','2025-09-01 22:15:47'),(8,'judge1','$2y$12$hXiTlQHXuKbsP52AgezX.epD8Ir20vZmOTqMSVgc1rJkkIUolT36G','Grape 1','judge1@gs25contest.com','judge',1,'2025-09-01 00:07:52','2025-08-28 04:10:26','2025-09-01 00:07:52'),(9,'judge2','$2y$12$QymM32xL9ASBD0w8Qh9VX.dVjW.XfcbJdo6l/rgM6TLz6yJTNwG52','Grape 2','judge2@gs25contest.com','judge',1,'2025-09-01 00:09:08','2025-08-28 04:11:18','2025-09-01 00:09:08'),(10,'judge3','$2y$12$t7WAwkqEyWvcHyu.zLPXNumuDYkwxK5R8dM0vfxGPaT0ErVY/djUa','Grape 3','judge3@gs25contest.com','judge',1,'2025-09-01 00:10:25','2025-08-28 04:11:53','2025-09-01 00:10:25'),(11,'judge4','$2y$12$9HdK3JoKNZC3idsPRJJMSu8wVOIJfe0EzoiZ4liYsgMYXTu61DnxC','Grape 4','judge4@gs25contest.com','judge',1,'2025-09-01 00:11:17','2025-08-28 04:12:27','2025-09-01 00:11:17'),(12,'judge5','$2y$12$G9Wd/IvqQp8N1dlMk6hAauJhrmNvFnzmdD4MTAIR2prb0RqrIkIOi','Grape 5','judge5@gs25contest.com','judge',1,'2025-09-01 00:12:10','2025-08-28 04:12:55','2025-09-01 00:12:10'),(14,'judge6','$2y$12$WKVb0ObzxyXSMf4QBn8F8.iXmCK.WoO.bBw.EdoLYZ1YA/sFXHZTq','Grape 6','judge6@gs25contest.com','judge',1,'2025-09-01 00:12:41','2025-08-28 04:20:05','2025-09-01 00:12:41');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluations`
--

DROP TABLE IF EXISTS `evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `video_submission_id` bigint unsigned NOT NULL,
  `admin_id` bigint unsigned NOT NULL,
  `pronunciation_score` int NOT NULL COMMENT '정확한 발음과 자연스러운 억양, 전달력 (1-100점)',
  `vocabulary_score` int NOT NULL COMMENT '올바른 어휘 및 표현 사용 (1-100점)',
  `fluency_score` int NOT NULL COMMENT '유창성 수준 (1-100점)',
  `confidence_score` int NOT NULL COMMENT '자신감, 긍정적이고 밝은 태도 (1-100점)',
  `total_score` int NOT NULL COMMENT '총점 (자동 계산, 최대 100점)',
  `comments` text COLLATE utf8mb4_unicode_ci COMMENT '심사 코멘트',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evaluations_video_submission_id_admin_id_unique` (`video_submission_id`,`admin_id`),
  KEY `evaluations_admin_id_foreign` (`admin_id`),
  CONSTRAINT `evaluations_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_video_submission_id_foreign` FOREIGN KEY (`video_submission_id`) REFERENCES `video_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluations`
--

LOCK TABLES `evaluations` WRITE;
/*!40000 ALTER TABLE `evaluations` DISABLE KEYS */;
INSERT INTO `evaluations` VALUES (1,2,8,10,8,10,9,37,NULL,'2025-09-01 00:08:17','2025-09-01 00:08:17'),(2,4,8,10,10,10,10,40,NULL,'2025-09-01 00:08:56','2025-09-01 00:08:56'),(3,3,9,10,7,9,10,36,NULL,'2025-09-01 00:09:26','2025-09-01 00:09:26'),(4,4,9,10,7,7,10,34,NULL,'2025-09-01 00:09:40','2025-09-01 00:09:40'),(5,2,10,10,9,10,10,39,NULL,'2025-09-01 00:10:45','2025-09-01 00:10:45'),(6,5,10,10,10,9,9,38,NULL,'2025-09-01 00:10:58','2025-09-01 00:10:58'),(7,1,11,10,8,9,8,35,NULL,'2025-09-01 00:11:33','2025-09-01 00:11:33'),(8,5,11,10,9,10,10,39,NULL,'2025-09-01 00:11:45','2025-09-01 00:11:45'),(9,3,12,9,8,7,10,34,NULL,'2025-09-01 00:12:23','2025-09-01 00:12:23'),(10,1,14,9,8,9,10,36,NULL,'2025-09-01 00:12:52','2025-09-01 00:12:52');
/*!40000 ALTER TABLE `evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutions`
--

DROP TABLE IF EXISTS `institutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `institutions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `institutions_name_unique` (`name`),
  KEY `institutions_name_index` (`name`),
  KEY `institutions_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institutions`
--

LOCK TABLES `institutions` WRITE;
/*!40000 ALTER TABLE `institutions` DISABLE KEYS */;
INSERT INTO `institutions` VALUES (1,'GrapeSEED Online',NULL,NULL,1,0,'2025-08-27 00:19:39','2025-08-27 00:19:39'),(2,'거창 세종유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(3,'거창 세종프랜즈클럽외국어교습소',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(4,'경기 군포 사랑유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(5,'경기 분당 누리봄유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(6,'경기 시흥 예일유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(7,'경기 오포 자연키즈랜드어린이집',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(8,'경기 평택 가온숲학원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-18 01:34:34'),(9,'경남 사천 한마음유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(10,'고양 원흥 씨앗유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(11,'과천 몬테소리어린이집',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(12,'광명 철산 예솔유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(13,'광양 혜화유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(14,'광양 힘스프랜즈클럽어학원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 05:06:40'),(15,'광주 광산 밀알두레학교',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(16,'광주 광산 세품크리스천스쿨',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(17,'광주 서구 그루터기어린이집',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(18,'구미 라온유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(19,'구미 리더스유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(20,'구미 선산유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(21,'구미 예일유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(22,'구미 옥계 그레이프어학원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(23,'구미 옥계 보눔유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 05:03:14'),(24,'군포 글로벌키즈어학원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(25,'군포 산본 애플트리어린이집',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(26,'김포 가현도담유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(27,'김포 효성유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(28,'김해 동상 글로벌창의어학원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(29,'남양주 별내 예닮유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(30,'대구 남구 씨앤에스어학원(CNS)',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(31,'대구 달서 사랑유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(32,'대구 달서 언어키움학원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(33,'대구 달서 예림유치원',NULL,NULL,1,0,'2025-08-14 04:41:32','2025-08-14 04:41:32'),(34,'대구 동구 온누리유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(35,'대구 무지개유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(36,'대구 북구 전박사몬테소리유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(37,'대구 북구 혜송유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(38,'대구 수성 무지개유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(39,'대구 아이별어린이집',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(40,'대전 D3 프랜즈클럽 학원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(41,'대전 대덕국제유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(42,'대전 도안 꿈내리유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(43,'대전 도안 레드애플유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(44,'대전 도안 프랜즈유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(45,'대전 둔산 서원유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(46,'대전 둔산 세명유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(47,'대전 둥지유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(48,'대전 산성 꿈내리유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(49,'대전 서구 롯데유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(50,'대전 서구 제나킨더유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(51,'대전 서구 키즈복스',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(52,'대전 서원그레이프씨드',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(53,'대전 유성 그레이프잉글리쉬교습소',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(54,'대전 유성 라온유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(55,'대전 유성 라온프랜즈클럽학원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(56,'대전 유성 미학유치원',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(57,'대전 유성 상아어린이집',NULL,NULL,1,0,'2025-08-14 04:46:50','2025-08-14 04:46:50'),(58,'대전 유성 정원유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(59,'대전 중구 드림키즈어린이집',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(60,'대전 하나빛캐슬유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(61,'동작 행복한숲유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(62,'동탄 더샘플학교',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(63,'부산 대천유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(64,'부산 메이플유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(65,'부산 명지 이튼스쿨',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(66,'부산 명지 프랜즈명지국제어학원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(67,'부산 명지국제유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(68,'부산 모음글로벌종합학원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(69,'부산 무지개유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(70,'부산 미라클스터디어학원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(71,'부산 반도보라유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(72,'부산 수정캐슬유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(73,'부산 예종종합학원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-18 01:26:13'),(74,'부산 진구 해바라기어린이집',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(75,'부산 진구 해바라기자연유치원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(76,'부산 해운대 그레이스키즈학원',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(77,'부산 해운대 반디기독학교',NULL,NULL,1,0,'2025-08-14 04:48:53','2025-08-14 04:48:53'),(78,'서울 금천 참사랑유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(79,'서울 금천 프렌즈클럽(Friends Club) 학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(80,'서울 노원 하게 한성유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(81,'서울 도봉 메이센어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:25:52'),(82,'서울 도봉 안골마을공동체',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(83,'서울 마포 주식회사 미담에듀컬',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(84,'서울 서대문 연희 직장선교학교',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(85,'서울 성북 돈암 영광유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(86,'서울 양천 샘터유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(87,'서울 양천 신목유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(88,'서울 양천 웨이몬학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(89,'서울 중랑 씨드영어교습소',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(90,'서초 방주예꼬스쿨',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(91,'성남 위례 3 어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(92,'성민프리미어스쿨',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(93,'성민프리미어스쿨 수원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(94,'세종 GLI 어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(95,'세종 글로벌교육센터',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(96,'세종 위리프랭클린어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(97,'수원 광교 이음유아학교',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(98,'수원 광교 자연에듀어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(99,'수원 금곡 엘키즈어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:26:32'),(100,'수원 금곡 킹스키즈유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(101,'수원 빅숲어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(102,'수원 영통 늘푸른유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(103,'수원 영통 메타리라유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(104,'수원 영통 프렌즈학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(105,'수원 장안 성민어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(106,'수원 장안 성민유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(107,'수원 팔달 데레사유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:26:53'),(108,'수원 팔달 밀알지엘학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:27:17'),(109,'수원 하늘을 나는 그림책',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:29:02'),(110,'수지 나리유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(111,'순천 제일유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(112,'순천 힘스어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:27:05'),(113,'순천 힘스유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:27:34'),(114,'시흥 모아드림어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(115,'시흥 시연어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(116,'안산 단원 청아유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(117,'안산 단원 프라임유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(118,'안산 본오예원유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(119,'안산 상록 백리유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(120,'안산 상록 서원어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(121,'안산 상록 서원유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(122,'안산 상록 킹스키즈어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(123,'안산 상록 킹스키즈어학원(영어유치부)',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(124,'안산 시드학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(125,'안산 아이숲유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(126,'안산 우리세상어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(127,'안성 아일린영어교습소',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(128,'안양 노촌새별어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(129,'안양 레인보우키즈어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(130,'안양 비산 한별유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(131,'안양 옥스포드숲어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(132,'안양 평촌 꿈열매유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(133,'안양 평촌 유잉글리시비에프씨학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(134,'안양 햇빛유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(135,'양산 니트(NEAT)어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(136,'양산 숲원유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(137,'양산 주식회사 로건',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(138,'여수 소화유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(139,'여수 여천 푸른학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(140,'여주 재능유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(141,'오산 동화마을유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(142,'오산 예인유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(143,'용인 강남유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(144,'용인 구갈 성민어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(145,'용인 구갈 성민유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(146,'용인 구갈 와우어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(147,'용인 기흥 숲속하늘유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(148,'용인 기흥 온샘유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(149,'용인 기흥 행복한숲어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(150,'용인 덕성어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(151,'용인 동백 행복한유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(152,'용인 마북 바나유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(153,'용인 보정 보라햇빛어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(154,'용인 새온빛어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(155,'용인 서천 예닮유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(156,'용인 서천 와이디케이(YDK)학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(157,'용인 성북 와이비엠(YBM)아이비스카이학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(158,'용인 수지 동천 하늘숲어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(159,'용인 수지 새성민어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(160,'용인 수지 성민메이센어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:30:04'),(161,'용인 수지 성민유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(162,'용인 수지 시립수지어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(163,'용인 수지 아이유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(164,'용인 수지 조이풀어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(165,'용인 수지 현대유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(166,'용인 수지 힙스프랜즈어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(167,'용인 수지키드빌리지유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(168,'용인 시립광교풍경채어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(169,'용인 시립보라해링턴어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(170,'용인 신동백어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(171,'용인 신통 EBC 어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(172,'용인 죽전 라온어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(173,'용인 죽전 성음유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(174,'용인 카라파운데이션(유치부)',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(175,'용인 카라파운데이션(중등부)',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(176,'용인 카라파운데이션(초등부)',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(177,'용인 화산 성민메이센어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 05:03:56'),(178,'용인 화산 성민유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(179,'용인 흥덕 숲리라어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(180,'용인 흥덕 숲리라유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(181,'용인 흥덕 아이미래유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(182,'용인 흥덕 테필린연구소',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(183,'용인 흥덕 프렌즈유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(184,'울산 남구 에스엔아이어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(185,'울산 동구 캠브리지관영세국제어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(186,'울산 북구 옥스포드관와이에스피(YSP)연세국제어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(187,'울산 울주 에코르와팡어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(188,'울산 중구 연세국제어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(189,'위례 리틀그레이프영어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(190,'위례 리틀포레스트어린이집(5세)',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(191,'위례 베리타스코리아',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(192,'위례 엘파스아이어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(193,'은혜샘플초등학교',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(194,'의왕 청계 FSS 어학원 유치부',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(195,'의왕 청계 FSS 어학원 초등부',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(196,'이천 성모유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(197,'이천 어린왕자어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(198,'이천 으뜸 펀키드어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(199,'이천 프렌즈어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(200,'이천 하버드어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(201,'익산 그레이프시드영어교습소',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(202,'인천 미추홀구 꿈나무어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(203,'인천 서구 더(THE)꿈나무어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(204,'인천 서창 이플렉스에듀',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 06:07:14'),(205,'인천 송도국제유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(206,'인천 청라 지에스아이어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(207,'일산 그루터기유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(208,'일산 다솜유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(209,'일산 햇빛유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(210,'전주 예닮교회 열매스쿨',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(211,'전주 완산 이엠에이(EMA)학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(212,'진주 그레이프시드어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(213,'진주 메이센어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:35:15'),(214,'진주 문산 소화유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(215,'진주 세종유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(216,'진주 햇빛놀이스쿨어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(217,'창원 그레이스글로벌기독학교',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(218,'창원 마산 하나영어교습소',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(219,'창원 성산 해바라기유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(220,'천안 다우리숲키즈어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(221,'천안 동남 높은뜻 씨앗스쿨',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:31:34'),(222,'천안 띵킹어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:31:58'),(223,'칠곡 아람어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(224,'칠곡 아람유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(225,'칠곡 플랜들리(Friendly)학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(226,'파주 문산 국제유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(227,'파주 문산 글로벌리더스학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(228,'파주 문산 성민학교',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(229,'파주 운정 다온유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(230,'파주 운정 예성유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(231,'파주 운정 이제이영어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(232,'팩트 아카데미',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:32:13'),(233,'평택 솔가람유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(234,'평택 안중 대일유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(235,'평택 자연유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(236,'포항 남구 카오룬어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(237,'포항 남구 풀잎어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(238,'포항 남구 풀잎유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(239,'포항 북구 고려유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(240,'포항 북구 예원유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(241,'포항 북구 자연과아이유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 05:06:03'),(242,'포항 북구 자연어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(243,'프라이머리스쿨 수원캠퍼스',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(244,'프라이머리스쿨 화산캠퍼스',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(245,'함양 꿈나무유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(246,'함양 리프어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(247,'화성 다일유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(248,'화성 동탄 1 라임어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(249,'화성 동탄 2 가람유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(250,'화성 동탄 2 그레이프프랜즈클럽어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(251,'화성 동탄 2 도담유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(252,'화성 동탄 2 리더스유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(253,'화성 동탄 2 주식회사월드조이',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(254,'화성 동탄 2 한빛학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(255,'화성 동탄 그레이프시드영어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(256,'화성 동탄 윤정유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(257,'화성 동탄 창의샘유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(258,'화성 병점 아이들세상유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(259,'화성 병점 알파어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(260,'화성 송산 꿈열매유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(261,'화성 신영통 GS하버드어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-18 01:32:31'),(262,'화성 지구촌기독학교',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(263,'화성 항남 그레이프시드어학원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(264,'화성 항남 예수항남기독학교',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(265,'화성 항남 예항어린이집',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(266,'화성 항남 이지유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07'),(267,'화성 행복한아이들유치원',NULL,NULL,1,0,'2025-08-14 04:54:07','2025-08-14 04:54:07');
/*!40000 ALTER TABLE `institutions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_07_30_004831_create_video_submissions_table',1),(5,'2025_08_01_020414_create_admins_table',1),(6,'2025_08_01_020421_create_evaluations_table',1),(7,'2025_08_05_113900_create_video_assignments_table',1),(8,'2025_08_05_114000_modify_evaluations_table',1),(9,'2025_08_05_144857_add_role_to_admins_table',1),(10,'2025_08_08_134229_update_evaluations_scoring_system',1),(11,'2025_08_12_113402_add_qualification_status_to_evaluations_table',1),(13,'2025_08_14_132502_create_institutions_table',2),(14,'2025_08_27_092333_modify_video_assignments_unique_constraint',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('TEeDvwwbX3eiMSaSwJWtbAVwTk5m1j5BxakiANuX','admin','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiY2NIOGdTRnBnNnNjQVpMRzJScE81Qm56VHU3d0hIQll2blJNOXVEMiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUyOiJsb2dpbl9hZG1pbl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtzOjU6ImFkbWluIjt9',1756764947);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Test User','test@example.com','2025-08-27 02:14:45','$2y$12$nRd.TjX0GNDTEgClNCCwVuuEnl292rSRJ.siXOUlAQ1eSH4.71sCi','T8OlMuih13','2025-08-27 02:14:45','2025-08-27 02:14:45');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_assignments`
--

DROP TABLE IF EXISTS `video_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `video_submission_id` bigint unsigned NOT NULL,
  `admin_id` bigint unsigned NOT NULL,
  `status` enum('assigned','in_progress','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_video_admin_assignment` (`video_submission_id`,`admin_id`),
  KEY `video_assignments_admin_id_status_index` (`admin_id`,`status`),
  CONSTRAINT `video_assignments_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_assignments_video_submission_id_foreign` FOREIGN KEY (`video_submission_id`) REFERENCES `video_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_assignments`
--

LOCK TABLES `video_assignments` WRITE;
/*!40000 ALTER TABLE `video_assignments` DISABLE KEYS */;
INSERT INTO `video_assignments` VALUES (3,3,9,'completed','2025-09-01 00:01:53','2025-09-01 00:09:10','2025-09-01 00:09:26','2025-09-01 00:01:53','2025-09-01 00:09:26'),(4,3,12,'completed','2025-09-01 00:01:53','2025-09-01 00:12:13','2025-09-01 00:12:23','2025-09-01 00:01:53','2025-09-01 00:12:23'),(5,1,11,'completed','2025-09-01 00:01:53','2025-09-01 00:11:20','2025-09-01 00:11:33','2025-09-01 00:01:53','2025-09-01 00:11:33'),(6,1,14,'completed','2025-09-01 00:01:53','2025-09-01 00:12:43','2025-09-01 00:12:52','2025-09-01 00:01:53','2025-09-01 00:12:52'),(7,2,8,'completed','2025-09-01 00:01:53','2025-09-01 00:07:55','2025-09-01 00:08:17','2025-09-01 00:01:53','2025-09-01 00:08:17'),(8,2,10,'completed','2025-09-01 00:01:53','2025-09-01 00:10:28','2025-09-01 00:10:45','2025-09-01 00:01:53','2025-09-01 00:10:45'),(9,4,8,'completed','2025-09-01 00:05:35',NULL,'2025-09-01 00:08:56','2025-09-01 00:05:35','2025-09-01 00:08:56'),(10,4,9,'completed','2025-09-01 00:05:35',NULL,'2025-09-01 00:09:40','2025-09-01 00:05:35','2025-09-01 00:09:40'),(11,5,10,'completed','2025-09-01 00:07:15',NULL,'2025-09-01 00:10:58','2025-09-01 00:07:15','2025-09-01 00:10:58'),(12,5,11,'completed','2025-09-01 00:07:15',NULL,'2025-09-01 00:11:45','2025-09-01 00:07:15','2025-09-01 00:11:45');
/*!40000 ALTER TABLE `video_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_submissions`
--

DROP TABLE IF EXISTS `video_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `institution_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_name_korean` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_name_english` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age` int NOT NULL,
  `parent_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_file_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_file_size` bigint NOT NULL,
  `unit_topic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `privacy_consent` tinyint(1) NOT NULL DEFAULT '0',
  `privacy_consent_at` timestamp NULL DEFAULT NULL,
  `notification_sent` tinyint(1) NOT NULL DEFAULT '0',
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  `status` enum('uploaded','processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'uploaded',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_submissions`
--

LOCK TABLES `video_submissions` WRITE;
/*!40000 ALTER TABLE `video_submissions` DISABLE KEYS */;
INSERT INTO `video_submissions` VALUES (1,'서울특별시 강동구','GrapeSEED Online','Test','김디디','DeeDee','예비 초 1학년',7,'김 토니','010-9522-0584','videos/1756684510_4V0Vx98SN9.mov','결선_leeyua0522.mov','mov',190276349,'Unit 7 My mike',1,'2025-08-31 23:53:58',0,NULL,'uploaded','2025-08-31 23:55:19','2025-08-31 23:55:19'),(2,'부산광역시 금정구','거창 세종프랜즈클럽외국어교습소','Test','김피트','Kim Ttu Bi','예비 초 1학년',7,'피트맘','010-9522-0584','videos/1756684662_wBK6DswzNb.mp4','test_aws_t4MbfwO (2).mp4','mp4',9224866,'Unit 5 My mike',1,'2025-08-31 23:57:09',0,NULL,'uploaded','2025-08-31 23:57:46','2025-08-31 23:57:46'),(3,'서울특별시 강남구','경기 군포 사랑유치원','Test','김철수','Pete','예비 초 1학년',7,'김 토니','010-9522-0584','videos/1756684720_YaV3vV2eFy.mp4','결선_seoyeon_grapeseed.mp4','mp4',671153364,'Unit 7 My mike',1,'2025-08-31 23:57:56',0,NULL,'uploaded','2025-09-01 00:01:14','2025-09-01 00:01:14'),(4,'서울특별시 강남구','광양 혜화유치원','Test','김뚜비','Pete','예비 초 1학년',7,'김 토니','010-9522-0584','videos/1756684979_RZAdSWt8VY.mp4','GMT20250218-094842_Recording_1920x1080.mp4','mp4',493735058,'Unit 5 My Watemelon',1,'2025-09-01 00:02:22',0,NULL,'uploaded','2025-09-01 00:04:48','2025-09-01 00:04:48'),(5,'서울특별시 강남구','구미 라온유치원','감사반','김아더','Arthor','예비 초 1학년',7,'이청아','010-9522-0584','videos/1756685205_dtj2dlfJG5.mp4','test_aws_t4MbfwO (2).mp4','mp4',9224866,'Unit 5 My old friend',1,'2025-09-01 00:06:10',0,NULL,'uploaded','2025-09-01 00:06:45','2025-09-01 00:06:45');
/*!40000 ALTER TABLE `video_submissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-03  8:24:13
