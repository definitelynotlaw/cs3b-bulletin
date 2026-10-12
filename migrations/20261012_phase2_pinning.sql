ALTER TABLE announcements
	ADD COLUMN is_pinned TINYINT(1) NOT NULL DEFAULT 0,
	ADD KEY idx_ann_pinned (is_pinned, status, created_at);