<?php
require 'config.php'; require 'functions.php'; require_officer();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$pinned = $_POST['pinned'] ?? null;
if (!$id || !in_array($pinned, ['0', '1'], true)) { http_response_code(400); exit('Invalid pin request.'); }
$statement = $pdo->prepare('UPDATE announcements SET is_pinned=? WHERE id=? AND deleted_at IS NULL');
$statement->execute([(int)$pinned, $id]);
$exists = $pdo->prepare('SELECT id FROM announcements WHERE id=? AND deleted_at IS NULL');
$exists->execute([$id]);
if (!$exists->fetch()) { http_response_code(404); exit('Announcement not found.'); }
header('Location: dashboard');
exit;