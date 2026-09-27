<?php
require 'config.php'; require 'functions.php';
$stmt = $pdo->query("SELECT a.*, u.name author, u.role author_role FROM announcements a JOIN users u ON u.id=a.author_id WHERE a.status='published' ORDER BY a.created_at DESC");
$posts = $stmt->fetchAll();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CS3B : Bulletin</title><link rel="stylesheet" href="assets/style.css"><script src="assets/theme.js" defer></script></head><body>
<header class="top"><div><strong>CS3B : Bulletin</strong><span>2026 – 2027</span></div>
<nav><a href="./">Home</a><a href="officers">Officers</a><a href="report-a-problem">Report a problem</a><?php if(is_logged_in()): ?><a href="profile">My Profile</a><a href="change-password">Change Password</a><a href="dashboard">Dashboard</a><a href="logout">Logout</a><?php endif; ?></nav></header>
<main class="container"><section class="hero"><p class="eyebrow">BSCS 3B • Academic Year 2026–2027</p><h1>Your class. Your updates. One bulletin.</h1><p>Announcements, class updates, resources, and a direct channel for concerns and reports.</p></section>
<h2>Latest Announcements</h2>
<?php if(!$posts): ?><div class="card"><p>No announcements have been published yet.</p></div><?php endif; ?>
<div class="grid"><?php foreach($posts as $p): ?>
<article class="card"><div class="meta"><span class="category"><?=e($p['category'])?></span><time class="date" datetime="<?=e(date('c', strtotime($p['created_at'])))?>"><?=e(date('M d, Y', strtotime($p['created_at'])))?></time></div>
<h3><?=e($p['title'])?></h3>
<?php if($p['image_path']): ?><img class="media" src="<?=e($p['image_path'])?>" alt="Announcement image"><?php endif; ?>
<?php if($p['video_path']): ?><video class="media" controls src="<?=e($p['video_path'])?>"></video><?php endif; ?>
<?php $embed=embed_video_url($p['video_url']); if($embed): ?><div class="video"><iframe src="<?=e($embed)?>" title="Announcement video" allowfullscreen></iframe></div><?php elseif($p['video_url']): ?><p><a href="<?=e($p['video_url'])?>" target="_blank" rel="noopener">Watch linked video ↗</a></p><?php endif; ?>
<p><?=nl2br(e($p['caption']))?></p><div class="postfoot"><small>Posted by <strong><?=e($p['author'])?></strong> · <?=e(role_label($p['author_role']))?></small><form method="post" action="upvote"><input type="hidden" name="announcement_id" value="<?=$p['id']?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="upvote" type="submit" aria-label="Acknowledge this announcement. <?=e($p['upvotes'])?> total acknowledgements.">Acknowledge <span class="acknowledgement-count" aria-hidden="true"><?=e($p['upvotes'])?></span></button></form></div>
</article><?php endforeach; ?></div></main><footer>CS3B : Bulletin · 2026 – 2027</footer></body></html>
