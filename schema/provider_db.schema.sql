-- MySQL dump 10.13  Distrib 5.5.31, for Linux (x86_64)
--
-- Host: localhost    Database: provider_db
-- ------------------------------------------------------
-- Server version	5.5.31

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `provider_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `provider_db` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `provider_db`;

--
-- Table structure for table `api_error_log`
--

DROP TABLE IF EXISTS `api_error_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_error_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_dump` text COLLATE utf8_bin,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_event_log`
--

DROP TABLE IF EXISTS `api_event_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_event_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime DEFAULT NULL,
  `ip_address` varchar(16) COLLATE utf8_bin DEFAULT NULL,
  `http_client` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `error_code` int(11) DEFAULT NULL,
  `message` text COLLATE utf8_bin,
  `session_id` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `session_dump` text COLLATE utf8_bin,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39793 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `avatar`
--

DROP TABLE IF EXISTS `avatar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avatar` (
  `avatar_id` int(10) NOT NULL AUTO_INCREMENT,
  `identity_id` int(10) NOT NULL,
  `name` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `width` int(5) DEFAULT NULL,
  `height` int(5) DEFAULT NULL,
  PRIMARY KEY (`avatar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `federated`
--

DROP TABLE IF EXISTS `federated`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `federated` (
  `federated_id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `identifier` varchar(64) COLLATE utf8_bin NOT NULL,
  `password_hash` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `display_name` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `profile_url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `vprofile_url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `secret_encrypted` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `secret_salt` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `server_password_salt` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `lockbox_half_key_encrypted` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `updated` bigint(10) DEFAULT NULL,
  `relogin_key` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `relogin_expires` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`federated_id`),
  KEY `FK_user_id_cascade_federated` (`user_id`),
  CONSTRAINT `FK_user_id_cascade_federated` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1530 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `legacy_email`
--

DROP TABLE IF EXISTS `legacy_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_email` (
  `email_id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `identifier` varchar(64) COLLATE utf8_bin NOT NULL,
  `password_hash` varchar(40) COLLATE utf8_bin NOT NULL,
  `display_name` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `profile_url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `vprofile_url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `secret_encrypted` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `secret_salt` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `server_password_salt` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `account_accessed` bit(1) NOT NULL,
  `pin_validated` bit(1) NOT NULL,
  `temporary_pin` int(6) DEFAULT NULL,
  `pin_expiry` bigint(10) DEFAULT NULL,
  `pin_daily_generation_counter` int(1) NOT NULL DEFAULT '0',
  `next_valid_pin_generation_time` bigint(10) DEFAULT NULL,
  `lockbox_half_key_encrypted` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `updated` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`email_id`),
  KEY `FK_user_id_cascade` (`user_id`),
  CONSTRAINT `FK_user_id_cascade` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1031 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `legacy_oauth`
--

DROP TABLE IF EXISTS `legacy_oauth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_oauth` (
  `oauth_id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `provider_type` varchar(10) COLLATE utf8_bin NOT NULL,
  `identifier` varchar(64) COLLATE utf8_bin NOT NULL,
  `provider_username` varchar(64) COLLATE utf8_bin NOT NULL,
  `full_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `profile_url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `avatar_url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `secret_encrypted` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `secret_salt` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `server_password_salt` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `token` varchar(350) COLLATE utf8_bin DEFAULT NULL,
  `secret` varchar(350) COLLATE utf8_bin DEFAULT NULL,
  `lockbox_half_key_encrypted` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `updated` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`oauth_id`),
  KEY `FK_user_id_cascade_oauth` (`user_id`),
  CONSTRAINT `FK_user_id_cascade_oauth` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1148 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `legacy_phone`
--

DROP TABLE IF EXISTS `legacy_phone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_phone` (
  `phone_id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `identifier` varchar(64) COLLATE utf8_bin NOT NULL,
  `password_hash` varchar(40) COLLATE utf8_bin NOT NULL,
  `display_name` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `profile_url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `vprofile_url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `secret_encrypted` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `secret_salt` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `server_password_salt` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `account_accessed` bit(1) NOT NULL,
  `pin_validated` bit(1) NOT NULL,
  `temporary_pin` int(6) DEFAULT NULL,
  `pin_expiry` bigint(10) DEFAULT NULL,
  `pin_daily_generation_counter` int(1) NOT NULL DEFAULT '0',
  `next_valid_pin_generation_time` bigint(10) DEFAULT NULL,
  `lockbox_half_key_encrypted` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `updated` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`phone_id`),
  KEY `FK_user_id_cascade_phone` (`user_id`),
  CONSTRAINT `FK_user_id_cascade_phone` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(10) NOT NULL AUTO_INCREMENT,
  `updated` bigint(10) DEFAULT NULL,
  `appid` varchar(200) COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1724 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-10-17 23:57:31
