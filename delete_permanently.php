<?php
require 'config.php'; require 'functions.php'; require_admin_or_moderator();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid announcement.'); }

$uploadPaths = [];
$removePaths = [];
try {
    $pdo->beginTransaction();
    $query = $pdo->prepare('SELECT image_path,video_path FROM announcements WHERE id=? FOR UPDATE');
    $query->execute([$id]);
    $announcement = $query->fetch();
    if (!$announcement) { $pdo->rollBack(); http_response_code(404); exit('Announcement not found.'); }
    if ($announcement['image_path']) { $uploadPaths['images'][$announcement['image_path']] = true; }
    if ($announcement['video_path']) { $uploadPaths['videos'][$announcement['video_path']] = true; }
    $revisionImages = $pdo->prepare('SELECT image_path FROM announcement_revisions WHERE announcement_id=? AND image_path IS NOT NULL');
    $revisionImages->execute([$id]);
    foreach ($revisionImages->fetchAll(PDO::FETCH_COLUMN) as $revisionImage) {
        if ($revisionImage) { $uploadPaths['images'][$revisionImage] = true; }
    }
    foreach ($uploadPaths as $kind => $paths) {
        foreach (array_keys($paths) as $path) {
            if ($kind === 'images') {
                $shared = $pdo->prepare('SELECT COUNT(*) FROM announcements WHERE image_path=? AND id<>? UNION ALL SELECT COUNT(*) FROM announcement_revisions WHERE image_path=? AND announcement_id<>? UNION ALL SELECT COUNT(*) FROM users WHERE photo_path=?');
                $shared->execute([$path, $id, $path, $id, $path]);
                $counts = $shared->fetchAll(PDO::FETCH_COLUMN);
                if ((int)array_sum($counts) === 0) { $removePaths[$kind][] = $path; }
            } else {
                $shared = $pdo->prepare('SELECT COUNT(*) FROM announcements WHERE video_path=? AND id<>?');
                $shared->execute([$path, $id]);
                if ((int)$shared->fetchColumn() === 0) { $removePaths[$kind][] = $path; }
            }
        }
    }
    $delete = $pdo->prepare('DELETE FROM announcements WHERE id=?');
    $delete->execute([$id]);
    if ($delete->rowCount() !== 1) { $pdo->rollBack(); http_response_code(404); exit('Announcement not found.'); }
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Permanent announcement deletion failed: ' . $error->getMessage());
    http_response_code(500);
    exit('Unable to permanently delete this announcement.');
}

foreach ($removePaths as $kind => $paths) {
    $uploadRoot = realpath(__DIR__ . '/uploads/' . $kind);
    if (!$uploadRoot) { continue; }
    foreach ($paths as $path) {
        if (!is_string($path)) { continue; }
        $uploadFile = realpath(__DIR__ . '/' . ltrim($path, '/'));
        if ($uploadFile && str_starts_with($uploadFile, $uploadRoot . DIRECTORY_SEPARATOR) && is_file($uploadFile) && !@unlink($uploadFile)) {
            error_log('Could not remove unreferenced announcement upload.');
        }
    }
}
header('Location: dashboard');
exit;