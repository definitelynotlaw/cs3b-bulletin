CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 default_password VARCHAR(255) NOT NULL,
 role ENUM('admin','officer','moderator','president','vice_president','secretary','treasurer') NOT NULL DEFAULT 'officer',
 photo_path VARCHAR(255) NULL,
 contact_email VARCHAR(190) NULL,
 facebook_url VARCHAR(500) NULL,
 linkedin_url VARCHAR(500) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO users (name,email,password,default_password,role,contact_email,facebook_url,linkedin_url) VALUES
('Lawrence','admin@cs3b-bulletin.local','$2y$10$1MyOsynvtKwByMgHNMGWi.5ZHU/7tf7tblaVo3o0r8z/TnFmYIDBS','$2y$10$1MyOsynvtKwByMgHNMGWi.5ZHU/7tf7tblaVo3o0r8z/TnFmYIDBS','admin','alibangbanglawrencejohn_bscs@plmun.edu.ph','https://www.facebook.com/Laaawrence','https://www.linkedin.com/in/laaawrence/'),
('Moderator','moderator@cs3b-bulletin.local','$2y$10$QNOf.OdlG8oMkDfE4NqnDupz2FrthCGbfXU9t8JAmTXM/RDW0UBNy','$2y$10$QNOf.OdlG8oMkDfE4NqnDupz2FrthCGbfXU9t8JAmTXM/RDW0UBNy','moderator',NULL,NULL,NULL),
('President','president@cs3b-bulletin.local','$2y$10$Fo431g4yxnNnlNS/XrrVj.BBhjAtvLWAdBbO4bJAmWl/x84lVoGl.','$2y$10$Fo431g4yxnNnlNS/XrrVj.BBhjAtvLWAdBbO4bJAmWl/x84lVoGl.','president',NULL,NULL,NULL),
('Vice-President','vicepresident@cs3b-bulletin.local','$2y$10$Mgvw.8/x2PSjAQZ.xhkrI.NOGqnGQnqGP2TewRHv1z5yB.0pHjY26','$2y$10$Mgvw.8/x2PSjAQZ.xhkrI.NOGqnGQnqGP2TewRHv1z5yB.0pHjY26','vice_president',NULL,NULL,NULL),
('Secretary','secretary@cs3b-bulletin.local','$2y$10$7FFSoDSzw/y3u61Z0c62Tut3LqZkVmtf9xNijsj9sYN0oT4aBqtjK','$2y$10$7FFSoDSzw/y3u61Z0c62Tut3LqZkVmtf9xNijsj9sYN0oT4aBqtjK','secretary',NULL,NULL,NULL),
('Treasurer','treasurer@cs3b-bulletin.local','$2y$10$aICDC.zVc16nSJ7Wi97yquB8fElS2Tk4QiFWUuNjE.e04lx9u09Be','$2y$10$aICDC.zVc16nSJ7Wi97yquB8fElS2Tk4QiFWUuNjE.e04lx9u09Be','treasurer',NULL,NULL,NULL);

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
 -- Seed account passwords are provided separately; replace them after deployment.
-- Import this file into the hosting database selected in phpMyAdmin.
