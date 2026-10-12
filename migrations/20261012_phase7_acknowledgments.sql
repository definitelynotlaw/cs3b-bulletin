ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE announcements ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcement_acknowledgments (
	announcement_id INT UNSIGNED NOT NULL,
	user_id INT UNSIGNED NOT NULL,
	acknowledged_at DATETIME NOT NULL,
	PRIMARY KEY (announcement_id, user_id),
	KEY idx_ann_ack_user (user_id, acknowledged_at),
	CONSTRAINT fk_ann_ack_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
	CONSTRAINT fk_ann_ack_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;