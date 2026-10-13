<?php
require 'config.php'; require 'functions.php'; require_admin_or_moderator();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$action = $_POST['archive_action'] ?? null;
if (!$id || !in_array($action, ['archive', 'unarchive'], true)) { http_response_code(400); exit('Invalid archive request.'); }

publish_due_announcements($pdo);
$now = campus_now()->format('Y-m-d H:i:s');
$archivedAt = $action === 'archive' ? $now : null;
$archiveReason = $action === 'archive' ? 'manual' : null;
$statement = $pdo->prepare("UPDATE announcements SET archived_at=?,archive_reason=? WHERE id=? AND deleted_at IS NULL AND status='published' AND (published_at IS NULL OR published_at<=?)");
$statement->execute([$archivedAt, $archiveReason, $id, $now]);
if ($statement->rowCount() === 0) {
    $eligible = $pdo->prepare("SELECT id FROM announcements WHERE id=? AND deleted_at IS NULL AND status='published' AND (published_at IS NULL OR published_at<=?)");
    $eligible->execute([$id, $now]);
    if (!$eligible->fetch()) {
        $exists = $pdo->prepare('SELECT id FROM announcements WHERE id=? AND deleted_at IS NULL');
        $exists->execute([$id]);
        if (!$exists->fetch()) { http_response_code(404); exit('Announcement not found.'); }
        http_response_code(409);
        exit('Only published announcements can be archived.');
    }
}
header('Location: dashboard');
exit;