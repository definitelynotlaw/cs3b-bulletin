<?php
require 'config.php'; require 'functions.php'; require_officer();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid announcement.'); }
$statement = $pdo->prepare('UPDATE announcements SET deleted_at=? WHERE id=? AND deleted_at IS NULL');
$statement->execute([campus_now()->format('Y-m-d H:i:s'), $id]);
if ($statement->rowCount() === 0) {
	$exists = $pdo->prepare('SELECT id FROM announcements WHERE id=?');
	$exists->execute([$id]);
	if (!$exists->fetch()) { http_response_code(404); exit('Announcement not found.'); }
}
header('Location:dashboard');
exit;
