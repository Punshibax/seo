<?php
declare(strict_types=1);

$secureCookie = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('you_aspirant_session');
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $secureCookie,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $configPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Setup required: copy config.example.php to config.php and add database settings.');
    }

    $loaded = require $configPath;
    if (!is_array($loaded)) {
        throw new RuntimeException('The config.php file must return a PHP array.');
    }
    $config = $loaded;
    return $config;
}

function database_connection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $database = app_config()['database'] ?? [];
    foreach (['host', 'name', 'user', 'password'] as $key) {
        if (!array_key_exists($key, $database)) {
            throw new RuntimeException('Database configuration is incomplete.');
        }
    }

    $port = (int) ($database['port'] ?? 3306);
    $charset = (string) ($database['charset'] ?? 'utf8mb4');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $database['host'], $port, $database['name'], $charset);
    $pdo = new PDO($dsn, (string) $database['user'], (string) $database['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($candidate): bool
{
    return is_string($candidate) && hash_equals(csrf_token(), $candidate);
}

function escape_html($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_admin_authenticated(): bool
{
    return !empty($_SESSION['admin_authenticated']) && !empty($_SESSION['admin_email']);
}

function current_user_id(): ?int
{
    $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function is_user_authenticated(): bool
{
    return current_user_id() !== null && !empty($_SESSION['user_email']);
}

function require_user(): void
{
    if (!is_user_authenticated()) {
        header('Location: /account');
        exit;
    }
}

function app_settings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    try {
        $rows = database_connection()->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
    } catch (Throwable $exception) {
        $settings = [];
    }
    return $settings;
}

function app_setting(string $key, string $default = ''): string
{
    $settings = app_settings();
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function youtube_video_id(string $url): string
{
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $matches)) {
        return $matches[1];
    }
    return '';
}

function canonical_url(string $path): string
{
    $base = rtrim(app_setting('site_url'), '/');
    return $base === '' ? '' : $base . '/' . ltrim($path, '/');
}

function require_admin(): void
{
    if (!is_admin_authenticated()) {
        header('Location: /admin/login');
        exit;
    }
}

function send_json(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}
