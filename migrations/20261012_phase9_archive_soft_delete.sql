ALTER TABLE announcements
	ADD COLUMN archived_at DATETIME NULL AFTER expires_at,
	ADD COLUMN archive_reason ENUM('manual','expired') NULL AFTER archived_at,
	ADD COLUMN deleted_at DATETIME NULL AFTER archive_reason,
	ADD KEY idx_ann_archive (status, deleted_at, archived_at, expires_at, published_at);