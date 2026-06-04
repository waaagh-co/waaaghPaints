<?php
// ── Site identity ─────────────────────────────────────────────────────────
// Shown in the footer and used for Open Graph / canonical meta tags.
define('SITE_TITLE',   'Waaagh! Paint');
define('SITE_DOMAIN',  'yourdomain.com');           // display only, no https://
define('SITE_AUTHOR',  'Your Name');
define('SITE_EMAIL',   'you@yourdomain.com');
define('SITE_URL',     'https://yourdomain.com/');  // trailing slash required

// ── Admin panel filename ──────────────────────────────────────────────────
// For security, rename admin.php to something harder to guess, then set this
// to match. Example: rename it to waaagh-control.php and set 'waaagh-control.php'.
// All internal redirects and links update automatically. Default: 'admin.php'.
define('ADMIN_FILENAME', 'admin.php');

// ── Admin password ────────────────────────────────────────────────────────
// Change this before deploying. Pick something strong.
define('ADMIN_PASSWORD', 'change-me');

// ── Analytics (optional) ──────────────────────────────────────────────────
// Set your GA4 Measurement ID (G-XXXXXXXXXX) or leave empty string to disable.
define('GA4_ID', '');

// ── Landing page customisation ────────────────────────────────────────────
// Override the mast image and tagline on the Looted Knowledge landing tab.
define('HERO_IMAGE',   'img/looted.png');
define('SITE_TAGLINE', "Dis is not a blog. It's a paintin' tool. Use it. Steal from it. Ignore half of it.");

// ── Feature toggles ───────────────────────────────────────────────────────
// Set to false to hide a feature entirely. Omitting a constant keeps it visible.
define('SHOW_HEATMAP',   true);   // Hobby Activity heatmap on the The Commissar page
define('SHOW_WC_NEWS',   true);   // Latest from Warhammer Community widget
define('SHOW_COMMISSAR', true);   // The Commissar's Dossier tab
