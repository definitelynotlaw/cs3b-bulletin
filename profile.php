<?php
require 'config.php'; require 'functions.php'; require_officer();
$userId = (int)$_SESSION['user']['id'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $facebookUrl = trim($_POST['facebook_url'] ?? '');
    $linkedinUrl = trim($_POST['linkedin_url'] ?? '');

    if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid public contact email or leave the field blank.';
    } elseif (!social_url_is_valid($facebookUrl, 'facebook')) {
        $error = 'Use an HTTPS Facebook or fb.com profile URL.';
    } elseif (!social_url_is_valid($linkedinUrl, 'linkedin')) {
        $error = 'Use an HTTPS LinkedIn profile URL.';
    }

    $photoPath = null;
    $hasPhoto = isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($error === '' && $hasPhoto) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $error = 'The photo upload failed. Try a smaller JPG, PNG, or WebP image.';
        } else {
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($_FILES['photo']['tmp_name']);
            $validImages = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            if (!isset($validImages[$extension]) || $validImages[$extension] !== $mime) {
                $error = 'Choose a valid JPG, PNG, or WebP photo.';
            } else {
                $photoPath = upload_file('photo', 'uploads/images', ['image/jpeg','image/png','image/webp'], 2 * 1024 * 1024);
                if (!$photoPath) $error = 'Choose a JPG, PNG, or WebP photo smaller than 2 MB.';
            }
        }
    }

    if ($error === '') {
        $update = $pdo->prepare('UPDATE users SET contact_email=?,facebook_url=?,linkedin_url=?,photo_path=COALESCE(?,photo_path) WHERE id=?');
        $update->execute([$contactEmail ?: null, $facebookUrl ?: null, $linkedinUrl ?: null, $photoPath, $userId]);
        header('Location:profile?saved=1');
        exit;
    }
}
$stmt = $pdo->prepare('SELECT name,email,role,photo_path,contact_email,facebook_url,linkedin_url FROM users WHERE id=?');
$stmt->execute([$userId]);
$profile = $stmt->fetch();
if (!$profile) exit('Profile not found.');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== '') {
    $profile['contact_email'] = $_POST['contact_email'] ?? $profile['contact_email'];
    $profile['facebook_url'] = $_POST['facebook_url'] ?? $profile['facebook_url'];
    $profile['linkedin_url'] = $_POST['linkedin_url'] ?? $profile['linkedin_url'];
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My Profile · CS3B</title><link rel="icon" href="/assets/brand/favicon.svg?v=<?=asset_version("assets/brand/favicon.svg")?>" type="image/svg+xml"><link rel="stylesheet" href="assets/style.css?v=<?=asset_version("assets/style.css")?>"><script src="assets/theme.js?v=<?=asset_version("assets/theme.js")?>" defer></script></head><body>
<header class="top"><div class="site-brand-wrap"><a href="./" class="site-brand" aria-label="Pilimon Bulletin home"><img class="site-brand__logo" src="assets/brand/pilimon-bulletin-wordmark-light.svg" alt="Pilimon Bulletin"><span class="site-brand__meta" aria-hidden="true"></span></a></div><nav><a href="./">Home</a><a href="calendar">Calendar</a><a href="officers">Officers</a><a href="report-a-problem">Report a problem</a><a href="profile">My Profile</a><a href="change-password">Change Password</a><a href="dashboard">Dashboard</a><form class="nav-logout-form" method="post" action="logout"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="nav-logout" type="submit">Logout</button></form></nav></header>
<main class="container narrow"><div class="dashhead"><div><p class="eyebrow">OFFICER DIRECTORY</p><h1>My Profile</h1></div><a href="officers">View officer directory</a></div>
<?php if($error): ?><p class="alert" role="alert"><?=e($error)?></p><?php endif; ?>
<?php if(isset($_GET['saved'])): ?><p class="alert success" role="status">Your profile has been updated.</p><?php endif; ?>
<form class="card form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<?php if($profile['photo_path']): ?><img class="profile-preview" src="<?=e($profile['photo_path'])?>" alt="Current profile photo"><?php else: ?><div class="profile-placeholder" aria-hidden="true"><?=e(strtoupper(substr($profile['name'], 0, 1)))?></div><?php endif; ?>
<p><strong><?=e($profile['name'])?></strong> · <?=e(role_label($profile['role']))?><br><small class="field-note">Login: <?=e($profile['email'])?></small></p>
<label>Profile photo<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
<label>Public contact email<input type="email" name="contact_email" maxlength="190" value="<?=e($profile['contact_email'] ?? '')?>" autocomplete="email"></label>
<label>Facebook profile URL<input type="url" name="facebook_url" maxlength="500" placeholder="https://www.facebook.com/your-profile" value="<?=e($profile['facebook_url'] ?? '')?>"></label>
<label>LinkedIn profile URL<input type="url" name="linkedin_url" maxlength="500" placeholder="https://www.linkedin.com/in/your-profile" value="<?=e($profile['linkedin_url'] ?? '')?>"></label>
<p class="field-note">Profile photos must be JPG, PNG, or WebP and no larger than 2 MB. Social links are optional and must use HTTPS.</p><button class="primary" type="submit">Save Profile</button></form></main></body></html>
