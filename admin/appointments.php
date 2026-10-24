<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$db = database_connection();
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'This page expired. Refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'booking');
        if ($action === 'add_slot') {
            $raw = is_string($_POST['starts_at'] ?? null) ? trim($_POST['starts_at']) : '';
            $expert = filter_var($_POST['slot_expert_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            try {
                $date = new DateTimeImmutable($raw);
                if ($date <= new DateTimeImmutable('now') || $date > new DateTimeImmutable('+90 days')) {
                    $error = 'Choose a future time within the next 90 days.';
                } else {
                    $expertId = $expert === false ? null : $expert;
                    if ($expertId !== null) {
                        $checkExpert = $db->prepare('SELECT id FROM experts WHERE id=:id AND active=1');
                        $checkExpert->execute(['id' => $expertId]);
                        if (!$checkExpert->fetchColumn()) $expertId = null;
                    }
                    $starts = $date->format('Y-m-d H:i:s');
                    $duplicate = $db->prepare('SELECT id FROM counselling_slots WHERE starts_at=:starts AND expert_id <=> :expert LIMIT 1');
                    $duplicate->execute(['starts' => $starts, 'expert' => $expertId]);
                    if ($duplicate->fetchColumn()) $error = 'That expert already has a slot at this time.';
                    else {
                        $stmt = $db->prepare("INSERT INTO counselling_slots (expert_id, starts_at, status) VALUES (:expert,:starts,'available')");
                        $stmt->execute(['expert' => $expertId, 'starts' => $starts]);
                        $notice = 'Available counselling time added.';
                    }
                }
            } catch (Throwable $exception) {
                $error = 'Could not add the time. It may already be on the calendar.';
            }
        } elseif ($action === 'remove_slot') {
            $stmt = $db->prepare("DELETE FROM counselling_slots WHERE id=:id AND status='available' AND starts_at>NOW()");
            $stmt->execute(['id' => (int) ($_POST['slot_id'] ?? 0)]);
            $notice = $stmt->rowCount() ? 'Time removed from the booking calendar.' : 'That time is already booked or no longer available.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? 'pending');
            $expert = filter_var($_POST['expert_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
                $error = 'Choose a valid booking status.';
            } else {
                $current = $db->prepare('SELECT status FROM bookings WHERE id=:id');
                $current->execute(['id' => $id]);
                $currentStatus = $current->fetchColumn();
                if ($currentStatus === 'cancelled' && $status !== 'cancelled') $error = 'A cancelled booking cannot be reopened. Create a new booking instead.';
                else {
                    $stmt = $db->prepare('UPDATE bookings SET status=:status,expert_id=:expert WHERE id=:id');
                    $stmt->execute(['status' => $status, 'expert' => $expert === false ? null : $expert, 'id' => $id]);
                    if ($status === 'cancelled') {
                        $db->prepare("UPDATE counselling_slots SET status='available' WHERE id=(SELECT slot_id FROM bookings WHERE id=:id)")->execute(['id' => $id]);
                    }
                    $notice = 'Counselling request updated.';
                }
            }
        }
    }
}
$experts = $db->query('SELECT id,name,specialty FROM experts WHERE active=1 ORDER BY name')->fetchAll();
$slots = $db->query("SELECT s.id,s.starts_at,e.name AS expert_name,e.specialty FROM counselling_slots s LEFT JOIN experts e ON e.id=s.expert_id WHERE s.status='available' AND s.starts_at>NOW() ORDER BY s.starts_at")->fetchAll();
$bookings = $db->query("SELECT b.*,e.name AS expert_name FROM bookings b LEFT JOIN experts e ON e.id=b.expert_id ORDER BY (b.status='pending') DESC,b.preferred_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Counselling requests · You Aspirant admin</title><link rel="stylesheet" href="../styles.css"><link rel="stylesheet" href="admin.css"><link rel="stylesheet" href="../features.css"></head>
<body class="admin-body">
<header class="dashboard-header"><a class="admin-brand" href="/admin"><span class="brand-mark"><span>Y</span></span><span><strong>you aspirant</strong><small>Counselling calendar</small></span></a><a class="logout-button" href="/admin">Inbox ↗</a></header>
<?php require __DIR__ . '/_nav.php'; ?>
<main class="dashboard-main">
  <div class="dashboard-title"><div><p class="admin-eyebrow">Review student time requests</p><h1>Counselling calendar</h1><p class="dashboard-subtitle">Publish open times, then confirm requests with the student.</p></div></div>
  <?php if ($notice): ?><p class="admin-notice"><?= escape_html($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="admin-error"><?= escape_html($error) ?></p><?php endif; ?>
  <div class="admin-content-grid">
    <form class="feature-panel feature-form" method="post"><h2>Add an open time</h2><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="add_slot"><label>Date and time<input type="datetime-local" name="starts_at" min="<?= escape_html(date('Y-m-d\TH:i', time() + 3600)) ?>" max="<?= escape_html(date('Y-m-d\TH:i', strtotime('+90 days'))) ?>" required></label><label>Expert, if known<select name="slot_expert_id"><option value="">Assign later</option><?php foreach ($experts as $expert): ?><option value="<?= (int) $expert['id'] ?>"><?= escape_html($expert['name']) ?> · <?= escape_html($expert['specialty']) ?></option><?php endforeach; ?></select></label><button class="button button-dark" type="submit">Publish time ↗</button></form>
    <section class="feature-panel"><h2>Open times</h2><?php if (!$slots): ?><p class="feature-muted">No open times. Add one so students can book.</p><?php endif; ?><div class="admin-list"><?php foreach ($slots as $slot): ?><article><div><strong><?= escape_html(date('D, M j · g:i a', strtotime((string) $slot['starts_at']))) ?></strong><small><?= escape_html($slot['expert_name'] ?: 'Expert to be assigned') ?><?= $slot['specialty'] ? ' · ' . escape_html($slot['specialty']) : '' ?></small></div><form method="post"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="action" value="remove_slot"><input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>"><button class="row-action" type="submit">Remove</button></form></article><?php endforeach; ?></div></section>
  </div>
  <h2 class="feature-section-title">Student requests</h2>
  <?php if (!$bookings): ?><div class="empty-inbox"><h3>No counselling requests yet.</h3></div><?php endif; ?>
  <div class="appointment-list"><?php foreach ($bookings as $booking): ?><article class="feature-panel appointment-card"><div><span class="status-pill status-<?= escape_html($booking['status']) ?>"><?= escape_html($booking['status']) ?></span><h2><?= escape_html($booking['name']) ?> · <?= escape_html($booking['interest']) ?></h2><p><a href="mailto:<?= escape_html($booking['email']) ?>"><?= escape_html($booking['email']) ?></a></p><p><strong>Slot:</strong> <?= escape_html(date('M j, Y · g:i a', strtotime((string) $booking['preferred_at']))) ?><?= $booking['expert_name'] ? ' · ' . escape_html($booking['expert_name']) : '' ?></p><?php if ($booking['note']): ?><p><?= nl2br(escape_html($booking['note'])) ?></p><?php endif; ?></div><form class="feature-form appointment-controls" method="post"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $booking['id'] ?>"><label>Assign expert<select name="expert_id"><option value="">Not assigned</option><?php foreach ($experts as $expert): ?><option value="<?= (int) $expert['id'] ?>" <?= (int)$booking['expert_id'] === (int)$expert['id'] ? 'selected' : '' ?>><?= escape_html($expert['name']) ?> · <?= escape_html($expert['specialty']) ?></option><?php endforeach; ?></select></label><label>Request status<select name="status"><?php foreach (['pending','confirmed','completed','cancelled'] as $status): ?><option value="<?= $status ?>" <?= $booking['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label><button class="button button-dark" type="submit">Save update ↗</button></form></article><?php endforeach; ?></div>
</main></body></html>
