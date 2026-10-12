<?php
require 'config.php'; require 'functions.php';
$stmt = $pdo->query("SELECT a.*, u.name author, u.role author_role, (SELECT COUNT(*) FROM announcement_upvotes x WHERE x.announcement_id=a.id) upvotes FROM announcements a JOIN users u ON u.id=a.author_id WHERE a.status='published' ORDER BY a.created_at DESC");
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
<?php if($p['image_path']): ?><button class="media-trigger" type="button" data-lightbox="<?=e($p['image_path'])?>" aria-label="View full image for <?=e($p['title'])?>"><img class="media" src="<?=e($p['image_path'])?>" alt="Announcement image"></button><?php endif; ?>
<?php $embed=embed_video_url($p['video_url']); if($embed): ?><div class="video"><iframe src="<?=e($embed)?>" title="Announcement video" allowfullscreen></iframe></div><?php elseif($p['video_url']): ?><p><a href="<?=e($p['video_url'])?>" target="_blank" rel="noopener">Watch linked video ↗</a></p><?php endif; ?>
<p><?=nl2br(e($p['caption']))?></p><div class="postfoot"><small>Posted by <strong><?=e($p['author'])?></strong> · <?=e(role_label($p['author_role']))?></small><form method="post" action="upvote"><input type="hidden" name="announcement_id" value="<?=$p['id']?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="upvote" type="submit" aria-label="Acknowledge this announcement. <?=e($p['upvotes'])?> total acknowledgements.">Acknowledge <span class="acknowledgement-count" aria-hidden="true"><?=e($p['upvotes'])?></span></button></form></div>
</article><?php endforeach; ?></div></main>
<style>
  .media-trigger { display: block; width: 100%; padding: 0; border: 0; background: transparent; cursor: zoom-in; }
  .lightbox[hidden] { display: none; }
  .lightbox { position: fixed; inset: 0; z-index: 9999; display: flex; flex-direction: column; background: rgba(10, 10, 10, .94); overflow: hidden; touch-action: none; }
  .lightbox-toolbar { position: absolute; top: max(12px, env(safe-area-inset-top)); right: max(12px, env(safe-area-inset-right)); z-index: 2; display: flex; gap: 8px; }
  .lightbox-toolbar button { width: 44px; height: 44px; padding: 0; border: 1px solid rgba(255, 255, 255, .32); border-radius: 4px; color: #fff; background: #202020; font: inherit; font-size: 24px; font-weight: 700; line-height: 1; cursor: pointer; }
  .lightbox-toolbar button:hover { background: #fff; color: #111; }
  .lightbox-toolbar button:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
  .lightbox-toolbar .lightbox-close { position: static; min-width: 0; min-height: 0; }
  .lightbox-toolbar .lightbox-close:hover { background: #fff; color: #111; }
  .lightbox-stage { width: 100%; height: 100%; min-height: 0; display: flex; align-items: center; justify-content: center; padding: 68px 16px 16px; overflow: hidden; cursor: grab; }
  .lightbox-stage.dragging { cursor: grabbing; }
  .lightbox img { display: block; width: auto; height: auto; max-width: 100%; max-height: 100%; object-fit: contain; user-select: none; -webkit-user-drag: none; transform-origin: center center; will-change: transform; }
  body.lightbox-open { overflow: hidden; }
  @media (max-width: 600px) { .lightbox-toolbar { top: max(10px, env(safe-area-inset-top)); right: max(10px, env(safe-area-inset-right)); gap: 6px; } .lightbox-toolbar button { width: 42px; height: 42px; } .lightbox-stage { padding: 64px 10px max(10px, env(safe-area-inset-bottom)); } }
</style>
<div class="lightbox" hidden role="dialog" aria-modal="true" aria-label="Expanded announcement image">
  <div class="lightbox-toolbar" aria-label="Image controls">
    <button class="lightbox-zoom-out" type="button" aria-label="Zoom out">−</button>
    <button class="lightbox-reset" type="button" aria-label="Reset zoom">↺</button>
    <button class="lightbox-zoom-in" type="button" aria-label="Zoom in">+</button>
    <button class="lightbox-close" type="button" aria-label="Close image">×</button>
  </div>
  <div class="lightbox-stage">
    <img alt="Expanded announcement image">
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const lightbox = document.querySelector('.lightbox');
    if (!lightbox) return;

    const stage = lightbox.querySelector('.lightbox-stage');
    const image = lightbox.querySelector('img');
    const closeButton = lightbox.querySelector('.lightbox-close');
    const zoomInButton = lightbox.querySelector('.lightbox-zoom-in');
    const zoomOutButton = lightbox.querySelector('.lightbox-zoom-out');
    const resetButton = lightbox.querySelector('.lightbox-reset');
    let scale = 1;
    let x = 0;
    let y = 0;
    let dragging = false;
    let startX = 0;
    let startY = 0;
    let previousFocus = null;

    function render() {
      image.style.transform = 'translate(' + x + 'px, ' + y + 'px) scale(' + scale + ')';
    }

    function resetView() {
      scale = 1;
      x = 0;
      y = 0;
      render();
    }

    function zoom(amount) {
      scale = Math.min(6, Math.max(0.5, scale + amount));
      if (scale <= 1) { x = 0; y = 0; }
      render();
    }

    function openLightbox(trigger) {
      previousFocus = trigger;
      image.src = trigger.dataset.lightbox;
      image.alt = trigger.querySelector('img')?.alt || 'Expanded announcement image';
      resetView();
      lightbox.hidden = false;
      document.body.classList.add('lightbox-open');
      closeButton.focus();
    }

    function closeLightbox() {
      lightbox.hidden = true;
      document.body.classList.remove('lightbox-open');
      image.src = '';
      resetView();
      if (previousFocus) previousFocus.focus();
    }

    document.querySelectorAll('.media-trigger').forEach(function (trigger) {
      trigger.addEventListener('click', function () { openLightbox(trigger); });
    });

    closeButton.addEventListener('click', closeLightbox);
    zoomInButton.addEventListener('click', function () { zoom(0.25); });
    zoomOutButton.addEventListener('click', function () { zoom(-0.25); });
    resetButton.addEventListener('click', resetView);

    stage.addEventListener('dblclick', function () { zoom(scale === 1 ? 1 : 1 - scale); });
    stage.addEventListener('wheel', function (event) {
      event.preventDefault();
      zoom(event.deltaY < 0 ? 0.25 : -0.25);
    }, { passive: false });

    stage.addEventListener('pointerdown', function (event) {
      if (scale <= 1) return;
      dragging = true;
      startX = event.clientX - x;
      startY = event.clientY - y;
      stage.classList.add('dragging');
      stage.setPointerCapture(event.pointerId);
    });

    stage.addEventListener('pointermove', function (event) {
      if (!dragging) return;
      x = event.clientX - startX;
      y = event.clientY - startY;
      render();
    });

    function stopDragging() {
      dragging = false;
      stage.classList.remove('dragging');
    }
    stage.addEventListener('pointerup', stopDragging);
    stage.addEventListener('pointercancel', stopDragging);

    lightbox.addEventListener('click', function (event) {
      if (event.target === stage) closeLightbox();
    });

    document.addEventListener('keydown', function (event) {
      if (lightbox.hidden) return;
      if (event.key === 'Escape') closeLightbox();
      if (event.key === '+' || event.key === '=') zoom(0.25);
      if (event.key === '-') zoom(-0.25);
      if (event.key === '0') resetView();
    });
  });
</script>
<footer>CS3B : Bulletin · 2026 – 2027</footer></body></html>
