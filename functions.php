<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function asset_version(string $path): string {
    $assetPath = __DIR__ . '/' . ltrim($path, '/');
    return is_file($assetPath) ? substr(hash_file('sha256', $assetPath), 0, 12) : '0';
}
function is_logged_in() { return isset($_SESSION['user']); }
function user_role() { return $_SESSION['user']['role'] ?? null; }
function role_label($role) {
    $labels = [
        'admin' => 'Admin',
        'officer' => 'Officer',
        'moderator' => 'Moderator',
        'president' => 'President',
        'vice_president' => 'Vice-President',
        'secretary' => 'Secretary',
        'treasurer' => 'Treasurer',
    ];
    return $labels[$role] ?? 'Officer';
}
function is_officer_or_admin() {
    return in_array(user_role(), ['officer','admin','moderator','president','vice_president','secretary','treasurer'], true);
}
function is_admin() { return user_role() === 'admin'; }
function is_admin_or_moderator() { return in_array(user_role(), ['admin', 'moderator'], true); }
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
function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Access denied.');
    }
}
function require_admin_or_moderator() {
    require_login();
    if (!is_admin_or_moderator()) {
        http_response_code(403);
        exit('Access denied.');
    }
}
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf() {
    $sessionToken = $_SESSION['csrf'] ?? null;
    $postedToken = $_POST['csrf'] ?? null;
    if (!is_string($sessionToken) || !is_string($postedToken) || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(419);
        exit('Invalid form token.');
    }
}
function campus_timezone() {
    static $timezone;
    return $timezone ??= new DateTimeZone('Asia/Manila');
}
function campus_now() {
    return new DateTimeImmutable('now', campus_timezone());
}
function parse_campus_date($value) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, campus_timezone());
    $errors = DateTimeImmutable::getLastErrors();
    return $date && (!$errors || (!$errors['warning_count'] && !$errors['error_count'])) && $date->format('Y-m-d') === $value ? $date : null;
}
function parse_campus_time($value) {
    return is_string($value) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value . ':00' : null;
}
function parse_campus_datetime($value) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, campus_timezone());
    $errors = DateTimeImmutable::getLastErrors();
    return $date && (!$errors || (!$errors['warning_count'] && !$errors['error_count'])) && $date->format('Y-m-d\TH:i') === $value ? $date : null;
}
function campus_datetime_input($value) {
    return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $value) ? str_replace(' ', 'T', substr($value, 0, 16)) : '';
}
function format_campus_datetime($value, $format) {
    $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, campus_timezone()) : false;
    return $date ? $date->format($format) : '';
}
function publish_due_announcements(PDO $pdo) {
    $statement = $pdo->prepare("UPDATE announcements SET status='published', published_at=publish_at, publish_at=NULL WHERE status='scheduled' AND deleted_at IS NULL AND archived_at IS NULL AND publish_at IS NOT NULL AND publish_at<=?");
    $statement->execute([campus_now()->format('Y-m-d H:i:s')]);
    return $statement->rowCount();
}
function normalize_announcement_tags($input) {
    if (!is_string($input)) return false;
    $parts = preg_split('/[,;\r\n]+/u', $input);
    if ($parts === false) return false;
    $tags = [];
    foreach ($parts as $part) {
        $label = trim(preg_replace('/\s+/u', ' ', trim($part)));
        if ($label === '') continue;
        if (mb_strlen($label, 'UTF-8') > 40 || count($tags) >= 10) return false;
        $slug = mb_strtolower($label, 'UTF-8');
        $slug = preg_replace('/[^\pL\pN]+/u', '-', $slug);
        $slug = trim($slug ?? '', '-');
        if ($slug === '' || mb_strlen($slug, 'UTF-8') > 40) return false;
        $tags[$slug] = ['slug' => $slug, 'label' => $label];
    }
    return array_values($tags);
}
function get_announcement_tags(PDO $pdo, $announcementId) {
    $statement = $pdo->prepare('SELECT t.label FROM announcement_tags t JOIN announcement_tag_map m ON m.tag_id=t.id WHERE m.announcement_id=? ORDER BY t.label');
    $statement->execute([(int)$announcementId]);
    return array_column($statement->fetchAll(), 'label');
}
function set_announcement_tags(PDO $pdo, $announcementId, array $tags) {
    $pdo->prepare('DELETE FROM announcement_tag_map WHERE announcement_id=?')->execute([(int)$announcementId]);
    $findOrCreate = $pdo->prepare('INSERT INTO announcement_tags (slug,label) VALUES (?,?) ON DUPLICATE KEY UPDATE label=VALUES(label)');
    $findId = $pdo->prepare('SELECT id FROM announcement_tags WHERE slug=?');
    $link = $pdo->prepare('INSERT INTO announcement_tag_map (announcement_id,tag_id) VALUES (?,?)');
    foreach ($tags as $tag) {
        $findOrCreate->execute([$tag['slug'], $tag['label']]);
        $findId->execute([$tag['slug']]);
        $link->execute([(int)$announcementId, (int)$findId->fetchColumn()]);
    }
}
function snapshot_announcement(PDO $pdo, array $announcement, $editorId, $restoredFrom = null) {
    $existing = $pdo->prepare('SELECT id FROM announcement_revisions WHERE announcement_id=? AND revision_number=?');
    $existing->execute([(int)$announcement['id'], (int)$announcement['version']]);
    $existingId = $existing->fetchColumn();
    if ($existingId) return (int)$existingId;
    $tagsJson = json_encode(get_announcement_tags($pdo, $announcement['id']), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $statement = $pdo->prepare('INSERT INTO announcement_revisions (announcement_id,revision_number,editor_id,title,category,caption,image_path,video_url,status,publish_at,published_at,expires_at,event_date,event_start,event_end,event_all_day,deadline_date,deadline_time,deadline_all_day,is_pinned,tags_json,restore_from_revision_id,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $statement->execute([
        (int)$announcement['id'], (int)$announcement['version'], $editorId ? (int)$editorId : null,
        $announcement['title'], $announcement['category'], $announcement['caption'], $announcement['image_path'], $announcement['video_url'], $announcement['status'],
        $announcement['publish_at'], $announcement['published_at'], $announcement['expires_at'], $announcement['event_date'], $announcement['event_start'], $announcement['event_end'],
        (int)$announcement['event_all_day'], $announcement['deadline_date'], $announcement['deadline_time'], (int)$announcement['deadline_all_day'], (int)$announcement['is_pinned'],
        $tagsJson, $restoredFrom ? (int)$restoredFrom : null, campus_now()->format('Y-m-d H:i:s'),
    ]);
    return (int)$pdo->lastInsertId();
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
function social_url_is_valid($url, $network) {
    if ($url === '') return true;
    $parts = parse_url($url);
    if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https') return false;
    $host = strtolower($parts['host'] ?? '');
    if ($network === 'facebook') {
        return $host === 'fb.com' || (bool)preg_match('/(^|\.)facebook\.com$/', $host);
    }
    if ($network === 'linkedin') {
        return (bool)preg_match('/(^|\.)linkedin\.com$/', $host);
    }
    return false;
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
