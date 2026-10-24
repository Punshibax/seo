<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
header('Cache-Control: no-store, private');
$db = database_connection();
$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $error = 'This page expired. Refresh and try again.';
    else {
        $values = [
            'site_url' => rtrim(trim((string)($_POST['site_url'] ?? '')), '/'),
            'seo_title' => trim((string)($_POST['seo_title'] ?? '')),
            'seo_description' => trim((string)($_POST['seo_description'] ?? '')),
            'seo_keywords' => trim((string)($_POST['seo_keywords'] ?? '')),
            'robots_directive' => in_array($_POST['robots_directive'] ?? '', ['index,follow','noindex,follow','index,nofollow','noindex,nofollow'], true) ? (string) $_POST['robots_directive'] : 'index,follow',
            'whatsapp_number' => preg_replace('/\D+/', '', (string)($_POST['whatsapp_number'] ?? '')) ?? '',
            'google_site_verification' => trim((string)($_POST['google_site_verification'] ?? '')),
            'google_analytics_id' => strtoupper(trim((string)($_POST['google_analytics_id'] ?? ''))),
            'ad_head_code' => (string)($_POST['ad_head_code'] ?? ''),
            'ad_body_code' => (string)($_POST['ad_body_code'] ?? ''),
        ];
        $urlParts = $values['site_url'] !== '' ? parse_url($values['site_url']) : [];
        if ($values['site_url'] !== '' && (filter_var($values['site_url'], FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) ($urlParts['scheme'] ?? '')), ['http', 'https'], true) || empty($urlParts['host']))) $error = 'Enter a valid website URL, including https://.';
        elseif ($values['seo_title'] === '' || text_length($values['seo_title']) > 200 || text_length($values['seo_description']) > 320 || text_length($values['seo_keywords']) > 500) $error = 'Add an SEO title and keep the description and keywords within the shown limits.';
        elseif ($values['google_analytics_id'] !== '' && !preg_match('/^G-[A-Z0-9]+$/', $values['google_analytics_id'])) $error = 'Google Analytics ID should look like G-XXXXXXXXXX.';
        elseif ($values['whatsapp_number'] !== '' && (strlen($values['whatsapp_number']) < 8 || strlen($values['whatsapp_number']) > 15)) $error = 'Enter a WhatsApp number with 8 to 15 digits, including the country code.';
        elseif (strlen($values['ad_head_code']) > 20000 || strlen($values['ad_body_code']) > 20000) $error = 'Ad or tracking code is too long.';
        else {
            $stmt = $db->prepare('INSERT INTO app_settings (setting_key,setting_value) VALUES (:key,:value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
            foreach ($values as $key => $value) $stmt->execute(['key'=>$key,'value'=>$value]);
            header('Location: settings?saved=1'); exit;
        }
    }
}
$settings = app_settings();
if (isset($_GET['saved'])) $notice = 'Settings saved. SEO files update automatically.';
$field = static fn(string $key, string $default = ''): string => escape_html($_POST[$key] ?? ($settings[$key] ?? $default));
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>SEO and site settings · You Aspirant admin</title><link rel="stylesheet" href="../styles.css"><link rel="stylesheet" href="admin.css"><link rel="stylesheet" href="../features.css"></head><body class="admin-body"><header class="dashboard-header"><a class="admin-brand" href="/admin"><span class="brand-mark"><span>Y</span></span><span><strong>you aspirant</strong><small>Site settings</small></span></a><a class="logout-button" href="/admin">Inbox ↗</a></header><?php require __DIR__ . '/_nav.php'; ?><main class="dashboard-main"><div class="dashboard-title"><div><p class="admin-eyebrow">Your site’s search presence</p><h1>SEO & integrations</h1><p class="dashboard-subtitle">Set defaults once. The site applies them to pages, the sitemap, and robots.txt.</p></div></div><?php if ($notice): ?><p class="admin-notice"><?= escape_html($notice) ?></p><?php endif; ?><?php if ($error): ?><p class="admin-error"><?= escape_html($error) ?></p><?php endif; ?><form class="feature-panel feature-form settings-form" method="post"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><h2>Search defaults</h2><label>Canonical website URL<input name="site_url" type="url" placeholder="https://your-domain.com" value="<?= $field('site_url') ?>"><small>Used to build sitemap.xml and canonical page links.</small></label><label>Default SEO title<input name="seo_title" maxlength="200" value="<?= $field('seo_title','You Aspirant — Career guidance that starts with you') ?>" required></label><label>Default description<textarea name="seo_description" maxlength="320" rows="3" required><?= $field('seo_description') ?></textarea><small>Keep under 320 characters.</small></label><label>Default keywords<input name="seo_keywords" maxlength="500" value="<?= $field('seo_keywords') ?>"></label><label>Default robots directive<select name="robots_directive"><?php foreach (['index,follow','noindex,follow','index,nofollow','noindex,nofollow'] as $option): ?><option value="<?= $option ?>" <?= $field('robots_directive','index,follow') === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select></label><h2>Contact & measurement</h2><label>WhatsApp phone number<input name="whatsapp_number" inputmode="tel" placeholder="Country code + number, digits only" value="<?= $field('whatsapp_number') ?>"><small>Example format: 919876543210. This powers the sticky WhatsApp button.</small></label><label>Google Search Console verification code<input name="google_site_verification" maxlength="255" value="<?= $field('google_site_verification') ?>"><small>Paste the content value from Google’s HTML tag. The meta tag is added to the homepage.</small></label><label>Google Analytics measurement ID<input name="google_analytics_id" maxlength="40" placeholder="G-XXXXXXXXXX" value="<?= $field('google_analytics_id') ?>"></label><h2>Ad and tracking snippets</h2><label>Code for the page head<textarea name="ad_head_code" rows="6" maxlength="20000"><?= $field('ad_head_code') ?></textarea><small>Only paste snippets from services you trust. This code is added to public pages.</small></label><label>Code near the end of the page body<textarea name="ad_body_code" rows="6" maxlength="20000"><?= $field('ad_body_code') ?></textarea></label><div class="feature-actions"><button class="button button-dark" type="submit">Save settings ↗</button><a href="../sitemap.xml" target="_blank" rel="noopener">Open sitemap ↗</a><a href="../robots.txt" target="_blank" rel="noopener">Open robots.txt ↗</a></div></form></main></body></html>
