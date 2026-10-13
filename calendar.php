<?php
require 'config.php'; require 'functions.php';
publish_due_announcements($pdo);
$monthInput = is_string($_GET['month'] ?? null) ? $_GET['month'] : campus_now()->format('Y-m');
$monthDate = preg_match('/^\d{4}-\d{2}$/', $monthInput) ? DateTimeImmutable::createFromFormat('!Y-m-d', $monthInput . '-01', campus_timezone()) : false;
if (!$monthDate || $monthDate->format('Y-m') !== $monthInput) { http_response_code(400); exit('Invalid calendar month.'); }
$view = is_string($_GET['view'] ?? null) ? $_GET['view'] : 'month';
if (!in_array($view, ['month', 'list'], true)) { http_response_code(400); exit('Invalid calendar view.'); }
$monthStart = $monthDate->format('Y-m-d');
$monthEnd = $monthDate->modify('first day of next month')->format('Y-m-d');
$now = campus_now();
$rangeStart = $view === 'month' ? $monthStart : $now->modify('-14 days')->format('Y-m-d');
$rangeEnd = $view === 'month' ? $monthEnd : $now->modify('+90 days')->format('Y-m-d');
$visible = "a.status='published' AND a.archived_at IS NULL AND a.deleted_at IS NULL AND (a.published_at IS NULL OR a.published_at<=?) AND (a.expires_at IS NULL OR a.expires_at>?)";
$statement = $pdo->prepare("SELECT a.id,a.title,a.category,a.event_date,a.event_start,a.event_all_day,a.deadline_date,a.deadline_time,a.deadline_all_day FROM announcements a WHERE $visible AND ((a.event_date>=? AND a.event_date<?) OR (a.deadline_date>=? AND a.deadline_date<?)) ORDER BY COALESCE(a.event_date,a.deadline_date),a.event_start,a.deadline_time,a.id");
$statement->execute([$now->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s'), $rangeStart, $rangeEnd, $rangeStart, $rangeEnd]);
$rows = $statement->fetchAll();
$today = $now->format('Y-m-d');
$byDate = [];
$listEntries = [];
foreach ($rows as $row) {
    if ($row['event_date']) {
        $entry = ['id' => $row['id'], 'title' => $row['title'], 'kind' => 'Event', 'time' => $row['event_all_day'] ? '' : substr((string)$row['event_start'], 0, 5)];
        $byDate[$row['event_date']][] = $entry;
        $listEntries[] = ['date' => $row['event_date'], 'entry' => $entry];
    }
    if ($row['deadline_date']) {
        $entry = ['id' => $row['id'], 'title' => $row['title'], 'kind' => 'Deadline', 'time' => $row['deadline_all_day'] ? '' : substr((string)$row['deadline_time'], 0, 5)];
        $byDate[$row['deadline_date']][] = $entry;
        $listEntries[] = ['date' => $row['deadline_date'], 'entry' => $entry];
    }
}
usort($listEntries, static fn($left, $right) => [$left['date'], $left['entry']['time'], $left['entry']['title']] <=> [$right['date'], $right['entry']['time'], $right['entry']['title']]);
$deadlineStatement = $pdo->prepare("SELECT a.id,a.title,a.deadline_date,a.deadline_time,a.deadline_all_day FROM announcements a WHERE $visible AND a.deadline_date>=? AND a.deadline_date<=? ORDER BY a.deadline_date,a.deadline_time,a.id");
$deadlineStatement->execute([$now->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s'), $now->modify('-14 days')->format('Y-m-d'), $now->modify('+90 days')->format('Y-m-d')]);
$deadlines = $deadlineStatement->fetchAll();
$firstWeekday = (int)$monthDate->format('N');
$daysInMonth = (int)$monthDate->format('t');
$previousMonth = $monthDate->modify('-1 month')->format('Y-m');
$nextMonth = $monthDate->modify('+1 month')->format('Y-m');
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Academic Calendar · CS3B</title><link rel="icon" href="/assets/brand/favicon.svg?v=<?=asset_version("assets/brand/favicon.svg")?>" type="image/svg+xml"><link rel="stylesheet" href="assets/style.css?v=<?=asset_version("assets/style.css")?>"><script src="assets/theme.js?v=<?=asset_version("assets/theme.js")?>" defer></script></head><body>
<header class="top"><div class="site-brand-wrap"><a href="./" class="site-brand" aria-label="Pilimon Bulletin home"><img class="site-brand__logo" src="assets/brand/pilimon-bulletin-wordmark-light.svg" alt="Pilimon Bulletin"><span class="site-brand__meta" aria-hidden="true"></span></a></div><nav><a href="./">Home</a><a href="calendar">Calendar</a><a href="officers">Officers</a><a href="report-a-problem">Report a problem</a><?php if(is_logged_in()): ?><a href="profile">My Profile</a><a href="change-password">Change Password</a><a href="dashboard">Dashboard</a><form class="nav-logout-form" method="post" action="logout"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="nav-logout" type="submit">Logout</button></form><?php endif; ?></nav></header>
<main class="container calendar-page"><div class="dashhead"><div><p class="eyebrow">BSCS 3B · 2026–2027</p><h1>Academic Calendar</h1></div><div class="calendar-views"><a class="<?=$view==='month'?'is-current':''?>" href="calendar?month=<?=e($monthInput)?>&amp;view=month">Month</a><a class="<?=$view==='list'?'is-current':''?>" href="calendar?month=<?=e($monthInput)?>&amp;view=list">List</a></div></div>
<div class="calendar-layout"><section class="calendar-main"><div class="calendar-heading"><a href="calendar?month=<?=e($previousMonth)?>&amp;view=<?=e($view)?>" aria-label="Previous month">←</a><h2><?=e($monthDate->format('F Y'))?></h2><a href="calendar?month=<?=e($nextMonth)?>&amp;view=<?=e($view)?>" aria-label="Next month">→</a></div>
<?php if ($view === 'month'): ?><div class="calendar-grid" role="grid" aria-label="Calendar for <?=e($monthDate->format('F Y'))?>"><?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday): ?><div class="calendar-weekday" role="columnheader"><?=e($weekday)?></div><?php endforeach; ?><?php for($blank=1;$blank<$firstWeekday;$blank++): ?><div class="calendar-day is-empty" role="gridcell" aria-hidden="true"></div><?php endfor; ?><?php for($day=1;$day<=$daysInMonth;$day++): $dateKey=$monthDate->setDate((int)$monthDate->format('Y'),(int)$monthDate->format('m'),$day)->format('Y-m-d'); ?><div class="calendar-day <?=$dateKey===$today?'is-today':''?>" role="gridcell"><time datetime="<?=e($dateKey)?>"><?=$day?></time><?php foreach($byDate[$dateKey]??[] as $entry): ?><a class="calendar-entry <?=strtolower($entry['kind'])?>" href="./?id=<?=$entry['id']?>"><?php if($entry['time']): ?><time><?=e($entry['time'])?></time><?php endif; ?><?=e($entry['title'])?><span><?=e($entry['kind'])?></span></a><?php endforeach; ?></div><?php endfor; ?></div>
<?php elseif ($listEntries): ?><ol class="calendar-list"><?php foreach($listEntries as $item): ?><li><time datetime="<?=e($item['date'])?>"><?=e(format_campus_datetime($item['date'].' 00:00:00','D, M j'))?><?php if($item['entry']['time']): ?> · <?=e($item['entry']['time'])?><?php endif; ?></time><span class="calendar-kind <?=strtolower($item['entry']['kind'])?>"><?=e($item['entry']['kind'])?></span><a href="./?id=<?=$item['entry']['id']?>"><?=e($item['entry']['title'])?></a></li><?php endforeach; ?></ol><?php else: ?><p class="calendar-empty">No events or deadlines in this period.</p><?php endif; ?></section>
<aside class="deadline-panel"><h2>Upcoming deadlines</h2><?php if($deadlines): ?><ul><?php foreach($deadlines as $deadline): $deadlineMoment=DateTimeImmutable::createFromFormat('!Y-m-d', $deadline['deadline_date'], campus_timezone()); $overdue=$deadline['deadline_date']<$today || ($deadline['deadline_date']===$today && !$deadline['deadline_all_day'] && $deadline['deadline_time'] && substr($deadline['deadline_time'],0,8)<=$now->format('H:i:s')); ?><li class="<?=$overdue?'is-overdue':''?>"><time datetime="<?=e($deadline['deadline_date'])?>"><?=e($deadlineMoment ? $deadlineMoment->format('M j') : $deadline['deadline_date'])?><?php if(!$deadline['deadline_all_day'] && $deadline['deadline_time']): ?> · <?=e(substr($deadline['deadline_time'],0,5))?><?php endif; ?></time><a href="./?id=<?=$deadline['id']?>"><?=e($deadline['title'])?></a><small><?=$overdue?'Overdue':'Upcoming'?></small></li><?php endforeach; ?></ul><?php else: ?><p class="calendar-empty">No upcoming deadlines.</p><?php endif; ?></aside></div></main><footer>Pilimon Bulletin · CS3B</footer></body></html>