<?php
require 'config.php'; require 'functions.php';
$staffView = is_officer_or_admin();
publish_due_announcements($pdo);
$archiveRaw = $_GET['archive'] ?? null;
if ($archiveRaw !== null && (!is_string($archiveRaw) || $archiveRaw !== '1')) { http_response_code(400); exit('Invalid announcement view.'); }
$archiveView = $archiveRaw === '1';
$detailRaw = $_GET['id'] ?? null;
if ($detailRaw !== null && (!is_string($detailRaw) || !ctype_digit($detailRaw) || (int)$detailRaw < 1)) { http_response_code(400); exit('Invalid announcement.'); }
$detailId = $detailRaw === null ? null : (int)$detailRaw;
$queryText = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$queryText = mb_substr($queryText, 0, 120, 'UTF-8');
$categoryFilter = is_string($_GET['category'] ?? null) ? trim($_GET['category']) : '';
$tagInput = is_string($_GET['tag'] ?? null) ? trim($_GET['tag']) : '';
$tagFilter = $tagInput === '' ? '' : normalize_announcement_tags($tagInput);
$startInput = is_string($_GET['from'] ?? null) ? $_GET['from'] : '';
$endInput = is_string($_GET['to'] ?? null) ? $_GET['to'] : '';
$startDate = $startInput === '' ? null : parse_campus_date($startInput);
$endDate = $endInput === '' ? null : parse_campus_date($endInput);
$statusInput = $staffView && is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$statusFilter = $archiveView ? '' : $statusInput;
if (mb_strlen($categoryFilter, 'UTF-8') > 60 || ($tagInput !== '' && ($tagFilter === false || count($tagFilter) !== 1)) || ($startInput !== '' && !$startDate) || ($endInput !== '' && !$endDate) || ($startDate && $endDate && $startDate > $endDate) || ($statusInput !== '' && !in_array($statusInput, ['draft', 'scheduled', 'published'], true))) { http_response_code(400); exit('Invalid announcement filters.'); }
$tagSlug = $tagFilter ? $tagFilter[0]['slug'] : '';
$filterParams = ['q' => $queryText, 'category' => $categoryFilter, 'tag' => $tagInput, 'from' => $startInput, 'to' => $endInput];
if ($staffView && $statusInput !== '') { $filterParams['status'] = $statusInput; }
$filterParams = array_filter($filterParams, static fn($value) => $value !== '');
$activeViewHref = './' . ($filterParams ? '?' . http_build_query($filterParams) : '');
$clearFiltersHref = $archiveView ? './?archive=1' : './';
$archiveParams = $filterParams;
$archiveParams['archive'] = '1';
$archiveViewHref = './?' . http_build_query($archiveParams);
$where = [];
$params = [];
$now = campus_now()->format('Y-m-d H:i:s');
if ($archiveView) {
  $where[] = "a.status='published'";
  $where[] = '(a.published_at IS NULL OR a.published_at<=?)';
  $where[] = 'a.deleted_at IS NULL';
  $where[] = '(a.archived_at IS NOT NULL OR (a.expires_at IS NOT NULL AND a.expires_at<=?))';
  array_push($params, $now, $now);
  if ($detailId !== null) { $where[] = 'a.id=?'; $params[] = $detailId; }
} elseif ($detailId !== null && $staffView) {
  $where[] = 'a.id=?';
  $where[] = 'a.deleted_at IS NULL';
  $where[] = 'a.archived_at IS NULL';
  $where[] = '(a.expires_at IS NULL OR a.expires_at>?)';
  array_push($params, $detailId, $now);
} elseif ($staffView && $statusFilter !== '') {
  $where[] = 'a.status=?';
  $where[] = 'a.deleted_at IS NULL';
  $where[] = 'a.archived_at IS NULL';
  $where[] = '(a.expires_at IS NULL OR a.expires_at>?)';
  array_push($params, $statusFilter, $now);
  if ($statusFilter === 'published') {
    $where[] = '(a.published_at IS NULL OR a.published_at<=?)';
    $params[] = $now;
  }
} else {
  $where[] = "a.status='published'";
  $where[] = '(a.published_at IS NULL OR a.published_at<=?) AND (a.expires_at IS NULL OR a.expires_at>?)';
  $where[] = 'a.archived_at IS NULL';
  $where[] = 'a.deleted_at IS NULL';
  array_push($params, $now, $now);
  if ($detailId !== null) { $where[] = 'a.id=?'; $params[] = $detailId; }
}
if ($detailId === null) {
  if ($queryText !== '') {
    $where[] = '(a.title LIKE ? OR a.caption LIKE ? OR a.category LIKE ? OR EXISTS (SELECT 1 FROM announcement_tag_map tm JOIN announcement_tags t ON t.id=tm.tag_id WHERE tm.announcement_id=a.id AND t.label LIKE ?))';
    $like = '%' . $queryText . '%';
    array_push($params, $like, $like, $like, $like);
  }
  if ($categoryFilter !== '') { $where[] = 'a.category=?'; $params[] = $categoryFilter; }
  if ($tagSlug !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM announcement_tag_map tm JOIN announcement_tags t ON t.id=tm.tag_id WHERE tm.announcement_id=a.id AND t.slug=?)';
    $params[] = $tagSlug;
  }
  if ($startDate) { $where[] = 'COALESCE(a.published_at,a.created_at)>=?'; $params[] = $startDate->format('Y-m-d') . ' 00:00:00'; }
  if ($endDate) { $where[] = 'COALESCE(a.published_at,a.created_at)<=?'; $params[] = $endDate->format('Y-m-d') . ' 23:59:59'; }
}
$viewerAckSelect = is_logged_in() ? '(SELECT COUNT(*) FROM announcement_acknowledgments v WHERE v.announcement_id=a.id AND v.user_id=?)' : '0';
$ackSelectParams = is_logged_in() ? [(int)$_SESSION['user']['id']] : [];
$sql = "SELECT a.*, u.name author, u.role author_role, (SELECT COUNT(*) FROM announcement_upvotes x WHERE x.announcement_id=a.id) upvotes, $viewerAckSelect viewer_acknowledged, (SELECT GROUP_CONCAT(t.label ORDER BY t.label SEPARATOR ', ') FROM announcement_tag_map tm JOIN announcement_tags t ON t.id=tm.tag_id WHERE tm.announcement_id=a.id) tag_labels FROM announcements a JOIN users u ON u.id=a.author_id WHERE " . implode(' AND ', $where) . ' ORDER BY a.is_pinned DESC, COALESCE(a.published_at,a.publish_at,a.created_at) DESC, a.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($ackSelectParams, $params));
$posts = $stmt->fetchAll();
if ($detailId !== null && !$posts) { http_response_code(404); exit('Announcement not found.'); }
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#183B70">
<title>CS3B : Bulletin</title><link rel="icon" href="/assets/brand/favicon.svg?v=<?=asset_version("assets/brand/favicon.svg")?>" type="image/svg+xml"><link rel="stylesheet" href="assets/style.css?v=<?=asset_version("assets/style.css")?>"><script src="assets/theme.js?v=<?=asset_version("assets/theme.js")?>" defer></script></head><body>
<header class="top"><div class="site-brand-wrap"><a href="./" class="site-brand" aria-label="Pilimon Bulletin home"><img class="site-brand__logo" src="assets/brand/pilimon-bulletin-wordmark-light.svg" alt="Pilimon Bulletin"><span class="site-brand__meta" aria-hidden="true"></span></a></div>
<nav><a href="./">Home</a><a href="calendar">Calendar</a><a href="officers">Officers</a><a href="report-a-problem">Report a problem</a><?php if(is_logged_in()): ?><a href="profile">My Profile</a><a href="change-password">Change Password</a><a href="dashboard">Dashboard</a><form class="nav-logout-form" method="post" action="logout"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="nav-logout" type="submit">Logout</button></form><?php endif; ?></nav></header>
<main class="container"><section class="hero"><p class="eyebrow">BSCS 3B • Academic Year 2026–2027</p><h1>Your class. Your updates. One bulletin.</h1><p>Announcements, class updates, resources, and a direct channel for concerns and reports.</p></section>
<?php if($detailId === null): ?><form class="bulletin-filters" method="get" action="./"><?php if($archiveView): ?><input type="hidden" name="archive" value="1"><?php if($staffView && $statusInput !== ''): ?><input type="hidden" name="status" value="<?=e($statusInput)?>"><?php endif; ?><?php endif; ?>
<div class="bulletin-search"><label class="visually-hidden" for="announcement-search">Search announcements</label><span class="bulletin-search__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 5 5"></path></svg></span><input id="announcement-search" type="search" name="q" maxlength="120" value="<?=e($queryText)?>" placeholder="Search announcements..."><button class="bulletin-search__submit" type="submit" aria-label="Search announcements"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14m-6-6 6 6-6 6"></path></svg></button></div>
<div class="filter-controls" role="group" aria-label="Announcement filters">
<div class="filter-control"><button class="filter-trigger" type="button" aria-expanded="false" aria-controls="category-filter" aria-label="Category filter" aria-pressed="<?= $categoryFilter !== '' ? 'true' : 'false' ?>"><span class="filter-trigger__icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6.5A1.5 1.5 0 0 1 4.5 5h5l2 2H19A2 2 0 0 1 21 9v8.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5z"></path></svg></span><span class="filter-trigger__label">Category</span><?php if($categoryFilter !== ''): ?><span class="filter-count" aria-label="1 active filter">1</span><?php endif; ?></button><div class="filter-popover" id="category-filter" hidden><label for="category-select">Category</label><select id="category-select" name="category"><option value="">All categories</option><?php foreach(['General','Academic','Schedule','Event','Reminder','Other'] as $category): ?><option value="<?=e($category)?>" <?=$categoryFilter===$category?'selected':''?>><?=e($category)?></option><?php endforeach; ?></select><button class="primary" type="submit">Apply category</button></div></div>
<div class="filter-control"><button class="filter-trigger" type="button" aria-expanded="false" aria-controls="tag-filter" aria-label="Tags filter" aria-pressed="<?= $tagInput !== '' ? 'true' : 'false' ?>"><span class="filter-trigger__icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V4h9l8.6 6.6a2 2 0 0 1 0 2.8Z"></path><circle cx="7.5" cy="8" r="1"></circle></svg></span><span class="filter-trigger__label">Tags</span><?php if($tagInput !== ''): ?><span class="filter-count" aria-label="1 active filter">1</span><?php endif; ?></button><div class="filter-popover" id="tag-filter" hidden><label for="tag-input">Tag</label><input id="tag-input" name="tag" maxlength="40" value="<?=e($tagInput)?>" placeholder="Filter by tag"><button class="primary" type="submit">Apply tag</button></div></div>
<div class="filter-control"><button class="filter-trigger" type="button" aria-expanded="false" aria-controls="date-filter" aria-label="Date filter" aria-pressed="<?= ($startInput !== '' || $endInput !== '') ? 'true' : 'false' ?>"><span class="filter-trigger__icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3.5" y="5" width="17" height="16" rx="2"></rect><path d="M7.5 3v4M16.5 3v4M4 9.5h16M8 13h2m4 0h2m-8 4h2m4 0h2"></path></svg></span><span class="filter-trigger__label">Date</span><?php if($startInput !== '' || $endInput !== ''): ?><span class="filter-count" aria-label="<?= ($startInput !== '' ? 1 : 0) + ($endInput !== '' ? 1 : 0) ?> active date filters"><?= ($startInput !== '' ? 1 : 0) + ($endInput !== '' ? 1 : 0) ?></span><?php endif; ?></button><div class="filter-popover" id="date-filter" hidden><div class="filter-date-fields"><label for="date-from">From<input id="date-from" type="date" name="from" value="<?=e($startInput)?>"></label><label for="date-to">To<input id="date-to" type="date" name="to" value="<?=e($endInput)?>"></label></div><button class="primary" type="submit">Apply dates</button></div></div>
<?php if($staffView): ?><div class="filter-control"><button class="filter-trigger" type="button" aria-expanded="false" <?=$staffView && !$archiveView ? 'aria-controls="status-filter"' : ''?> aria-label="Status filter" aria-pressed="<?= $statusFilter !== '' ? 'true' : 'false' ?>" <?=$staffView && !$archiveView ? '' : 'disabled title="Status is fixed to published announcements in this view"'?>><span class="filter-trigger__icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01"></path></svg></span><span class="filter-trigger__label">Status</span><?php if($statusFilter !== ''): ?><span class="filter-count" aria-label="1 active filter">1</span><?php endif; ?></button><?php if(!$archiveView): ?><div class="filter-popover" id="status-filter" hidden><label for="status-select">Status</label><select id="status-select" name="status"><option value="">Public announcements</option><?php foreach(['published'=>'Published','scheduled'=>'Scheduled','draft'=>'Draft'] as $value=>$label): ?><option value="<?=$value?>" <?=$statusFilter===$value?'selected':''?>><?=e($label)?></option><?php endforeach; ?></select><button class="primary" type="submit">Apply status</button></div><?php endif; ?></div><?php endif; ?>
<a class="filter-trigger archive-trigger" href="<?=e($archiveViewHref)?>" <?=$archiveView?'aria-current="page"':''?> aria-label="View archived announcements"><span class="filter-trigger__icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 7h18v14H3zM2 3h20v4H2zm7 8h6"></path></svg></span><span class="filter-trigger__label">Archive</span></a>
<a class="filter-trigger filter-reset" href="./" aria-label="Reset search and all filters"><span class="filter-trigger__icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 11a9 9 0 0 1 15.4-6.4L21 7M21 3v4h-4M21 13a9 9 0 0 1-15.4 6.4L3 17m0 4v-4h4"></path></svg></span><span class="filter-trigger__label">Reset</span></a>
</div></form><?php else: ?><p><a href="<?=$archiveView?e($archiveViewHref):e($activeViewHref)?>">← <?=$archiveView?'Archived Announcements':'All announcements'?></a></p><?php endif; ?>
<?php if($archiveView): ?><p class="archive-return"><a href="<?=e($activeViewHref)?>">← Latest announcements</a></p><?php endif; ?>
<h2><?=$archiveView ? 'Archived Announcements' : ($detailId === null ? 'Latest Announcements' : 'Announcement')?></h2>
<?php if(!$posts): ?><div class="card"><p><?=$archiveView ? 'No archived announcements match these filters.' : ($queryText || $categoryFilter || $tagInput || $startInput || $endInput ? 'No announcements match these filters.' : 'No announcements have been published yet.')?></p><?php if($queryText || $categoryFilter || $tagInput || $startInput || $endInput): ?><p><a href="<?=e($clearFiltersHref)?>">Clear filters</a></p><?php endif; ?></div><?php endif; ?>
<div class="grid"><?php foreach($posts as $p): $postUrl = './?id=' . (int)$p['id'] . ($archiveView ? '&archive=1' : ''); ?>
<article class="card announcement-card"><div class="announcement-card__body"><div class="meta"><span class="category"><?=e($p['category'])?></span><?php if($p['is_pinned']): ?><span class="pin-indicator">Pinned</span><?php endif; ?><time class="date" datetime="<?=e(date('c', strtotime($p['created_at'])))?>"><?=e($p['published_at'] ? format_campus_datetime($p['published_at'], 'M d, Y') : date('M d, Y', strtotime($p['created_at'])))?></time></div>
<?php if($archiveView): ?><p class="archive-reason"><?php if($p['archive_reason']==='expired' && $p['archived_at']): ?>Expired <?=e(format_campus_datetime($p['archived_at'], 'M d, Y · g:i A'))?>; kept archived<?php elseif($p['archived_at']): ?>Manually archived <?=e(format_campus_datetime($p['archived_at'], 'M d, Y · g:i A'))?><?php endif; ?><?php if($p['expires_at'] && $p['expires_at'] <= $now && $p['archive_reason']!=='expired'): ?><?=$p['archived_at']?' · ':''?>Expired <?=e(format_campus_datetime($p['expires_at'], 'M d, Y · g:i A'))?><?php endif; ?></p><?php endif; ?>
<h3><a class="announcement-title" href="<?=e($postUrl)?>"><?=e($p['title'])?></a></h3><?php if($p['tag_labels']): ?><div class="announcement-tags"><?php foreach(explode(', ', $p['tag_labels']) as $tagLabel): ?><span class="announcement-tag"><?=e($tagLabel)?></span><?php endforeach; ?></div><?php endif; ?>
<?php if($p['image_path']): ?><button class="media-trigger" type="button" data-lightbox="<?=e($p['image_path'])?>" aria-label="View full image for <?=e($p['title'])?>"><img class="media" src="<?=e($p['image_path'])?>" alt="Announcement image"></button><?php endif; ?>
<?php $embed=embed_video_url($p['video_url']); if($embed): ?><div class="video"><iframe src="<?=e($embed)?>" title="Announcement video" allowfullscreen></iframe></div><?php elseif($p['video_url']): ?><p><a href="<?=e($p['video_url'])?>" target="_blank" rel="noopener">Watch linked video ↗</a></p><?php endif; ?>
<p><?=nl2br(e($p['caption']))?></p></div><div class="postfoot announcement-card__footer"><small class="announcement-card__author">Posted by <strong><?=e($p['author'])?></strong> · <?=e(role_label($p['author_role']))?></small><div class="announcement-card__acknowledgments"><?php if(!$archiveView): ?><form method="post" action="upvote"><input type="hidden" name="announcement_id" value="<?=$p['id']?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="upvote" type="submit" aria-label="Acknowledge this announcement. <?=e($p['upvotes'])?> total acknowledgements.">Visitor acknowledge <span class="acknowledgement-count" aria-hidden="true"><?=e($p['upvotes'])?></span></button></form><?php if(is_logged_in()): ?><form method="post" action="acknowledge"><input type="hidden" name="announcement_id" value="<?=$p['id']?>"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="primary" type="submit" <?=$p['viewer_acknowledged']?'disabled':''?>><?=$p['viewer_acknowledged']?'Acknowledged':'Mark as acknowledged'?></button></form><?php endif; ?><?php endif; ?></div></div>
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
<footer>Pilimon Bulletin · CS3B</footer></body></html>
