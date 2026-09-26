<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function is_logged_in() { return isset($_SESSION['user']); }
function user_role() { return $_SESSION['user']['role'] ?? null; }
function is_officer_or_admin() { return in_array(user_role(), ['officer','admin'], true); }
function require_login() {
    if (!is_logged_in()) {
        header('Location: login');
        exit;
    }
}
function require_officer() {
    require_login();
    if (!is_officer_or_admin()) {
        http_response_code(403);
        exit('Access denied.');
    }
}
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Invalid form token.');
    }
}
function upload_file($field, $dir, $allowed, $maxBytes) {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    if ($_FILES[$field]['size'] > $maxBytes) return null;

    $tmp = $_FILES[$field]['tmp_name'];
    $mime = mime_content_type($tmp);
    if (!in_array($mime, $allowed, true)) return null;

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $target = __DIR__ . '/' . trim($dir, '/') . '/' . $name;
    if (!move_uploaded_file($tmp, $target)) return null;
    return trim($dir, '/') . '/' . $name;
}
function embed_video_url($url) {
    $url = trim($url);
    if ($url === '') return null;
    if (preg_match('~youtu\.be/([A-Za-z0-9_-]{6,})~', $url, $m)) return 'https://www.youtube.com/embed/'.$m[1];
    if (preg_match('~youtube\.com/watch\?v=([A-Za-z0-9_-]{6,})~', $url, $m)) return 'https://www.youtube.com/embed/'.$m[1];
    if (preg_match('~youtube\.com/shorts/([A-Za-z0-9_-]{6,})~', $url, $m)) return 'https://www.youtube.com/embed/'.$m[1];
    if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) return 'https://player.vimeo.com/video/'.$m[1];
    return null;
}
