-- MySQL dump 10.13  Distrib 5.5.31, for Linux (x86_64)
--
-- Host: localhost    Database: hf_password2_db
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
-- Current Database: `hf_password2_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `hf_password2_db` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `hf_password2_db`;

--
-- Table structure for table `agent`
--

DROP TABLE IF EXISTS `agent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agent` (
  `agentId` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(100) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `url` varchar(100) NOT NULL,
  `userAgent` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`agentId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `application`
--

DROP TABLE IF EXISTS `application`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(11) NOT NULL,
  `appId` varchar(40) NOT NULL,
  `name` varchar(40) NOT NULL,
  `sharedSecret` varchar(40) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK5CA405501E364908` (`user`),
  CONSTRAINT `FK5CA405501E364908` FOREIGN KEY (`user`) REFERENCES `user` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `avatar`
--

DROP TABLE IF EXISTS `avatar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avatar` (
  `avatarId` int(11) NOT NULL AUTO_INCREMENT,
  `height` varchar(8) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `url` varchar(100) NOT NULL,
  `width` varchar(8) DEFAULT NULL,
  `identityId` int(11) NOT NULL,
  PRIMARY KEY (`avatarId`),
  KEY `FKAC32C159CFE61F89` (`identityId`),
  CONSTRAINT `FKAC32C159CFE61F89` FOREIGN KEY (`identityId`) REFERENCES `identity` (`identityId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `certificate`
--

