<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
$base = rtrim(app_setting('site_url'), '/');
echo "User-agent: *\nDisallow: /admin\nDisallow: /account\nDisallow: /my-account\nDisallow: /student-action\nDisallow: /student-notifications\n";
if ($base !== '') echo 'Sitemap: ' . $base . "/sitemap.xml\n";
