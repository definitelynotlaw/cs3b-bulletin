CREATE DATABASE IF NOT EXISTS cs3b_bulletin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cs3b_bulletin;

CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','officer') NOT NULL DEFAULT 'officer',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE announcements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(200) NOT NULL,
 category VARCHAR(60) NOT NULL DEFAULT 'General',
 caption TEXT NOT NULL,
 image_path VARCHAR(255) NULL,
 video_path VARCHAR(255) NULL,
 video_url VARCHAR(500) NULL,
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 author_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_ann_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE announcement_upvotes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 announcement_id INT UNSIGNED NOT NULL,
 visitor_identifier CHAR(64) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY one_vote (announcement_id, visitor_identifier),
 CONSTRAINT fk_vote_ann FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
);

CREATE TABLE reports (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL,
 type ENUM('Concern','Issue','Report','Suggestion','Other') NOT NULL DEFAULT 'Other',
 subject VARCHAR(200) NOT NULL,
 message TEXT NOT NULL,
 attachment_path VARCHAR(255) NULL,
 status ENUM('New','Reviewing','Resolved') NOT NULL DEFAULT 'New',
 handled_by INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_report_handler FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create your first account by generating a password hash with PHP:
-- php -r "echo password_hash('CHANGE_THIS_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
-- Then insert it:
-- INSERT INTO users(name,email,password,role) VALUES('Class Admin','admin@example.com','PASTE_HASH_HERE','admin');
