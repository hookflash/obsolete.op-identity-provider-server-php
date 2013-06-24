/*
Navicat MySQL Data Transfer

Source Server         : lockbox
Source Server Version : 50531
Source Host           : ec2-23-21-25-158.compute-1.amazonaws.com:3306
Source Database       : provider_db

Target Server Type    : MYSQL
Target Server Version : 50531
File Encoding         : 65001

Date: 2013-06-21 08:03:57
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `api_error_log`
-- ----------------------------
DROP TABLE IF EXISTS `api_error_log`;
CREATE TABLE `api_error_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_dump` text COLLATE utf8_bin,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
-- Records of api_error_log
-- ----------------------------

-- ----------------------------
-- Table structure for `api_event_log`
-- ----------------------------
DROP TABLE IF EXISTS `api_event_log`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5962 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
-- Records of api_event_log
-- ----------------------------

-- ----------------------------
-- Table structure for `avatar`
-- ----------------------------
DROP TABLE IF EXISTS `avatar`;
CREATE TABLE `avatar` (
  `avatar_id` int(10) NOT NULL AUTO_INCREMENT,
  `identity_id` int(10) NOT NULL,
  `name` varchar(64) COLLATE utf8_bin DEFAULT NULL,
  `url` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `width` int(5) DEFAULT NULL,
  `height` int(5) DEFAULT NULL,
  PRIMARY KEY (`avatar_id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
-- Records of avatar
-- ----------------------------

-- ----------------------------
-- Table structure for `federated`
-- ----------------------------
DROP TABLE IF EXISTS `federated`;
CREATE TABLE `federated` (
  `federated_id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `identifier` varchar(64) COLLATE utf8_bin NOT NULL,
  `password_hash` varchar(40) COLLATE utf8_bin NOT NULL,
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
  PRIMARY KEY (`federated_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1206 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
-- Records of federated
-- ----------------------------

-- ----------------------------
-- Table structure for `legacy_email`
-- ----------------------------
DROP TABLE IF EXISTS `legacy_email`;
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
  PRIMARY KEY (`email_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1031 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
-- Records of legacy_email
-- ----------------------------

-- ----------------------------
-- Table structure for `legacy_oauth`
-- ----------------------------
DROP TABLE IF EXISTS `legacy_oauth`;
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
  `lockbox_half_key_encrypted` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `token` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `secret` varchar(100) COLLATE utf8_bin DEFAULT NULL,
  `updated` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`oauth_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1034 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
-- Records of legacy_oauth
-- ----------------------------

-- ----------------------------
-- Table structure for `legacy_phone`
-- ----------------------------
DROP TABLE IF EXISTS `legacy_phone`;
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
  PRIMARY KEY (`phone_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1010 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
-- Records of legacy_phone
-- ----------------------------

-- ----------------------------
-- Table structure for `user`
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` int(10) NOT NULL AUTO_INCREMENT,
  `updated` bigint(10) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1286 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- ----------------------------
-- Records of user
-- ----------------------------
