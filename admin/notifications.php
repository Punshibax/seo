<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (!is_admin_authenticated()) { http_response_code(401); header('Content-Type: application/json'); echo '{"error":"sign-in required"}'; exit; }
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $db = database_connection();
    $newLeads = (int) $db->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn();
    $unreadReplies = (int) $db->query("SELECT COUNT(*) FROM lead_messages WHERE sender_type = 'student' AND is_read = 0")->fetchColumn();
    $pendingBookings = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    echo json_encode(['new_leads'=>$newLeads,'unread_replies'=>$unreadReplies,'pending_bookings'=>$pendingBookings,'total'=>$newLeads+$unreadReplies+$pendingBookings]);
} catch (Throwable $exception) { http_response_code(503); echo '{"error":"notifications unavailable"}'; }
