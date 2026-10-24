<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
if (!is_user_authenticated()) { http_response_code(401); header('Content-Type: application/json'); echo '{"error":"sign-in required"}'; exit; }
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
$q=database_connection()->prepare("SELECT COUNT(*) FROM lead_messages m INNER JOIN leads l ON l.id=m.lead_id WHERE l.user_id=:uid AND m.sender_type='admin' AND m.is_read=0");
$q->execute(['uid'=>current_user_id()]); echo json_encode(['unread'=>(int)$q->fetchColumn()]);
