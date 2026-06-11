<?php
require_once 'config.php';

$year    = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$year    = max(2018, min((int)date('Y') + 1, $year));
$yearStr = (string)$year;
$isCurrent = ($year === (int)date('Y'));

$models    = file_exists(__DIR__ . '/data/models.json')   ? (json_decode(file_get_contents(__DIR__ . '/data/models.json'),   true) ?? []) : [];
$benchData = file_exists(__DIR__ . '/data/bench.json')    ? (json_decode(file_get_contents(__DIR__ . '/data/bench.json'),    true) ?? []) : [];
$battles   = file_exists(__DIR__ . '/data/battles.json')  ? (json_decode(file_get_contents(__DIR__ . '/data/battles.json'),  true) ?? []) : [];
$journal   = file_exists(__DIR__ . '/data/journal.json')  ? (json_decode(file_get_contents(__DIR__ . '/data/journal.json'),  true) ?? []) : [];
$shame     = file_exists(__DIR__ . '/data/shame.json')    ? (json_decode(file_get_contents(__DIR__ . '/data/shame.json'),    true) ?? []) : [];
$goalsData = file_exists(__DIR__ . '/data/goals.json')    ? (json_decode(file_get_contents(__DIR__ . '/data/goals.json'),    true) ?? []) : [];

// ── Goal ─────────────────────────────────────────────────────────────────────
$rawGoal    = $goalsData[$yearStr] ?? null;
$goalTarget = $rawGoal ? (is_array($rawGoal) ? (int)($rawGoal['target'] ?? 0) : (int)$rawGoal) : 0;
$goalSeed   = ($rawGoal && is_array($rawGoal)) ? (int)($rawGoal['seed'] ?? 0) : 0;

// ── Models painted in year ────────────────────────────────────────────────────
$modelsByMonth  = array_fill(1, 12, 0);
$modelsThisYear = $goalSeed;
$factionCounts  = [];
$galleryCards   = [];

foreach ($models as $m) {
    $yearCount   = 0;
    $hasSessions = !empty($m['sessions']);

    // Primary: sessions logged in this year
    foreach ($m['sessions'] ?? [] as $s) {
        if (substr($s['date'] ?? '', 0, 4) !== $yearStr) continue;
        $cnt = max(1, (int)($s['count'] ?? 1));
        $yearCount += $cnt;
        $mo = (int)substr($s['date'], 5, 2);
        if ($mo >= 1 && $mo <= 12) $modelsByMonth[$mo] += $cnt;
    }

    // Fallback: no sessions logged at all — use the scheme's date + count fields
    if ($yearCount === 0 && !$hasSessions && substr($m['date'] ?? '', 0, 4) === $yearStr) {
        $yearCount = max(1, (int)($m['count'] ?? 1));
        $mo = (int)substr($m['date'], 5, 2);
        if ($mo >= 1 && $mo <= 12) $modelsByMonth[$mo] += $yearCount;
    }

    if ($yearCount === 0) continue;
    $modelsThisYear += $yearCount;

    $faction = trim($m['faction'] ?? '');
    if ($faction) $factionCounts[$faction] = ($factionCounts[$faction] ?? 0) + $yearCount;

    $images = $m['images'] ?? [];
    $thumb  = '';
    if (!empty($m['featured']) && is_array($m['featured'])) {
        foreach ($m['featured'] as $idx) {
            if (!empty($images[$idx])) { $thumb = $images[$idx]; break; }
        }
    }
    if (!$thumb && !empty($images[0])) $thumb = $images[0];

    $galleryCards[] = [
        'name'      => $m['name'] ?? '',
        'faction'   => $faction,
        'thumb'     => $thumb,
        'theme_hex' => $m['theme_hex'] ?? '',
        'count'     => $yearCount,
    ];
}
arsort($factionCounts);
usort($galleryCards, fn($a, $b) => $b['count'] - $a['count']);
$galleryCards = array_filter(array_slice($galleryCards, 0, 24), fn($c) => !empty($c['thumb']));

