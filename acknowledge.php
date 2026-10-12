<?php
require 'config.php'; require 'functions.php'; require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid announcement.'); }
publish_due_announcements($pdo);
$statement = $pdo->prepare('SELECT status,published_at,publish_at,expires_at FROM announcements WHERE id=?');
$statement->execute([$id]);
$announcement = $statement->fetch();
$now = campus_now();
$visible = $announcement && ($announcement['status'] === 'published' && (!$announcement['published_at'] || $announcement['published_at'] <= $now->format('Y-m-d H:i:s')) && (!$announcement['expires_at'] || $announcement['expires_at'] > $now->format('Y-m-d H:i:s')));
if (!$visible && !is_officer_or_admin()) { http_response_code(404); exit('Announcement not found.'); }
if (!$announcement) { http_response_code(404); exit('Announcement not found.'); }
$insert = $pdo->prepare('INSERT IGNORE INTO announcement_acknowledgments (announcement_id,user_id,acknowledged_at) VALUES (?,?,?)');
$insert->execute([$id, (int)$_SESSION['user']['id'], $now->format('Y-m-d H:i:s')]);
header('Location: ./?id=' . $id);
exit;