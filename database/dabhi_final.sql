-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: localhost    Database: dabhi_final
-- ------------------------------------------------------
-- Server version	9.1.0

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
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `landmark` varchar(150) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `country` varchar(60) NOT NULL DEFAULT 'India',
  `address_type` enum('home','office','other') NOT NULL DEFAULT 'home',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_addr_user` (`user_id`),
  KEY `idx_addr_pincode` (`pincode`),
  CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (2,4,'Amit Shah','9898989898','Opp Swaminarayan Temple',NULL,NULL,'Junagadh','Gujarat','362001','India','home',0,'2026-09-20 10:00:14');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` int unsigned DEFAULT NULL,
  `old_value` json DEFAULT NULL,
  `new_value` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_user` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,5,'product_toggled','product',1,NULL,NULL,'::1','2026-09-20 10:37:01'),(2,5,'product_toggled','product',2,NULL,NULL,'::1','2026-09-20 10:37:04');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `build_box_items`
--

DROP TABLE IF EXISTS `build_box_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `build_box_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `build_box_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_bbi_box` (`build_box_id`),
  KEY `fk_bbi_variant` (`variant_id`),
  CONSTRAINT `fk_bbi_box` FOREIGN KEY (`build_box_id`) REFERENCES `build_boxes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bbi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `build_box_items`
--

