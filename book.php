<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
header('Cache-Control: no-store, private');
$notice = '';
$error = '';
$name = is_user_authenticated() ? (string) ($_SESSION['user_name'] ?? '') : '';
$email = is_user_authenticated() ? (string) ($_SESSION['user_email'] ?? '') : '';
$interest = '';
$slotId = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
    $interest = is_string($_POST['interest'] ?? null) ? trim($_POST['interest']) : '';
    $slotId = is_string($_POST['slot_id'] ?? null) ? trim($_POST['slot_id']) : '';
    $note = is_string($_POST['note'] ?? null) ? trim($_POST['note']) : '';
    $allowed = ['Career guidance', 'Skills and training', 'Interview practice', 'A little help figuring that out'];
    try {
        $slotNumber = filter_var($slotId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $error = 'This page expired. Refresh and try again.';
        elseif ($name === '' || text_length($name) > 120 || filter_var($email, FILTER_VALIDATE_EMAIL) === false || !in_array($interest, $allowed, true) || $slotNumber === false || text_length($note) > 1000) $error = 'Check your details and choose one of the available counselling times.';
        else {
            $db = database_connection();
            $db->beginTransaction();
            $slotQuery = $db->prepare("SELECT id, expert_id, starts_at FROM counselling_slots WHERE id=:id AND status='available' AND starts_at>NOW() FOR UPDATE");
            $slotQuery->execute(['id' => $slotNumber]);
            $slot = $slotQuery->fetch();
            if (!$slot) { $db->rollBack(); $error = 'That time was just taken. Choose another available slot.'; }
            else {
                $stmt = $db->prepare('INSERT INTO bookings (user_id, expert_id, slot_id, name, email, interest, preferred_at, note) VALUES (:uid, :expert, :slot, :name, :email, :interest, :preferred, :note)');
                $stmt->execute(['uid' => current_user_id(), 'expert' => $slot['expert_id'], 'slot' => $slot['id'], 'name' => $name, 'email' => $email, 'interest' => $interest, 'preferred' => $slot['starts_at'], 'note' => $note === '' ? null : $note]);
                $db->prepare("UPDATE counselling_slots SET status='booked' WHERE id=:id")->execute(['id' => $slot['id']]);
                $db->commit();
                $notice = 'Your counselling time request is in. The team will confirm it with you.';
                $slotId = '';
            }
        }
    } catch (Throwable $exception) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) $db->rollBack();
        $error = 'We could not save that booking request. Check the database setup and try again.';
    }
}
$slots = [];
try { $slots = database_connection()->query("SELECT s.id,s.starts_at,e.name AS expert_name,e.specialty FROM counselling_slots s LEFT JOIN experts e ON e.id=s.expert_id WHERE s.status='available' AND s.starts_at>NOW() AND s.starts_at<=DATE_ADD(NOW(),INTERVAL 90 DAY) ORDER BY s.starts_at LIMIT 80")->fetchAll(); } catch (Throwable $exception) {}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="<?= escape_html(app_setting('robots_directive','index,follow')) ?>"><meta name="keywords" content="<?= escape_html(app_setting('seo_keywords')) ?>"><link rel="canonical" href="<?= escape_html(canonical_url('/book')) ?>"><title>Book a counselling slot · You Aspirant</title><meta name="description" content="Request a career counselling conversation with You Aspirant. Choose a time that works and the team will confirm availability."><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="features.css"><?php require __DIR__ . '/includes/tracking-head.php'; ?></head><body class="feature-page"><header class="feature-header"><a class="brand" href="/"><span class="brand-mark"><span>Y</span></span><span class="brand-copy"><strong>you aspirant</strong><small>Make your next step yours.</small></span></a><nav><a href="quiz">Career quiz</a><a href="<?= is_user_authenticated() ? 'my-account' : 'account' ?>"><?= is_user_authenticated() ? 'My account' : 'Sign in' ?></a></nav></header><main class="feature-shell"><p class="eyebrow eyebrow-muted">One conversation can help</p><h1>Request a counselling slot.</h1><p>Choose a preferred time and tell us what you’d like to talk about. We’ll confirm the slot by email. Your request stays pending until the team confirms it.</p><?php if ($notice !== ''): ?><p class="feature-success" role="status"><?= escape_html($notice) ?><?php if (is_user_authenticated()): ?> <a href="my-account">View your account ↗</a><?php endif; ?></p><?php endif; ?><?php if ($error !== ''): ?><p class="feature-alert" role="alert"><?= escape_html($error) ?></p><?php endif; ?><form class="feature-panel feature-form" method="post"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><label>Your name<input name="name" maxlength="120" value="<?= escape_html($name) ?>" autocomplete="name" required></label><label>Email address<input type="email" name="email" maxlength="254" value="<?= escape_html($email) ?>" autocomplete="email" required></label><label>What would you like to discuss?<select name="interest" required><option value="">Choose a topic</option><?php foreach (['Career guidance', 'Skills and training', 'Interview practice', 'A little help figuring that out'] as $option): ?><option <?= $interest === $option ? 'selected' : '' ?>><?= escape_html($option) ?></option><?php endforeach; ?></select></label><label>Available date and time<select name="slot_id" required><option value="">Choose an available time</option><?php foreach ($slots as $slot): ?><option value="<?= (int) $slot['id'] ?>" <?= (string) $slotId === (string) $slot['id'] ? 'selected' : '' ?>><?= escape_html(date('D, M j · g:i a', strtotime((string) $slot['starts_at']))) ?><?= $slot['expert_name'] ? ' · ' . escape_html($slot['expert_name']) : '' ?></option><?php endforeach; ?></select><?php if (!$slots): ?><small>No times are open right now. Please check back soon or <a href="/#contact">send us a message</a>.</small><?php endif; ?></label><label>Anything helpful to know? <small>Optional</small><textarea name="note" rows="4" maxlength="1000"></textarea></label><button class="button button-dark" type="submit">Request this time ↗</button><p class="feature-muted">This is a request, not a confirmed appointment. The team will follow up to confirm availability.</p></form></main><?php require __DIR__ . '/includes/sticky-ctas.php'; require __DIR__ . '/includes/tracking-body.php'; ?></body></html>
