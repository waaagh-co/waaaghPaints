<?php
require_once 'config.php';

if (!defined('SHOWCASE_PUBLIC') || !SHOWCASE_PUBLIC) {
    http_response_code(403);
    exit;
}

$all    = file_exists(__DIR__ . '/data/models.json')
          ? (json_decode(file_get_contents(__DIR__ . '/data/models.json'), true) ?? [])
          : [];
$models = array_values(array_filter($all, fn($m) => !empty($m['featured'])));
usort($models, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

$siteTitle    = (defined('SHOWCASE_TITLE') && SHOWCASE_TITLE !== '') ? SHOWCASE_TITLE : (defined('SITE_TITLE') ? SITE_TITLE : 'Showcase');
$siteAuthor   = defined('SITE_AUTHOR') ? SITE_AUTHOR : '';
$siteUrl      = defined('SITE_URL')    ? rtrim(SITE_URL, '/') : '';
$modelCount   = array_sum(array_map(function($m) {
    $f = $m['featured'] ?? null;
    return is_array($f) ? count($f) : 1;
}, $models));

// Build system filter list from featured models only
$rawSystems = array_unique(array_filter(array_column($models, 'system')));
sort($rawSystems);

// OG image: first image of first model
$ogImage = '';
foreach ($models as $m) {
    if (!empty($m['images'][0])) {
        $ogImage = $siteUrl . '/' . ltrim($m['images'][0], '/');
        break;
    }
}

function sc_sys_slug(string $s): string {
    return [
        '40k'      => '40k',
        '30k / HH' => '30k',
        'AoS'      => 'aos',
        'Kill Team' => 'kt',
        'Blood Bowl' => 'bb',
        'Necromunda' => 'necro',
        'Epic'     => 'epic',
        'OPR'      => 'opr',
        'Old World' => 'ow',
    ][$s] ?? 'other';
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($siteTitle) ?><?= $siteAuthor ? ' · ' . e($siteAuthor) : '' ?></title>
<meta name="description" content="<?= e($modelCount . ' painted miniatures' . ($siteAuthor ? ' by ' . $siteAuthor : '')) ?>">
<?php if ($ogImage): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta property="og:title" content="<?= e($siteTitle) ?>">
<meta property="og:description" content="<?= e($modelCount . ' painted miniatures' . ($siteAuthor ? ' by ' . $siteAuthor : '')) ?>">
<meta property="og:type" content="website">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&display=swap" rel="stylesheet">
<style>
/* ── Reset & tokens ─────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --gold:       #c9a227;
  --gold-dim:   #6a4f10;
  --border:     #2a2010;
  --bg-base:    #0e0d0a;
  --bg-dark:    #1a1408;
  --text-main:  #c4b49a;
  --text-dim:   #7a6a4a;
  --font-head:  'Cinzel', Georgia, serif;
}
body {
  background: var(--bg-base);
  color: var(--text-main);
  font-family: Georgia, serif;
  min-height: 100vh;
}
a { color: var(--gold-dim); text-decoration: none; }
a:hover { color: var(--gold); }

/* ── Hero ────────────────────────────────────────────────────────────────── */
.sc-hero {
  text-align: center;
  padding: 56px 24px 36px;
  border-bottom: 1px solid var(--border);
  background: linear-gradient(to bottom, #1a1408 0%, var(--bg-base) 100%);
}
.sc-hero-title {
  font-family: var(--font-head);
  font-size: clamp(1.6rem, 5vw, 3rem);
  font-weight: 600;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--gold);
  line-height: 1.15;
}
.sc-hero-author {
  font-family: var(--font-head);
  font-size: .8rem;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--text-dim);
  margin-top: 8px;
}
.sc-hero-meta {
  font-size: .75rem;
  color: #4a3a18;
  letter-spacing: .1em;
  text-transform: uppercase;
  margin-top: 14px;
  font-family: var(--font-head);
}

/* ── Filters ─────────────────────────────────────────────────────────────── */
.sc-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  max-width: 1200px;
  margin: 0 auto;
}
.sc-pill {
  font-family: var(--font-head);
  font-size: .65rem;
  letter-spacing: .1em;
  text-transform: uppercase;
  padding: 4px 12px;
  border-radius: 3px;
  border: 1px solid var(--border);
  background: var(--bg-dark);
  color: var(--text-dim);
  cursor: pointer;
  transition: border-color .15s, color .15s;
}
.sc-pill:hover { border-color: var(--gold-dim); color: var(--text-main); }
.sc-pill.active { border-color: var(--gold); color: var(--gold); }

