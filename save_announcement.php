<?php
require 'config.php'; require 'functions.php'; require_officer();
if($_SERVER['REQUEST_METHOD']!=='POST') exit;
verify_csrf(); $id=(int)($_POST['id']??0); $title=trim($_POST['title']??'');$cat=trim($_POST['category']??'General');$caption=trim($_POST['caption']??'');$status=$_POST['status']??'draft';$url=trim($_POST['video_url']??'');
if (!in_array($status, ['draft', 'scheduled', 'published'], true)) { http_response_code(422); exit('Invalid publication status.'); }
if (mb_strlen($title, 'UTF-8') > 200 || mb_strlen($cat, 'UTF-8') > 60 || mb_strlen($caption, 'UTF-8') > 60000 || mb_strlen($url, 'UTF-8') > 500) { http_response_code(422); exit('One or more fields exceed the allowed length.'); }
if ($status !== 'draft' && ($title === '' || $caption === '')) { http_response_code(422); exit('Published announcements need a title and body.'); }
if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) { http_response_code(422); exit('Enter a valid video URL.'); }
$now = campus_now();
$publishAt = $status === 'scheduled' ? parse_campus_datetime($_POST['publish_at'] ?? null) : null;
$expiresAt = trim($_POST['expires_at'] ?? '') === '' ? null : parse_campus_datetime($_POST['expires_at']);
if ($status === 'scheduled' && (!$publishAt || $publishAt <= $now)) { http_response_code(422); exit('Choose a valid future publication time.'); }
if (trim($_POST['expires_at'] ?? '') !== '' && !$expiresAt) { http_response_code(422); exit('Choose a valid expiration time.'); }
$effectivePublication = $status === 'scheduled' ? $publishAt : $now;
if ($expiresAt && $expiresAt <= $effectivePublication) { http_response_code(422); exit('Expiration must be later than publication.'); }
$publishAtValue = $publishAt ? $publishAt->format('Y-m-d H:i:s') : null;
$expiresAtValue = $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null;
$eventDateInput = trim($_POST['event_date'] ?? '');
$eventDate = $eventDateInput === '' ? null : parse_campus_date($eventDateInput);
$eventAllDay = isset($_POST['event_all_day']) ? 1 : 0;
$eventStartInput = trim($_POST['event_start'] ?? '');
$eventEndInput = trim($_POST['event_end'] ?? '');
$eventStart = $eventStartInput === '' ? null : parse_campus_time($eventStartInput);
$eventEnd = $eventEndInput === '' ? null : parse_campus_time($eventEndInput);
$deadlineDateInput = trim($_POST['deadline_date'] ?? '');
$deadlineDate = $deadlineDateInput === '' ? null : parse_campus_date($deadlineDateInput);
$deadlineAllDay = isset($_POST['deadline_all_day']) ? 1 : 0;
$deadlineTimeInput = trim($_POST['deadline_time'] ?? '');
$deadlineTime = $deadlineTimeInput === '' ? null : parse_campus_time($deadlineTimeInput);
if (($eventDateInput !== '' && !$eventDate) || ($eventStartInput !== '' && !$eventStart) || ($eventEndInput !== '' && !$eventEnd) || ($eventDate && !$eventAllDay && !$eventStart) || (!$eventDate && ($eventStartInput !== '' || $eventEndInput !== '' || !$eventAllDay)) || ($eventEnd && (!$eventStart || $eventEnd <= $eventStart))) { http_response_code(422); exit('Enter a valid event date and time.'); }
if (($deadlineDateInput !== '' && !$deadlineDate) || ($deadlineTimeInput !== '' && !$deadlineTime) || ($deadlineDate && !$deadlineAllDay && !$deadlineTime) || (!$deadlineDate && ($deadlineTimeInput !== '' || !$deadlineAllDay))) { http_response_code(422); exit('Enter a valid deadline date and time.'); }
$eventDateValue = $eventDate?->format('Y-m-d');
$deadlineDateValue = $deadlineDate?->format('Y-m-d');
$tags = normalize_announcement_tags($_POST['tags'] ?? '');
if ($tags === false) { http_response_code(422); exit('Use at most 10 tags, each no longer than 40 characters.'); }
$image=upload_file('image','uploads/images',['image/jpeg','image/png','image/webp'],5*1024*1024);
$pdo->beginTransaction();
try {
if($id){
 $old=$pdo->prepare("SELECT * FROM announcements WHERE id=? FOR UPDATE");$old->execute([$id]);$o=$old->fetch();
 if(!$o){$pdo->rollBack();http_response_code(404);exit('Announcement not found.');}
 $expectedVersion = filter_var($_POST['version'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
 if (!$expectedVersion || (int)$expectedVersion !== (int)$o['version']) { $pdo->rollBack(); http_response_code(409); exit('This announcement changed after the form was opened. Reload it and review the latest version.'); }
 snapshot_announcement($pdo, $o, $_SESSION['user']['id']);
 $image=$image?:$o['image_path'];
 $publishedAt = $status === 'published' ? (($o['status'] === 'published' && $o['published_at']) ? $o['published_at'] : $now->format('Y-m-d H:i:s')) : null;
 $s=$pdo->prepare("UPDATE announcements SET title=?,category=?,caption=?,image_path=?,video_url=?,status=?,publish_at=?,published_at=?,expires_at=?,event_date=?,event_start=?,event_end=?,event_all_day=?,deadline_date=?,deadline_time=?,deadline_all_day=?,version=version+1,updated_at=NOW() WHERE id=?");
 $s->execute([$title,$cat,$caption,$image,$url,$status,$publishAtValue,$publishedAt,$expiresAtValue,$eventDateValue,$eventStart,$eventEnd,$eventAllDay,$deadlineDateValue,$deadlineTime,$deadlineAllDay,$id]);
}else{
 $publishedAt = $status === 'published' ? $now->format('Y-m-d H:i:s') : null;
 $s=$pdo->prepare("INSERT INTO announcements(title,category,caption,image_path,video_url,status,author_id,publish_at,published_at,expires_at,event_date,event_start,event_end,event_all_day,deadline_date,deadline_time,deadline_all_day) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
 $s->execute([$title,$cat,$caption,$image,$url,$status,$_SESSION['user']['id'],$publishAtValue,$publishedAt,$expiresAtValue,$eventDateValue,$eventStart,$eventEnd,$eventAllDay,$deadlineDateValue,$deadlineTime,$deadlineAllDay]);
 $id=(int)$pdo->lastInsertId();
}
set_announcement_tags($pdo,$id,$tags);
$pdo->commit();
}catch(Throwable $error){
 if($pdo->inTransaction())$pdo->rollBack();
 throw $error;
}
header('Location:dashboard');exit;
