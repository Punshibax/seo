<?php
$trackingId = app_setting('google_analytics_id');
if (preg_match('/^G-[A-Z0-9]+$/i', $trackingId)): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= escape_html($trackingId) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= escape_html($trackingId) ?>');</script>
<?php endif; ?>
<?= app_setting('ad_head_code') ?>
