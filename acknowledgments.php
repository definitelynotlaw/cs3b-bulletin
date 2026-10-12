<?php
require 'config.php'; require 'functions.php'; require_officer();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Invalid announcement.'); }
$announcementQuery = $pdo->prepare('SELECT id,title FROM announcements WHERE id=?');
$announcementQuery->execute([$id]);
$announcement = $announcementQuery->fetch();
if (!$announcement) { http_response_code(404); exit('Announcement not found.'); }
$recordsQuery = $pdo->prepare('SELECT u.name,a.acknowledged_at FROM announcement_acknowledgments a JOIN users u ON u.id=a.user_id WHERE a.announcement_id=? ORDER BY a.acknowledged_at DESC,u.name LIMIT 250');
$recordsQuery->execute([$id]);
$records = $recordsQuery->fetchAll();
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acknowledgments · CS3B</title><link rel="stylesheet" href="assets/style.css"><script src="assets/theme.js" defer></script></head><body><header class="top"><div><strong>CS3B : Bulletin</strong><span>Staff view</span></div><nav><a href="dashboard">Dashboard</a><a href="./?id=<?=$id?>">Announcement</a><a href="logout">Logout</a></nav></header><main class="container narrow"><div class="dashhead"><div><p class="eyebrow">Acknowledgment report</p><h1><?=e($announcement['title'])?></h1></div><a href="dashboard">Back to dashboard</a></div><p class="field-note"><?=count($records)?> recorded user acknowledgments<?php if(count($records)===250): ?> · showing the latest 250<?php endif; ?></p><?php if($records): ?><div class="tablewrap"><table><thead><tr><th>User</th><th>Acknowledged at</th></tr></thead><tbody><?php foreach($records as $record): ?><tr><td><?=e($record['name'])?></td><td><time datetime="<?=e($record['acknowledged_at'])?>"><?=e(format_campus_datetime($record['acknowledged_at'],'M j, Y · g:i A'))?></time></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="card"><p>No signed-in users have acknowledged this announcement yet.</p></div><?php endif; ?></main></body></html>