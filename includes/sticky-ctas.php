<?php
$stickyNumber = preg_replace('/\D+/', '', app_setting('whatsapp_number'));
$enquiryTarget = basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'index.php' ? '#contact' : '/#contact';
?>
<div class="sticky-ctas" aria-label="Quick contact"><a class="sticky-enquiry" href="<?= escape_html($enquiryTarget) ?>">Send an enquiry <span aria-hidden="true">↗</span></a><?php if ($stickyNumber !== ''): ?><a class="sticky-whatsapp" href="https://wa.me/<?= escape_html($stickyNumber) ?>?text=Hi%20You%20Aspirant%2C%20I%27d%20like%20to%20know%20about%20career%20guidance." target="_blank" rel="noopener">WhatsApp <span aria-hidden="true">↗</span></a><?php endif; ?></div>