// ── Bench sessions ────────────────────────────────────────────────────────────
$sessionsThisYear = 0;
$minutesThisYear  = 0;
$sessionsByMonth  = array_fill(1, 12, 0);
$sessionsByDow    = array_fill(0, 7, 0);

foreach ($benchData as $b) {
    foreach ($b['sessions'] ?? [] as $s) {
        if (substr($s['date'] ?? '', 0, 4) !== $yearStr) continue;
        $sessionsThisYear++;
        $minutesThisYear += (int)($s['duration'] ?? 0);
        $mo  = (int)substr($s['date'], 5, 2);
        $dow = (int)date('w', strtotime($s['date']));
        if ($mo >= 1 && $mo <= 12) $sessionsByMonth[$mo]++;
        if ($dow >= 0 && $dow <= 6) $sessionsByDow[$dow]++;
    }
}
$hoursAtBench = $minutesThisYear > 0 ? round($minutesThisYear / 60, 1) : 0;
$bestDowIdx   = (int)array_search(max($sessionsByDow ?: [0]), $sessionsByDow);
$dowNames     = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// ── Battles ───────────────────────────────────────────────────────────────────
$battlesThisYear = array_values(array_filter($battles, fn($b) => substr($b['date'] ?? '', 0, 4) === $yearStr));
$battleCount     = count($battlesThisYear);
$bRecord         = ['w' => 0, 'l' => 0, 'd' => 0];
$bByArmy         = [];
foreach ($battlesThisYear as $b) {
    $r = $b['result'] === 'win' ? 'w' : ($b['result'] === 'loss' ? 'l' : 'd');
    $bRecord[$r]++;
    $army = $b['my_army'] ?? 'Unknown';
    if (!isset($bByArmy[$army])) $bByArmy[$army] = ['w' => 0, 'l' => 0, 'd' => 0];
    $bByArmy[$army][$r]++;
}
$bestArmy  = null; $bestArmyW = -1;
foreach ($bByArmy as $army => $rec) {
    if ($rec['w'] > $bestArmyW) { $bestArmyW = $rec['w']; $bestArmy = $army; }
}
$winRate = $battleCount > 0 ? (int)round($bRecord['w'] / $battleCount * 100) : 0;

// ── Journal + shame ───────────────────────────────────────────────────────────
$journalThisYear = count(array_filter($journal, fn($j) => substr($j['date'] ?? '', 0, 4) === $yearStr));
$shameThisYear   = array_values(array_filter($shame,   fn($s) => substr($s['acquired'] ?? '', 0, 4) === $yearStr));
$shameBoxes      = count($shameThisYear);
$shameUnits      = array_sum(array_map(fn($s) => max(1, (int)($s['count'] ?? 1)), $shameThisYear));

// ── Available years ───────────────────────────────────────────────────────────
$yearsWithData = [];
foreach ($models as $m) {
    foreach ($m['sessions'] ?? [] as $s) {
        $y = substr($s['date'] ?? '', 0, 4);
        if ($y >= '2018') $yearsWithData[$y] = true;
    }
}
foreach ($battles as $b) {
    $y = substr($b['date'] ?? '', 0, 4);
    if ($y >= '2018') $yearsWithData[$y] = true;
}
foreach ($goalsData as $y => $g) {
    if ($y >= '2018') $yearsWithData[$y] = true;
}
$yearsWithData[$yearStr] = true;
ksort($yearsWithData);
$availableYears = array_keys($yearsWithData);

// ── Chart helpers ─────────────────────────────────────────────────────────────
$monthNames     = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthNamesLong = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$maxModelsMonth = max(array_values($modelsByMonth) ?: [1]);
$bestModelMo    = 0; $bestModelMoCnt = 0;
foreach ($modelsByMonth as $mo => $cnt) {
    if ($cnt > $bestModelMoCnt) { $bestModelMoCnt = $cnt; $bestModelMo = $mo; }
}

