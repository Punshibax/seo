<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_admin_authenticated() || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: login');
    exit;
}

$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => 'Lax',
]);
session_destroy();
header('Location: login');
exit;
