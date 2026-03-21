-- ================================================
-- COMPLETE SQL SCRIPT FOR Bayawan Mini Hotel (bmh database)
-- ================================================
-- Generated/updated: March 2026
-- ================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =============================================
-- Create / use database safely
-- =============================================
CREATE DATABASE IF NOT EXISTS `bmh` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `bmh`;

-- =============================================
-- 1. admin_cred
-- =============================================
DROP TABLE IF EXISTS `admin_cred`;
CREATE TABLE `admin_cred` (
  `sr_no`      INT(11)       NOT NULL AUTO_INCREMENT,
  `admin_name` VARCHAR(150)  NOT NULL,
  `admin_pass` VARCHAR(150)  NOT NULL,
  `admin_role` VARCHAR(20)   NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`sr_no`),
  UNIQUE KEY `uniq_admin_name` (`admin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Only one admin account
INSERT INTO `admin_cred` (`sr_no`, `admin_name`, `admin_pass`, `admin_role`) VALUES
(1, 'Jayson', '12345', 'admin'),
(2, 'Keito', '12345', 'receptionist');

-- =============================================
-- 2. user_cred
-- =============================================
DROP TABLE IF EXISTS `user_cred`;
CREATE TABLE `user_cred` (
  `id`                INT(11)         NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(100)    NOT NULL,
  `email`             VARCHAR(150)    NOT NULL,
  `phonenum`          VARCHAR(30)     NOT NULL,
  `address`           VARCHAR(250)    NOT NULL,
  `pincode`           VARCHAR(20)     NOT NULL,
  `dob`               DATE            NOT NULL,
  `profile`           VARCHAR(150)    DEFAULT 'default.jpg',
  `password`          VARCHAR(255)    NOT NULL,
  `is_verified`       TINYINT(1)      NOT NULL DEFAULT 0,
  `email_verified_at` DATETIME        DEFAULT NULL,
  `remember_token`    VARCHAR(100)    DEFAULT NULL,
  `remember_expires`  DATETIME        DEFAULT NULL,
  `status`            TINYINT(1)      NOT NULL DEFAULT 1,
  `last_login`        DATETIME        DEFAULT NULL,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`),
  INDEX `idx_email` (`email`),
  INDEX `idx_phonenum` (`phonenum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No pre-inserted users, registered by users themselves

-- =============================================
-- 3. booking_details
-- =============================================
DROP TABLE IF EXISTS `booking_details`;
CREATE TABLE `booking_details` (
  `sr_no`      INT(11)       NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11)       NOT NULL,
  `room_name`  VARCHAR(100)  NOT NULL,
  `price`      INT(11)       NOT NULL,
  `total_pay`  INT(11)       NOT NULL,
  `room_no`    VARCHAR(100)  DEFAULT NULL,
  `user_name`  VARCHAR(100)  NOT NULL,
  `phonenum`   VARCHAR(100)  NOT NULL,
  `address`    VARCHAR(150)  NOT NULL,
  PRIMARY KEY (`sr_no`),
  KEY `booking_id` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 4. booking_order
-- =============================================
DROP TABLE IF EXISTS `booking_order`;
CREATE TABLE `booking_order` (
  `booking_id`      INT(11)         NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11)         NOT NULL,
  `room_id`         INT(11)         NOT NULL,
  `check_in`        DATE            NOT NULL,
  `check_out`       DATE            NOT NULL,
  `arrival`         INT(11)         NOT NULL DEFAULT 0,
  `refund`          INT(11)         DEFAULT NULL,
  `refund_id`       VARCHAR(100)    NULL DEFAULT NULL COMMENT 'PayMongo refund ID (ref_xxxx)',
  `refund_amt`      DECIMAL(10,2)   DEFAULT NULL COMMENT 'Calculated refund amount based on cancellation policy',
  `no_show`         TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = guest marked as no-show by admin',
  `booking_status`  VARCHAR(100)    NOT NULL DEFAULT 'pending',
  `order_id`        VARCHAR(150)    NOT NULL,
  `trans_id`        VARCHAR(200)    DEFAULT NULL,
  `trans_amt`       INT(11)         NOT NULL DEFAULT 0,
  `trans_status`    VARCHAR(100)    NOT NULL DEFAULT 'pending',
  `trans_resp_msg`  VARCHAR(200)    DEFAULT NULL,
  `rate_review`     INT(11)         DEFAULT NULL,
  `datentime`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 5. carousel
-- =============================================
DROP TABLE IF EXISTS `carousel`;
CREATE TABLE `carousel` (
  `sr_no` INT(11)       NOT NULL AUTO_INCREMENT,
  `image` VARCHAR(150)  NOT NULL,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No pre-inserted images, added by admin

-- =============================================
-- 6. contact_details
-- =============================================
DROP TABLE IF EXISTS `contact_details`;
CREATE TABLE `contact_details` (
  `sr_no`     INT(11)       NOT NULL AUTO_INCREMENT,
  `address`   VARCHAR(150)  NOT NULL,
  `gmap`      VARCHAR(100)  NOT NULL,
  `pn1`       VARCHAR(20)   NOT NULL,
  `pn2`       VARCHAR(20)   NOT NULL DEFAULT '',
  `email`     VARCHAR(100)  NOT NULL,
  `fb`        VARCHAR(100)  NOT NULL DEFAULT '',
  `insta`     VARCHAR(100)  NOT NULL DEFAULT '',
  `tw`        VARCHAR(100)  NOT NULL DEFAULT '',
  `iframe`    TEXT          NOT NULL,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pre-insert one row since settings page expects sr_no=1
INSERT INTO `contact_details` (`sr_no`, `address`, `gmap`, `pn1`, `pn2`, `email`, `fb`, `insta`, `tw`, `iframe`) VALUES
(1, 'Bayawan City, Negros Oriental, Philippines, 6221', '', '09534559021', '09534559022', 'bayawanminihotel@gmail.com', '', '', '', '');

-- =============================================
-- 7. facilities
-- =============================================
DROP TABLE IF EXISTS `facilities`;
CREATE TABLE `facilities` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `icon`        VARCHAR(100)  NOT NULL,
  `name`        VARCHAR(50)   NOT NULL,
  `description` VARCHAR(250)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No pre-inserted facilities, added by admin

-- =============================================
-- 8. features
-- =============================================
DROP TABLE IF EXISTS `features`;
CREATE TABLE `features` (
  `id`   INT(11)      NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50)  NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS `rate_limit`;
CREATE TABLE `rate_limit` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `ip`         VARCHAR(45)  NOT NULL,
  `action`     VARCHAR(50)  NOT NULL  COMMENT 'e.g. contact_form, user_login',
  `attempts`   INT(11)      NOT NULL  DEFAULT 1,
  `last_attempt` DATETIME   NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locked_until` DATETIME   DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ip_action` (`ip`, `action`),
  INDEX `idx_ip_action` (`ip`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No pre-inserted features, added by admin

-- =============================================
-- 9. rating_review
-- =============================================
DROP TABLE IF EXISTS `rating_review`;
CREATE TABLE `rating_review` (
  `sr_no`       INT(11)       NOT NULL AUTO_INCREMENT,
  `booking_id`  INT(11)       NOT NULL,
  `room_id`     INT(11)       NOT NULL,
  `user_id`     INT(11)       NOT NULL,
  `rating`      INT(11)       NOT NULL,
  `review`      VARCHAR(200)  NOT NULL,
  `seen`        INT(11)       NOT NULL DEFAULT 0,
  `datentime`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sr_no`),
  KEY `booking_id` (`booking_id`),
  KEY `room_id`    (`room_id`),
  KEY `user_id`    (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 10. rooms
-- =============================================
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150)  NOT NULL,
  `area`        INT(11)       NOT NULL,
  `price`       INT(11)       NOT NULL,
  `quantity`    INT(11)       NOT NULL,
  `adult`       INT(11)       NOT NULL,
  `children`    INT(11)       NOT NULL,
  `description` VARCHAR(350)  NOT NULL,
  `status`      TINYINT(4)    NOT NULL DEFAULT 1,
  `removed`     INT(11)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No pre-inserted rooms, added by admin

