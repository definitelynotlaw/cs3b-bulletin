<?php
require 'config.php'; require 'functions.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location:./'); exit; }
verify_csrf();
$id=(int)($_POST['announcement_id']??0);
$identifier = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown').'|'.($_SERVER['HTTP_USER_AGENT'] ?? ''));
$check=$pdo->prepare("SELECT id FROM announcement_upvotes WHERE announcement_id=? AND visitor_identifier=?");
$check->execute([$id,$identifier]);
if($check->fetch()) {
  $pdo->prepare("DELETE FROM announcement_upvotes WHERE announcement_id=? AND visitor_identifier=?")->execute([$id,$identifier]);
} else {
  $pdo->prepare("INSERT INTO announcement_upvotes (announcement_id,visitor_identifier) VALUES (?,?)")->execute([$id,$identifier]);
}
header('Location: ./'); exit;
