-- phpMyAdmin SQL Dump
-- http://www.phpmyadmin.net

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='contain users data. (default password for users is "pass".)' AUTO_INCREMENT=3 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`) VALUES
(1, 'admin@localhost', '$2y$10$/FRHJxB0GuW82R9uo2wNu.Hq.FADk522Nvg.BY90wwkbomjBRE6kK'),
(2, 'test@localhost', '$2y$10$/FRHJxB0GuW82R9uo2wNu.Hq.FADk522Nvg.BY90wwkbomjBRE6kK');

-- --------------------------------------------------------

--
-- Table structure for table `user_devicecookie_failedattempts`
--

DROP TABLE IF EXISTS `user_devicecookie_failedattempts`;
CREATE TABLE IF NOT EXISTS `user_devicecookie_failedattempts` (
  `attempt_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL COMMENT 'refer to users.id',
  `user_email` varchar(255) DEFAULT NULL,
  `datetime` datetime DEFAULT current_timestamp() COMMENT 'failed authentication on date/time',
  `devicecookie_nonce` varchar(50) DEFAULT NULL COMMENT 'device cookie NONCE (if present).',
  `devicecookie_signature` longtext DEFAULT NULL COMMENT 'device cookie signature (if present).',
  PRIMARY KEY (`attempt_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='contain login failed attempt for existing users.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `user_devicecookie_failedattempts`
--


-- --------------------------------------------------------

--
-- Table structure for table `user_devicecookie_lockout`
--

DROP TABLE IF EXISTS `user_devicecookie_lockout`;
CREATE TABLE IF NOT EXISTS `user_devicecookie_lockout` (
  `lockout_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL COMMENT 'refer to users.id',
  `devicecookie_nonce` varchar(50) DEFAULT NULL COMMENT 'device cookie NONCE.',
  `devicecookie_signature` longtext DEFAULT NULL COMMENT 'device cookie signature.',
  `lockout_untrusted_clients` int(1) NOT NULL DEFAULT 0 COMMENT '0=just lockout selected device cookie, 1=lockout all untrusted clients.',
  `lockout_until` datetime DEFAULT NULL COMMENT 'lockout selected user (user_id) until date/time.',
  PRIMARY KEY (`lockout_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='contain user account lockout.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `user_devicecookie_lockout`
--

