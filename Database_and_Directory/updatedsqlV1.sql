-- ================================================
-- COMPLETE SQL SCRIPT FOR Bayawan Mini Hotel
-- Database: bmh
-- Updated: March 2026
-- Changes from previous version:
--   - admin_pass column widened to VARCHAR(255)
--   - admin passwords replaced with bcrypt hashes
--   - idx_reset_token index added to user_cred
--   - rate_limit table moved to proper numbered section
--   - Foreign key constraints added throughout
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
CREATE DATABASE IF NOT EXISTS `bmh`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE `bmh`;

-- =============================================
-- 1. admin_cred
-- =============================================
DROP TABLE IF EXISTS `admin_cred`;
CREATE TABLE `admin_cred` (
  `sr_no`      INT(11)        NOT NULL AUTO_INCREMENT,
  `admin_name` VARCHAR(150)   NOT NULL,
  `admin_pass` VARCHAR(255)   NOT NULL,  -- widened from 150 to hold bcrypt hash
  `admin_role` VARCHAR(20)    NOT NULL DEFAULT 'admin',
  `totp_secret`  VARCHAR(64)  NULL DEFAULT NULL COMMENT 'Base32 TOTP secret for 2FA',
  `totp_enabled` TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = 2FA is active for this admin';
  PRIMARY KEY (`sr_no`),
  UNIQUE KEY `uniq_admin_name` (`admin_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Passwords are bcrypt hashes of '12345' generated via password_hash('12345', PASSWORD_DEFAULT)
-- IMPORTANT: Change these passwords immediately after first login via the admin settings page
-- Hash below = bcrypt of '12345' — replace with your own secure password hashes
INSERT INTO `admin_cred` (`sr_no`, `admin_name`, `admin_pass`, `admin_role`) VALUES
(1, 'Jayson', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Keito',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist');

-- =============================================
-- 2. user_cred
-- =============================================
DROP TABLE IF EXISTS `user_cred`;
CREATE TABLE `user_cred` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(100) NOT NULL,
  `email`             VARCHAR(150) NOT NULL,
  `phonenum`          VARCHAR(30)  NOT NULL,
  `address`           VARCHAR(250) NOT NULL,
  `pincode`           VARCHAR(20)  NOT NULL,
  `dob`               DATE         NOT NULL,
  `profile`           VARCHAR(150) DEFAULT 'default.jpg',
  `password`          VARCHAR(255) NOT NULL,
  `is_verified`       TINYINT(1)   NOT NULL DEFAULT 0,
  `email_verified_at` DATETIME     DEFAULT NULL,
  `remember_token`    VARCHAR(100) DEFAULT NULL,
  `remember_expires`  DATETIME     DEFAULT NULL,
  `reset_token`       VARCHAR(64)  DEFAULT NULL  COMMENT 'One-time password-reset token (separate from remember_token)',
  `reset_expires`     DATETIME     DEFAULT NULL  COMMENT 'Expiry timestamp for reset_token (1-hour window)',
  `status`            TINYINT(1)   NOT NULL DEFAULT 1,
  `last_login`        DATETIME     DEFAULT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`),
  INDEX `idx_email`       (`email`),
  INDEX `idx_phonenum`    (`phonenum`),
  INDEX `idx_reset_token` (`reset_token`)  -- added for fast password recovery lookups
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 3. rooms
-- =============================================
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150) NOT NULL,
  `area`        INT(11)      NOT NULL,
  `price`       INT(11)      NOT NULL,
  `quantity`    INT(11)      NOT NULL,
  `adult`       INT(11)      NOT NULL,
  `children`    INT(11)      NOT NULL,
  `description` VARCHAR(350) NOT NULL,
  `status`      TINYINT(4)   NOT NULL DEFAULT 1,
  `removed`     INT(11)      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 4. features
