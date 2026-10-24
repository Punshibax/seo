<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
$base = rtrim(app_setting('site_url'), '/');
if ($base === '') $base = 'http://localhost';
$urls = [$base . '/', $base . '/quiz', $base . '/book', $base . '/blog'];
try {
    $posts = database_connection()->query("SELECT slug FROM blog_posts WHERE status='published' AND published_at<=NOW() ORDER BY published_at DESC")->fetchAll();
    foreach ($posts as $post) $urls[] = $base . '/article?slug=' . rawurlencode((string)$post['slug']);
} catch (Throwable $exception) {}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach ($urls as $url) echo '<url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>';
echo '</urlset>';