DROP TABLE IF EXISTS `certificate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate` (
  `serviceId` int(11) NOT NULL,
  `certificate` longtext,
  `expires` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`serviceId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contact`
--

DROP TABLE IF EXISTS `contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact` (
  `contactId` int(11) NOT NULL AUTO_INCREMENT,
  `contact` varchar(100) DEFAULT NULL,
  `providerId` int(11) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `peer_contactId` int(11) DEFAULT NULL,
  PRIMARY KEY (`contactId`),
  KEY `FK38B72420EE642572` (`peer_contactId`),
  CONSTRAINT `FK38B72420EE642572` FOREIGN KEY (`peer_contactId`) REFERENCES `peer` (`contactId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `finder`
--

DROP TABLE IF EXISTS `finder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` bit(1) NOT NULL,
  `created` bigint(20) DEFAULT NULL,
  `expires` bigint(20) DEFAULT NULL,
  `finderId` varchar(40) NOT NULL,
  `noContacts` int(11) NOT NULL,
  `priority` int(11) DEFAULT NULL,
  `providerId` int(11) DEFAULT NULL,
  `region` varchar(30) DEFAULT NULL,
  `srv` varchar(200) NOT NULL,
  `transport` varchar(100) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `finderBundle_finderId` int(11) DEFAULT NULL,
  `finderBundleXml_finderId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKB40978265E315D0E` (`finderBundleXml_finderId`),
  KEY `FKB4097826B8B33DB2` (`finderBundle_finderId`),
  CONSTRAINT `FKB4097826B8B33DB2` FOREIGN KEY (`finderBundle_finderId`) REFERENCES `finder_bundle` (`finderId`),
  CONSTRAINT `FKB40978265E315D0E` FOREIGN KEY (`finderBundleXml_finderId`) REFERENCES `finder_bundle_xml` (`finderId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `finder_bundle`
--

DROP TABLE IF EXISTS `finder_bundle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finder_bundle` (
  `finderId` int(11) NOT NULL,
  `finderBundle` longtext,
  PRIMARY KEY (`finderId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `finder_bundle_xml`
--

DROP TABLE IF EXISTS `finder_bundle_xml`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finder_bundle_xml` (
  `finderId` int(11) NOT NULL,
  `finderBundle` longtext,
  PRIMARY KEY (`finderId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `finder_config`
--

DROP TABLE IF EXISTS `finder_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finder_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `finderId` varchar(40) NOT NULL,
  `config_key` varchar(32) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `config_value` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hcs_instance`
--

DROP TABLE IF EXISTS `hcs_instance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hcs_instance` (
  `instanceId` int(11) NOT NULL AUTO_INCREMENT,
  `alive` int(11) NOT NULL,
  `ip` varchar(150) NOT NULL,
  `type` int(11) NOT NULL,
  PRIMARY KEY (`instanceId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `identity`
--

DROP TABLE IF EXISTS `identity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `identity` (
  `identityId` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime DEFAULT NULL,
  `feed` varchar(150) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `profile` varchar(150) DEFAULT NULL,
  `proofBundle` longtext,
  `providerId` int(11) NOT NULL,
  `stableId` varchar(64) DEFAULT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `uri` varchar(100) NOT NULL,
  `vProfile` varchar(150) DEFAULT NULL,
  `weight` bigint(20) DEFAULT NULL,
  `contact_contactId` int(11) DEFAULT NULL,
  PRIMARY KEY (`identityId`),
  KEY `FKF7E870BE6DF1054A` (`contact_contactId`),
  CONSTRAINT `FKF7E870BE6DF1054A` FOREIGN KEY (`contact_contactId`) REFERENCES `contact` (`contactId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lockbox_account`
--

DROP TABLE IF EXISTS `lockbox_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lockbox_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accountId` varchar(40) NOT NULL,
  `domain` varchar(50) DEFAULT NULL,
  `hash` varchar(50) DEFAULT NULL,
  `keyLockboxHalf` varchar(100) DEFAULT NULL,
  `updatedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lockbox_content`
--

DROP TABLE IF EXISTS `lockbox_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lockbox_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accountId` varchar(40) NOT NULL,
  `contentKey` varchar(32) NOT NULL,
  `contentValue` longtext NOT NULL,
  `namespaceId` varchar(150) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lockbox_grant`
--

DROP TABLE IF EXISTS `lockbox_grant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lockbox_grant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accountId` varchar(40) NOT NULL,
  `grantId` varchar(40) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `agent_agentId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FKE52EB8DD4B09B779` (`agent_agentId`),
  CONSTRAINT `FKE52EB8DD4B09B779` FOREIGN KEY (`agent_agentId`) REFERENCES `agent` (`agentId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lockbox_identity`
--

DROP TABLE IF EXISTS `lockbox_identity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lockbox_identity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accountId` varchar(40) NOT NULL,
  `identityProvider` varchar(64) DEFAULT NULL,
  `identityUri` varchar(100) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lockbox_namespace`
--

DROP TABLE IF EXISTS `lockbox_namespace`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lockbox_namespace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `namespaceId` varchar(40) NOT NULL,
  `preapproved` bit(1) DEFAULT NULL,
  `updatedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lockbox_permission`
--

DROP TABLE IF EXISTS `lockbox_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lockbox_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grantId` varchar(40) NOT NULL,
  `namespaceId` varchar(150) DEFAULT NULL,
  `updatedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login`
--

DROP TABLE IF EXISTS `login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login` (
  `identityId` int(11) NOT NULL AUTO_INCREMENT,
  `accessExpires` bigint(20) DEFAULT NULL,
  `accessSecret` varchar(40) DEFAULT NULL,
  `accessToken` varchar(32) DEFAULT NULL,
  `clientExpires` bigint(20) NOT NULL,
  `clientToken` varchar(40) NOT NULL,
  `hash` varchar(64) DEFAULT NULL,
  `profileLastUpdated` bigint(20) DEFAULT NULL,
  `providerId` int(11) NOT NULL,
  `reloginAccesskey` longtext,
  `secretDKEncripted` varchar(200) DEFAULT NULL,
  `secretEncripted` varchar(200) DEFAULT NULL,
  `secretSalt` varchar(64) DEFAULT NULL,
  `serverToken` varchar(40) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `uriEncrypted` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`identityId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password`
--

DROP TABLE IF EXISTS `password`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password` (
  `passwordId` int(11) NOT NULL AUTO_INCREMENT,
  `providerId` int(11) NOT NULL,
  `secretPart` varchar(200) NOT NULL,
  `secretSalt` varchar(200) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `uriHash` varchar(40) NOT NULL,
  PRIMARY KEY (`passwordId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `peer`
--

DROP TABLE IF EXISTS `peer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `peer` (
  `contactId` int(11) NOT NULL,
  `peer` longtext,
  PRIMARY KEY (`contactId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `private_key`
--

DROP TABLE IF EXISTS `private_key`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `private_key` (
  `serviceId` int(11) NOT NULL,
  `expires` bigint(20) DEFAULT NULL,
  `privateKey` longtext,
  PRIMARY KEY (`serviceId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `privatepeer`
--

DROP TABLE IF EXISTS `privatepeer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `privatepeer` (
  `contactId` int(11) NOT NULL,
  `privatePeer` longtext,
  PRIMARY KEY (`contactId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `provider`
--

DROP TABLE IF EXISTS `provider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `provider` (
  `providerId` int(11) NOT NULL AUTO_INCREMENT,
  `active` int(11) NOT NULL,
  `hostingSecret` varchar(100) NOT NULL,
  `identityAccessValidateUrl` varchar(150) DEFAULT NULL,
  `identitySecretServerMagic` varchar(40) NOT NULL,
  `loginUri` varchar(150) DEFAULT NULL,
  `name` varchar(40) NOT NULL,
  `rolodexSupported` bit(1) DEFAULT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `uri` varchar(255) NOT NULL,
  `user` int(11) NOT NULL,
  PRIMARY KEY (`providerId`),
  KEY `FKC52405F11E364908` (`user`),
  CONSTRAINT `FKC52405F11E364908` FOREIGN KEY (`user`) REFERENCES `user` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `service_method`
--

DROP TABLE IF EXISTS `service_method`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_method` (
  `methodId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `url` varchar(200) NOT NULL,
  `serviceId` int(11) NOT NULL,
  PRIMARY KEY (`methodId`),
  KEY `FK847C01AB79C8CC6D` (`serviceId`),
  CONSTRAINT `FK847C01AB79C8CC6D` FOREIGN KEY (`serviceId`) REFERENCES `service_type` (`serviceTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `service_type`
--

DROP TABLE IF EXISTS `service_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_type` (
  `serviceTypeId` int(11) NOT NULL AUTO_INCREMENT,
  `serviceKey` varchar(200) DEFAULT NULL,
  `providerId` int(11) NOT NULL,
  `serviceId` varchar(40) NOT NULL,
  `service_type` varchar(30) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `version` varchar(10) NOT NULL,
  `certificate_serviceId` int(11) DEFAULT NULL,
  `privateKey_serviceId` int(11) DEFAULT NULL,
  PRIMARY KEY (`serviceTypeId`),
  KEY `FK15766A84234511DB` (`privateKey_serviceId`),
  KEY `FK15766A84B64111CD` (`certificate_serviceId`),
  CONSTRAINT `FK15766A84B64111CD` FOREIGN KEY (`certificate_serviceId`) REFERENCES `certificate` (`serviceId`),
  CONSTRAINT `FK15766A84234511DB` FOREIGN KEY (`privateKey_serviceId`) REFERENCES `private_key` (`serviceId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `turn`
--

DROP TABLE IF EXISTS `turn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alive` int(11) NOT NULL,
  `providerId` int(11) DEFAULT NULL,
  `serverId` varchar(50) DEFAULT NULL,
  `type` varchar(10) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  `uri` varchar(100) DEFAULT NULL,
  `version` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `userId` int(11) NOT NULL AUTO_INCREMENT,
  `companyName` varchar(64) DEFAULT NULL,
  `email` varchar(40) NOT NULL,
  `firstName` varchar(64) DEFAULT NULL,
  `lastName` varchar(64) DEFAULT NULL,
  `password` varchar(40) NOT NULL,
  `updatedOn` datetime DEFAULT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-10-17 23:57:12
