<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$slug = is_string($_GET['slug'] ?? null) ? trim($_GET['slug']) : '';
$statement = database_connection()->prepare("SELECT * FROM blog_posts WHERE slug = :slug AND status = 'published' AND published_at <= NOW() LIMIT 1");
$statement->execute(['slug' => $slug]);
$post = $statement->fetch();
if (!$post) { http_response_code(404); }
$title = $post ? ((string) $post['seo_title'] !== '' ? $post['seo_title'] : $post['title']) : 'Article not found';
$description = $post ? ((string) $post['seo_description'] !== '' ? $post['seo_description'] : $post['excerpt']) : 'This article could not be found.';
$globalRobots = app_setting('robots_directive', 'index,follow');
$robots = $post ? (string) $post['robots'] : 'noindex,nofollow';
if (str_starts_with($globalRobots, 'noindex')) $robots = $globalRobots;
$keywords = $post && (string) $post['seo_keywords'] !== '' ? (string) $post['seo_keywords'] : app_setting('seo_keywords');
$canonical = $post ? canonical_url('/article?slug=' . rawurlencode((string) $post['slug'])) : '';
$articleJson = $post ? json_encode(['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => (string) $post['title'], 'description' => $description, 'datePublished' => (string) $post['published_at'], 'dateModified' => (string) $post['updated_at'], 'publisher' => ['@type' => 'Organization', 'name' => 'You Aspirant']], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '';
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="<?= escape_html($description) ?>"><meta name="keywords" content="<?= escape_html($keywords) ?>"><?php if ($canonical !== ''): ?><link rel="canonical" href="<?= escape_html($canonical) ?>"><?php endif; ?><meta name="robots" content="<?= escape_html($robots) ?>"><title><?= escape_html($title) ?> · You Aspirant</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="features.css"><?php require __DIR__ . '/includes/tracking-head.php'; ?><?php if ($articleJson !== ''): ?><script type="application/ld+json"><?= $articleJson ?></script><?php endif; ?></head><body class="feature-page"><header class="feature-header"><a class="brand" href="/"><span class="brand-mark"><span>Y</span></span><span class="brand-copy"><strong>you aspirant</strong><small>Make your next step yours.</small></span></a><a href="blog">← All articles</a></header><main class="feature-shell article-shell"><?php if ($post): ?><p class="eyebrow eyebrow-muted"><?= escape_html(date('F j, Y', strtotime((string) $post['published_at']))) ?></p><h1><?= escape_html($post['title']) ?></h1><?php if ($post['excerpt'] !== ''): ?><p class="article-excerpt"><?= escape_html($post['excerpt']) ?></p><?php endif; ?><article class="article-body"><?= nl2br(escape_html($post['body'])) ?></article><p class="article-end"><a href="book">Talk with a career guide ↗</a></p><?php else: ?><h1>We couldn’t find that article.</h1><p><a href="blog">Browse all articles ↗</a></p><?php endif; ?></main><?php require __DIR__ . '/includes/sticky-ctas.php'; require __DIR__ . '/includes/tracking-body.php'; ?></body></html>
