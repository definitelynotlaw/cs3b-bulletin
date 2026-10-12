<?php
require 'config.php'; require 'functions.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location:./'); exit; }
verify_csrf();
$id=filter_input(INPUT_POST,'announcement_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(400);exit('Invalid announcement.');}
publish_due_announcements($pdo);
$visible=$pdo->prepare("SELECT id FROM announcements WHERE id=? AND status='published' AND (published_at IS NULL OR published_at<=?) AND (expires_at IS NULL OR expires_at>?)");
$now=campus_now()->format('Y-m-d H:i:s');
$visible->execute([$id,$now,$now]);
if(!$visible->fetch()){http_response_code(404);exit('Announcement not found.');}
$identifier = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown').'|'.($_SERVER['HTTP_USER_AGENT'] ?? ''));
$check=$pdo->prepare("SELECT id FROM announcement_upvotes WHERE announcement_id=? AND visitor_identifier=?");
$check->execute([$id,$identifier]);
if($check->fetch()) {
  $pdo->prepare("DELETE FROM announcement_upvotes WHERE announcement_id=? AND visitor_identifier=?")->execute([$id,$identifier]);
} else {
  $pdo->prepare("INSERT INTO announcement_upvotes (announcement_id,visitor_identifier) VALUES (?,?)")->execute([$id,$identifier]);
}
header('Location: ./'); exit;
