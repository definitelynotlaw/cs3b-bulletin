<?php
require 'config.php'; require 'functions.php'; require_officer();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id=?');
    $stmt->execute([$_SESSION['user']['id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 12 || strlen($newPassword) > 72) {
        $error = 'Choose a password between 12 and 72 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'The new passwords do not match.';
    } elseif (password_verify($newPassword, $user['password'])) {
        $error = 'Choose a password different from your current password.';
    } else {
        $update = $pdo->prepare('UPDATE users SET password=? WHERE id=?');
        $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user']['id']]);
        header('Location:change-password?updated=1');
        exit;
    }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Change Password · CS3B</title><link rel="stylesheet" href="assets/style.css"><script src="assets/theme.js" defer></script></head><body>
<header class="top"><div><strong>CS3B : Bulletin</strong><span><?=e(role_label(user_role()))?></span></div><nav><a href="dashboard">Dashboard</a><a href="profile">My Profile</a><a href="logout">Logout</a></nav></header>
<main class="container narrow"><div class="dashhead"><h1>Change Password</h1><a href="dashboard">Back to dashboard</a></div>
<?php if($error): ?><p class="alert" role="alert"><?=e($error)?></p><?php endif; ?>
<?php if(isset($_GET['updated'])): ?><p class="alert success" role="status">Your password has been changed.</p><?php endif; ?>
<form class="card form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
<label>New password<input type="password" name="new_password" autocomplete="new-password" minlength="12" maxlength="72" required></label>
<label>Confirm new password<input type="password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="72" required></label>
<p class="field-note">Use 12 to 72 characters. Forgot your password? Ask an Admin to restore your initial password.</p><button class="primary" type="submit">Update Password</button></form></main></body></html>
