<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('This request expired.'); }
unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name']);
session_regenerate_id(true);
header('Location: account', true, 303);
