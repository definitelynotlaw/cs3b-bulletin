<?php
require 'config.php'; require 'functions.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location:dashboard');
    exit;
}
verify_csrf();
$userId = (int)($_POST['user_id'] ?? 0);
$stmt = $pdo->prepare('SELECT default_password FROM users WHERE id=?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    http_response_code(404);
    exit('User not found.');
}
$update = $pdo->prepare('UPDATE users SET password=default_password WHERE id=?');
$update->execute([$userId]);
header('Location:dashboard?password_reset=1');
exit;