$goalPct    = $goalTarget > 0 ? min(100, (int)round($modelsThisYear / $goalTarget * 100)) : 0;
$hasAnyData = ($modelsThisYear > 0 || $sessionsThisYear > 0 || $battleCount > 0 || $journalThisYear > 0);

$flavours = [
    'legendary' => "An absolute beast of a year. The hobby gods are pleased.",
    'great'     => "A solid campaign. The brushes were rarely dry.",
    'decent'    => "A respectable tally. You showed up for the hobby.",
    'quiet'     => "A quieter year. The models wait with patience.",
];
if     ($modelsThisYear >= 100 || $sessionsThisYear >= 80) $flavour = 'legendary';
elseif ($modelsThisYear >= 40  || $sessionsThisYear >= 40) $flavour = 'great';
elseif ($hasAnyData)                                        $flavour = 'decent';
else                                                        $flavour = 'quiet';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

$siteTitle  = defined('SITE_TITLE')  ? SITE_TITLE  : 'Waaagh! Paint';
$siteAuthor = defined('SITE_AUTHOR') ? SITE_AUTHOR : '';
$siteUrl    = defined('SITE_URL')    ? rtrim(SITE_URL, '/') : '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($yearStr) ?> Year in Review · <?= e($siteTitle) ?></title>
<meta name="robots" content="noindex">
<link rel="icon" href="favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --gold:#c9a227;--gold-dim:#6a4f10;--gold-mid:#a07a1a;
  --border:#2a2010;--bg-base:#0e0d0a;--bg-dark:#1a1408;
  --text-main:#c4b49a;--text-dim:#7a6a4a;
  --font-head:'Cinzel',Georgia,serif;
}
html{scroll-behavior:smooth}
body{background:var(--bg-base);color:var(--text-main);font-family:Georgia,serif;min-height:100vh}
a{color:var(--gold-dim);text-decoration:none;transition:color .15s}
a:hover{color:var(--gold)}

.sec-title{
  font-family:var(--font-head);font-size:.68rem;letter-spacing:.2em;
  text-transform:uppercase;color:var(--text-dim);text-align:center;
  margin-bottom:20px;
}
hr.div{border:none;border-top:1px solid var(--border);margin:0}
.wrap{max-width:1100px;margin:0 auto;padding:0 28px}

