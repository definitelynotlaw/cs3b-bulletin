<?php
require 'config.php'; require 'functions.php'; require_officer();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid announcement.'); }
$statement = $pdo->prepare('DELETE FROM announcements WHERE id=?');
$statement->execute([$id]);
header('Location:dashboard');
exit;