LOCK TABLES `build_box_items` WRITE;
/*!40000 ALTER TABLE `build_box_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `build_box_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `build_boxes`
--

DROP TABLE IF EXISTS `build_boxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `build_boxes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `box_name` varchar(120) NOT NULL,
  `weight_limit_grams` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_bb_user` (`user_id`),
  CONSTRAINT `fk_bb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `build_boxes`
--

LOCK TABLES `build_boxes` WRITE;
/*!40000 ALTER TABLE `build_boxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `build_boxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int unsigned NOT NULL,
  `variant_id` int unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `box_group_id` char(36) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ci_cart` (`cart_id`),
  KEY `idx_ci_box_group` (`box_group_id`),
  KEY `fk_ci_variant` (`variant_id`),
  CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES (1,3,1,2,NULL,'2026-09-20 09:58:34'),(2,4,1,2,NULL,'2026-09-20 09:58:51'),(3,5,1,2,NULL,'2026-09-20 10:00:14');
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `session_token` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cart_user` (`user_id`),
  KEY `idx_cart_session` (`session_token`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES (1,NULL,'3e34681cc35d2c28f416561c245a55e47bc27d2f63a84eaa9d7a6204c33d9031','2026-09-20 09:58:21','2026-09-20 09:58:21'),(2,NULL,'5696ad3eb35a2f41c977e5d0695b17c7f63cdf9ad4bb36fa45152d7341056974','2026-09-20 09:58:21','2026-09-20 09:58:21'),(3,NULL,'f40d2a9ec06d39b3cba4e40a4ed3de1bc4d03db09d2230e16e96cc9e9de15220','2026-09-20 09:58:34','2026-09-20 09:58:34'),(4,NULL,'f948ea9ef8f85a2714c27ce9ef03e3678b748623479f724d3ff80a5f8fa1798f','2026-09-20 09:58:51','2026-09-20 09:58:51'),(5,NULL,'03fe6a40b7cd18af2a10a970acbbcea78900277c200b3da90b2cc17382a8ad1b','2026-09-20 10:00:14','2026-09-20 10:00:14'),(6,NULL,'cd8d747b3325c31dfd69a574cadafbf392553e3ae0b7903899c7462466cc2ee8','2026-09-20 10:00:38','2026-09-20 10:00:38'),(7,NULL,'c7c212328864fe2795d02c0c5bc7064fbf2943acde5471097878e8d70d887a5e','2026-09-20 10:00:42','2026-09-20 10:00:42'),(8,NULL,'9e9e4144ceae1d41956db744168a0975727ce7f5682a5535a8e5dc65d31c8a45','2026-09-20 10:00:47','2026-09-20 10:00:47'),(9,NULL,'330f639738bfc513f51d2888b41b95e95b85c07836a09ddc0145ee342e9b9de3','2026-09-20 10:01:32','2026-09-20 10:01:32'),(10,NULL,'8275c1f039fa7fc6fb7ba18ffa40bbe4fd10047cd4c1b1f063a4e1743b80fac0','2026-09-20 10:31:59','2026-09-20 10:31:59'),(11,5,NULL,'2026-09-20 10:37:13','2026-09-20 10:37:13'),(12,NULL,'a0ca35321d9d3c2496843283e0fa217249242dd07980bbeecb82ee3f2f8de09c','2026-09-20 10:38:36','2026-09-20 10:38:36'),(13,NULL,'d240c61f7ce358f8d7aadf8faf5bb2417eb8f05c7756a6bb36f2042128c13871','2026-09-20 11:01:59','2026-09-20 11:01:59'),(14,NULL,'3644beabecc797df0c478724e25afe9e3a7cf30388e3c1163d35b2d357bb668f','2026-09-20 11:03:36','2026-09-20 11:03:36'),(15,NULL,'9c06126802a446f9d252d0393acca1d5002ab11cff8f3e9f18acf838cace3eb4','2026-09-20 11:04:37','2026-09-20 11:04:37'),(16,NULL,'866e9f91ef3cb610c75d65888fbb6cd09ccec402a94125c6793557810facd0f3','2026-09-20 11:04:55','2026-09-20 11:04:55'),(17,NULL,'30de29b40ba5f149136571896f5388065881c9221f3a7f66e4f7dc08cffd13db','2026-09-20 11:05:06','2026-09-20 11:05:06'),(18,NULL,'cd92355bb41e85958964cbd3f7513c6e0c4356d18f9690d739c8c67ef409bfc8','2026-09-20 11:05:14','2026-09-20 11:05:14'),(19,NULL,'2373f1dba7b6c743947a7ba149711d5ed4639a791dacb4dcc742815ec3f53f88','2026-09-20 11:05:25','2026-09-20 11:05:25'),(20,NULL,'1476804d831e5edec1928325a48042fcdce8d1f05567f265c154bdf4e8b19631','2026-09-20 11:05:34','2026-09-20 11:05:34');
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` text,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint NOT NULL DEFAULT '0',
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_cat_parent` (`parent_id`),
  CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,NULL,'Chikki','chikki',NULL,NULL,1,1,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(2,NULL,'Sweets','sweets',NULL,NULL,1,2,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(3,NULL,'Namkeen','namkeen',NULL,NULL,1,3,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(4,NULL,'Combos','combos',NULL,NULL,1,4,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(5,NULL,'Gifts','gifts',NULL,NULL,1,5,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(6,1,'Gud Chikki','gud-chikki',NULL,NULL,1,1,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(7,1,'Sugar Chikki','sugar-chikki',NULL,NULL,1,2,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(8,1,'Til Chikki','til-chikki',NULL,NULL,1,3,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(9,1,'Dryfruit Chikki','dryfruit-chikki',NULL,NULL,1,4,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usage`
--

DROP TABLE IF EXISTS `coupon_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usage` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `order_id` int unsigned NOT NULL,
  `used_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_cu_coupon` (`coupon_id`),
  KEY `fk_cu_user` (`user_id`),
  CONSTRAINT `fk_cu_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usage`
--

LOCK TABLES `coupon_usage` WRITE;
/*!40000 ALTER TABLE `coupon_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_uses`
--

DROP TABLE IF EXISTS `coupon_uses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_uses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `order_id` int unsigned NOT NULL,
  `used_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_cu_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_uses`
--

LOCK TABLES `coupon_uses` WRITE;
/*!40000 ALTER TABLE `coupon_uses` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_uses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('flat','percent') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit_total` int unsigned DEFAULT NULL,
  `usage_limit_per_user` smallint unsigned DEFAULT NULL,
  `valid_from` datetime NOT NULL,
  `valid_to` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gst_settings`
--

DROP TABLE IF EXISTS `gst_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gst_settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `business_name` varchar(180) NOT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `business_state` varchar(60) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `is_gst_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gst_settings`
--

LOCK TABLES `gst_settings` WRITE;
/*!40000 ALTER TABLE `gst_settings` DISABLE KEYS */;
INSERT INTO `gst_settings` VALUES (1,'Dabhi Chikki','24AAJDUDHEJ5555','Gujarat','Rajkot, Gujarat, India',1,'2026-09-20 10:51:09');
/*!40000 ALTER TABLE `gst_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_batches`
--

DROP TABLE IF EXISTS `inventory_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int unsigned NOT NULL,
  `batch_number` varchar(60) NOT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `quantity_received` int unsigned NOT NULL,
  `quantity_remaining` int unsigned NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `supplier_name` varchar(150) DEFAULT NULL,
  `received_by` int unsigned DEFAULT NULL,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_batch` (`variant_id`,`batch_number`),
  KEY `idx_batch_expiry` (`expiry_date`),
  KEY `idx_batch_variant_remaining` (`variant_id`,`quantity_remaining`),
  KEY `fk_batch_received_by` (`received_by`),
  CONSTRAINT `fk_batch_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_batch_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_batches`
--

LOCK TABLES `inventory_batches` WRITE;
/*!40000 ALTER TABLE `inventory_batches` DISABLE KEYS */;
INSERT INTO `inventory_batches` VALUES (1,1,'BATCH-MAN-250',NULL,'2027-01-04',100,98,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(2,2,'BATCH-MAN-500',NULL,'2027-01-04',100,99,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(3,3,'BATCH-MAN-1KG',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(4,4,'BATCH-TIL-250',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(5,5,'BATCH-TIL-500',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(6,6,'BATCH-TIL-1KG',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(7,7,'BATCH-DAL-250',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(8,8,'BATCH-DAL-500',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(9,9,'BATCH-DAL-1KG',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(10,10,'BATCH-MIX-250',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(11,11,'BATCH-MIX-500',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory'),(12,12,'BATCH-MIX-1KG',NULL,'2027-01-04',100,100,NULL,NULL,NULL,'2026-09-20 09:55:29','Initial inventory');
/*!40000 ALTER TABLE `inventory_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_counters`
--

DROP TABLE IF EXISTS `invoice_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_counters` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `financial_year` varchar(9) NOT NULL,
  `last_number` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `financial_year` (`financial_year`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_counters`
--

LOCK TABLES `invoice_counters` WRITE;
/*!40000 ALTER TABLE `invoice_counters` DISABLE KEYS */;
INSERT INTO `invoice_counters` VALUES (1,'2026-2027',1);
/*!40000 ALTER TABLE `invoice_counters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `invoice_number` varchar(40) NOT NULL,
  `financial_year` varchar(9) NOT NULL,
  `pdf_path` varchar(255) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  CONSTRAINT `fk_inv_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,5,'DC/26-27/000001','2026-2027','/uploads/invoices/DC_26-27_000001.pdf','2026-09-20 11:04:21');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications_log`
--

DROP TABLE IF EXISTS `notifications_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `channel` enum('email','whatsapp','sms') NOT NULL,
  `event_type` varchar(60) NOT NULL,
  `recipient` varchar(190) NOT NULL,
  `status` enum('sent','failed','skipped_disabled') NOT NULL,
  `response_message` text,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_order` (`order_id`),
  CONSTRAINT `fk_notif_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications_log`
--

LOCK TABLES `notifications_log` WRITE;
/*!40000 ALTER TABLE `notifications_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `product_id` int unsigned DEFAULT NULL,
  `variant_id` int unsigned NOT NULL,
  `batch_id` int unsigned DEFAULT NULL,
  `product_name_snapshot` varchar(180) NOT NULL,
  `variant_label_snapshot` varchar(60) NOT NULL,
  `quantity` int unsigned NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `gst_rate_percent` decimal(4,2) NOT NULL,
  `gst_amount` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `box_group_id` char(36) DEFAULT NULL,
  `product_name` varchar(180) DEFAULT NULL,
  `variant_label` varchar(60) DEFAULT NULL,
  `weight_grams` int unsigned DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `gst_rate` decimal(4,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`),
  KEY `idx_oi_variant` (`variant_id`),
  KEY `fk_oi_batch` (`batch_id`),
  CONSTRAINT `fk_oi_batch` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,2,1,1,NULL,'Mandvi Chikki','250 g',2,120.00,5.00,12.00,240.00,NULL,'Mandvi Chikki','250 g',250,'MAN-250',150.00,120.00,5.00),(4,5,1,2,2,'Mandvi Chikki','500g',1,200.00,5.00,10.00,200.00,NULL,'Mandvi Chikki','500g',500,'MAN-500',250.00,200.00,5.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_history`
--

DROP TABLE IF EXISTS `order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `status` varchar(30) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `changed_by` int unsigned DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_osh_order` (`order_id`),
  KEY `fk_osh_user` (`changed_by`),
  CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_osh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_history`
--

LOCK TABLES `order_status_history` WRITE;
/*!40000 ALTER TABLE `order_status_history` DISABLE KEYS */;
INSERT INTO `order_status_history` VALUES (1,2,'placed','Order placed by customer.',NULL,'2026-09-20 10:00:14'),(4,5,'placed','Order placed',NULL,'2026-09-20 11:04:21'),(5,5,'shipped','',5,'2026-09-20 11:13:00');
/*!40000 ALTER TABLE `order_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(30) NOT NULL,
  `user_id` int unsigned NOT NULL,
  `guest_email` varchar(190) DEFAULT NULL,
  `status` enum('placed','confirmed','packed','shipped','out_for_delivery','delivered','cancelled','return_requested','returned') NOT NULL DEFAULT 'placed',
  `payment_method` enum('cod','online') NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded','partially_refunded') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `coupon_id` int unsigned DEFAULT NULL,
  `shipping_charge` decimal(8,2) NOT NULL DEFAULT '0.00',
  `cod_charge` decimal(8,2) NOT NULL DEFAULT '0.00',
  `cgst_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sgst_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `igst_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `total_weight_grams` int unsigned NOT NULL,
  `shipping_address_id` int unsigned DEFAULT NULL,
  `billing_address_id` int unsigned DEFAULT NULL,
  `customer_notes` varchar(255) DEFAULT NULL,
  `placed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ship_name` varchar(150) DEFAULT NULL,
  `ship_phone` varchar(20) DEFAULT NULL,
  `ship_line1` varchar(255) DEFAULT NULL,
  `ship_line2` varchar(255) DEFAULT NULL,
  `ship_city` varchar(100) DEFAULT NULL,
  `ship_state` varchar(100) DEFAULT NULL,
  `ship_pincode` varchar(10) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `gst_amount` decimal(10,2) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_order_user` (`user_id`),
  KEY `idx_order_status` (`status`),
  KEY `idx_order_placed_at` (`placed_at`),
  KEY `fk_order_ship_addr` (`shipping_address_id`),
  KEY `fk_order_bill_addr` (`billing_address_id`),
  KEY `fk_order_coupon` (`coupon_id`),
  CONSTRAINT `fk_order_bill_addr` FOREIGN KEY (`billing_address_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_order_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_ship_addr` FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (2,'DC-2026-000001',4,'amit@example.com','placed','cod','pending',240.00,0.00,NULL,60.00,0.00,0.00,0.00,0.00,300.00,500,2,2,NULL,'2026-09-20 10:00:14','2026-09-20 10:00:14','Amit Shah','9898989898',NULL,NULL,'Junagadh','Gujarat','362001',300.00,NULL,NULL),(5,'DC-2026-D70383',5,NULL,'shipped','cod','pending',200.00,0.00,NULL,45.00,0.00,5.00,5.00,0.00,245.00,500,NULL,NULL,NULL,'2026-09-20 11:04:21','2026-09-20 11:13:00','Test Customer','9876543210','123 Test Marg',NULL,'Ahmedabad','Gujarat','380001',245.00,NULL,NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_verifications`
--

DROP TABLE IF EXISTS `otp_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_verifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(190) NOT NULL,
  `purpose` enum('login','signup','checkout','password_reset') NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_otp_identifier` (`identifier`,`purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_verifications`
--

LOCK TABLES `otp_verifications` WRITE;
/*!40000 ALTER TABLE `otp_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_user` (`user_id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `gateway` enum('cashfree','cod') NOT NULL,
  `gateway_order_id` varchar(100) DEFAULT NULL,
  `gateway_payment_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(5) NOT NULL DEFAULT 'INR',
  `status` enum('created','pending','success','failed','refunded') NOT NULL DEFAULT 'created',
  `raw_response` longtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pay_order` (`order_id`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(80) NOT NULL,
  `label` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL,
  `applies_to_role` enum('admin','employee','both') NOT NULL DEFAULT 'both',
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_financials','View financial reports & revenue','financial','admin','Sales totals, revenue, profit margins'),(2,'manage_gst','Manage GST settings & filings','financial','admin','GST rate config and GST reports'),(3,'view_product_analytics','View product performance analytics','analytics','both','Best sellers, slow movers'),(4,'manage_products','Add / edit products','products','both',''),(5,'manage_prices','Edit product pricing','products','admin','Separate from general product editing'),(6,'manage_categories','Add / edit / toggle categories','products','admin',''),(7,'manage_inventory','Receive stock & manage batches','inventory','both',''),(8,'view_expiry_alerts','View near-expiry stock alerts','inventory','both',''),(9,'manage_orders','View & update order status','orders','both',''),(10,'print_shipping_labels','Generate / print shipping labels','orders','both',''),(11,'manage_shipping_rules','Manage shipping zones & rates','settings','admin',''),(12,'manage_notifications','Toggle email/WhatsApp/SMS settings','settings','admin',''),(13,'manage_payment_settings','Toggle COD / configure Cashfree','settings','admin',''),(14,'manage_coupons','Create & manage discount coupons','marketing','both',''),(15,'manage_reviews','Approve / reject product reviews','marketing','both',''),(16,'generate_reports','Generate custom/weekly/monthly reports','financial','admin',''),(17,'manage_employees','Add employees & reset their passwords','users','admin',''),(18,'manage_admins','Add admins & reset their passwords','users','admin','Superadmin always has this; admin needs it explicitly granted'),(19,'view_audit_log','View the admin/employee activity log','users','admin','');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pincode_zones`
--

DROP TABLE IF EXISTS `pincode_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pincode_zones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pincode` varchar(10) NOT NULL,
  `zone_id` int unsigned NOT NULL,
  `is_serviceable` tinyint(1) NOT NULL DEFAULT '1',
  `cod_available` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `pincode` (`pincode`),
  KEY `idx_pz_zone` (`zone_id`),
  CONSTRAINT `fk_pz_zone` FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pincode_zones`
--

LOCK TABLES `pincode_zones` WRITE;
/*!40000 ALTER TABLE `pincode_zones` DISABLE KEYS */;
INSERT INTO `pincode_zones` VALUES (1,'360001',1,1,1),(2,'360002',1,1,1),(3,'360003',1,1,1),(4,'360004',1,1,1),(5,'360005',1,1,1),(6,'360575',1,1,1),(7,'380001',1,1,1),(8,'380015',1,1,1),(9,'390001',1,1,1),(10,'395001',1,1,1);
/*!40000 ALTER TABLE `pincode_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(150) DEFAULT NULL,
  `sort_order` smallint NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_pimg_product` (`product_id`),
  CONSTRAINT `fk_pimg_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,1,'assets/images/products/mandvi-chikki-1.jpg','Mandvi Chikki',1,1),(2,1,'assets/images/products/mandvi-chikki-2.jpg','Mandvi Chikki',2,0),(3,2,'assets/images/products/til-chikki-1.jpg','TIL Chikki',1,1),(4,2,'assets/images/products/til-chikki-2.jpg','TIL Chikki',2,0),(5,3,'assets/images/products/daliya-chikki-1.jpg','Daliya Chikki',1,1),(6,3,'assets/images/products/daliya-chikki-2.jpg','Daliya Chikki',2,0),(7,4,'assets/images/products/3-mix-chikki-1.jpg','3 Mix Chikki',1,1),(8,4,'assets/images/products/3-mix-chikki-2.jpg','3 Mix Chikki',2,0);
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `sku` varchar(60) NOT NULL,
  `weight_grams` int unsigned NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `reorder_level` int unsigned NOT NULL DEFAULT '10',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_variant_product` (`product_id`),
  CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (1,1,'MAN-250',250,150.00,120.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(2,1,'MAN-500',500,250.00,200.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(3,1,'MAN-1KG',1000,460.00,380.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(4,2,'TIL-250',250,150.00,120.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(5,2,'TIL-500',500,250.00,200.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(6,2,'TIL-1KG',1000,460.00,380.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(7,3,'DAL-250',250,150.00,120.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(8,3,'DAL-500',500,250.00,200.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(9,3,'DAL-1KG',1000,460.00,380.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(10,4,'MIX-250',250,180.00,150.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(11,4,'MIX-500',500,300.00,250.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(12,4,'MIX-1KG',1000,540.00,450.00,10,1,'2026-09-20 09:55:29','2026-09-20 09:55:29');
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `name` varchar(180) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `short_description` varchar(300) DEFAULT NULL,
  `description` text,
  `hsn_code` varchar(10) DEFAULT NULL,
  `gst_rate_percent` decimal(4,2) NOT NULL DEFAULT '5.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_prod_category` (`category_id`),
  KEY `fk_prod_created_by` (`created_by`),
  FULLTEXT KEY `ftx_prod_search` (`name`,`short_description`,`description`),
  CONSTRAINT `fk_prod_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_prod_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Mandvi Chikki','mandvi-chikki','Classic groundnut chikki made with pure jaggery — crunchy, sweet, and irresistible.','Our Mandvi Chikki is crafted from the finest groundnuts and pure sugarcane jaggery. No added sugar, no preservatives. Every bite is a crisp, golden celebration of tradition. Perfect as an everyday snack or gifting option.','1704',5.00,1,1,NULL,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 10:51:09'),(2,1,'TIL Chikki','til-chikki','Sesame seed chikki with pure jaggery — nutty, fragrant, and wholesome.','Our TIL Chikki combines toasted white sesame seeds with rich jaggery syrup, pressed into perfectly crisp bars. Rich in calcium and iron, this traditional snack is as nutritious as it is delicious.','1704',5.00,1,1,NULL,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 10:51:09'),(3,1,'Daliya Chikki','daliya-chikki','Broken wheat chikki with jaggery — hearty, wholesome, and uniquely satisfying.','Daliya Chikki is a unique take on the classic chikki, made with roasted broken wheat (daliya) and pure jaggery. High in fibre and energy, it is the perfect guilt-free snack to power through your day.','1704',5.00,1,1,NULL,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29'),(4,1,'3 Mix Chikki','3-mix-chikki','A delightful blend of Mandvi, Til, and Coconut Crush — three flavours in every bar.','Our signature 3 Mix Chikki brings together the goodness of groundnuts, sesame seeds, and coconut crush in one perfectly balanced bar. A crowd favourite and the ideal introduction to Dabhi Chikki. Great for gifting!','1704',5.00,1,1,NULL,NULL,NULL,'2026-09-20 09:55:29','2026-09-20 09:55:29');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `order_item_id` int unsigned DEFAULT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text,
  `image_path` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_review_product` (`product_id`),
  KEY `fk_review_user` (`user_id`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_rating` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (2,1,4,NULL,5,'Best peanut chikki in Gujarat! The jaggery flavor is pure and authentic, not overly sweet. Very crunchy.',NULL,1,'2026-09-14 10:51:28'),(3,1,5,NULL,5,'Ordered a 1kg box. Crisp snap, freshly roasted groundnuts. Reminds me of traditional winter taste!',NULL,1,'2026-09-12 10:51:28'),(4,1,4,NULL,4,'Quality is top notch. Delivery was fast to Ahmedabad.',NULL,1,'2026-09-15 10:51:28'),(5,2,5,NULL,5,'White sesame chikki is superb. Soft bite yet crunchy, pure desi jaggery aroma. High quality sesame.',NULL,1,'2026-09-16 10:51:28'),(6,2,4,NULL,5,'Must-have for winters! Rich in calcium and so delicious.',NULL,1,'2026-09-15 10:51:28'),(7,3,4,NULL,5,'Very light, roasted gram crunch is delightful. Perfect healthy tea-time snack.',NULL,1,'2026-09-07 10:51:28'),(8,3,5,NULL,4,'Super crispy and fresh. Kids loved it.',NULL,1,'2026-09-15 10:51:28'),(9,4,4,NULL,5,'The 3 Mix chikki is unbeatable! Groundnut, sesame, and coconut blend is pure royalty.',NULL,1,'2026-09-17 10:51:28'),(10,4,5,NULL,5,'Phenomenal taste. The coconut crunch elevates the entire flavor profile. Best signature item!',NULL,1,'2026-09-19 10:51:28'),(11,4,5,NULL,5,'Sent as gifts to relatives in Mumbai. Everyone loved the fresh aroma and packaging.',NULL,1,'2026-09-08 10:51:28');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text,
  `updated_by` int unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `fk_settings_user` (`updated_by`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_theme','light',NULL,'2026-09-20 09:55:29'),(2,'cod_enabled','1',NULL,'2026-09-20 09:55:29'),(3,'online_payment_enabled','1',NULL,'2026-09-20 09:55:29'),(4,'cashfree_mode','test',NULL,'2026-09-20 09:55:29'),(5,'whatsapp_notifications_enabled','0',NULL,'2026-09-20 09:55:29'),(6,'sms_notifications_enabled','0',NULL,'2026-09-20 09:55:29'),(7,'email_notifications_enabled','1',NULL,'2026-09-20 09:55:29'),(8,'free_shipping_threshold','999',NULL,'2026-09-20 09:55:29'),(9,'build_box_weight_limit_grams','2000',NULL,'2026-09-20 09:55:29'),(10,'low_stock_default_threshold','10',NULL,'2026-09-20 09:55:29'),(11,'expiry_alert_days','30',NULL,'2026-09-20 09:55:29'),(12,'site_maintenance_mode','0',NULL,'2026-09-20 09:55:29'),(13,'app_name','Dabhi Chikki',NULL,'2026-09-20 09:55:29'),(14,'app_tagline','Pure Taste Since 2009',NULL,'2026-09-20 09:55:29'),(15,'currency','INR',NULL,'2026-09-20 09:55:29'),(16,'currency_symbol','₹',NULL,'2026-09-20 09:55:29'),(17,'free_shipping_above','999',NULL,'2026-09-20 10:51:09'),(18,'default_shipping_charge','60',NULL,'2026-09-20 09:55:29'),(21,'contact_phone','+91 98765 43210',NULL,'2026-09-20 09:55:29'),(22,'contact_email','orders@dabhichikki.com',NULL,'2026-09-20 09:55:29'),(24,'gst_enabled','1',NULL,'2026-09-20 10:51:09');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_labels`
--

DROP TABLE IF EXISTS `shipping_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_labels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `courier_name` varchar(80) DEFAULT NULL,
  `tracking_number` varchar(80) DEFAULT NULL,
  `label_pdf_path` varchar(255) NOT NULL,
  `generated_by` int unsigned DEFAULT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `fk_label_user` (`generated_by`),
  CONSTRAINT `fk_label_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_label_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_labels`
--

LOCK TABLES `shipping_labels` WRITE;
/*!40000 ALTER TABLE `shipping_labels` DISABLE KEYS */;
INSERT INTO `shipping_labels` VALUES (2,5,'BlueDart Express','TRK691454935','/uploads/labels/label_5.pdf',5,'2026-09-20 11:04:21');
/*!40000 ALTER TABLE `shipping_labels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_rates`
--

DROP TABLE IF EXISTS `shipping_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_rates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` int unsigned NOT NULL,
  `weight_from_grams` int unsigned NOT NULL,
  `weight_to_grams` int unsigned NOT NULL,
  `rate` decimal(8,2) NOT NULL,
  `cod_extra_charge` decimal(8,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `idx_rate_zone_weight` (`zone_id`,`weight_from_grams`,`weight_to_grams`),
  CONSTRAINT `fk_rate_zone` FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_rates`
--

LOCK TABLES `shipping_rates` WRITE;
/*!40000 ALTER TABLE `shipping_rates` DISABLE KEYS */;
INSERT INTO `shipping_rates` VALUES (1,1,0,250,30.00,20.00),(2,1,251,500,45.00,20.00),(3,1,501,1000,65.00,20.00),(4,1,1001,2000,90.00,20.00),(5,2,0,250,50.00,25.00),(6,2,251,500,70.00,25.00),(7,2,501,1000,100.00,25.00),(8,2,1001,2000,140.00,25.00);
/*!40000 ALTER TABLE `shipping_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_zones`
--

DROP TABLE IF EXISTS `shipping_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_zones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_zones`
--

LOCK TABLES `shipping_zones` WRITE;
/*!40000 ALTER TABLE `shipping_zones` DISABLE KEYS */;
INSERT INTO `shipping_zones` VALUES (1,'Gujarat (Local)','Same-state delivery','2026-09-20 09:55:29'),(2,'Rest of India','All other serviceable pincodes','2026-09-20 09:55:29');
/*!40000 ALTER TABLE `shipping_zones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` int unsigned NOT NULL,
  `batch_id` int unsigned DEFAULT NULL,
  `movement_type` enum('purchase_in','sale_out','adjustment_in','adjustment_out','return_in','damage_out') NOT NULL,
  `quantity` int NOT NULL,
  `reference_type` varchar(40) DEFAULT NULL,
  `reference_id` int unsigned DEFAULT NULL,
  `performed_by` int unsigned DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stockmove_variant` (`variant_id`),
  KEY `idx_stockmove_batch` (`batch_id`),
  KEY `fk_stockmove_user` (`performed_by`),
  CONSTRAINT `fk_stockmove_batch` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stockmove_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stockmove_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (3,2,2,'sale_out',1,'order',5,5,'Order DC-2026-D70383','2026-09-20 11:04:21');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_addresses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `label` varchar(50) DEFAULT 'Home',
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `line1` varchar(255) NOT NULL,
  `line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ua_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_permissions`
--

DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `granted_by` int unsigned DEFAULT NULL,
  `granted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_permission` (`user_id`,`permission_id`),
  KEY `fk_up_permission` (`permission_id`),
  KEY `fk_up_granted_by` (`granted_by`),
  CONSTRAINT `fk_up_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_up_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_permissions`
--

LOCK TABLES `user_permissions` WRITE;
/*!40000 ALTER TABLE `user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `role` enum('superadmin','admin','employee','buyer') NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` datetime DEFAULT NULL,
  `phone_verified_at` datetime DEFAULT NULL,
  `must_reset_password` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int unsigned DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`),
  KEY `idx_users_role` (`role`),
  KEY `fk_users_created_by` (`created_by`),
  CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,'d89c310b-62d1-4b49-8d36-72b86b4a86b0','buyer','Amit Shah','amit@example.com','9898989898','',1,NULL,NULL,0,NULL,NULL,'2026-09-20 10:00:14','2026-09-20 10:00:14'),(5,'f9b7c5ad-d59a-4451-b203-73c083e90fa0','superadmin','Jenish','jenish@gmail.com','9537973949','$2y$12$VsfLNUNcEdDyu.j2XXwnxeA46O09t15NEawLQwOh1Mmf6dcfJR6fu',1,NULL,NULL,0,NULL,'2026-09-20 11:06:51','2026-09-04 22:26:17','2026-09-20 11:06:51');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-20 11:58:44
