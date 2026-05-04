/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.9-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: opatrovatelky_nette
-- ------------------------------------------------------
-- Server version	11.4.9-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `sany_pages`
--

DROP TABLE IF EXISTS `sany_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sany_pages` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `url` varchar(50) NOT NULL,
  `parent` int(3) NOT NULL,
  `permission` int(2) unsigned NOT NULL,
  `active` int(1) NOT NULL,
  `in_menu` int(1) unsigned NOT NULL,
  `show_parents` int(1) NOT NULL DEFAULT 0,
  `show_same_level` int(1) NOT NULL DEFAULT 0,
  `header` int(1) NOT NULL DEFAULT 1,
  `sidebar_right` int(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `template_folder` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permissions` (`permission`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_slovak_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sany_users`
--

DROP TABLE IF EXISTS `sany_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sany_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `second_name` varchar(50) DEFAULT NULL,
  `acronym` varchar(10) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `permission` int(2) unsigned NOT NULL DEFAULT 2,
  `color` varchar(10) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `permission` (`permission`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_slovak_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_active`
--

DROP TABLE IF EXISTS `sn_active`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_active` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_agencies`
--

DROP TABLE IF EXISTS `sn_agencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_agencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `street_number` varchar(255) DEFAULT NULL,
  `psc` varchar(20) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 1,
  `ico` varchar(255) DEFAULT NULL,
  `ic_dph` varchar(255) DEFAULT NULL,
  `web` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1,
  `person_name` varchar(255) DEFAULT NULL,
  `person_surname` varchar(255) DEFAULT NULL,
  `notice` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_babysitter_disease`
--

DROP TABLE IF EXISTS `sn_babysitter_disease`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_babysitter_disease` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `babysitter_id` int(11) NOT NULL,
  `disease_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=22502 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_babysitter_position_preference`
--

DROP TABLE IF EXISTS `sn_babysitter_position_preference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_babysitter_position_preference` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `babysitter_id` int(11) NOT NULL,
  `work_position_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_babysitter_qualification`
--

DROP TABLE IF EXISTS `sn_babysitter_qualification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_babysitter_qualification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `babysitter_id` int(11) NOT NULL,
  `work_position_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_country`
--

DROP TABLE IF EXISTS `sn_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `german` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_families`
--

DROP TABLE IF EXISTS `sn_families`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_families` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_number` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `street_number` varchar(255) DEFAULT NULL,
  `psc` varchar(20) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 1,
  `phone` varchar(255) DEFAULT NULL,
  `person_email` varchar(255) DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1,
  `person_name` varchar(255) DEFAULT NULL,
  `person_surname` varchar(255) DEFAULT NULL,
  `person_phone` varchar(50) DEFAULT NULL,
  `notice` text DEFAULT NULL,
  `billing` text DEFAULT NULL,
  `partner_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `acquired_by_user_id` int(11) NOT NULL DEFAULT 0,
  `order_status` int(11) NOT NULL DEFAULT 0,
  `contract_status` int(11) NOT NULL DEFAULT 0,
  `patient_phone` varchar(50) DEFAULT NULL,
  `deleted` int(11) NOT NULL DEFAULT 0,
  `type` int(11) NOT NULL DEFAULT 1,
  `company_name` varchar(255) DEFAULT NULL,
  `employer` text DEFAULT NULL,
  `accommodation_address` text DEFAULT NULL,
  `de_project_number` text DEFAULT NULL,
  `project_description` text DEFAULT NULL,
  `project_positions` text DEFAULT NULL,
  `project_available_positions` text DEFAULT NULL,
  `work_status_staff` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_family_proposal`
--

DROP TABLE IF EXISTS `sn_family_proposal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_family_proposal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `family_id` int(11) NOT NULL DEFAULT 0,
  `babysitter_id` int(11) NOT NULL DEFAULT 0,
  `date_starting_work` date DEFAULT NULL,
  `date_proposal_sended` date DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `notice` text DEFAULT NULL,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `date_created` date DEFAULT NULL,
  `deleted` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1761 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_files`
--

DROP TABLE IF EXISTS `sn_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission` int(11) NOT NULL DEFAULT 2,
  `dir` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_slovak_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_slovak_ci DEFAULT NULL,
  `user` int(11) NOT NULL,
  `upload` datetime NOT NULL DEFAULT current_timestamp(),
  `active` int(1) NOT NULL DEFAULT 1,
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_slovak_ci DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `notice` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `status2` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4162 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_slovak_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_missing_registry`
--

DROP TABLE IF EXISTS `sn_missing_registry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_missing_registry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `type_pn` int(11) NOT NULL DEFAULT 0,
  `type_ocr` int(11) NOT NULL DEFAULT 0,
  `type_lekar` int(11) NOT NULL DEFAULT 0,
  `type_sviatok` int(11) NOT NULL DEFAULT 0,
  `type_zastup` int(11) NOT NULL DEFAULT 0,
  `type_sluzba` int(11) NOT NULL DEFAULT 0,
  `type_dovolenka` int(11) NOT NULL DEFAULT 0,
  `notice` text DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `deleted` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_opatrovatelky`
--

DROP TABLE IF EXISTS `sn_opatrovatelky`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_opatrovatelky` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_number` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `age` int(11) NOT NULL DEFAULT 0,
  `pohlavie` int(11) NOT NULL DEFAULT 0,
  `country` int(11) NOT NULL DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1,
  `status` int(11) NOT NULL DEFAULT 0,
  `birthday` date DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `smoker` int(11) NOT NULL DEFAULT 0,
  `height` int(11) NOT NULL DEFAULT 0,
  `weight` int(11) NOT NULL DEFAULT 0,
  `phone` varchar(50) DEFAULT NULL,
  `phone2` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `driving_licence` int(11) NOT NULL DEFAULT 0,
  `city` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `working_status` int(11) NOT NULL DEFAULT 0,
  `agency_id` int(11) NOT NULL DEFAULT 0,
  `contact_person_name` varchar(255) DEFAULT NULL,
  `contact_person_phone` varchar(255) DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `notice` text DEFAULT NULL,
  `blacklist` int(11) NOT NULL DEFAULT 0,
  `first_contact_user_id` int(11) NOT NULL DEFAULT 0,
  `about` text DEFAULT NULL,
  `allergy` int(11) NOT NULL DEFAULT 1,
  `allergy_detail` text DEFAULT NULL,
  `education` int(11) NOT NULL DEFAULT 0,
  `course` int(11) NOT NULL DEFAULT 0,
  `course_detail` text DEFAULT NULL,
  `ready_drive` int(11) NOT NULL DEFAULT 0,
  `how_long_work` text DEFAULT NULL,
  `how_long_work_german` text DEFAULT NULL,
  `language_skills` int(11) NOT NULL DEFAULT 0,
  `language_skills_other` text DEFAULT NULL,
  `working_area` int(11) NOT NULL DEFAULT 0,
  `daily_care` int(11) NOT NULL DEFAULT 0,
  `hourly_care` int(11) NOT NULL DEFAULT 0,
  `time_scale` text DEFAULT NULL,
  `work_place` text DEFAULT NULL,
  `work_description` text DEFAULT NULL,
  `general_activities` text DEFAULT NULL,
  `rating_agency` text DEFAULT NULL,
  `profil_show_contact` int(11) NOT NULL DEFAULT 1,
  `type` int(11) NOT NULL DEFAULT 1,
  `job_position_interest` text DEFAULT NULL,
  `work_shoes` int(11) DEFAULT NULL,
  `shoe_size` int(11) DEFAULT NULL,
  `german_tax_id` varchar(50) DEFAULT NULL,
  `accommodation_type` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1776 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_partners`
--

DROP TABLE IF EXISTS `sn_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `street_number` varchar(255) DEFAULT NULL,
  `psc` varchar(20) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT 1,
  `ico` varchar(255) DEFAULT NULL,
  `ic_dph` varchar(255) DEFAULT NULL,
  `web` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `active` int(11) NOT NULL DEFAULT 1,
  `person_name` varchar(255) DEFAULT NULL,
  `person_surname` varchar(255) DEFAULT NULL,
  `notice` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_permission`
--

DROP TABLE IF EXISTS `sn_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_permission` (
  `permission` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_pohlavie`
--

DROP TABLE IF EXISTS `sn_pohlavie`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_pohlavie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pohlavie` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_accommodation_type`
--

DROP TABLE IF EXISTS `sn_select_accommodation_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_accommodation_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accommodation_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_diseases`
--

DROP TABLE IF EXISTS `sn_select_diseases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_diseases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` varchar(255) DEFAULT NULL,
  `german` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_driving_licence`
--

DROP TABLE IF EXISTS `sn_select_driving_licence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_driving_licence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` varchar(255) DEFAULT NULL,
  `german` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_education`
--

DROP TABLE IF EXISTS `sn_select_education`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_education` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` varchar(255) DEFAULT NULL,
  `german` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_family_project`
--

DROP TABLE IF EXISTS `sn_select_family_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_family_project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` varchar(255) DEFAULT NULL,
  `german` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_language`
--

DROP TABLE IF EXISTS `sn_select_language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` text DEFAULT NULL,
  `german` text DEFAULT NULL,
  `stars` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_missing_type`
--

DROP TABLE IF EXISTS `sn_select_missing_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_missing_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_payment_period`
--

DROP TABLE IF EXISTS `sn_select_payment_period`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_payment_period` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_smoker`
--

DROP TABLE IF EXISTS `sn_select_smoker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_smoker` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` varchar(255) DEFAULT NULL,
  `german` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_work_position`
--

DROP TABLE IF EXISTS `sn_select_work_position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_work_position` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_work_role`
--

DROP TABLE IF EXISTS `sn_select_work_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_work_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` varchar(255) DEFAULT NULL,
  `german` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_work_status_staff`
--

DROP TABLE IF EXISTS `sn_select_work_status_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_work_status_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contract` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_working_area`
--

DROP TABLE IF EXISTS `sn_select_working_area`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_working_area` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` varchar(255) DEFAULT NULL,
  `german` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_working_status`
--

DROP TABLE IF EXISTS `sn_select_working_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_working_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` varchar(255) DEFAULT NULL,
  `german` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_select_yes_no`
--

DROP TABLE IF EXISTS `sn_select_yes_no`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_select_yes_no` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) DEFAULT NULL,
  `german` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_babysitters`
--

DROP TABLE IF EXISTS `sn_status_babysitters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_babysitters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_complaint`
--

DROP TABLE IF EXISTS `sn_status_complaint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_complaint` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_documents`
--

DROP TABLE IF EXISTS `sn_status_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_documents_a1`
--

DROP TABLE IF EXISTS `sn_status_documents_a1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_documents_a1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_fa`
--

DROP TABLE IF EXISTS `sn_status_fa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_fa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_families`
--

DROP TABLE IF EXISTS `sn_status_families`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_families` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_partners`
--

DROP TABLE IF EXISTS `sn_status_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_proposal`
--

DROP TABLE IF EXISTS `sn_status_proposal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_proposal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_todo`
--

DROP TABLE IF EXISTS `sn_status_todo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_todo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_status_turnus`
--

DROP TABLE IF EXISTS `sn_status_turnus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_status_turnus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) DEFAULT NULL,
  `color` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_todo_client`
--

DROP TABLE IF EXISTS `sn_todo_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_todo_client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `family_id` int(11) NOT NULL DEFAULT 0,
  `babysitter_id` int(11) NOT NULL DEFAULT 0,
  `todo_from_user` int(11) NOT NULL DEFAULT 0,
  `todo_to_user_1` int(11) NOT NULL DEFAULT 0,
  `todo_to_user_2` int(11) NOT NULL DEFAULT 0,
  `todo_created` date DEFAULT NULL,
  `todo_deadline` date DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=238 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_translate`
--

DROP TABLE IF EXISTS `sn_translate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_translate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slovak` text DEFAULT NULL,
  `german` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sn_turnus`
--

DROP TABLE IF EXISTS `sn_turnus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sn_turnus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `babysitter_id` int(11) NOT NULL DEFAULT 0,
  `family_id` int(11) NOT NULL DEFAULT 0,
  `agency_id` int(11) NOT NULL DEFAULT 0,
  `partner_id` int(11) DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `invoice_number` varchar(50) DEFAULT NULL,
  `preinvoice_number` varchar(50) DEFAULT NULL,
  `invoice_status` int(11) NOT NULL DEFAULT 0,
  `complaint` text DEFAULT NULL,
  `complaint_status` int(11) NOT NULL DEFAULT 0,
  `date_created` date DEFAULT NULL,
  `working_status` int(11) NOT NULL DEFAULT 0,
  `user_created` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `bonus` float NOT NULL DEFAULT 0,
  `holiday` float NOT NULL DEFAULT 0,
  `commission_complet` float NOT NULL DEFAULT 0,
  `commission_partners` float NOT NULL DEFAULT 0,
  `payment_period_partner` int(11) NOT NULL DEFAULT 0,
  `commission_4ms` float NOT NULL DEFAULT 0,
  `payment_period` int(11) NOT NULL DEFAULT 0,
  `remaining_payment` float DEFAULT NULL,
  `travel_expenses` varchar(100) DEFAULT NULL,
  `sva` varchar(100) DEFAULT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `travel_costs_arrival` float NOT NULL DEFAULT 0,
  `travel_costs_departure` float NOT NULL DEFAULT 0,
  `fee` float NOT NULL DEFAULT 0,
  `fee_ag` float NOT NULL DEFAULT 0,
  `fee_bk` float NOT NULL DEFAULT 0,
  `notice` text DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `status_a1` int(11) NOT NULL DEFAULT 0,
  `deleted` int(11) NOT NULL DEFAULT 0,
  `work_position_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3387 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-05-04  9:39:30
