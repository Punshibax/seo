<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
header('Cache-Control: no-store, private');
$db = database_connection();
$notice = '';
$error = '';
$editId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editing = null;
if ($editId) { $q = $db->prepare('SELECT * FROM blog_posts WHERE id = :id'); $q->execute(['id' => $editId]); $editing = $q->fetch() ?: null; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $error = 'This page expired. Refresh and try again.';
    else {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
        if ($slug === '') $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title) ?? '', '-'));
        $slug = trim(preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '', '-');
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $seoTitle = trim((string) ($_POST['seo_title'] ?? ''));
        $seoDescription = trim((string) ($_POST['seo_description'] ?? ''));
        $seoKeywords = trim((string) ($_POST['seo_keywords'] ?? ''));
        $robots = in_array($_POST['robots'] ?? '', ['index,follow', 'noindex,follow'], true) ? (string) $_POST['robots'] : 'index,follow';
        $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? (string) $_POST['status'] : 'draft';
        if ($title === '' || text_length($title) > 200 || $slug === '' || strlen($slug) > 220 || $body === '' || text_length($excerpt) > 500 || text_length($seoTitle) > 200 || text_length($seoDescription) > 320 || text_length($seoKeywords) > 500) $error = 'Add a title, valid URL slug, and article text. Keep the SEO fields within their displayed limits.';
        else {
            try {
                if ($id) {
                    $sql = "UPDATE blog_posts SET title=:title, slug=:slug, excerpt=:excerpt, body=:body, seo_title=:seo_title, seo_description=:seo_description, seo_keywords=:seo_keywords, robots=:robots, status=:status, published_at=CASE WHEN :published='published' THEN COALESCE(published_at, NOW()) ELSE published_at END WHERE id=:id";
                    $stmt = $db->prepare($sql); $stmt->execute(['title'=>$title,'slug'=>$slug,'excerpt'=>$excerpt,'body'=>$body,'seo_title'=>$seoTitle,'seo_description'=>$seoDescription,'seo_keywords'=>$seoKeywords,'robots'=>$robots,'status'=>$status,'published'=>$status,'id'=>$id]);
                } else {
                    $stmt = $db->prepare("INSERT INTO blog_posts (title,slug,excerpt,body,seo_title,seo_description,seo_keywords,robots,status,published_at) VALUES (:title,:slug,:excerpt,:body,:seo_title,:seo_description,:seo_keywords,:robots,:status,IF(:published='published',NOW(),NULL))");
                    $stmt->execute(['title'=>$title,'slug'=>$slug,'excerpt'=>$excerpt,'body'=>$body,'seo_title'=>$seoTitle,'seo_description'=>$seoDescription,'seo_keywords'=>$seoKeywords,'robots'=>$robots,'status'=>$status,'published'=>$status]);
                }
                header('Location: blogs?saved=1'); exit;
            } catch (Throwable $exception) { $error = 'Could not save this post. Check that its URL slug is unique.'; }
        }
    }
}
if (isset($_GET['saved'])) $notice = 'Article saved.';
$posts = $db->query('SELECT id,title,slug,status,updated_at FROM blog_posts ORDER BY updated_at DESC')->fetchAll();
$v = static fn(string $key, string $default = ''): string => escape_html($_POST[$key] ?? (is_array($editing) ? ($editing[$key] ?? $default) : $default));
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Blog editor · You Aspirant</title><link rel="stylesheet" href="../styles.css"><link rel="stylesheet" href="admin.css"><link rel="stylesheet" href="../features.css"></head><body class="admin-body"><header class="dashboard-header"><a class="admin-brand" href="/admin"><span class="brand-mark"><span>Y</span></span><span><strong>you aspirant</strong><small>Content desk</small></span></a><a class="logout-button" href="/admin">Inbox ↗</a></header><?php require __DIR__ . '/_nav.php'; ?><main class="dashboard-main"><div class="dashboard-title"><div><p class="admin-eyebrow">Search-friendly articles</p><h1>Blog editor</h1></div></div><?php if ($notice): ?><p class="admin-notice"><?= escape_html($notice) ?></p><?php endif; ?><?php if ($error): ?><p class="admin-error"><?= escape_html($error) ?></p><?php endif; ?><div class="admin-content-grid"><section class="feature-panel"><h2><?= $editing ? 'Edit article' : 'Write an article' ?></h2><form class="feature-form" method="post"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $editing ? (int)$editing['id'] : '' ?>"><label>Article title<input name="title" maxlength="200" value="<?= $v('title') ?>" required></label><label>URL slug<input name="slug" maxlength="220" value="<?= $v('slug') ?>" placeholder="leave blank to create from title"></label><label>Short summary<textarea name="excerpt" maxlength="500" rows="3"><?= $v('excerpt') ?></textarea></label><label>Article text<textarea name="body" rows="12" required><?= $v('body') ?></textarea><small>Write plain text with a blank line between paragraphs. HTML is escaped on the public page.</small></label><h3>Search settings</h3><label>SEO title<input name="seo_title" maxlength="200" value="<?= $v('seo_title') ?>"></label><label>SEO description<textarea name="seo_description" maxlength="320" rows="2"><?= $v('seo_description') ?></textarea></label><label>SEO keywords<input name="seo_keywords" maxlength="500" value="<?= $v('seo_keywords') ?>"></label><label>Search visibility<select name="robots"><option value="index,follow" <?= $v('robots','index,follow') === 'index,follow' ? 'selected' : '' ?>>Index and follow</option><option value="noindex,follow" <?= $v('robots') === 'noindex,follow' ? 'selected' : '' ?>>Do not index</option></select></label><label>Publication status<select name="status"><option value="draft" <?= $v('status','draft') === 'draft' ? 'selected' : '' ?>>Save as draft</option><option value="published" <?= $v('status') === 'published' ? 'selected' : '' ?>>Publish now</option></select></label><button class="button button-dark" type="submit">Save article ↗</button></form></section><section class="feature-panel"><h2>Articles</h2><?php if (!$posts): ?><p class="feature-muted">No articles yet.</p><?php endif; ?><div class="admin-list"><?php foreach ($posts as $post): ?><article><div><strong><?= escape_html($post['title']) ?></strong><small><?= escape_html($post['status']) ?> · /article?slug=<?= escape_html($post['slug']) ?></small></div><a href="blogs?id=<?= (int)$post['id'] ?>">Edit ↗</a></article><?php endforeach; ?></div><p class="feature-help"><a href="../blog" target="_blank" rel="noopener">View public blog ↗</a></p></section></div></main></body></html>