/* ── Masonry grid ────────────────────────────────────────────────────────── */
.sc-grid {
  columns: 3;
  column-gap: 10px;
  padding: 14px;
  max-width: 1200px;
  margin: 0 auto;
}
@media (max-width: 860px) { .sc-grid { columns: 2; } }
@media (max-width: 480px) { .sc-grid { columns: 1; } }

/* ── Cards ───────────────────────────────────────────────────────────────── */
.sc-card {
  break-inside: avoid;
  position: relative;
  display: block;
  cursor: pointer;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 10px;
  border: 1px solid var(--border);
  transition: transform .2s ease, box-shadow .2s ease;
}
.sc-card:hover {
  transform: scale(1.015);
  box-shadow: 0 4px 24px var(--card-glow, var(--gold-dim));
  border-color: var(--gold-dim);
}
.sc-photo {
  width: 100%;
  display: block;
  aspect-ratio: unset;
}
.sc-overlay {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  background: linear-gradient(to top, rgba(0,0,0,.9) 0%, rgba(0,0,0,.5) 50%, transparent 100%);
  padding: 36px 10px 10px;
  pointer-events: none;
}
.sc-name {
  font-family: var(--font-head);
  font-size: .72rem;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--text-main);
  line-height: 1.3;
}
.sc-badges {
  display: flex;
  gap: 5px;
  align-items: center;
  margin-top: 4px;
  opacity: 0;
  transform: translateY(4px);
  transition: opacity .2s, transform .2s;
}
.sc-card:hover .sc-badges { opacity: 1; transform: none; }
.sc-summary-row {
  font-size: .6rem;
  color: #8a7a5a;
  margin-top: 4px;
  line-height: 1.4;
  opacity: 0;
  transform: translateY(4px);
  transition: opacity .2s .04s, transform .2s .04s;
}
.sc-card:hover .sc-summary-row { opacity: 1; transform: none; }
.sc-faction {
  font-size: .6rem;
  color: var(--text-dim);
  letter-spacing: .06em;
  font-family: var(--font-head);
}

