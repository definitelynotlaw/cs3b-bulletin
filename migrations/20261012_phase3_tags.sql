ALTER TABLE announcements ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcement_tags (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	slug VARCHAR(40) NOT NULL UNIQUE,
	label VARCHAR(40) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcement_tag_map (
	announcement_id INT UNSIGNED NOT NULL,
	tag_id INT UNSIGNED NOT NULL,
	PRIMARY KEY (announcement_id, tag_id),
	KEY idx_ann_tag_map_tag (tag_id),
	CONSTRAINT fk_ann_tag_map_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
	CONSTRAINT fk_ann_tag_map_tag FOREIGN KEY (tag_id) REFERENCES announcement_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;