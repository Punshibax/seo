<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(403); exit('This request expired.');
}
$leadId = filter_var($_POST['lead_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$body = is_string($_POST['body'] ?? null) ? trim($_POST['body']) : '';
if ($leadId === false || $body === '' || text_length($body) > 3000) { header('Location: my-account'); exit; }
$db = database_connection();
$check = $db->prepare('SELECT id FROM leads WHERE id = :id AND user_id = :uid');
$check->execute(['id' => $leadId, 'uid' => current_user_id()]);
if ($check->fetchColumn()) {
    $insert = $db->prepare("INSERT INTO lead_messages (lead_id, sender_type, body) VALUES (:lead, 'student', :body)");
    $insert->execute(['lead' => $leadId, 'body' => $body]);
    $db->prepare("UPDATE leads SET status = 'new' WHERE id = :id")->execute(['id' => $leadId]);
}
header('Location: my-account#lead-' . (int) $leadId, true, 303);
