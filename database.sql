-- Bakery Collectible Card & Loyalty System
-- Import in phpMyAdmin (Import tab). Creates the database and all tables.
CREATE DATABASE IF NOT EXISTS bakery_loyalty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bakery_loyalty;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mobile VARCHAR(20) NOT NULL,              -- normalized: digits only
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_generated INT UNSIGNED NOT NULL DEFAULT 0,
  total_claimed INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uq_customer_mobile (mobile)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code CHAR(8) NOT NULL,
  status ENUM('Available','Assigned','Claimed','Revoked') NOT NULL DEFAULT 'Available',
  customer_id INT UNSIGNED NULL,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  assigned_at DATETIME NULL,
  claimed_at DATETIME NULL,
  revoked_at DATETIME NULL,
  UNIQUE KEY uq_code (code),
  KEY idx_codes_status (status),
  KEY idx_codes_customer (customer_id, status),
  CONSTRAINT fk_codes_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS claims (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NULL,
  claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_claim_code (code_id),       -- a code can be claimed only once, enforced by the database
  KEY idx_claims_customer (customer_id),
  KEY idx_claims_date (claimed_at),
  CONSTRAINT fk_claims_code FOREIGN KEY (code_id) REFERENCES codes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_claims_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  CONSTRAINT fk_claims_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reward_settings (
  id TINYINT UNSIGNED PRIMARY KEY,           -- always 1 (single settings row)
  cards_per_reward INT UNSIGNED NOT NULL DEFAULT 5,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_rewards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  milestone INT UNSIGNED NOT NULL,           -- 1st reward, 2nd reward, ...
  cards_required INT UNSIGNED NOT NULL,      -- setting in force when it was earned
  earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  redeemed_at DATETIME NULL,
  UNIQUE KEY uq_customer_milestone (customer_id, milestone),   -- same milestone can never be issued twice
  KEY idx_rewards_redeemed (redeemed_at),
  CONSTRAINT fk_rewards_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT IGNORE INTO reward_settings (id, cards_per_reward) VALUES (1, 5);

-- Default admin: username "admin", password "ChangeMe123!"  (change it after first login)
INSERT IGNORE INTO admins (username, password_hash) VALUES ('admin', '$2y$10$BuioTeoI0paPJjQ2amfHVesKU3jDjcTe7VlZrMTjS/Fkxrram1Dj.');
