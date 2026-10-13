<?php
require 'config.php'; require 'functions.php'; require_officer();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$revisionId = filter_input(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$expectedVersion = filter_input(INPUT_POST, 'version', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id || !$revisionId || !$expectedVersion) { http_response_code(400); exit('Invalid restore request.'); }
$pdo->beginTransaction();
try {
    $currentQuery = $pdo->prepare('SELECT * FROM announcements WHERE id=? AND deleted_at IS NULL FOR UPDATE');
    $currentQuery->execute([$id]);
    $current = $currentQuery->fetch();
    if (!$current) { $pdo->rollBack(); http_response_code(404); exit('Announcement not found.'); }
    if ((int)$current['version'] !== $expectedVersion) { $pdo->rollBack(); http_response_code(409); exit('This announcement changed. Reload its history before restoring.'); }
    $revisionQuery = $pdo->prepare('SELECT * FROM announcement_revisions WHERE id=? AND announcement_id=?');
    $revisionQuery->execute([$revisionId, $id]);
    $revision = $revisionQuery->fetch();
    if (!$revision) { $pdo->rollBack(); http_response_code(404); exit('Revision not found.'); }
    $tags = json_decode($revision['tags_json'], true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($tags) || count($tags) > 10) { $pdo->rollBack(); http_response_code(422); exit('This revision cannot be restored.'); }
    $normalizedTags = normalize_announcement_tags(implode(', ', $tags));
    if ($normalizedTags === false) { $pdo->rollBack(); http_response_code(422); exit('This revision contains invalid tags.'); }
    snapshot_announcement($pdo, $current, $_SESSION['user']['id']);
    $update = $pdo->prepare('UPDATE announcements SET title=?,category=?,caption=?,image_path=?,video_url=?,status=?,publish_at=?,published_at=?,expires_at=?,event_date=?,event_start=?,event_end=?,event_all_day=?,deadline_date=?,deadline_time=?,deadline_all_day=?,is_pinned=?,version=version+1,updated_at=NOW() WHERE id=? AND version=?');
    $update->execute([
        $revision['title'], $revision['category'], $revision['caption'], $revision['image_path'], $revision['video_url'], $revision['status'], $revision['publish_at'], $revision['published_at'], $revision['expires_at'],
        $revision['event_date'], $revision['event_start'], $revision['event_end'], $revision['event_all_day'], $revision['deadline_date'], $revision['deadline_time'], $revision['deadline_all_day'], $revision['is_pinned'], $id, $expectedVersion,
    ]);
    if ($update->rowCount() !== 1) { $pdo->rollBack(); http_response_code(409); exit('This announcement changed. Reload its history before restoring.'); }
    set_announcement_tags($pdo, $id, $normalizedTags);
    $restored = $current;
    foreach (['title','category','caption','image_path','video_url','status','publish_at','published_at','expires_at','event_date','event_start','event_end','event_all_day','deadline_date','deadline_time','deadline_all_day','is_pinned'] as $field) $restored[$field] = $revision[$field];
    $restored['version'] = $expectedVersion + 1;
    snapshot_announcement($pdo, $restored, $_SESSION['user']['id'], $revisionId);
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $error;
}
header('Location: announcement-history?id=' . $id);
exit;