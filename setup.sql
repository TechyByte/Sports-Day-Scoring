-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 16, 2012 at 02:20 AM
-- Server version: 5.5.16
-- PHP Version: 5.3.8

-- With some custom additions by James Wallis (these are marked)

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `scoresystem_v1_04`
--
CREATE DATABASE `scoresystem_v1_04` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `scoresystem_v1_04`;

-- Custom addition to create database user - James Wallis
FLUSH PRIVILEGES;
GRANT SELECT, INSERT, UPDATE, DELETE ON scoresystem_v1_04.* TO 'scoresystem_user'@'localhost' IDENTIFIED BY PASSWORD '*B7217E5C14B7583885A3CD2F87512C5CDA549390';

-- --------------------------------------------------------

--
-- Table structure for table `compotemplates`
--

DROP TABLE IF EXISTS `compotemplates`;
CREATE TABLE IF NOT EXISTS `compotemplates` (
  `ct_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `max_events_per_competitor` tinyint(3) unsigned NOT NULL DEFAULT '3',
  PRIMARY KEY (`ct_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `comtem_events`
--

DROP TABLE IF EXISTS `comtem_events`;
CREATE TABLE IF NOT EXISTS `comtem_events` (
  `e_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ct_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `ss_id` int(10) unsigned NOT NULL,
  `counts_to_limit` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`e_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=515 ;

-- --------------------------------------------------------

--
-- Table structure for table `comtem_groupeligibility`
--

DROP TABLE IF EXISTS `comtem_groupeligibility`;
CREATE TABLE IF NOT EXISTS `comtem_groupeligibility` (
  `ge_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sub_id` int(10) unsigned NOT NULL,
  `e_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ge_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=518 ;

-- --------------------------------------------------------

--
-- Table structure for table `comtem_scoreschemes`
--

DROP TABLE IF EXISTS `comtem_scoreschemes`;
CREATE TABLE IF NOT EXISTS `comtem_scoreschemes` (
  `ss_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ct_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `count_entrants_per_team` tinyint(3) unsigned NOT NULL,
  `result_order` enum('asc','desc') NOT NULL,
  `result_type` varchar(30) NOT NULL,
  `result_units` varchar(20) NOT NULL,
  `result_units_dp` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ss_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=34 ;

-- --------------------------------------------------------

--
-- Table structure for table `comtem_scorescheme_scores`
--

DROP TABLE IF EXISTS `comtem_scorescheme_scores`;
CREATE TABLE IF NOT EXISTS `comtem_scorescheme_scores` (
  `sssc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ss_id` int(10) unsigned NOT NULL,
  `place` tinyint(3) unsigned NOT NULL,
  `score` decimal(10,6) NOT NULL,
  PRIMARY KEY (`sssc_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=296 ;

-- --------------------------------------------------------

--
-- Table structure for table `comtem_subgroups`
--

DROP TABLE IF EXISTS `comtem_subgroups`;
CREATE TABLE IF NOT EXISTS `comtem_subgroups` (
  `sub_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ct_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`sub_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=57 ;

-- --------------------------------------------------------

--
-- Table structure for table `comtem_teams`
--

DROP TABLE IF EXISTS `comtem_teams`;
CREATE TABLE IF NOT EXISTS `comtem_teams` (
  `t_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ct_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`t_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=83 ;

-- --------------------------------------------------------

--
-- Table structure for table `days`
--

DROP TABLE IF EXISTS `days`;
CREATE TABLE IF NOT EXISTS `days` (
  `d_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `year` smallint(5) unsigned NOT NULL DEFAULT '0',
  `max_events_per_competitor` tinyint(3) unsigned NOT NULL,
  `is_active_day` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`d_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_competitors`
--

DROP TABLE IF EXISTS `day_competitors`;
CREATE TABLE IF NOT EXISTS `day_competitors` (
  `c_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `d_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `t_id` int(10) unsigned NOT NULL,
  `sub_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`c_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=750 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_events`
--

DROP TABLE IF EXISTS `day_events`;
CREATE TABLE IF NOT EXISTS `day_events` (
  `e_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `d_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `ss_id` int(10) unsigned NOT NULL,
  `counts_to_limit` tinyint(1) NOT NULL DEFAULT '1',
  `scoring_status` enum('not_done','done_wrong','done_correct') NOT NULL DEFAULT 'not_done',
  PRIMARY KEY (`e_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=193 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_groupeligibility`
--

DROP TABLE IF EXISTS `day_groupeligibility`;
CREATE TABLE IF NOT EXISTS `day_groupeligibility` (
  `ge_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sub_id` int(10) unsigned NOT NULL,
  `e_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ge_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=193 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_records`
--

DROP TABLE IF EXISTS `day_records`;
CREATE TABLE IF NOT EXISTS `day_records` (
  `rec_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `d_id` int(10) unsigned NOT NULL,
  `e_id` int(10) unsigned NOT NULL,
  `name_competitor` varchar(50) DEFAULT NULL,
  `name_team` varchar(50) DEFAULT NULL,
  `result` decimal(10,6) DEFAULT NULL,
  `yearset` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`rec_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=193 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_scores`
--

DROP TABLE IF EXISTS `day_scores`;
CREATE TABLE IF NOT EXISTS `day_scores` (
  `sc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `d_id` int(10) unsigned NOT NULL,
  `e_id` int(10) unsigned NOT NULL,
  `c_id` int(10) unsigned NOT NULL,
  `t_id` int(10) unsigned NOT NULL,
  `place` tinyint(3) unsigned NOT NULL,
  `result` decimal(10,6) NOT NULL,
  `worth` decimal(10,6) unsigned NOT NULL,
  PRIMARY KEY (`sc_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1300 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_scoreschemes`
--

DROP TABLE IF EXISTS `day_scoreschemes`;
CREATE TABLE IF NOT EXISTS `day_scoreschemes` (
  `ss_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `d_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `count_entrants_per_team` tinyint(3) unsigned NOT NULL,
  `result_order` enum('asc','desc') NOT NULL,
  `result_type` varchar(30) NOT NULL,
  `result_units` varchar(20) NOT NULL,
  `result_units_dp` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ss_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_scorescheme_scores`
--

DROP TABLE IF EXISTS `day_scorescheme_scores`;
CREATE TABLE IF NOT EXISTS `day_scorescheme_scores` (
  `sssc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ss_id` int(10) unsigned NOT NULL,
  `place` tinyint(3) unsigned NOT NULL,
  `score` decimal(10,6) NOT NULL,
  PRIMARY KEY (`sssc_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=115 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_subgroups`
--

DROP TABLE IF EXISTS `day_subgroups`;
CREATE TABLE IF NOT EXISTS `day_subgroups` (
  `sub_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `d_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`sub_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=21 ;

-- --------------------------------------------------------

--
-- Table structure for table `day_teams`
--

DROP TABLE IF EXISTS `day_teams`;
CREATE TABLE IF NOT EXISTS `day_teams` (
  `t_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `d_id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `initscore` decimal(10,6) NOT NULL DEFAULT '0.000000',
  PRIMARY KEY (`t_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
CREATE TABLE IF NOT EXISTS `logs` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `u_id` int(10) unsigned NOT NULL,
  `actiondata` text NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=443 ;

-- --------------------------------------------------------

--
-- Table structure for table `usergroups`
--

DROP TABLE IF EXISTS `usergroups`;
CREATE TABLE IF NOT EXISTS `usergroups` (
  `ug_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `perm_admin` tinyint(1) NOT NULL DEFAULT '0',
  `perm_daymanage` tinyint(1) NOT NULL DEFAULT '0',
  `perm_scoreedit` tinyint(1) NOT NULL DEFAULT '0',
  `perm_scoreview` tinyint(1) NOT NULL DEFAULT '0',
  `perm_stats` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ug_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `usergroups`
--

INSERT INTO `usergroups` (`ug_id`, `name`, `perm_admin`, `perm_daymanage`, `perm_scoreedit`, `perm_scoreview`, `perm_stats`) VALUES
(1, 'Administrator', 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `u_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `password` varchar(40) NOT NULL DEFAULT '',
  `ug_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`u_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`u_id`, `name`, `password`, `ug_id`) VALUES
(1, 'root', 'dc76e9f0c0006e8f919e0c515c66dbba3982f785', 1);