-- =============================================
-- 11. room_facilities
-- =============================================
DROP TABLE IF EXISTS `room_facilities`;
CREATE TABLE `room_facilities` (
  `sr_no`         INT(11) NOT NULL AUTO_INCREMENT,
  `room_id`       INT(11) NOT NULL,
  `facilities_id` INT(11) NOT NULL,
  PRIMARY KEY (`sr_no`),
  KEY `facilities_id` (`facilities_id`),
  KEY `room_id`       (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 12. room_features
-- =============================================
DROP TABLE IF EXISTS `room_features`;
CREATE TABLE `room_features` (
  `sr_no`       INT(11) NOT NULL AUTO_INCREMENT,
  `room_id`     INT(11) NOT NULL,
  `features_id` INT(11) NOT NULL,
  PRIMARY KEY (`sr_no`),
  KEY `features_id` (`features_id`),
  KEY `room_id`     (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 13. room_images
-- =============================================
DROP TABLE IF EXISTS `room_images`;
CREATE TABLE `room_images` (
  `sr_no`     INT(11)       NOT NULL AUTO_INCREMENT,
  `room_id`   INT(11)       NOT NULL,
  `image`     VARCHAR(150)  NOT NULL,
  `thumb`     TINYINT(4)    NOT NULL DEFAULT 0,
  PRIMARY KEY (`sr_no`),
  KEY `room_id` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No pre-inserted images, added by admin

-- =============================================
-- 14. settings
-- =============================================
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `sr_no`       INT(11)       NOT NULL AUTO_INCREMENT,
  `site_title`  VARCHAR(50)   NOT NULL,
  `site_about`  VARCHAR(250)  NOT NULL,
  `shutdown`    TINYINT(1)    NOT NULL DEFAULT 0,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pre-insert one row since dashboard expects sr_no=1
INSERT INTO `settings` (`sr_no`, `site_title`, `site_about`, `shutdown`) VALUES
(1, 'Bayawan Mini Hotel', 'Welcome to Bayawan Mini Hotel, your home away from home in the heart of Bayawan City, Negros Oriental. We offer comfortable and affordable accommodations for every type of traveler.', 0);

-- =============================================
-- 15. team_details
-- =============================================
DROP TABLE IF EXISTS `team_details`;
CREATE TABLE `team_details` (
  `sr_no`    INT(11)       NOT NULL AUTO_INCREMENT,
  `name`     VARCHAR(50)   NOT NULL,
  `picture`  VARCHAR(150)  NOT NULL,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- No pre-inserted team members, added by admin

-- =============================================
-- 16. user_queries
-- =============================================
DROP TABLE IF EXISTS `user_queries`;
CREATE TABLE `user_queries` (
  `sr_no`     INT(11)       NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(50)   NOT NULL,
  `email`     VARCHAR(150)  NOT NULL,
  `subject`   VARCHAR(200)  NOT NULL,
  `message`   VARCHAR(500)  NOT NULL,
  `datentime` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `seen`      TINYINT(4)    NOT NULL DEFAULT 0,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Finalize transaction
-- =============================================
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;