/* System badges (match main app) */
.sys-game-badge {
  display: inline-block;
  font-size: .55rem;
  padding: 1px 6px;
  border-radius: 3px;
  color: #9a8a6a;
  letter-spacing: .07em;
  font-family: var(--font-head);
  text-transform: uppercase;
}
.sys-40k    { background: #5a1a1a; }
.sys-30k    { background: #4a3a0a; }
.sys-aos    { background: #1a2a5a; }
.sys-kt     { background: #0a3a3a; }
.sys-bb     { background: #0a3a1a; }
.sys-necro  { background: #3a0a5a; }
.sys-opr    { background: #1a2a3a; }
.sys-epic   { background: #1a3a1a; }
.sys-ow     { background: #2a3a1a; }
.sys-other  { background: #2a2a2a; }

/* ── Empty state ─────────────────────────────────────────────────────────── */
.sc-empty {
  text-align: center;
  padding: 80px 24px;
  color: var(--text-dim);
  font-family: var(--font-head);
  font-size: .8rem;
  letter-spacing: .1em;
  text-transform: uppercase;
}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.sc-footer {
  border-top: 1px solid var(--border);
  padding: 24px;
  text-align: center;
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: center;
  align-items: center;
  margin-top: 24px;
}
.sc-footer-link {
  font-family: var(--font-head);
  font-size: .65rem;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--text-dim);
  transition: color .15s;
}
.sc-footer-link:hover { color: var(--gold); }
.sc-social-icon { width: 18px; height: 18px; fill: var(--text-dim); transition: fill .15s; vertical-align: middle; }
.sc-social-icon:hover { fill: var(--gold); }

/* ── Lightbox ────────────────────────────────────────────────────────────── */
.sc-lb-overlay {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0,0,0,.93);
  align-items: center;
  justify-content: center;
}
.sc-lb-overlay.open { display: flex; }
.sc-lb-img {
  max-width: 90vw;
  max-height: 88vh;
  object-fit: contain;
  border: 1px solid var(--border);
  box-shadow: 0 0 60px rgba(0,0,0,.9), 0 0 20px rgba(201,162,39,.12);
}
.sc-lb-close {
  position: absolute;
  top: 8px; right: 14px;
  font-size: 32px;
  color: var(--text-dim);
  cursor: pointer;
  line-height: 1;
  transition: color .15s;
}
.sc-lb-close:hover { color: var(--gold); }
.sc-lb-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  font-size: 42px;
  color: var(--text-dim);
  cursor: pointer;
  background: rgba(0,0,0,.5);
  padding: 6px 14px;
  border-radius: 3px;
  transition: color .15s, background .15s;
  user-select: none;
}
.sc-lb-arrow:hover { color: var(--gold); background: rgba(0,0,0,.8); }
.sc-lb-prev { left: 10px; }
.sc-lb-next { right: 10px; }
.sc-lb-counter {
  position: absolute;
  bottom: 14px; left: 50%;
  transform: translateX(-50%);
  font-family: var(--font-head);
  font-size: .6rem;
  letter-spacing: .1em;
  color: var(--text-dim);
}
</style>
</head>
<body>

<header class="sc-hero">
  <h1 class="sc-hero-title"><?= e($siteTitle) ?></h1>
  <?php if ($siteAuthor): ?>
    <p class="sc-hero-author"><?= e($siteAuthor) ?></p>
  <?php endif; ?>
  <p class="sc-hero-meta"><?= $modelCount ?> painted model<?= $modelCount !== 1 ? 's' : '' ?></p>
</header>

<?php if (!empty($rawSystems)): ?>
<nav class="sc-filters" id="sc-filters" aria-label="Filter by game system">
  <button class="sc-pill active" data-sys="">All</button>
  <?php foreach ($rawSystems as $sys): ?>
    <button class="sc-pill" data-sys="<?= e($sys) ?>"><?= e($sys) ?></button>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<main class="sc-grid" id="sc-grid">
<?php if (empty($models)): ?>
  <p class="sc-empty">No models showcased yet.</p>
<?php else: ?>
  <?php foreach ($models as $m):
    $slug    = sc_sys_slug($m['system'] ?? '');
    $glow    = preg_match('/^#[0-9a-f]{6}$/i', $m['theme_hex'] ?? '') ? $m['theme_hex'] : '#6a4f10';
    $images  = array_values(array_filter($m['images'] ?? []));
    $imgJson = htmlspecialchars(json_encode($images), ENT_QUOTES, 'UTF-8');
    $finish  = trim($m['summary']['finish'] ?? '');
    $tech    = trim($m['summary']['technique'] ?? '');
    $sumLine = implode(' · ', array_filter([$finish, $tech]));
    // Resolve which image indices to show as individual cards
    $featVal  = $m['featured'] ?? null;
    $showIdxs = is_array($featVal) ? $featVal : [0];
    foreach ($showIdxs as $imgIdx):
      $thumb = $images[$imgIdx] ?? $images[0] ?? '';
  ?>
  <div class="sc-card"
       data-sys="<?= e($m['system'] ?? '') ?>"
       data-images="<?= $imgJson ?>"
       data-start="<?= (int)$imgIdx ?>"
       style="--card-glow:<?= e($glow) ?>44">
    <?php if ($thumb): ?>
      <img class="sc-photo" src="<?= e($thumb) ?>" alt="<?= e($m['name'] ?? '') ?>" loading="lazy">
    <?php else: ?>
      <div class="sc-photo" style="height:200px;background:#1a1408;"></div>
    <?php endif; ?>
    <div class="sc-overlay">
      <div class="sc-name"><?= e($m['name'] ?? '') ?></div>
      <div class="sc-badges">
        <?php if (!empty($m['system'])): ?>
          <span class="sys-game-badge sys-<?= e($slug) ?>"><?= e($m['system']) ?></span>
        <?php endif; ?>
        <?php if (!empty($m['faction'])): ?>
          <span class="sc-faction"><?= e($m['faction']) ?></span>
        <?php endif; ?>
      </div>
      <?php if ($sumLine): ?>
        <div class="sc-summary-row"><?= e($sumLine) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; endforeach; ?>
<?php endif; ?>
</main>

<footer class="sc-footer">
  <?php if (defined('SOCIAL_INSTAGRAM') && SOCIAL_INSTAGRAM): ?>
    <a href="<?= e(SOCIAL_INSTAGRAM) ?>" class="sc-footer-link" target="_blank" rel="noopener" title="Instagram">
      <svg class="sc-social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
    </a>
  <?php endif; ?>
  <?php if (defined('SOCIAL_THREADS') && SOCIAL_THREADS): ?>
    <a href="<?= e(SOCIAL_THREADS) ?>" class="sc-footer-link" target="_blank" rel="noopener" title="Threads">
      <svg class="sc-social-icon" viewBox="0 0 192 192" xmlns="http://www.w3.org/2000/svg"><path d="M141.537 88.988a66.667 66.667 0 0 0-2.518-1.143c-1.482-27.307-16.403-42.94-41.457-43.1h-.34c-14.986 0-27.449 6.396-35.12 18.036l13.779 9.452c5.73-8.695 14.724-10.548 21.348-10.548h.229c8.249.053 14.474 2.452 18.503 7.129 2.932 3.405 4.893 8.111 5.864 14.05-7.314-1.243-15.224-1.626-23.68-1.14-23.82 1.371-39.134 15.264-38.105 34.568.522 9.792 5.4 18.216 13.735 23.719 7.047 4.652 16.124 6.927 25.557 6.412 12.458-.683 22.231-5.436 29.049-14.127 5.178-6.6 8.453-15.153 9.899-25.93 5.937 3.583 10.337 8.298 12.767 13.966 4.132 9.635 4.373 25.468-8.546 38.376-11.319 11.308-24.925 16.2-45.488 16.351-22.809-.169-40.06-7.484-51.275-21.742C35.236 139.966 29.808 120.682 29.605 96c.203-24.682 5.63-43.966 16.133-57.317C56.954 24.425 74.204 17.11 97.013 16.94c22.975.17 40.526 7.52 52.171 21.847 5.71 7.026 10.015 15.86 12.853 26.162l16.147-4.308c-3.44-12.68-8.853-23.606-16.219-32.668C147.036 9.607 125.202.195 97.24 0h-.483C68.962.195 47.42 9.643 32.638 28.08 19.474 44.479 12.666 67.316 12.43 95.983v.034c.236 28.667 7.044 51.503 20.208 67.903C47.42 182.358 68.962 191.806 96.757 192h.483c24.814-.182 42.29-6.67 56.655-21.017 18.941-18.925 18.131-42.521 11.992-57.01-4.087-9.535-11.777-17.353-23.35-23.985zm-40.505 34.678c-10.44.587-21.286-4.098-21.821-14.135-.396-7.442 5.296-15.746 22.462-16.735 1.966-.113 3.895-.169 5.79-.169 6.235 0 12.068.606 17.371 1.765-1.978 24.702-13.58 28.713-23.802 29.274z"/></svg>
    </a>
  <?php endif; ?>
  <?php if (defined('SOCIAL_YOUTUBE') && SOCIAL_YOUTUBE): ?>
    <a href="<?= e(SOCIAL_YOUTUBE) ?>" class="sc-footer-link" target="_blank" rel="noopener" title="YouTube">
      <svg class="sc-social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>
    </a>
  <?php endif; ?>
  <?php if (defined('SOCIAL_FACEBOOK') && SOCIAL_FACEBOOK): ?>
    <a href="<?= e(SOCIAL_FACEBOOK) ?>" class="sc-footer-link" target="_blank" rel="noopener" title="Facebook">
      <svg class="sc-social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
    </a>
  <?php endif; ?>
  <?php if ($siteUrl): ?>
    <a href="<?= e($siteUrl) ?>" class="sc-footer-link">← <?= e(defined('SITE_TITLE') ? SITE_TITLE : 'Back') ?></a>
  <?php endif; ?>
</footer>

<!-- Lightbox -->
<div class="sc-lb-overlay" id="sc-lb" role="dialog" aria-modal="true" aria-label="Image viewer">
  <span class="sc-lb-close" id="sc-lb-close" aria-label="Close">&times;</span>
  <span class="sc-lb-arrow sc-lb-prev" id="sc-lb-prev" aria-label="Previous">&#8249;</span>
  <img class="sc-lb-img" id="sc-lb-img" src="" alt="">
  <span class="sc-lb-arrow sc-lb-next" id="sc-lb-next" aria-label="Next">&#8250;</span>
  <span class="sc-lb-counter" id="sc-lb-counter"></span>
</div>

<script>
(function() {
  // ── Filter pills ──────────────────────────────────────────────────────────
  const pills = document.querySelectorAll('.sc-pill');
  const cards = document.querySelectorAll('.sc-card');
  pills.forEach(function(pill) {
    pill.addEventListener('click', function() {
      pills.forEach(function(p) { p.classList.remove('active'); });
      pill.classList.add('active');
      const sys = pill.dataset.sys;
      cards.forEach(function(card) {
        card.style.display = (!sys || card.dataset.sys === sys) ? '' : 'none';
      });
    });
  });

  // ── Lightbox ──────────────────────────────────────────────────────────────
  const overlay  = document.getElementById('sc-lb');
  const img      = document.getElementById('sc-lb-img');
  const counter  = document.getElementById('sc-lb-counter');
  const btnClose = document.getElementById('sc-lb-close');
  const btnPrev  = document.getElementById('sc-lb-prev');
  const btnNext  = document.getElementById('sc-lb-next');
  let lbImages = [], lbIdx = 0;

  function lbShow() {
    img.src = lbImages[lbIdx];
    const total = lbImages.length;
    counter.textContent = total > 1 ? (lbIdx + 1) + ' / ' + total : '';
    btnPrev.style.visibility = (total > 1 && lbIdx > 0)           ? '' : 'hidden';
    btnNext.style.visibility = (total > 1 && lbIdx < total - 1)   ? '' : 'hidden';
  }

  function lbOpen(images, startIdx) {
    lbImages = images;
    lbIdx = startIdx || 0;
    lbShow();
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function lbClose() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    img.src = '';
  }

  btnClose.addEventListener('click', lbClose);
  btnPrev.addEventListener('click', function() { if (lbIdx > 0) { lbIdx--; lbShow(); } });
  btnNext.addEventListener('click', function() { if (lbIdx < lbImages.length - 1) { lbIdx++; lbShow(); } });

  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) lbClose();
  });

  document.addEventListener('keydown', function(e) {
    if (!overlay.classList.contains('open')) return;
    if (e.key === 'Escape')      lbClose();
    if (e.key === 'ArrowLeft'  && lbIdx > 0)                    { lbIdx--; lbShow(); }
    if (e.key === 'ArrowRight' && lbIdx < lbImages.length - 1)  { lbIdx++; lbShow(); }
  });

  // Swipe support
  let touchStartX = null;
  overlay.addEventListener('touchstart', function(e) { touchStartX = e.touches[0].clientX; }, { passive: true });
  overlay.addEventListener('touchend', function(e) {
    if (touchStartX === null) return;
    const dx = e.changedTouches[0].clientX - touchStartX;
    touchStartX = null;
    if (Math.abs(dx) < 40) return;
    if (dx < 0 && lbIdx < lbImages.length - 1) { lbIdx++; lbShow(); }
    if (dx > 0 && lbIdx > 0)                    { lbIdx--; lbShow(); }
  }, { passive: true });

  // Wire up card clicks
  cards.forEach(function(card) {
    card.addEventListener('click', function() {
      let images = [];
      try { images = JSON.parse(card.dataset.images || '[]'); } catch(e) {}
      if (!images.length) return;
      lbOpen(images, parseInt(card.dataset.start || '0', 10));
    });
  });
})();
</script>
</body>
</html>
