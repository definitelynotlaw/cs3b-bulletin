<?php
require 'config.php'; require 'functions.php'; require_officer();
$posts=$pdo->query("SELECT a.*,u.name author,(SELECT COUNT(*) FROM announcement_upvotes x WHERE x.announcement_id=a.id) upvotes FROM announcements a JOIN users u ON u.id=a.author_id ORDER BY a.created_at DESC")->fetchAll();
$reports=$pdo->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 30")->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard · CS3B</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="top"><div><strong>CS3B : Bulletin</strong><span>Officer Dashboard</span></div><nav><a href="./">View Site</a><a href="logout">Logout</a></nav></header><main class="container"><div class="dashhead"><div><p class="eyebrow"><?=e(strtoupper(user_role()))?></p><h1>Dashboard</h1></div><a class="primary button" href="announcement-form">+ New Announcement</a></div>
<h2>Announcements</h2><div class="tablewrap"><table><tr><th>Title</th><th>Status</th><th>Upvotes</th><th>Actions</th></tr><?php foreach($posts as $p): ?><tr><td><?=e($p['title'])?></td><td><?=e($p['status'])?></td><td><?=e($p['upvotes'])?></td><td><a href="announcement-form?id=<?=$p['id']?>">Edit</a> · <a href="delete-announcement?id=<?=$p['id']?>" onclick="return confirm('Delete this announcement?')">Delete</a></td></tr><?php endforeach; ?></table></div>
<h2>Recent Reports & Concerns</h2><div class="tablewrap"><table><tr><th>Type</th><th>Subject</th><th>Email</th><th>Status</th><th>Action</th></tr><?php foreach($reports as $r): ?><tr><td><?=e($r['type'])?></td><td><?=e($r['subject'])?></td><td><?=e($r['email'])?></td><td><?=e($r['status'])?></td><td><a href="report?id=<?=$r['id']?>">View</a></td></tr><?php endforeach; ?></table></div>
</main></body></html>
