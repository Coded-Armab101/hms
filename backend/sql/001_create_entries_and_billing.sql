-- Migration: Create unified `entries` table and basic billing tables
-- Run this in your hmisphp database (e.g., via phpMyAdmin or mysql CLI)

CREATE TABLE IF NOT EXISTS `entries` (
  `entry_id` INT NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT DEFAULT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `content` LONGTEXT NOT NULL,
  `author_id` INT NOT NULL,
  `author_name` VARCHAR(200) DEFAULT NULL,
  `author_role` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  INDEX (`entity_type`, `entity_id`),
  INDEX (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS `bills` (
  `bill_id` INT NOT NULL AUTO_INCREMENT,
  `patient_id` INT NOT NULL,
  `created_by` INT DEFAULT NULL,
  `created_by_name` VARCHAR(200) DEFAULT NULL,
  `status` ENUM('open','paid','void') NOT NULL DEFAULT 'open',
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bill_id`),
  INDEX (`patient_id`),
  INDEX (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bill_items` (
  `item_id` INT NOT NULL AUTO_INCREMENT,
  `bill_id` INT NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  INDEX (`bill_id`),
  CONSTRAINT `fk_bill_items_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills`(`bill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notes:
-- 1) `entity_type` should be a short string such as 'prescription','lab','pharmacy','nursing','medical_record'
-- 2) `entity_id` can point to a visit id, order id, or patient id depending on your data model
-- 3) Back up your database before running migrations