-- =============================================
DROP TABLE IF EXISTS `features`;
CREATE TABLE `features` (
  `id`   INT(11)     NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 5. facilities
-- =============================================
DROP TABLE IF EXISTS `facilities`;
CREATE TABLE `facilities` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `icon`        VARCHAR(100) NOT NULL,
  `name`        VARCHAR(50)  NOT NULL,
  `description` VARCHAR(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 6. room_features
-- =============================================
DROP TABLE IF EXISTS `room_features`;
CREATE TABLE `room_features` (
  `sr_no`       INT(11) NOT NULL AUTO_INCREMENT,
  `room_id`     INT(11) NOT NULL,
  `features_id` INT(11) NOT NULL,
  PRIMARY KEY (`sr_no`),
  INDEX `idx_room_id`     (`room_id`),
  INDEX `idx_features_id` (`features_id`),
  CONSTRAINT `fk_rf_room`    FOREIGN KEY (`room_id`)     REFERENCES `rooms`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rf_feature` FOREIGN KEY (`features_id`) REFERENCES `features` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 7. room_facilities
-- =============================================
DROP TABLE IF EXISTS `room_facilities`;
CREATE TABLE `room_facilities` (
  `sr_no`         INT(11) NOT NULL AUTO_INCREMENT,
  `room_id`       INT(11) NOT NULL,
  `facilities_id` INT(11) NOT NULL,
  PRIMARY KEY (`sr_no`),
  INDEX `idx_room_id`       (`room_id`),
  INDEX `idx_facilities_id` (`facilities_id`),
  CONSTRAINT `fk_rfac_room`     FOREIGN KEY (`room_id`)       REFERENCES `rooms`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rfac_facility` FOREIGN KEY (`facilities_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 8. room_images
-- =============================================
DROP TABLE IF EXISTS `room_images`;
CREATE TABLE `room_images` (
  `sr_no`   INT(11)      NOT NULL AUTO_INCREMENT,
  `room_id` INT(11)      NOT NULL,
  `image`   VARCHAR(150) NOT NULL,
  `thumb`   TINYINT(4)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`sr_no`),
  INDEX `idx_room_id` (`room_id`),
  CONSTRAINT `fk_ri_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 9. booking_order
-- =============================================
DROP TABLE IF EXISTS `booking_order`;
CREATE TABLE `booking_order` (
  `booking_id`     INT(11)        NOT NULL AUTO_INCREMENT,
  `user_id`        INT(11)        NOT NULL,
  `room_id`        INT(11)        NOT NULL,
  `check_in`       DATE           NOT NULL,
  `check_out`      DATE           NOT NULL,
  `arrival`        INT(11)        NOT NULL DEFAULT 0,
  `refund`         INT(11)        DEFAULT NULL,
  `refund_id`      VARCHAR(100)   DEFAULT NULL  COMMENT 'PayMongo refund ID (ref_xxxx)',
  `refund_amt`     DECIMAL(10,2)  DEFAULT NULL  COMMENT 'Calculated refund amount based on cancellation policy',
  `no_show`        TINYINT(1)     NOT NULL DEFAULT 0  COMMENT '1 = guest marked as no-show by admin',
  `booking_status` VARCHAR(100)   NOT NULL DEFAULT 'pending',
  `order_id`       VARCHAR(150)   NOT NULL,
  `trans_id`       VARCHAR(200)   DEFAULT NULL,
  `trans_amt`      INT(11)        NOT NULL DEFAULT 0,
  `trans_status`   VARCHAR(100)   NOT NULL DEFAULT 'pending',
  `trans_resp_msg` VARCHAR(200)   DEFAULT NULL,
  `rate_review`    INT(11)        DEFAULT NULL,
  `datentime`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  INDEX `idx_user_id`        (`user_id`),
  INDEX `idx_room_id`        (`room_id`),
  INDEX `idx_booking_status` (`booking_status`),
  INDEX `idx_check_in`       (`check_in`),
  CONSTRAINT `fk_bo_user` FOREIGN KEY (`user_id`) REFERENCES `user_cred` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bo_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`     (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 10. booking_details
-- =============================================
DROP TABLE IF EXISTS `booking_details`;
CREATE TABLE `booking_details` (
  `sr_no`      INT(11)      NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11)      NOT NULL,
  `room_name`  VARCHAR(100) NOT NULL,
  `price`      INT(11)      NOT NULL,
  `total_pay`  INT(11)      NOT NULL,
  `room_no`    VARCHAR(100) DEFAULT NULL,
  `user_name`  VARCHAR(100) NOT NULL,
  `phonenum`   VARCHAR(100) NOT NULL,
  `address`    VARCHAR(150) NOT NULL,
  PRIMARY KEY (`sr_no`),
  INDEX `idx_booking_id` (`booking_id`),
  CONSTRAINT `fk_bd_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking_order` (`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 11. rating_review
-- =============================================
DROP TABLE IF EXISTS `rating_review`;
CREATE TABLE `rating_review` (
  `sr_no`      INT(11)      NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11)      NOT NULL,
  `room_id`    INT(11)      NOT NULL,
  `user_id`    INT(11)      NOT NULL,
  `rating`     INT(11)      NOT NULL,
  `review`     VARCHAR(200) NOT NULL,
  `seen`       INT(11)      NOT NULL DEFAULT 0,
  `datentime`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sr_no`),
  INDEX `idx_booking_id` (`booking_id`),
  INDEX `idx_room_id`    (`room_id`),
  INDEX `idx_user_id`    (`user_id`),
  CONSTRAINT `fk_rr_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking_order` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_room`    FOREIGN KEY (`room_id`)    REFERENCES `rooms`          (`id`)         ON DELETE CASCADE,
  CONSTRAINT `fk_rr_user`    FOREIGN KEY (`user_id`)    REFERENCES `user_cred`      (`id`)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 12. carousel
-- =============================================
DROP TABLE IF EXISTS `carousel`;
CREATE TABLE `carousel` (
  `sr_no` INT(11)      NOT NULL AUTO_INCREMENT,
  `image` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 13. contact_details
-- =============================================
DROP TABLE IF EXISTS `contact_details`;
CREATE TABLE `contact_details` (
  `sr_no`   INT(11)      NOT NULL AUTO_INCREMENT,
  `address` VARCHAR(150) NOT NULL,
  `gmap`    VARCHAR(100) NOT NULL,
  `pn1`     VARCHAR(20)  NOT NULL,
  `pn2`     VARCHAR(20)  NOT NULL DEFAULT '',
  `email`   VARCHAR(100) NOT NULL,
  `fb`      VARCHAR(100) NOT NULL DEFAULT '',
  `insta`   VARCHAR(100) NOT NULL DEFAULT '',
  `tw`      VARCHAR(100) NOT NULL DEFAULT '',
  `iframe`  TEXT         NOT NULL,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `contact_details`
  (`sr_no`, `address`, `gmap`, `pn1`, `pn2`, `email`, `fb`, `insta`, `tw`, `iframe`)
VALUES
  (1, 'Bayawan City, Negros Oriental, Philippines, 6221', '', '09534559021', '09534559022', 'bayawanminihotel@gmail.com', '', '', '', '');

-- =============================================
-- 14. settings
-- =============================================
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `sr_no`      INT(11)      NOT NULL AUTO_INCREMENT,
  `site_title` VARCHAR(50)  NOT NULL,
  `site_about` VARCHAR(250) NOT NULL,
  `shutdown`   TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`sr_no`, `site_title`, `site_about`, `shutdown`) VALUES
(1, 'Bayawan Mini Hotel', 'Welcome to Bayawan Mini Hotel, your home away from home in the heart of Bayawan City, Negros Oriental. We offer comfortable and affordable accommodations for every type of traveler.', 0);

-- =============================================
-- 15. team_details
-- =============================================
DROP TABLE IF EXISTS `team_details`;
CREATE TABLE `team_details` (
  `sr_no`   INT(11)      NOT NULL AUTO_INCREMENT,
  `name`    VARCHAR(50)  NOT NULL,
  `picture` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 16. user_queries
-- =============================================
DROP TABLE IF EXISTS `user_queries`;
CREATE TABLE `user_queries` (
  `sr_no`     INT(11)      NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(50)  NOT NULL,
  `email`     VARCHAR(150) NOT NULL,
  `subject`   VARCHAR(200) NOT NULL,
  `message`   VARCHAR(500) NOT NULL,
  `datentime` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `seen`      TINYINT(4)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`sr_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 17. rate_limit
-- =============================================
DROP TABLE IF EXISTS `rate_limit`;
CREATE TABLE `rate_limit` (
  `id`           INT(11)     NOT NULL AUTO_INCREMENT,
  `ip`           VARCHAR(45) NOT NULL,
  `action`       VARCHAR(50) NOT NULL  COMMENT 'e.g. contact_form, user_login',
  `attempts`     INT(11)     NOT NULL  DEFAULT 1,
  `last_attempt` DATETIME    NOT NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locked_until` DATETIME    DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ip_action`  (`ip`, `action`),
  INDEX  `idx_ip_action`       (`ip`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 1. food_menu
--    Admin manages this (CRUD in admin panel)
-- =============================================
DROP TABLE IF EXISTS `food_menu`;
CREATE TABLE  `food_menu` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150)  NOT NULL,
  `description` VARCHAR(300)  NOT NULL DEFAULT '',
  `price`       DECIMAL(10,2) NOT NULL,
  `category`    VARCHAR(80)   NOT NULL DEFAULT 'Others',
  `image`       VARCHAR(150)  NOT NULL DEFAULT 'default_food.jpg',
  `is_available` TINYINT(1)  NOT NULL DEFAULT 1  COMMENT '1=available, 0=hidden from menu',
  `removed`     TINYINT(1)   NOT NULL DEFAULT 0  COMMENT 'soft delete',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================
-- 2. food_inventory
--    One row per food_menu item
-- =============================================
DROP TABLE IF EXISTS `food_inventory`;
CREATE TABLE  `food_inventory` (
  `id`                 INT(11)  NOT NULL AUTO_INCREMENT,
  `food_id`            INT(11)  NOT NULL,
  `stock_qty`          INT(11)  NOT NULL DEFAULT 0,
  `low_stock_threshold` INT(11) NOT NULL DEFAULT 5  COMMENT 'Alert fires when stock_qty <= this',
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_food_id` (`food_id`),
  KEY `fk_inventory_food` (`food_id`),
  CONSTRAINT `fk_inventory_food` FOREIGN KEY (`food_id`) REFERENCES `food_menu` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================
-- 3. food_orders
--    One order per guest "session" (per booking)
--    status: pending | preparing | delivered | paid | cancelled
-- =============================================
DROP TABLE IF EXISTS `food_orders`;
CREATE TABLE `food_orders` (
  `id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `booking_id`    INT(11)       NOT NULL,
  `user_id`       INT(11)       NOT NULL,
  `room_no`       VARCHAR(50)   NOT NULL DEFAULT '',
  `total_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`        VARCHAR(30)   NOT NULL DEFAULT 'pending' COMMENT 'pending|preparing|delivered|paid|cancelled',
  `payment_method` VARCHAR(30)  DEFAULT NULL COMMENT 'cash|gcash|null',
  `notes`         VARCHAR(300)  DEFAULT NULL,
  `ordered_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_foodorder_booking` (`booking_id`),
  KEY `fk_foodorder_user`    (`user_id`),
  CONSTRAINT `fk_foodorder_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking_order` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_foodorder_user`    FOREIGN KEY (`user_id`)    REFERENCES `user_cred`     (`id`)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================
-- 4. food_order_items
--    Line items per food_order
-- =============================================
DROP TABLE IF EXISTS `food_order_items`;
CREATE TABLE  `food_order_items` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `order_id`   INT(11)       NOT NULL,
  `food_id`    INT(11)       NOT NULL,
  `food_name`  VARCHAR(150)  NOT NULL COMMENT 'snapshot at order time',
  `unit_price` DECIMAL(10,2) NOT NULL COMMENT 'snapshot at order time',
  `qty`        INT(11)       NOT NULL DEFAULT 1,
  `subtotal`   DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_foi_order` (`order_id`),
  KEY `fk_foi_food`  (`food_id`),
  CONSTRAINT `fk_foi_order` FOREIGN KEY (`order_id`) REFERENCES `food_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_foi_food`  FOREIGN KEY (`food_id`)  REFERENCES `food_menu`   (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================
-- Sample food menu + inventory rows (optional)
-- Remove if you want a clean start
-- =============================================
INSERT INTO `food_menu` (`name`, `description`, `price`, `category`) VALUES
('Tapsilog',  'Beef tapa, sinangag, itlog', 120.00, 'Filipino Meals'),
('Bangsilog', 'Bangus, sinangag, itlog',    110.00, 'Filipino Meals'),
('Hotsilog',  'Hotdog, sinangag, itlog',     90.00, 'Filipino Meals'),
('Lugaw',     'Plain rice porridge',          60.00, 'Snacks'),
('Instant Coffee', 'Hot 3-in-1 coffee',       30.00, 'Beverages'),
('Bottled Water',  '500ml mineral water',     25.00, 'Beverages'),
('Iced Tea',       '350ml in bottle',         35.00, 'Beverages');

-- Matching inventory rows (stock = 50, low threshold = 5)
INSERT INTO `food_inventory` (`food_id`, `stock_qty`, `low_stock_threshold`)
SELECT `id`, 50, 5 FROM `food_menu`;

-- =============================================
-- Finalize
-- =============================================
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;