/* Hero */
.rv-hero{
  text-align:center;padding:64px 24px 44px;
  background:linear-gradient(to bottom,var(--bg-dark) 0%,var(--bg-base) 100%);
  border-bottom:1px solid var(--border);
}
.rv-hero-label{
  font-family:var(--font-head);font-size:.68rem;letter-spacing:.25em;
  text-transform:uppercase;color:var(--text-dim);margin-bottom:10px;
}
.rv-year{
  font-family:var(--font-head);font-size:clamp(5rem,18vw,10rem);font-weight:900;
  letter-spacing:.04em;color:var(--gold);line-height:.9;display:block;
}
.rv-hero-tagline{
  font-family:var(--font-head);font-size:.78rem;letter-spacing:.1em;
  text-transform:uppercase;color:var(--text-dim);margin-top:22px;font-style:italic;
}
.rv-hero-author{font-size:.72rem;color:#4a3a18;margin-top:8px;letter-spacing:.06em}

/* Year nav */
.rv-year-nav{
  display:flex;justify-content:center;flex-wrap:wrap;gap:6px;
  padding:14px 24px;border-bottom:1px solid var(--border);
}
.rv-year-nav a,.rv-year-nav span{
  font-family:var(--font-head);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;
  padding:4px 10px;border-radius:3px;border:1px solid var(--border);
}
.rv-year-nav a{color:var(--text-dim);background:var(--bg-dark)}
.rv-year-nav a:hover{color:var(--gold);border-color:var(--gold-dim)}
.rv-year-nav .cur{color:var(--gold);border-color:var(--gold);background:var(--bg-dark)}

/* Stats row */
.rv-stats{
  display:flex;flex-wrap:wrap;gap:1px;
  background:var(--border);
  border-bottom:1px solid var(--border);
}
.rv-stat{
  flex:1;min-width:110px;background:var(--bg-base);
  display:flex;flex-direction:column;align-items:center;
  padding:28px 16px;text-align:center;
}
.rv-stat-num{
  font-family:var(--font-head);font-size:2.4rem;font-weight:600;
  color:var(--gold);line-height:1;
}
.rv-stat-lbl{
  font-family:var(--font-head);font-size:.56rem;letter-spacing:.12em;
  text-transform:uppercase;color:var(--text-dim);margin-top:8px;
}
.rv-stat-sub{font-size:.63rem;color:#5a4a28;margin-top:4px}

/* Goal */
.rv-goal{padding:40px 28px;max-width:700px;margin:0 auto}
.rv-goal-hd{
  display:flex;justify-content:space-between;align-items:baseline;margin-bottom:10px;
}
.rv-goal-lbl{font-family:var(--font-head);font-size:.68rem;letter-spacing:.15em;text-transform:uppercase;color:var(--text-dim)}
.rv-goal-nums{font-family:var(--font-head);font-size:.85rem;color:var(--text-main)}
.rv-goal-wrap{height:10px;background:var(--border);border-radius:5px;overflow:hidden}
.rv-goal-fill{height:100%;background:linear-gradient(to right,var(--gold-dim),var(--gold));border-radius:5px}
.rv-goal-note{font-size:.68rem;color:var(--text-dim);margin-top:8px;text-align:right}
.rv-goal-note.done{color:var(--gold)}

/* Month chart */
.rv-chart{padding:40px 0}
.rv-chart-bars{
  display:flex;align-items:flex-end;gap:3px;height:100px;
  padding:0 28px;
}
.rv-chart-col{
  flex:1;display:flex;flex-direction:column;align-items:center;
  gap:3px;height:100%;justify-content:flex-end;
}
.rv-bar{
  width:100%;background:var(--gold-dim);border-radius:2px 2px 0 0;
  min-height:2px;
}
.rv-bar.peak{background:var(--gold)}
.rv-bar.empty{background:#181410;min-height:2px}
.rv-bar-n{font-family:var(--font-head);font-size:.5rem;color:#5a4a28;line-height:1}
.rv-chart-lbls{
  display:flex;gap:3px;border-top:1px solid var(--border);
  padding:6px 28px 0;margin-top:2px;
}
.rv-chart-lbl{
  flex:1;text-align:center;font-family:var(--font-head);
  font-size:.52rem;letter-spacing:.05em;text-transform:uppercase;color:var(--text-dim);
}
.rv-chart-lbl.peak{color:var(--gold)}

/* Gallery */
.rv-gallery-sec{padding:40px 28px}
.rv-gallery{
  columns:4;column-gap:8px;
  max-width:1100px;margin:0 auto;
}
@media(max-width:900px){.rv-gallery{columns:3}}
@media(max-width:560px){.rv-gallery{columns:2}}
.rv-card{
  break-inside:avoid;position:relative;cursor:pointer;
  border-radius:3px;overflow:hidden;margin-bottom:8px;
  border:1px solid var(--border);
  transition:transform .2s,box-shadow .2s,border-color .2s;
}
.rv-card:hover{
  transform:scale(1.015);border-color:var(--gold-dim);
  box-shadow:0 4px 20px var(--cg,rgba(201,162,39,.2));
}
.rv-card img{width:100%;display:block}
.rv-card-ov{
  position:absolute;bottom:0;left:0;right:0;
  background:linear-gradient(to top,rgba(0,0,0,.85) 0%,transparent 100%);
  padding:28px 8px 8px;pointer-events:none;
}
.rv-card-name{
  font-family:var(--font-head);font-size:.63rem;
  letter-spacing:.07em;text-transform:uppercase;color:var(--text-main);
}
.rv-card-fac{font-size:.57rem;color:var(--text-dim);margin-top:2px}

/* Two-column lower */
.rv-lower{
  display:grid;grid-template-columns:1fr 1fr;
  gap:1px;background:var(--border);
  border-top:1px solid var(--border);border-bottom:1px solid var(--border);
}
@media(max-width:680px){.rv-lower{grid-template-columns:1fr}}
.rv-panel{background:var(--bg-base);padding:40px 32px}
.rv-panel .sec-title{text-align:left}

/* Faction bars */
.rv-fac-row{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.rv-fac-name{
  width:130px;font-family:var(--font-head);font-size:.67rem;
  letter-spacing:.04em;color:var(--text-main);text-align:right;flex-shrink:0;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.rv-fac-bar{flex:1;height:6px;background:var(--border);border-radius:3px}
.rv-fac-fill{height:100%;background:var(--gold-dim);border-radius:3px}
.rv-fac-fill.top{background:var(--gold)}
.rv-fac-cnt{
  width:28px;font-family:var(--font-head);font-size:.67rem;
  color:var(--text-dim);text-align:right;flex-shrink:0;
}

/* Battle record */
.rv-battle-row{display:flex;gap:12px;margin-bottom:24px}
.rv-bs{
  flex:1;text-align:center;padding:16px 8px;border-radius:3px;
}
.rv-bs.w{background:rgba(74,138,74,.1);border:1px solid rgba(74,138,74,.25)}
.rv-bs.l{background:rgba(138,58,58,.1);border:1px solid rgba(138,58,58,.25)}
.rv-bs.d{background:rgba(90,90,58,.1);border:1px solid rgba(90,90,58,.2)}
.rv-bs-n{font-family:var(--font-head);font-size:2rem;line-height:1}
.rv-bs.w .rv-bs-n{color:#6ab86a}
.rv-bs.l .rv-bs-n{color:#b86a6a}
.rv-bs.d .rv-bs-n{color:#8a8a5a}
.rv-bs-lbl{font-size:.58rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-dim);margin-top:4px}
.rv-notes{font-size:.75rem;color:var(--text-dim);line-height:1.7}
.rv-notes strong{color:var(--text-main)}

/* Flavour */
.rv-flavour{text-align:center;padding:52px 24px;max-width:700px;margin:0 auto}
.rv-flavour-q{
  font-family:var(--font-head);font-size:clamp(.85rem,2vw,1.1rem);
  letter-spacing:.06em;color:var(--text-dim);font-style:italic;line-height:1.65;
}

/* Empty */
.rv-empty{text-align:center;padding:80px 24px}
.rv-empty-lbl{
  font-family:var(--font-head);font-size:.78rem;letter-spacing:.18em;
  text-transform:uppercase;color:var(--text-dim);
}
.rv-empty-sub{font-size:.72rem;color:#4a3a18;margin-top:14px;letter-spacing:.06em}

/* Footer */
.rv-footer{border-top:1px solid var(--border);padding:24px;text-align:center}
.rv-footer a{
  font-family:var(--font-head);font-size:.62rem;letter-spacing:.1em;
  text-transform:uppercase;color:var(--text-dim);
}

/* Lightbox */
.lb{
  display:none;position:fixed;inset:0;z-index:200;
  background:rgba(0,0,0,.93);align-items:center;justify-content:center;
}
.lb.open{display:flex}
.lb img{max-width:90vw;max-height:88vh;object-fit:contain;border:1px solid var(--border)}
.lb-x{position:absolute;top:8px;right:14px;font-size:32px;color:var(--text-dim);cursor:pointer;line-height:1}
.lb-x:hover{color:var(--gold)}
.lb-arr{
  position:absolute;top:50%;transform:translateY(-50%);
  font-size:42px;color:var(--text-dim);cursor:pointer;
  background:rgba(0,0,0,.5);padding:6px 14px;border-radius:3px;user-select:none;
}
.lb-arr:hover{color:var(--gold)}
.lb-prev{left:10px}.lb-next{right:10px}
</style>
</head>
<body>

<header class="rv-hero">
  <p class="rv-hero-label">Year in Review</p>
  <span class="rv-year"><?= e($yearStr) ?></span>
  <?php if ($hasAnyData): ?>
    <p class="rv-hero-tagline"><?= e($flavours[$flavour]) ?></p>
  <?php endif; ?>
  <?php if ($siteAuthor): ?>
    <p class="rv-hero-author"><?= e($siteAuthor) ?></p>
  <?php endif; ?>
</header>

<nav class="rv-year-nav">
  <?php foreach ($availableYears as $y): ?>
    <?php if ($y === $yearStr): ?>
      <span class="cur"><?= e($y) ?></span>
    <?php else: ?>
      <a href="?year=<?= e($y) ?>"><?= e($y) ?></a>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>

<?php if (!$hasAnyData): ?>
<div class="rv-empty">
  <p class="rv-empty-lbl">No data recorded for <?= e($yearStr) ?></p>
  <p class="rv-empty-sub">The brushes were cold. The dice unrolled.</p>
</div>

<?php else: ?>

<!-- Key stats -->
<div class="rv-stats">
  <div class="rv-stat">
    <span class="rv-stat-num"><?= $modelsThisYear ?></span>
    <span class="rv-stat-lbl">Models Painted</span>
    <?php if ($goalSeed > 0): ?>
      <span class="rv-stat-sub">incl. <?= $goalSeed ?> pre-painted</span>
    <?php elseif (count($factionCounts) > 1): ?>
      <span class="rv-stat-sub"><?= count($factionCounts) ?> factions</span>
    <?php endif; ?>
  </div>
  <?php if ($sessionsThisYear > 0): ?>
  <div class="rv-stat">
    <span class="rv-stat-num"><?= $sessionsThisYear ?></span>
    <span class="rv-stat-lbl">Bench Sessions</span>
    <?php if ($hoursAtBench > 0): ?><span class="rv-stat-sub"><?= $hoursAtBench ?>h at the desk</span><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if ($battleCount > 0): ?>
  <div class="rv-stat">
    <span class="rv-stat-num"><?= $battleCount ?></span>
    <span class="rv-stat-lbl">Battles Fought</span>
    <span class="rv-stat-sub"><?= $bRecord['w'] ?>W &middot; <?= $bRecord['l'] ?>L &middot; <?= $bRecord['d'] ?>D</span>
  </div>
  <?php endif; ?>
  <?php if ($journalThisYear > 0): ?>
  <div class="rv-stat">
    <span class="rv-stat-num"><?= $journalThisYear ?></span>
    <span class="rv-stat-lbl">Notes Scribbled</span>
  </div>
  <?php endif; ?>
  <?php if ($shameBoxes > 0): ?>
  <div class="rv-stat">
    <span class="rv-stat-num"><?= $shameBoxes ?></span>
    <span class="rv-stat-lbl">Boxes Acquired</span>
    <span class="rv-stat-sub"><?= $shameUnits ?> unit<?= $shameUnits !== 1 ? 's' : '' ?></span>
  </div>
  <?php endif; ?>
</div>

<?php if ($goalTarget > 0): ?>
<section class="rv-goal">
  <div class="rv-goal-hd">
    <span class="rv-goal-lbl"><?= e($yearStr) ?> Goal</span>
    <span class="rv-goal-nums"><?= $modelsThisYear ?> / <?= $goalTarget ?> &nbsp;<span style="color:var(--gold)"><?= $goalPct ?>%</span></span>
  </div>
  <div class="rv-goal-wrap">
    <div class="rv-goal-fill" style="width:<?= $goalPct ?>%"></div>
  </div>
  <?php if ($goalPct >= 100): ?>
    <p class="rv-goal-note done">Goal smashed. &#10003;</p>
  <?php elseif ($isCurrent): ?>
    <p class="rv-goal-note"><?= $goalTarget - $modelsThisYear ?> to go</p>
  <?php else: ?>
    <p class="rv-goal-note"><?= $goalTarget - $modelsThisYear ?> short of target</p>
  <?php endif; ?>
</section>
<hr class="div">
<?php endif; ?>

<?php if ($modelsThisYear > $goalSeed): ?>
<section class="rv-chart wrap">
  <p class="sec-title">Models Painted by Month</p>
  <div class="rv-chart-bars">
    <?php for ($mo = 1; $mo <= 12; $mo++):
      $cnt = $modelsByMonth[$mo];
      $h   = $maxModelsMonth > 0 ? max(2, (int)round($cnt / $maxModelsMonth * 90)) : 2;
      $peak = ($mo === $bestModelMo && $cnt > 0);
    ?>
      <div class="rv-chart-col">
        <?php if ($cnt > 0): ?><span class="rv-bar-n"><?= $cnt ?></span><?php endif; ?>
        <div class="rv-bar <?= $peak ? 'peak' : ($cnt === 0 ? 'empty' : '') ?>" style="height:<?= $h ?>px"></div>
      </div>
    <?php endfor; ?>
  </div>
  <div class="rv-chart-lbls">
    <?php for ($mo = 1; $mo <= 12; $mo++): ?>
      <span class="rv-chart-lbl <?= ($mo === $bestModelMo && $modelsByMonth[$mo] > 0) ? 'peak' : '' ?>"><?= $monthNames[$mo] ?></span>
    <?php endfor; ?>
  </div>
</section>
<hr class="div">
<?php endif; ?>

<?php if (!empty($galleryCards)): ?>
<section class="rv-gallery-sec">
  <p class="sec-title">Work Painted This Year</p>
  <div class="rv-gallery" id="rv-gallery">
    <?php foreach ($galleryCards as $i => $card):
      $glow = preg_match('/^#[0-9a-f]{6}$/i', $card['theme_hex'] ?? '') ? $card['theme_hex'] . '44' : 'rgba(201,162,39,.18)';
    ?>
      <div class="rv-card" data-idx="<?= $i ?>" style="--cg:<?= e($glow) ?>">
        <img src="<?= e($card['thumb']) ?>" alt="<?= e($card['name']) ?>" loading="lazy">
        <div class="rv-card-ov">
          <div class="rv-card-name"><?= e($card['name']) ?></div>
          <?php if ($card['faction']): ?><div class="rv-card-fac"><?= e($card['faction']) ?></div><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<hr class="div">
<?php endif; ?>

<?php $hasFac = !empty($factionCounts); $hasBat = $battleCount > 0; ?>
<?php if ($hasFac || $hasBat || $sessionsThisYear > 0): ?>
<div class="rv-lower">

  <?php if ($hasFac): ?>
  <div class="rv-panel">
    <p class="sec-title">Factions Painted</p>
    <?php
      $topFac   = array_key_first($factionCounts);
      $maxFac   = max(array_values($factionCounts) ?: [1]);
      $showFacs = array_slice($factionCounts, 0, 8, true);
    ?>
    <?php foreach ($showFacs as $fac => $cnt):
      $pct = (int)round($cnt / $maxFac * 100);
    ?>
      <div class="rv-fac-row">
        <span class="rv-fac-name" title="<?= e($fac) ?>"><?= e($fac) ?></span>
        <div class="rv-fac-bar"><div class="rv-fac-fill <?= $fac === $topFac ? 'top' : '' ?>" style="width:<?= $pct ?>%"></div></div>
        <span class="rv-fac-cnt"><?= $cnt ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($hasBat): ?>
  <div class="rv-panel">
    <p class="sec-title">Battle Honours</p>
    <div class="rv-battle-row">
      <div class="rv-bs w"><div class="rv-bs-n"><?= $bRecord['w'] ?></div><div class="rv-bs-lbl">Wins</div></div>
      <div class="rv-bs l"><div class="rv-bs-n"><?= $bRecord['l'] ?></div><div class="rv-bs-lbl">Losses</div></div>
      <div class="rv-bs d"><div class="rv-bs-n"><?= $bRecord['d'] ?></div><div class="rv-bs-lbl">Draws</div></div>
    </div>
    <div class="rv-notes">
      <p><strong><?= $winRate ?>%</strong> win rate across <?= $battleCount ?> game<?= $battleCount !== 1 ? 's' : '' ?></p>
      <?php if ($bestArmy && $bRecord['w'] > 0): ?>
        <p>Best army: <strong><?= e($bestArmy) ?></strong></p>
      <?php endif; ?>
    </div>
  </div>

  <?php elseif ($sessionsThisYear > 0): ?>
  <div class="rv-panel">
    <p class="sec-title">Session Notes</p>
    <div class="rv-notes">
      <?php if ($hoursAtBench > 0): ?><p><strong><?= $hoursAtBench ?>h</strong> at the painting desk</p><?php endif; ?>
      <?php if ($bestModelMo > 0 && $bestModelMoCnt > 0): ?>
        <p>Best month: <strong><?= e($monthNamesLong[$bestModelMo]) ?></strong> &mdash; <?= $bestModelMoCnt ?> model<?= $bestModelMoCnt !== 1 ? 's' : '' ?></p>
      <?php endif; ?>
      <?php if ($sessionsThisYear > 0 && max($sessionsByDow) > 0): ?>
        <p>Most active day: <strong><?= e($dowNames[$bestDowIdx]) ?></strong></p>
      <?php endif; ?>
      <?php if ($shameBoxes > 0): ?>
        <p><?= $shameBoxes ?> new box<?= $shameBoxes !== 1 ? 'es' : '' ?> added to the pile this year.</p>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<div class="rv-flavour">
  <p class="rv-flavour-q">&ldquo;<?= e($flavours[$flavour]) ?>&rdquo;</p>
</div>

<?php endif; // $hasAnyData ?>

<footer class="rv-footer">
  <a href="index.php">&larr; Back to <?= e($siteTitle) ?></a>
</footer>

<div class="lb" id="lb">
  <span class="lb-x" id="lb-x">&times;</span>
  <span class="lb-arr lb-prev" id="lb-prev">&#8249;</span>
  <img id="lb-img" src="" alt="">
  <span class="lb-arr lb-next" id="lb-next">&#8250;</span>
</div>

<script>
(function(){
  const cards = Array.from(document.querySelectorAll('.rv-card[data-idx]'));
  const imgs  = cards.map(c => c.querySelector('img')?.src).filter(Boolean);
  if (!imgs.length) return;

  const lb    = document.getElementById('lb');
  const lbImg = document.getElementById('lb-img');
  const lbX   = document.getElementById('lb-x');
  const prev  = document.getElementById('lb-prev');
  const next  = document.getElementById('lb-next');
  let idx = 0;

  function show(i) {
    idx = i;
    lbImg.src = imgs[i];
    prev.style.visibility = i > 0 ? '' : 'hidden';
    next.style.visibility = i < imgs.length - 1 ? '' : 'hidden';
  }
  function open(i)  { show(i); lb.classList.add('open'); document.body.style.overflow = 'hidden'; }
  function close()  { lb.classList.remove('open'); document.body.style.overflow = ''; lbImg.src = ''; }

  cards.forEach((c, i) => c.addEventListener('click', () => open(i)));
  lbX.addEventListener('click', close);
  prev.addEventListener('click', () => { if (idx > 0) show(idx - 1); });
  next.addEventListener('click', () => { if (idx < imgs.length - 1) show(idx + 1); });
  lb.addEventListener('click', e => { if (e.target === lb) close(); });
  document.addEventListener('keydown', e => {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft'  && idx > 0)              show(idx - 1);
    if (e.key === 'ArrowRight' && idx < imgs.length - 1) show(idx + 1);
  });
  let tx = null;
  lb.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, {passive:true});
  lb.addEventListener('touchend',   e => {
    if (tx === null) return;
    const dx = e.changedTouches[0].clientX - tx; tx = null;
    if (Math.abs(dx) < 40) return;
    if (dx < 0 && idx < imgs.length - 1) show(idx + 1);
    if (dx > 0 && idx > 0)               show(idx - 1);
  }, {passive:true});
})();
</script>
</body>
</html>
