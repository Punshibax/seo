<?php
declare(strict_types=1);

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? $path : '/';
$root = realpath(__DIR__);

// Match the Apache rules: never serve private project files or setup scripts.
if (preg_match('~^/(?:includes|database|\.git)(?:/|$)~i', $path)
    || preg_match('~^/(?:config(?:\.example)?\.php|create_admin\.php|router\.php|\.env(?:\.|/|$))~i', $path)) {
    http_response_code(404);
    exit('Not found.');
}

if ($path === '/robots.txt') {
    require __DIR__ . '/robots.php';
    exit;
}
if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    exit;
}

// Redirect old PHP URLs to their public extensionless address.
if (preg_match('~\.php$~i', $path)) {
    $relative = ltrim($path, '/');
    $candidate = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    if ($candidate !== false && $root !== false && str_starts_with(strtolower($candidate), strtolower($root . DIRECTORY_SEPARATOR))) {
        $cleanPath = substr($path, 0, -4);
        if ($path === '/index.php') $cleanPath = '/';
        if ($path === '/admin/index.php') $cleanPath = '/admin/';
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        http_response_code(308);
        header('Location: ' . $cleanPath . ($query !== '' ? '?' . $query : ''));
        exit;
    }
}

if ($path === '/admin') {
    http_response_code(308);
    header('Location: /admin/');
    exit;
}

if ($path === '/') {
    $target = '/index.php';
    $file = __DIR__ . '/index.php';
} elseif ($path === '/admin/') {
    $target = '/admin/index.php';
    $file = __DIR__ . '/admin/index.php';
} else {
    // Let the built-in server serve existing static files directly.
    $static = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/')));
    if ($static !== false && $root !== false && str_starts_with(strtolower($static), strtolower($root . DIRECTORY_SEPARATOR)) && is_file($static)) {
        return false;
    }

    if (str_contains($path, '..') || !preg_match('~^/[A-Za-z0-9_./-]+$~', $path)) {
        http_response_code(404);
        exit('Not found.');
    }
    $target = $path . '.php';
    $file = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($target, '/'));
}

$realTarget = realpath($file);
if ($realTarget === false || $root === false || !str_starts_with(strtolower($realTarget), strtolower($root . DIRECTORY_SEPARATOR)) || !is_file($realTarget)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = $target;
$_SERVER['PHP_SELF'] = $target;
$_SERVER['SCRIPT_FILENAME'] = $realTarget;
require $realTarget;
