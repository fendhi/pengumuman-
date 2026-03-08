-- Create database first (phpMyAdmin / CLI):
-- CREATE DATABASE pengumuman CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE pengumuman;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL,
  pdf_original_name VARCHAR(255) NULL,
  pdf_stored_name VARCHAR(255) NULL,
  pdf_size_bytes INT UNSIGNED NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_announcements_created_at (created_at),
  KEY idx_announcements_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS participants (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  announcement_id INT UNSIGNED NOT NULL,
  applicant_id VARCHAR(50) NOT NULL,
  participant_name VARCHAR(150) NOT NULL,
  schedule_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_participants_announcement (announcement_id),
  KEY idx_participants_schedule (schedule_date, start_time),
  CONSTRAINT fk_participants_announcement
    FOREIGN KEY (announcement_id) REFERENCES announcements(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(64) NOT NULL,
  `value` TEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create default admin user.
-- Default credentials: admin / Admin@12345
-- IMPORTANT: change after first login.
INSERT INTO admins (username, password_hash)
VALUES ('admin', '$2y$12$R/v58szik0gu0oNVDVkfqeRLzh52YcnRzk4zQgxzwKt66kLr0hOK6')
ON DUPLICATE KEY UPDATE username = username;
