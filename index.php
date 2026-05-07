<?php
require_once __DIR__ . '/config.php';
$paintsJsonFile = __DIR__ . '/data/paints.json';
if (file_exists($paintsJsonFile)) {
  $paints = json_decode(file_get_contents($paintsJsonFile), true) ?? [];
} else {
  $paints = [];
  foreach (glob(__DIR__ . '/inventory/*.csv') ?: [] as $path) {
    if (basename($path) === 'conversions.csv') continue;
    $fh = fopen($path, 'r');
    if (!$fh) continue;
    while (($line = fgets($fh)) !== false) {
      $fields = array_map('trim', explode('|', $line));
      if (count($fields) < 5 || $fields[0] === '' || $fields[1] === '') continue;
      $paints[] = [
        'brand' => $fields[0],
        'name'  => $fields[1],
        'color' => $fields[2],
        'hue'   => $fields[3],
        'layer' => $fields[4],
      ];
    }
    fclose($fh);
  }
}

$paintsJson = json_encode($paints, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// Load gallery models
$modelsFile = __DIR__ . '/data/models.json';
$models = file_exists($modelsFile) ? (json_decode(file_get_contents($modelsFile), true) ?? []) : [];
$modelsJson = json_encode($models, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$plannedFile = __DIR__ . '/data/planned.json';
$planned     = file_exists($plannedFile) ? (json_decode(file_get_contents($plannedFile), true) ?? []) : [];
$plannedJson = json_encode($planned, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$booksFile     = __DIR__ . '/data/books.json';
$hasBooks      = file_exists($booksFile);
$booksData     = $hasBooks ? (json_decode(file_get_contents($booksFile), true) ?? []) : [];
$booksDataJson = $hasBooks ? json_encode($booksData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]';

$journalFile = __DIR__ . '/data/journal.json';
$hasJournal  = file_exists($journalFile);
$journalData = $hasJournal ? (json_decode(file_get_contents($journalFile), true) ?? []) : [];

$shameFile  = __DIR__ . '/data/shame.json';
$hasShame   = file_exists($shameFile);
$shameData  = $hasShame ? (json_decode(file_get_contents($shameFile), true) ?? []) : [];
$shameActive     = array_values(array_filter($shameData, fn($s) => empty($s['promoted_to'])));
$cnt_shame       = count($shameActive);
$cnt_shame_units = array_sum(array_map(fn($s) => max(1, (int)($s['count'] ?? 1)), $shameActive));

$brushesFile     = __DIR__ . '/data/brushes.json';
$hasBrushes      = file_exists($brushesFile);
$brushesData     = $hasBrushes ? (json_decode(file_get_contents($brushesFile), true) ?? []) : [];
$brushesDataJson = $hasBrushes ? json_encode($brushesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]';

$benchFile     = __DIR__ . '/data/bench.json';
$hasBench      = file_exists($benchFile);
$benchData     = $hasBench ? (json_decode(file_get_contents($benchFile), true) ?? []) : [];
$benchDataJson = $hasBench ? json_encode($benchData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]';

$recipesFile     = __DIR__ . '/data/recipes.json';
$hasRecipes      = file_exists($recipesFile);
$recipesData     = $hasRecipes ? (json_decode(file_get_contents($recipesFile), true) ?? []) : [];
$recipesDataJson = $hasRecipes ? json_encode($recipesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]';

$forcesFile     = __DIR__ . '/data/forces.json';
$hasForces      = file_exists($forcesFile);
$forcesData     = $hasForces ? (json_decode(file_get_contents($forcesFile), true) ?? []) : [];
$forcesDataJson = $hasForces ? json_encode($forcesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]';
$cnt_forces     = count($forcesData);

// Factions is opt-in: visible only when at least one entry has a faction field set
$factionSet = [];
foreach ($models   as $m)  { if (!empty($m['faction']))  $factionSet[trim($m['faction'])]  = true; }
foreach ($planned  as $pl) { if (!empty($pl['faction'])) $factionSet[trim($pl['faction'])] = true; }
if ($hasBench)   foreach ($benchData   as $b) { if (!empty($b['faction']))  $factionSet[trim($b['faction'])]  = true; }
if ($hasRecipes) foreach ($recipesData as $r) { if (!empty($r['faction']))  $factionSet[trim($r['faction'])]  = true; }
$factionCount = count($factionSet);
$hasFactions  = $factionCount > 0;

$wishlistFile     = __DIR__ . '/data/wishlist.json';
$hasWishlist      = file_exists($wishlistFile);
$wishlistData     = $hasWishlist ? (json_decode(file_get_contents($wishlistFile), true) ?? []) : [];
$wishlistDataJson = $hasWishlist ? json_encode($wishlistData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]';

$goalsData    = file_exists(__DIR__ . '/data/goals.json') ? (json_decode(file_get_contents(__DIR__ . '/data/goals.json'), true) ?? []) : [];
$curYear      = date('Y');
$rawGoal      = $goalsData[$curYear] ?? null;
$curYearGoal  = is_array($rawGoal) ? (int)($rawGoal['target'] ?? 0) : (int)($rawGoal ?? 0);
$curYearSeed  = is_array($rawGoal) ? (int)($rawGoal['seed']   ?? 0) : 0;
$curYearCount = $curYearSeed;
if ($curYearGoal > 0) {
  foreach ($models as $m) {
    foreach ($m['sessions'] ?? [] as $s) {
      if (!empty($s['date']) && substr($s['date'], 0, 4) === $curYear)
        $curYearCount += max(1, (int)($s['count'] ?? 1));
    }
  }
}
$goalPct = $curYearGoal > 0 ? min(100, (int)round($curYearCount / $curYearGoal * 100)) : 0;

// Load conversions for equivalency tab
$conversionsData = [];
$convPath = __DIR__ . '/inventory/conversions.csv';
if (file_exists($convPath)) {
  $fh = fopen($convPath, 'r');
  if ($fh) {
    while (($line = fgets($fh)) !== false) {
      $f = array_map('trim', explode('|', $line));
      if (count($f) < 4 || $f[0] === '' || $f[0] === 'Citadel') continue;
      $conversionsData[] = [
        'citadel'  => $f[0],
        'vallejo'  => ($f[1] !== '' && $f[1] !== '-') ? $f[1] : null,
        'proAcryl' => ($f[2] !== '' && $f[2] !== '-') ? $f[2] : null,
        'ttc'      => ($f[3] !== '' && $f[3] !== '-') ? $f[3] : null,
        'valMatch' => $f[4] ?? '',
        'paMatch'  => $f[5] ?? '',
        'ttcMatch' => $f[6] ?? '',
      ];
    }
    fclose($fh);
  }
}
$conversionsDataJson = json_encode($conversionsData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// ── Tab traffic tracker ───────────────────────────────
if (($_POST['action'] ?? '') === 'track_tab') {
  header('Content-Type: application/json');
  $tab     = trim($_POST['tab'] ?? '');
  $allowed = ['contents', 'inventory', 'brushes', 'gallery', 'factions', 'equiv', 'recipes', 'planned', 'bench', 'shame', 'wd', 'books', 'journals'];
  if (in_array($tab, $allowed, true)) {
    $file  = __DIR__ . '/data/tab_stats.json';
    $stats = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    $stats[$tab] = ($stats[$tab] ?? 0) + 1;
    file_put_contents($file, json_encode($stats), LOCK_EX);
  }
  echo json_encode(['ok' => true]);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
<?php if (defined('GA4_ID') && GA4_ID !== ''): ?>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars(GA4_ID) ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= htmlspecialchars(GA4_ID) ?>');
  </script>
<?php endif; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Waaagh! Paint Collection</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0d0d1a">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Waaagh! Paint">
  <link rel="apple-touch-icon" href="img/logo_sm.png">
  <link rel="canonical" href="<?= htmlspecialchars(SITE_URL) ?>">
  <meta name="description" content="Personal Warhammer 40k hobby tracker - paint inventory, painted model gallery, step-by-step painting recipes, workbench progress, and codex reference library.">
  <meta property="og:type"        content="website">
  <meta property="og:url"         content="<?= htmlspecialchars(SITE_URL) ?>">
  <meta property="og:title"       content="Waaagh! Paint Collection">
  <meta property="og:description" content="Personal Warhammer 40k hobby tracker - paint inventory, painted model gallery, step-by-step painting recipes, workbench progress, and codex reference library.">
  <meta property="og:image"       content="<?= htmlspecialchars(SITE_URL) ?>img/logo_sm.png">
  <meta name="twitter:card"        content="summary">
  <meta name="twitter:title"       content="Waaagh! Paint Collection">
  <meta name="twitter:description" content="Personal Warhammer 40k hobby tracker - paint inventory, painted model gallery, step-by-step painting recipes, workbench progress, and codex reference library.">
  <meta name="twitter:image"       content="<?= htmlspecialchars(SITE_URL) ?>img/logo_sm.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=23">
  <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"Waaagh! Paint Collection","url":"<?= htmlspecialchars(SITE_URL) ?>","description":"Personal Warhammer 40k hobby paint collection tracker - model gallery, recipes, workbench, and codex reference library."}</script>
</head>

<body>

  <header>
    <img src="img/logo_sm.png" alt="Waaagh! Paint Collection" class="logo">
    <p>Warhammer 40k hobby paints &nbsp;-&nbsp; Notes</p>
    <p class="header-disclaimer">Personal hobby journal - not an official or commercial resource. Paint schemes, conversions, and notes are my own. Feel free to reference anything here.</p>
  </header>

  <nav class="tab-nav">
    <button class="tab-btn active" data-tab="contents"><span class="tab-full">Looted Knowledge</span><span class="tab-short">Contents</span></button>
    <!-- The Pipeline -->
    <?php if ($hasRecipes): ?><button class="tab-btn tab-group-start tab-pipeline" data-tab="recipes" title="The Pipeline">Recipes</button><?php endif; ?>
    <button class="tab-btn tab-pipeline<?= $hasRecipes ? '' : ' tab-group-start' ?>" data-tab="gallery" title="The Pipeline"><span class="tab-full">Paint Schemes</span><span class="tab-short">Schemes</span></button>
    <button class="tab-btn tab-pipeline" data-tab="planned" title="The Pipeline">Planned</button>
    <?php if ($hasBench): ?><button class="tab-btn tab-pipeline" data-tab="bench" title="The Pipeline"><span class="tab-full">On the Bench</span><span class="tab-short">Bench</span></button><?php endif; ?>
    <!-- Your Armies -->
    <?php if ($hasFactions): ?><button class="tab-btn tab-group-start" data-tab="factions" title="Your Armies">Factions</button><?php endif; ?>
    <?php if ($hasForces): ?><button class="tab-btn<?= $hasFactions ? '' : ' tab-group-start' ?>" data-tab="forces" title="Your Armies">Forces</button><?php endif; ?>
    <!-- The Workbench -->
    <button class="tab-btn tab-group-start" data-tab="inventory" title="The Workbench"><span class="tab-full">Paint Inventory</span><span class="tab-short">Inventory</span></button>
    <?php if ($hasBrushes): ?><button class="tab-btn" data-tab="brushes" title="The Workbench">Brushes</button><?php endif; ?>
    <?php if ($hasShame): ?><button class="tab-btn" data-tab="shame" title="The Workbench"><span class="tab-full">Pile of Shame</span><span class="tab-short">Shame</span></button><?php endif; ?>
    <?php if ($hasWishlist): ?><button class="tab-btn" data-tab="wishlist" title="The Workbench"><span class="tab-full">Wishlist</span><span class="tab-short">Wish</span></button><?php endif; ?>
    <!-- The Library -->
    <button class="tab-btn tab-group-start tab-equiv-rainbow" data-tab="equiv" title="The Library"><span class="tab-full">Equivalency</span><span class="tab-short">Equiv.</span></button>
    <?php if ($hasBooks): ?><button class="tab-btn" data-tab="books" title="The Library"><span class="tab-full">Codices</span><span class="tab-short">Codex</span></button><?php endif; ?>
    <?php if ($hasJournal): ?><button class="tab-btn<?= $hasBooks ? '' : ' tab-group-start' ?>" data-tab="journals" title="The Library"><span class="tab-full">Scrap Notes</span><span class="tab-short">Scrap</span></button><?php endif; ?>
  </nav>

  <?php if ($hasBrushes): ?>
    <div id="tab-brushes" class="tab-panel">
      <div id="brush-controls">
        <a class="tab-label" href="#" onclick="copyTabLink(event,'brushes')" title="Copy link to this tab">Brushes</a>
        <input type="search" id="brush-search" class="tab-search" placeholder="Search brand, series, use, notes&hellip;" autocomplete="off">
        <div class="brush-filter-pills" id="brush-filter-pills">
          <button class="brush-filter-pill active" data-cond="all">All</button>
          <button class="brush-filter-pill" data-cond="prime">Prime</button>
          <button class="brush-filter-pill" data-cond="workhorse">Workhorse</button>
          <button class="brush-filter-pill" data-cond="retired">Retired</button>
        </div>
        <span id="brush-count"></span>
      </div>
      <p class="tab-blurb">The tools of the craft, catalogued by condition. A dull brush is a wasted session.</p>
      <div id="brush-list"></div>
      <div id="brush-empty" class="hidden">No brushes yet - add one in admin.</div>
    </div><!-- #tab-brushes -->
  <?php endif; ?>

  <div id="tab-gallery" class="tab-panel">
    <div id="gallery-controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'gallery')" title="Copy link to this tab">Paint Schemes</a>
      <input type="search" id="gallery-search" class="tab-search" placeholder="Search schemes, techniques, paints&hellip;" autocomplete="off">
      <span class="active-faction-pill hidden" id="active-faction-pill"></span>
      <button id="ready-filter-btn" onclick="toggleReadyFilter()">Ready only</button>
      <select id="gallery-sys-select" onchange="gallerySystemFilter=this.value;showAllGallery=false;renderGallery()" class="mini-select">
        <option value="">All systems</option>
        <option value="40k">40k</option>
        <option value="30k / HH">30k / HH</option>
        <option value="AoS">AoS</option>
        <option value="Kill Team">Kill Team</option>
        <option value="Blood Bowl">Blood Bowl</option>
        <option value="Necromunda">Necromunda</option>
        <option value="OPR">OPR</option>
        <option value="Other">Other</option>
      </select>
      <button class="faction-pull-btn hidden" id="faction-pull-btn" onclick="openFactionPull(factionFilter)">Pull faction</button>
    </div>
    <p class="tab-blurb">Every scheme painted, every colour committed to record. The full campaign in permanent ink.</p>
    <div class="gallery-wrap">
      <div class="gallery-grid" id="gallery-grid"></div>
      <div id="gallery-more" class="hidden"></div>
      <div class="gallery-empty hidden" id="gallery-empty">
        No models yet - add one in admin.
      </div>
    </div>
  </div>

  <div id="tab-contents" class="tab-panel active">
    <div class="contents-wrap">
      <div class="contents-mast">
        <div class="contents-issue">Scrap Log Nº <?= date('Y') ?></div>
        <h2 class="contents-title">Looted Knowledge</h2>
        <p class="contents-tagline">Dis is not a blog. It's a paintin' tool. Use it. Steal from it. Ignore half of it.</p>
      </div>

      <?php
      // Counts for the contents page (computed inline; cheap)
      $cnt_paints   = count($paints);
      $cnt_owned    = count(array_filter($paints, fn($p) => ($p['stock'] ?? '') !== 'wanted'));
      $cnt_low_out  = count(array_filter($paints, fn($p) => in_array($p['stock'] ?? '', ['low', 'out'], true)));
      $cnt_models   = count($models);
      $cnt_models_painted = array_sum(array_map(fn($m) => max(1, (int)($m['count'] ?? 1)), $models));
      $cnt_planned  = count($planned);
      $cnt_brushes  = $hasBrushes ? count(array_filter($brushesData, fn($b) => ($b['condition'] ?? 'prime') !== 'retired')) : 0;
      $cnt_bench    = $hasBench   ? count(array_filter($benchData, fn($b) => ($b['stage'] ?? 'built') !== 'done')) : 0;
      $cnt_recipes  = $hasRecipes ? count($recipesData) : 0;
      $cnt_books    = $hasBooks   ? count($booksData) : 0;
      $cnt_journal  = $hasJournal ? count($journalData) : 0;
      $cnt_hex      = count(array_filter($paints, fn($p) => !empty($p['hex'])));
      $cnt_eq       = count($conversionsData);

      // Ready-to-start planned schemes (every paint owned and not out)
      $ownedKeys = [];
      foreach ($paints as $_p) {
        $st = $_p['stock'] ?? '';
        if ($st !== 'wanted' && $st !== 'out') {
          $ownedKeys[$_p['brand'] . '|' . $_p['name']] = true;
          $ownedKeys[$_p['brand'] . '|' . $_p['name'] . '|' . ($_p['layer'] ?? '')] = true;
        }
      }
      $cnt_ready = 0;
      foreach ($planned as $_pl) {
        if (empty($_pl['colors'])) { $cnt_ready++; continue; }
        $allOwned = true;
        foreach ($_pl['colors'] as $_c) {
          if (!isset($ownedKeys[$_c])) {
            $parts2 = explode('|', $_c);
            $k2 = $parts2[0] . '|' . ($parts2[1] ?? '');
            if (!isset($ownedKeys[$k2])) { $allOwned = false; break; }
          }
        }
        if ($allOwned) $cnt_ready++;
      }
      unset($ownedKeys, $_p, $_pl, $_c, $allOwned, $parts2, $k2, $st);

      // Most-recently-touched active bench project (for the "Currently on the Bench" strip)
      $latestBench = null;
      if ($hasBench && !empty($benchData)) {
        $active = array_values(array_filter($benchData, fn($b) => ($b['stage'] ?? 'built') !== 'done'));
        usort($active, fn($a, $b) => strcmp($b['last_touched'] ?? '', $a['last_touched'] ?? ''));
        $latestBench = $active[0] ?? null;
      }

      $touchedAgo = '';
      if ($latestBench && !empty($latestBench['last_touched'])) {
        $ts = strtotime($latestBench['last_touched']);
        if ($ts) {
          $days = floor((time() - $ts) / 86400);
          if ($days <= 0)       $touchedAgo = 'Touched today';
          elseif ($days === 1.0)$touchedAgo = 'Touched yesterday';
          elseif ($days < 7)    $touchedAgo = 'Touched ' . (int)$days . ' days ago';
          elseif ($days < 30)   $touchedAgo = 'Touched ' . (int)floor($days / 7) . ' week' . (floor($days / 7) !== 1.0 ? 's' : '') . ' ago';
          else                  $touchedAgo = 'Touched ' . (int)floor($days / 30) . ' month' . (floor($days / 30) !== 1.0 ? 's' : '') . ' ago';
        }
      }

      $benchStageLabels = [
        'built' => 'Built', 'primed' => 'Primed', 'basecoated' => 'Basecoated',
        'washed' => 'Washed', 'highlighted' => 'Highlighted', 'based' => 'Based',
        'varnished' => 'Varnished', 'done' => 'Done',
      ];
      ?>

      <div class="hero-wrap">
      <div class="hero-bar">
        <?php if ($latestBench): ?>
          <a class="hero-bench" data-jump="bench" title="Jump to On the Bench">
            <div class="hero-bench-label">Under da Brush</div>
            <?php
              $bnImgs = $latestBench['wip_images'] ?? [];
              $bnImg = !empty($bnImgs) ? $bnImgs[count($bnImgs) - 1] : '';
              $bnStage = $latestBench['stage'] ?? 'built';
            ?>
            <div class="hero-bench-main">
              <?php if ($bnImg): ?>
                <div class="hero-bench-img" style="background-image:url('<?= htmlspecialchars($bnImg, ENT_QUOTES) ?>')"></div>
              <?php else: ?>
                <div class="hero-bench-img hero-bench-img-empty">NO<br>PHOTO</div>
              <?php endif; ?>
              <div class="hero-bench-info">
                <div class="hero-bench-name"><?= htmlspecialchars($latestBench['name'], ENT_QUOTES) ?></div>
                <?php if (!empty($latestBench['faction'])): ?>
                  <div class="hero-bench-faction"><?= htmlspecialchars($latestBench['faction'], ENT_QUOTES) ?></div>
                <?php endif; ?>
                <div class="hero-bench-meta">
                  <span class="bench-stage-label stage-<?= htmlspecialchars($bnStage, ENT_QUOTES) ?>"><?= htmlspecialchars($benchStageLabels[$bnStage] ?? $bnStage, ENT_QUOTES) ?></span>
                  <?php if ($touchedAgo): ?><span class="hero-bench-touched"><?= htmlspecialchars($touchedAgo, ENT_QUOTES) ?></span><?php endif; ?>
                </div>
              </div>
            </div>
          </a>
        <?php endif; ?>

        <div id="hero-heatmap" class="hero-heatmap"></div>
      </div>

      <?php if ($hasJournal && !empty($journalData)):
        $jnLatest = $journalData[0];
        $jnDate   = $jnLatest['date'] ?? '';
        $jnRaw    = preg_replace('/@\[(?:\w+):[^\]|]+\|([^\]]+)\]/', '$1', $jnLatest['body'] ?? '');
        $jnSnip   = function_exists('mb_strimwidth') ? mb_strimwidth($jnRaw, 0, 90, '…') : (strlen($jnRaw) > 90 ? substr($jnRaw, 0, 90) . '…' : $jnRaw);
      ?>
      <a class="hero-note-strip" data-jump="journals" title="Jump to Scrap Notes">
        <span class="hero-note-label">Latest Note<?= $jnDate ? ' &middot; ' . htmlspecialchars($jnDate) : '' ?></span>
        <span class="hero-note-body"><?= htmlspecialchars($jnSnip) ?></span>
      </a>
      <?php endif; ?>
      <?php if ($curYearGoal > 0): ?>
      <div class="hero-goal-strip">
        <span class="hero-goal-label"><?= $curYear ?> Goal</span>
        <div class="hero-goal-bar-wrap"><div class="hero-goal-bar-fill" style="width:<?= $goalPct ?>%"></div></div>
        <span class="hero-goal-num"><?= $curYearCount ?> / <?= $curYearGoal ?></span>
        <span class="hero-goal-pct<?= $goalPct >= 100 ? ' goal-complete' : '' ?>"><?= $goalPct ?>%<?= $goalPct >= 100 ? ' &#10003;' : '' ?></span>
      </div>
      <?php endif; ?>
      </div>

      <?php
      ?>
      <div class="contents-grid">

        <!-- ── The Pipeline ── -->
        <div class="pipeline-band">
          <div class="pipeline-band-title">The Pipeline<span class="pipeline-band-sub">from idea to table</span></div>
          <div class="pipeline-nodes">
            <?php if ($hasRecipes): ?>
              <a class="pipeline-node" data-jump="recipes">
                <div class="pipeline-node-name">Recipes</div>
                <div class="pipeline-node-num"><?= $cnt_recipes ?></div>
                <div class="pipeline-node-blurb">Techniques, locked in</div>
              </a>
              <div class="pipeline-arrow">&#8594;</div>
            <?php endif; ?>
            <a class="pipeline-node" data-jump="gallery">
              <div class="pipeline-node-name">Paint Schemes</div>
              <div class="pipeline-node-num"><?= $cnt_models ?></div>
              <div class="pipeline-node-blurb">Finished work<?= $cnt_models_painted > $cnt_models ? ' &middot; ' . $cnt_models_painted . ' models' : '' ?></div>
            </a>
            <div class="pipeline-arrow">&#8594;</div>
            <a class="pipeline-node" data-jump="planned">
              <div class="pipeline-node-name">Planned</div>
              <div class="pipeline-node-num"><?= $cnt_planned ?></div>
              <div class="pipeline-node-blurb"><?= $cnt_ready > 0 ? '<span style="color:#60c060">' . $cnt_ready . ' ready now</span>' : 'What&rsquo;s next' ?></div>
            </a>
            <?php if ($hasBench): ?>
              <div class="pipeline-arrow">&#8594;</div>
              <a class="pipeline-node" data-jump="bench">
                <div class="pipeline-node-name">On the Bench</div>
                <div class="pipeline-node-num"><?= $cnt_bench ?></div>
                <div class="pipeline-node-blurb"><?= ($hasShame && $cnt_shame_units > 0) ? $cnt_shame_units . ' in the pile' : 'Under da brush' ?></div>
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- ── Your Armies + The Workbench ── -->
        <div class="armies-workbench-row">
          <?php if ($hasFactions || $hasForces): ?>
          <div class="armies-section">
            <div class="contents-section-title">Your Armies</div>
            <?php if ($hasFactions): ?>
            <a class="contents-entry" data-jump="factions">
              <div class="contents-entry-name">Factions</div>
              <div class="contents-entry-blurb">Every army in one view - finished schemes, bench work, planned, and the full palette built for each faction.</div>
              <div class="contents-entry-count"><?= $factionCount ?> faction<?= $factionCount !== 1 ? 's' : '' ?> represented</div>
            </a>
            <?php endif; ?>
            <?php if ($hasForces): ?>
              <a class="contents-entry" data-jump="forces">
                <div class="contents-entry-name">Forces &amp; Rosters</div>
                <div class="contents-entry-blurb">Named rosters for Kill Team, OPR, Blood Bowl, and more - painted count vs. target, with scheme thumbnails.</div>
                <div class="contents-entry-count"><?= $cnt_forces ?> force<?= $cnt_forces !== 1 ? 's' : '' ?> assembled</div>
              </a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="workbench-section">
            <div class="contents-section-title">The Workbench</div>
            <a class="contents-entry" data-jump="inventory">
              <div class="contents-entry-name">Paint Inventory</div>
              <div class="contents-entry-blurb">Every paint catalogued with stock status. Know exactly what to grab before you sit down.</div>
              <div class="contents-entry-count"><?= $cnt_owned ?> owned<?= $cnt_low_out > 0 ? ' &middot; <span style="color:#c97a20">' . $cnt_low_out . ' low/out</span>' : '' ?></div>
            </a>
            <?php if ($hasBrushes): ?>
              <a class="contents-entry" data-jump="brushes">
                <div class="contents-entry-name">Brushes</div>
                <div class="contents-entry-blurb">Prime, workhorse, retired - the tools that actually touch the paint.</div>
                <div class="contents-entry-count"><?= $cnt_brushes ?> active</div>
              </a>
            <?php endif; ?>
            <?php if ($hasShame): ?>
              <a class="contents-entry" data-jump="shame">
                <div class="contents-entry-name">Pile of Shame</div>
                <div class="contents-entry-blurb">Boxes judgin' you from da shelf. Promote one to Planned or Bench when you&rsquo;re ready.</div>
                <div class="contents-entry-count"><?= $cnt_shame ?> box<?= $cnt_shame !== 1 ? 'es' : '' ?> waiting</div>
              </a>
            <?php endif; ?>
            <?php if ($hasWishlist): ?>
              <a class="contents-entry" data-jump="wishlist">
                <div class="contents-entry-name">Wishlist</div>
                <div class="contents-entry-blurb">Paints, kits, brushes, and books you want to acquire. Never lose track of a want.</div>
                <div class="contents-entry-count"><?= count($wishlistData) ?> item<?= count($wishlistData) !== 1 ? 's' : '' ?></div>
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- ── The Library ── -->
        <div class="library-band">
          <div class="library-band-title">The Library</div>
          <div class="library-entries">
            <a class="contents-entry" data-jump="equiv">
              <div class="contents-entry-name">Equivalency</div>
              <div class="contents-entry-blurb">Cross-brand swap chart - Citadel &#8596; Vallejo &#8596; Pro Acryl &#8596; Two Thin Coats.</div>
              <div class="contents-entry-count"><?= $cnt_eq ?> equivalencies</div>
            </a>
            <?php if ($hasBooks): ?>
              <a class="contents-entry" data-jump="books">
                <div class="contents-entry-name">Codices</div>
                <div class="contents-entry-blurb">Army books, supplements, and campaigns - lore, colour schemes, and reference notes.</div>
                <div class="contents-entry-count"><?= $cnt_books ?> cod<?= $cnt_books !== 1 ? 'ices' : 'ex' ?></div>
              </a>
            <?php endif; ?>
            <?php if ($hasJournal): ?>
              <a class="contents-entry" data-jump="journals">
                <div class="contents-entry-name">Scrap Notes</div>
                <div class="contents-entry-blurb">A running diary of sessions and discoveries - the narrative thread connecting all the work.</div>
                <div class="contents-entry-count"><?= $cnt_journal ?> entr<?= $cnt_journal !== 1 ? 'ies' : 'y' ?></div>
              </a>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div><!-- #tab-contents -->

  <div id="tab-inventory" class="tab-panel">

    <div id="controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'inventory')" title="Copy link to this tab">Paint Inventory</a>
      <input type="search" id="search" placeholder="Search name or hue&hellip;" autocomplete="off">

      <select id="filter-brand">
        <option value="">All Brands</option>
        <?php
        $brands = array_unique(array_column($paints, 'brand'));
        sort($brands);
        foreach ($brands as $b) echo '<option value="' . htmlspecialchars($b, ENT_QUOTES) . '">' . htmlspecialchars($b, ENT_QUOTES) . '</option>';
        ?>
      </select>

      <select id="filter-color">
        <option value="">All Colours</option>
        <optgroup label="── Paint ──">
          <option value="White">White</option>
          <option value="Grey">Grey</option>
          <option value="Black">Black</option>
          <option value="Flesh">Flesh</option>
          <option value="Brown">Brown</option>
          <option value="Red">Red</option>
          <option value="Orange">Orange</option>
          <option value="Yellow">Yellow</option>
          <option value="Green">Green</option>
          <option value="Blue">Blue</option>
          <option value="Purple">Purple</option>
          <option value="Pink">Pink</option>
          <option value="Metallic">Metallic</option>
          <option value="Fluorescent">Fluorescent</option>
        </optgroup>
        <optgroup label="── Specialty ──">
          <option value="Wash">Wash</option>
          <option value="Shade">Shade</option>
          <option value="Contrast">Contrast</option>
          <option value="Ink">Ink</option>
          <option value="Special">Special</option>
        </optgroup>
        <optgroup label="── Utility ──">
          <option value="Effect">Effect</option>
          <option value="Medium">Medium</option>
          <option value="Texture">Texture</option>
          <option value="Primer">Primer</option>
          <option value="Pigment">Pigment</option>
          <option value="Fluid">Fluid</option>
          <option value="Utility">Utility</option>
        </optgroup>
      </select>

      <select id="filter-layer">
        <option value="">All Layers</option>
        <optgroup label="── Citadel ──">
          <option value="Base">Base</option>
          <option value="Contrast">Contrast</option>
          <option value="Shade">Shade</option>
          <option value="Air">Air</option>
          <option value="Technical">Technical</option>
        </optgroup>
        <optgroup label="── Pro Acryl ──">
          <option value="Metallic">Metallic</option>
          <option value="Transparent">Transparent</option>
          <option value="Fluorescent">Fluorescent</option>
          <option value="Special">Special</option>
        </optgroup>
        <optgroup label="── Army Painter ──">
          <option value="Speedpaint">Speedpaint</option>
          <option value="Speedpaint Metallic">Speedpaint Metallic</option>
          <option value="Airbrush Metallic">Airbrush Metallic</option>
        </optgroup>
        <optgroup label="── Vallejo ──">
          <option value="Model Color">Model Color</option>
          <option value="Model Air">Model Air</option>
          <option value="Ink">Ink</option>
          <option value="Varnish">Varnish</option>
          <option value="X-Opaque">X-Opaque</option>
        </optgroup>
        <optgroup label="── Utility ──">
          <option value="Medium">Medium</option>
          <option value="Effect">Effect</option>
          <option value="Texture">Texture</option>
          <option value="Terrain">Terrain</option>
          <option value="Weathering">Weathering</option>
          <option value="Mud">Mud</option>
          <option value="Primer">Primer</option>
          <option value="Tool">Tool</option>
        </optgroup>
        <optgroup label="── Oils ──">
          <option value="Oil">Oil</option>
        </optgroup>
      </select>

      <button id="reset">Reset</button>
      <span id="count"></span>
    </div>
    <p class="tab-blurb">The armoury laid bare. Know your stock before you commit colour to plastic.</p>
    <div class="table-wrap">
      <table id="paint-table">
        <thead>
          <tr>
            <th data-col="color" style="width:40px"></th>
            <th data-col="brand">Brand <span class="sort-icon"></span></th>
            <th data-col="name">Name <span class="sort-icon"></span></th>
            <th data-col="color" class="col-colour">Colour <span class="sort-icon"></span></th>
            <th data-col="hue" class="col-hue">Hue <span class="sort-icon"></span></th>
            <th data-col="layer">Layer <span class="sort-icon"></span></th>
            <th data-col="schemes" class="col-used" style="width:52px;text-align:center">Used <span class="sort-icon"></span></th>
          </tr>
        </thead>
        <tbody id="tbody"></tbody>
      </table>
      <div id="empty">No paints match your filters.</div>
    </div>


  </div><!-- #tab-inventory -->

  <?php if ($hasWishlist): ?>
  <div id="tab-wishlist" class="tab-panel">
    <div id="wishlist-controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'wishlist')" title="Copy link to this tab">Wishlist</a>
      <input id="wishlist-search" type="search" class="tab-search" placeholder="Search wishlist&hellip;">
      <div id="wishlist-type-pills" class="pill-row"></div>
      <div id="wishlist-pri-pills" class="pill-row">
        <button class="wish-pp active" data-wpri="all">All</button>
        <button class="wish-pp" data-wpri="high">High</button>
        <button class="wish-pp" data-wpri="medium">Medium</button>
        <button class="wish-pp" data-wpri="low">Low</button>
      </div>
      <button id="wishlist-copy-btn" class="filter-btn-sm">Copy</button>
      <button id="wishlist-print-btn" class="filter-btn-sm">Print</button>
      <span id="wishlist-count"></span>
    </div>
    <p class="tab-blurb">Acquisitions under consideration, ranked by priority. The procurement list before the purchase.</p>
    <div id="wishlist-grid" class="wishlist-grid"></div>
    <div id="wishlist-empty" class="tab-empty hidden">No items match.</div>
  </div>
  <?php endif; ?>

  <?php if ($hasShame): ?>
  <div id="tab-shame" class="tab-panel">
    <div id="shame-controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'shame')" title="Copy link to this tab">Pile of Shame</a>
      <input id="shame-search" type="search" class="tab-search" placeholder="Search boxes...">
      <div id="shame-filter-pills" class="pill-row">
        <button class="shame-fp active" data-filter="active">Active</button>
        <button class="shame-fp" data-filter="promoted">Promoted</button>
        <button class="shame-fp" data-filter="all">All</button>
      </div>
      <span id="shame-summary"></span>
    </div>
    <p class="tab-blurb">Boxed, sealed, optimistically purchased. All of it logged here. None of it judged.</p>
    <div class="shame-grid" id="shame-grid"></div>
    <div id="shame-more"></div>
    <div id="shame-empty" class="tab-empty hidden">No boxes match.</div>
  </div>
  <?php endif; ?>

  <div id="tab-planned" class="tab-panel">
    <div id="planned-controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'planned')" title="Copy link to this tab">Planned</a>
      <input type="search" id="planned-search" class="tab-search" placeholder="Search schemes&hellip;" autocomplete="off">
      <div id="planned-ready-pills" class="pill-row">
        <button class="planned-rp active" data-ready="all">All</button>
        <button class="planned-rp" data-ready="ready">Ready</button>
        <button class="planned-rp" data-ready="almost">Almost</button>
        <button class="planned-rp" data-ready="needs">Needs Work</button>
      </div>
      <select id="planned-sys-select" onchange="plannedSystemFilter=this.value;renderPlanned()" class="mini-select">
        <option value="">All systems</option>
        <option value="40k">40k</option>
        <option value="30k / HH">30k / HH</option>
        <option value="AoS">AoS</option>
        <option value="Kill Team">Kill Team</option>
        <option value="Blood Bowl">Blood Bowl</option>
        <option value="Necromunda">Necromunda</option>
        <option value="OPR">OPR</option>
        <option value="Other">Other</option>
      </select>
      <span id="planned-count"></span>
      <button id="shop-list-btn" onclick="openShoppingList()">Shopping List</button>
    </div>
    <p class="tab-blurb">Schemes queued for the brush. Check readiness, flag what's missing, and hold the line until the order arrives.</p>
    <div class="planned-grid" id="planned-grid"></div>
    <div id="planned-empty" class="planned-empty hidden">
      No planned schemes yet - add one in admin.
    </div>
  </div><!-- #tab-planned -->

  <?php if ($hasBench): ?>
    <div id="tab-bench" class="tab-panel">
      <div id="bench-controls">
        <a class="tab-label" href="#" onclick="copyTabLink(event,'bench')" title="Copy link to this tab">On the Bench</a>
        <input type="search" id="bench-search" class="tab-search" placeholder="Search projects, factions, notes&hellip;" autocomplete="off">
        <div class="bench-filter-pills" id="bench-filter-pills">
          <button class="bench-filter-pill active" data-stage="all">All</button>
          <button class="bench-filter-pill" data-stage="active">Active</button>
          <button class="bench-filter-pill" data-stage="done">Done</button>
        </div>
        <select id="bench-sys-select" onchange="benchSystemFilter=this.value;window._renderBench&&window._renderBench()" class="mini-select">
          <option value="">All systems</option>
          <option value="40k">40k</option>
          <option value="30k / HH">30k / HH</option>
          <option value="AoS">AoS</option>
          <option value="Kill Team">Kill Team</option>
          <option value="Blood Bowl">Blood Bowl</option>
          <option value="Necromunda">Necromunda</option>
          <option value="OPR">OPR</option>
          <option value="Other">Other</option>
        </select>
        <span id="bench-count"></span>
      </div>
      <p class="tab-blurb">Active projects only - the grind between primed grey and varnished glory.</p>
      <div class="bench-grid" id="bench-grid"></div>
      <div id="bench-empty" class="bench-empty hidden">
        No projects on the bench yet - add one in admin.
      </div>
    </div><!-- #tab-bench -->
  <?php endif; ?>

  <?php if ($hasForces): ?>
  <div id="tab-forces" class="tab-panel">
    <div id="forces-controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'forces')" title="Copy link to this tab">Forces &amp; Rosters</a>
      <input type="search" id="forces-search" class="tab-search" placeholder="Search forces, factions&hellip;" autocomplete="off">
      <span id="forces-count"></span>
    </div>
    <p class="tab-blurb">Named armies, muster counts, readiness assessed. The order of battle, written down.</p>
    <div class="forces-grid" id="forces-grid"></div>
    <div id="forces-empty" class="tab-empty hidden">
      No forces yet - add one in admin.
    </div>
  </div><!-- #tab-forces -->
  <?php endif; ?>


  <?php if ($hasBooks): ?>
    <div id="tab-books" class="tab-panel">
      <div id="bl-controls">
        <a class="tab-label" href="#" onclick="copyTabLink(event,'books')" title="Copy link to this tab">Codices</a>
        <input type="search" id="bl-search" class="tab-search" placeholder="Search faction, title, edition, notes&hellip;" autocomplete="off">
        <span id="bl-count"></span>
      </div>
      <p class="tab-blurb">The library of war. Army books and supplements catalogued for reference at the desk.</p>
      <div id="bl-list"></div>
      <div id="bl-empty" class="hidden">No codices yet - add one in admin.</div>
    </div><!-- #tab-books -->
  <?php endif; ?>

  <?php if ($hasJournal): ?>
    <div id="tab-journals" class="tab-panel">
      <div id="jn-controls">
        <a class="tab-label" href="#" onclick="copyTabLink(event,'journals')" title="Copy link to this tab">Scrap Notes</a>
        <input type="search" id="jn-search" class="tab-search" placeholder="Search all entries&hellip;" autocomplete="off">
        <div class="jn-month-nav" id="jn-month-nav">
          <button class="jn-nav-btn" id="jn-prev" title="Previous month">&#8249;</button>
          <button class="jn-month-label" id="jn-month-label" title="Click to jump to a year"></button>
          <button class="jn-nav-btn" id="jn-next" title="Next month">&#8250;</button>
        </div>
        <span id="jn-count"></span>
      </div>
      <div id="jn-year-picker" class="jn-year-picker hidden"></div>
      <p class="tab-blurb">Thoughts from the desk, dated and kept. Technique discoveries, session logs, anything worth remembering.</p>
      <div id="jn-list"></div>
      <div id="jn-empty" class="hidden">No entries yet - add one in admin.</div>
    </div><!-- #tab-journals -->
  <?php endif; ?>

  <?php if ($hasFactions): ?>
  <div id="tab-factions" class="tab-panel">
    <div id="factions-controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'factions')" title="Copy link to this tab">Factions</a>
      <input type="search" id="factions-search" class="tab-search" placeholder="Search factions&hellip;" autocomplete="off">
      <span id="factions-count"></span>
    </div>
    <p class="tab-blurb">The full order of battle, arranged by allegiance. Every faction's palette, recipes, and pipeline in one place.</p>
    <div id="factions-wrap"></div>
    <div id="factions-empty" class="hidden">No factions yet - tag a scheme, project, or recipe with a faction name and it will show up here.</div>
  </div><!-- #tab-factions -->
  <?php endif; ?>

  <div id="tab-equiv" class="tab-panel">
    <div id="equiv-controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'equiv')" title="Copy link to this tab">Equivalency</a>
      <input type="search" id="equiv-search" class="tab-search" placeholder="Search paint name or match rating&hellip;" autocomplete="off">
      <span id="equiv-count"></span>
    </div>
    <div class="equiv-explainer">
      <p>All equivalences are compared against Citadel (now Warhammer Paints) colours. Match ratings reflect personal real-world results - coverage and colour on a model, not theory. Washes, Contrast, and technical paints are approximations only. (Click &#9998; for notes or ★ for rating.)</p>
      <div class="equiv-match-legend">
        <span class="equiv-legend-item"><span class="eq-match-dot eq-match-near"></span>Near identical - safe 1:1 swap</span>
        <span class="equiv-legend-item"><span class="eq-match-dot eq-match-usable"></span>Usable - slight shift, fine for tabletop</span>
        <span class="equiv-legend-item"><span class="eq-match-dot eq-match-avoid"></span>Avoid - poor match</span>
      </div>
    </div>
    <div class="equiv-legend">
      <span class="equiv-legend-item"><span class="eq-dot owned"></span>Owned</span>
      <span class="equiv-legend-item"><span class="eq-dot low"></span>Low stock</span>
      <span class="equiv-legend-item"><span class="eq-dot out"></span>Out</span>
      <span class="equiv-legend-item"><span class="eq-dot wanted"></span>Wanted</span>
      <span class="equiv-legend-item"><span class="eq-dot missing"></span>Not owned</span>
    </div>
    <div class="equiv-brand-pills">
      <button class="equiv-bp active" data-compare="val">Vallejo</button>
      <button class="equiv-bp" data-compare="pa">Pro Acryl</button>
      <button class="equiv-bp" data-compare="ttc">Two Thin Coats</button>
    </div>
    <div class="equiv-table-wrap">
      <table class="equiv-table">
        <thead>
          <tr>
            <th>Citadel</th>
            <th>Vallejo</th>
            <th>Pro Acryl</th>
            <th>Two Thin Coats</th>
          </tr>
        </thead>
        <tbody id="equiv-tbody"></tbody>
      </table>
      <div id="equiv-empty">No equivalencies match your search.</div>
    </div>
  </div><!-- #tab-equiv -->


  <?php if ($hasRecipes): ?>
    <div id="tab-recipes" class="tab-panel">
      <div id="recipes-controls">
        <a class="tab-label" href="#" onclick="copyTabLink(event,'recipes')" title="Copy link to this tab">Recipes</a>
        <input type="search" id="recipes-search" class="tab-search" placeholder="Search recipes, steps, notes&hellip;" autocomplete="off">
        <div class="recipes-filter-pills" id="recipes-filter-pills">
          <button class="recipes-filter-pill active" data-cat="all">All</button>
          <button class="recipes-filter-pill" data-cat="__orphan__">Unused</button>
        </div>
        <span id="recipes-count"></span>
      </div>
      <p class="tab-blurb">Proven technique, written down step by step. The difference between a consistent result and a happy accident.</p>
      <div id="recipes-grid"></div>
      <div id="recipes-empty" class="recipes-empty hidden">
        No recipes yet - add one in admin.
      </div>
    </div><!-- #tab-recipes -->
  <?php endif; ?>

  <footer>
    <div class="footer-sigil">&#9760;&ensp;&#9760;&ensp;&#9760;</div>
    <div class="footer-domain"><?= htmlspecialchars(SITE_DOMAIN) ?></div>
    <div class="footer-rule"><span class="footer-rule-gem"></span></div>
    <div class="footer-copy">&copy; MMXXVI &nbsp;&middot;&nbsp; <?= htmlspecialchars(SITE_AUTHOR) ?> &nbsp;&middot;&nbsp; All Rights Reserved</div>
    <div class="footer-contact"><a href="mailto:<?= htmlspecialchars(SITE_EMAIL) ?>"><?= htmlspecialchars(SITE_EMAIL) ?></a></div>
  </footer>

  <!-- ── Notes Drawer ── -->
  <div class="notes-overlay" id="notes-overlay">
    <div class="notes-sheet">
      <div class="notes-sheet-header">
        <div>
          <div class="notes-sheet-name" id="notes-paint-name"></div>
          <div class="notes-sheet-brand" id="notes-paint-brand"></div>
        </div>
        <button class="notes-close" onclick="closeNotes()">&times;</button>
      </div>
      <div id="notes-stars-section">
        <div class="notes-stars-label">Quality Rating</div>
        <div class="notes-stars-row" id="notes-star-picker">
          <span class="nsp-star" data-val="1">★</span>
          <span class="nsp-star" data-val="2">★</span>
          <span class="nsp-star" data-val="3">★</span>
          <span class="nsp-star" data-val="4">★</span>
          <span class="nsp-star" data-val="5">★</span>
        </div>
      </div>
      <div id="notes-body"></div>
    </div>
  </div>

  <!-- ── Used-In ── -->
  <div class="used-in-overlay" id="used-in-overlay">
    <div class="used-in-sheet">
      <div class="used-in-paint-name" id="used-in-paint-name"></div>
      <div class="used-in-brand" id="used-in-brand"></div>
      <div id="used-in-content"></div>
      <button class="used-in-close" onclick="closeUsedIn()">Close</button>
    </div>
  </div>

  <!-- ── Pull Sheet ── -->
  <div class="pull-overlay" id="pull-overlay">
    <div class="pull-sheet">
      <div class="pull-title" id="pull-title"></div>
      <div class="pull-faction" id="pull-faction"></div>
      <div id="pull-content"></div>
      <div class="pull-actions">
        <button onclick="window.print()">Print</button>
        <button id="pull-copy-btn" onclick="copyPullList()">Copy</button>
        <button onclick="closePull()">Close</button>
      </div>
    </div>
  </div>

  <!-- ── Shopping List ── -->
  <div class="shop-overlay" id="shop-overlay">
    <div class="shop-sheet">
      <div class="shop-title">Shopping List</div>
      <div class="shop-subtitle" id="shop-subtitle"></div>
      <div id="shop-content"></div>
      <div class="shop-actions">
        <button onclick="printShopList()">Print</button>
        <button id="shop-copy-btn" onclick="copyShoppingList()">Copy</button>
        <button onclick="closeShoppingList()">Close</button>
      </div>
    </div>
  </div>

  <!-- ── Lightbox ── -->
  <div class="lightbox-overlay" id="lightbox">
    <span class="lb-close" id="lb-close">&times;</span>
    <span class="lb-arrow lb-prev" id="lb-prev">&#8249;</span>
    <img class="lb-img" id="lb-img" src="" alt="">
    <span class="lb-arrow lb-next" id="lb-next">&#8250;</span>
    <span class="lb-counter" id="lb-counter"></span>
  </div>

  <!-- ── Recipe Guide ── -->
  <div class="recipe-guide-overlay" id="recipe-guide-overlay">
    <div class="recipe-guide-card">
      <button class="recipe-guide-close" onclick="closeRecipeGuide()">&times;</button>
      <div class="recipe-guide-title" id="recipe-guide-title"></div>
      <div class="recipe-guide-counter" id="recipe-guide-counter"></div>
      <div class="recipe-guide-step-content" id="recipe-guide-step-content"></div>
      <div class="recipe-guide-dots" id="recipe-guide-dots"></div>
      <div class="recipe-guide-nav">
        <button class="recipe-guide-prev" id="recipe-guide-prev" onclick="stepGuide(-1)">&#8249;</button>
        <button class="recipe-guide-next" id="recipe-guide-next" onclick="stepGuide(1)">&#8250;</button>
      </div>
    </div>
  </div>

  <!-- ── Global Search ── -->
  <button id="gs-trigger" type="button" title="Search everything (Ctrl+K or /)" aria-label="Open global search">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  </button>

  <div id="gs-overlay" class="gs-overlay" role="dialog" aria-modal="true">
    <div class="gs-modal">
      <div class="gs-input-wrap">
        <svg class="gs-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" id="gs-input" placeholder="Search paints, schemes, recipes, anything&hellip;" autocomplete="off">
        <kbd class="gs-esc">Esc</kbd>
      </div>
      <div id="gs-results" class="gs-results"></div>
      <div class="gs-foot"><kbd>&uarr;</kbd><kbd>&darr;</kbd> navigate · <kbd>Enter</kbd> open · <kbd>Esc</kbd> close</div>
    </div>
  </div>

  <script>
    const PAINTS = <?= $paintsJson ?>;
    let paintUsage = new Map(); // populated after MODELS is defined
    // 3-part unique key: brand|name|layer
    const paintKey = p => p.brand + '|' + p.name + '|' + (p.layer || '');
    const paintOwned = new Set(PAINTS.filter(p => p.stock !== 'wanted').map(paintKey));
    const paintStock = new Map(PAINTS.filter(p => p.stock).map(p => [paintKey(p), p.stock]));
    const PLANNED = <?= $plannedJson ?>;
    const CONVERSIONS_DATA = <?= $conversionsDataJson ?>;
    <?php if ($hasBooks): ?>const BOOKS_DATA = <?= $booksDataJson ?>;
    <?php endif; ?>
    <?php if ($hasBrushes): ?>const BRUSHES_DATA = <?= $brushesDataJson ?>;
    <?php endif; ?>
    <?php if ($hasBench): ?>const BENCH_DATA = <?= $benchDataJson ?>;
    <?php endif; ?>
    <?php if ($hasRecipes): ?>const RECIPES_DATA = <?= $recipesDataJson ?>;
    <?php endif; ?>
    <?php if ($hasForces): ?>const FORCES_DATA = <?= $forcesDataJson ?>;
    <?php endif; ?>
    <?php if ($hasWishlist): ?>const WISHLIST_DATA = <?= json_encode($wishlistData, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE) ?>;<?php endif; ?>
    <?php if ($hasShame): ?>const SHAME_DATA = <?= json_encode($shameData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    <?php endif; ?>
    <?php if ($hasJournal): ?>const JOURNAL_DATA = <?= json_encode($journalData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    <?php endif; ?>
    const paintByKeyLC = new Map(PAINTS.map(p => [(p.brand + '|' + p.name).toLowerCase(), p.stock || '']));

    // Upgrade legacy 2-part stored keys to 3-part where unambiguous
    const _legacyUpgrade = new Map();
    PAINTS.forEach(p => {
      const k2 = p.brand + '|' + p.name;
      _legacyUpgrade.set(k2, _legacyUpgrade.has(k2) ? null : paintKey(p));
    });

    function upgradeKey(c) {
      if (c.split('|').length >= 3) return c;
      return _legacyUpgrade.get(c) || c;
    }

    // Brand slug for CSS class
    function brandSlug(b) {
      return b.toLowerCase().replace(/\s+/g, '');
    }

    // Sort state
    let sortCol = 'name';
    let sortDir = 1; // 1=asc, -1=desc

    // Controls
    const searchEl = document.getElementById('search');
    const filterBrand = document.getElementById('filter-brand');
    const filterColor = document.getElementById('filter-color');
    const filterLayer = document.getElementById('filter-layer');
    const resetBtn = document.getElementById('reset');
    const countEl = document.getElementById('count');
    const tbody = document.getElementById('tbody');
    const emptyEl = document.getElementById('empty');
    const table = document.getElementById('paint-table');
    const headers = document.querySelectorAll('th[data-col]');

    function render() {
      const q = searchEl.value.toLowerCase();
      const brand = filterBrand.value;
      const color = filterColor.value;
      const layer = filterLayer.value;

      let filtered = PAINTS.filter(p => {
        if (brand && p.brand !== brand) return false;
        if (color && p.color !== color) return false;
        if (layer && p.layer !== layer) return false;
        if (q && !p.name.toLowerCase().includes(q) &&
          !p.hue.toLowerCase().includes(q) &&
          !p.brand.toLowerCase().includes(q)) return false;
        return true;
      });

      // Sort
      filtered.sort((a, b) => {
        if (sortCol === 'schemes') {
          const av = paintUsage.get(paintKey(a)) || 0;
          const bv = paintUsage.get(paintKey(b)) || 0;
          return (av - bv) * sortDir || a.name.localeCompare(b.name);
        }
        let av = a[sortCol] || '';
        let bv = b[sortCol] || '';
        const primary = av.localeCompare(bv) * sortDir;
        if (primary !== 0) return primary;
        return a.name.localeCompare(b.name);
      });

      // Render rows
      if (filtered.length === 0) {
        tbody.innerHTML = '';
        table.style.display = 'none';
        emptyEl.style.display = 'block';
      } else {
        table.style.display = '';
        emptyEl.style.display = 'none';
        tbody.innerHTML = filtered.map(p => {
          const slug = brandSlug(p.brand);
          const swatchClass = 'swatch swatch-' + p.color.replace(/\s+/g, '');
          const badgeClass = 'badge badge-' + p.layer.replace(/\s+/g, '');
          const used = paintUsage.get(paintKey(p)) || 0;
          const countHtml = used > 0 ?
            `<span class="scheme-count${used > 1 ? ' workhorse' : ''}">${used}</span>` :
            '';
          const stockVal = p.stock || '';
          const stockBadge = stockVal ?
            `<span class="inv-stock-badge inv-stock-${stockVal}">${stockVal}</span>` :
            '';
          const pid = paintKey(p);
          const starVal = p.stars || 0;
          const notesBtn = `<button class="notes-btn${p.notes ? ' has-notes' : ''}" title="${p.notes ? 'View notes' : 'No notes'}" data-pid="${esc(pid)}" data-stars="${starVal}" data-notes-brand="${esc(p.brand)}" data-notes-name="${esc(p.name)}" data-notes-text="${esc(p.notes||'')}">&#9998;</button>`;
          const starBtn = `<button class="star-rate-btn${starVal ? ' has-stars' : ''}" title="${starVal ? starVal + ' stars' : 'Rate this paint'}" data-pid="${esc(pid)}" data-stars="${starVal}" data-notes-brand="${esc(p.brand)}" data-notes-name="${esc(p.name)}" data-notes-text="${esc(p.notes||'')}">★</button>`;
          return `<tr class="brand-${slug}" data-brand="${esc(p.brand)}" data-name="${esc(p.name)}" data-layer="${esc(p.layer||'')}" title="Show schemes using this paint">
        <td><span class="${swatchClass}" title="${p.color}"></span></td>
        <td>${esc(p.brand)}</td>
        <td>${esc(p.name)}${stockBadge}${notesBtn}${starBtn}</td>
        <td class="col-colour">${esc(p.color)}</td>
        <td class="col-hue">${esc(p.hue)}</td>
        <td><span class="${badgeClass}">${esc(p.layer)}</span></td>
        <td class="col-used" style="text-align:center">${countHtml}</td>
      </tr>`;
        }).join('');
      }

      countEl.textContent = filtered.length + ' of ' + PAINTS.length + ' paints';

      // Update sort indicators
      headers.forEach(th => {
        const icon = th.querySelector('.sort-icon');
        if (!icon) return;
        if (th.dataset.col === sortCol) {
          th.classList.add('active');
          icon.textContent = sortDir === 1 ? ' ▲' : ' ▼';
        } else {
          th.classList.remove('active');
          icon.textContent = '';
        }
      });
    }

    function esc(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    // Recipe reference badges helper - emits "Uses: [Recipe A] [Recipe B]" row or empty string
    function collectAllPaints(direct, recipeIds) {
      const seen = new Set();
      const out = [];
      (direct || []).forEach(c => { if (!seen.has(c)) { seen.add(c); out.push(c); } });
      if (recipeIds && recipeIds.length && typeof window._RECIPE_BY_ID !== 'undefined') {
        recipeIds.forEach(rid => {
          const r = window._RECIPE_BY_ID.get(rid);
          if (!r) return;
          (r.steps || []).forEach(s => {
              if (s.paint && !seen.has(s.paint)) { seen.add(s.paint); out.push(s.paint); }
              if (s.mix_paint && !seen.has(s.mix_paint)) { seen.add(s.mix_paint); out.push(s.mix_paint); }
            });
        });
      }
      return out;
    }

    function renderRecipeRefs(recipeIds) {
      if (typeof window._RECIPE_BY_ID === 'undefined' || !recipeIds || !recipeIds.length) return '';
      const badges = recipeIds.map(rid => {
        const r = window._RECIPE_BY_ID.get(rid);
        if (!r) return '';
        return `<span class="recipe-ref-badge" onclick="_jumpToRecipe('${esc(rid)}');event.stopPropagation();">${esc(r.name)}</span>`;
      }).filter(Boolean).join('');
      return badges ? `<div class="recipe-ref-row"><span style="font-family:'Cinzel',serif;font-size:9px;letter-spacing:.06em;color:#4a3a1a;margin-right:4px">Uses</span>${badges}</div>` : '';
    }

    // Sort on header click
    headers.forEach(th => {
      if (!th.querySelector('.sort-icon')) return; // swatch column has no icon
      th.addEventListener('click', () => {
        const col = th.dataset.col;
        if (sortCol === col) {
          sortDir *= -1;
        } else {
          sortCol = col;
          sortDir = 1;
        }
        render();
      });
    });

    // Filter/search events
    searchEl.addEventListener('input', render);
    filterBrand.addEventListener('change', render);
    filterColor.addEventListener('change', render);
    filterLayer.addEventListener('change', render);

    resetBtn.addEventListener('click', () => {
      searchEl.value = '';
      filterBrand.value = '';
      filterColor.value = '';
      filterLayer.value = '';
      sortCol = 'name';
      sortDir = 1;
      render();
    });

    // ── Gallery ──────────────────────────────────────
    const MODELS = <?= $modelsJson ?>;
    MODELS.forEach(m => {
      const mc = Math.max(1, parseInt(m.count || 1, 10));
      (m.colors || []).forEach(c => {
        const k = upgradeKey(c);
        paintUsage.set(k, (paintUsage.get(k) || 0) + mc);
      });
    });

    // Initial render - must be after MODELS loop so paintUsage is populated
    render();

    // Returns the full effective paint list for a scheme: own colors + step paints from referenced recipes (deduped).
    // _RECIPE_BY_ID is set lazily by the Recipes IIFE; falls back to own colors when recipes aren't loaded yet.
    function effectiveColors(m) {
      const own = m.colors || [];
      if (!m.recipes || !m.recipes.length || !window._RECIPE_BY_ID) return own;
      const seen = new Set(own.map(c => upgradeKey(c).toLowerCase()));
      const extra = [];
      for (const rid of m.recipes) {
        const r = window._RECIPE_BY_ID.get(rid);
        if (!r) continue;
        for (const step of (r.steps || [])) {
          if (!step.paint) continue;
          const uk = upgradeKey(step.paint).toLowerCase();
          if (!seen.has(uk)) { seen.add(uk); extra.push(step.paint); }
        }
      }
      return extra.length ? own.concat(extra) : own;
    }

    let factionFilter = '';
    let gallerySystemFilter = '';
    let showAllGallery = false;
    let gallerySearch = '';
    let readyFilter = false;

    const SYS_COLORS = {'40k':'#5a1a1a','30k / HH':'#4a3a0a','AoS':'#1a2a5a','Kill Team':'#0a3a3a','Blood Bowl':'#0a3a1a','Necromunda':'#3a0a5a','OPR':'#1a2a3a','Other':'#2a2a2a'};
    const sysSlug = s => ({'40k':'40k','30k / HH':'30k','AoS':'aos','Kill Team':'kt','Blood Bowl':'bb','Necromunda':'necro','Epic':'epic','OPR':'opr'}[s] || 'other');

    function modelReadiness(m) {
      const cols = effectiveColors(m);
      if (!cols.length) return null;
      let blocked = 0,
        hasLow = false;
      for (const c of cols) {
        const uk = upgradeKey(c);
        if (!paintOwned.has(uk) || paintStock.get(uk) === 'out') blocked++;
        else if (paintStock.get(uk) === 'low') hasLow = true;
      }
      if (blocked > 0) return {
        state: 'blocked',
        blocked
      };
      if (hasLow) return {
        state: 'low'
      };
      return {
        state: 'ready'
      };
    }

    function toggleReadyFilter() {
      readyFilter = !readyFilter;
      showAllGallery = false;
      document.getElementById('ready-filter-btn').classList.toggle('active', readyFilter);
      renderGallery();
    }

    function formatDesc(raw) {
      if (!raw) return '';
      const lines = raw.split(/\r?\n/);
      let out = '';
      for (const line of lines) {
        const t = line.trim();
        if (!t) continue;
        const sub = /^\s{2,}-/.test(line);
        if (/^[A-Z]{2,}/.test(t) && !t.startsWith('-')) {
          out += `<div class="desc-hd">${esc(t)}</div>`;
          continue;
        }
        const step = t.match(/^-\s+([^:]+):\s+(.+)$/);
        if (step) {
          out += `<div class="desc-step${sub ? ' desc-sub' : ''}"><span class="desc-lbl">${esc(step[1])}</span><span class="desc-val">${esc(step[2])}</span></div>`;
          continue;
        }
        if (t.startsWith('-')) {
          out += `<div class="desc-step${sub ? ' desc-sub' : ''}"><span class="desc-val">${esc(t.slice(1).trim())}</span></div>`;
          continue;
        }
        out += `<span>${esc(t)}</span><br>`;
      }
      return out ? `<div class="model-desc">${out}</div>` : '';
    }

    function renderGallery() {
      const grid = document.getElementById('gallery-grid');
      const emptyEl = document.getElementById('gallery-empty');
      const moreEl = document.getElementById('gallery-more');
      const factionPill = document.getElementById('active-faction-pill');
      const factionPullBtn = document.getElementById('faction-pull-btn');

      if (factionFilter) {
        factionPill.textContent = factionFilter + ' \u00d7';
        factionPill.style.display = 'inline-block';
        factionPullBtn.style.display = 'inline-block';
      } else {
        factionPill.style.display = 'none';
        factionPullBtn.style.display = 'none';
      }

      let list = factionFilter ?
        MODELS.filter(m => (m.faction || '') === factionFilter) :
        MODELS;

      if (gallerySystemFilter) {
        list = list.filter(m => (m.system || '') === gallerySystemFilter);
      }

      const q = gallerySearch.toLowerCase().trim();
      if (q) {
        list = list.filter(m =>
          (m.name || '').toLowerCase().includes(q) ||
          (m.faction || '').toLowerCase().includes(q) ||
          (m.description || '').toLowerCase().includes(q) ||
          (m.colors || []).some(c => c.toLowerCase().includes(q)) ||
          Object.values(m.summary || {}).some(v => (v || '').toLowerCase().includes(q))
        );
      }

      if (readyFilter) {
        list = list.filter(m => {
          const r = modelReadiness(m);
          return r && r.state !== 'blocked';
        });
      }

      if (!list.length) {
        grid.innerHTML = '';
        moreEl.style.display = 'none';
        emptyEl.style.display = 'block';
        emptyEl.innerHTML = q ?
          `No schemes match &ldquo;${esc(gallerySearch)}&rdquo;` :
          `No models yet - add one in admin.`;
        return;
      }
      emptyEl.style.display = 'none';

      // Sort by date descending; entries without a date fall to the end
      const sorted = list.slice().sort((a, b) => (b.date || '').localeCompare(a.date || ''));
      const limited = (!showAllGallery && !q && !readyFilter && sorted.length > 12) ? sorted.slice(0, 12) : sorted;

      if (!showAllGallery && !q && !readyFilter && sorted.length > 12) {
        const remaining = sorted.length - 12;
        moreEl.style.display = 'block';
        moreEl.innerHTML = `<div class="gallery-more-fade"></div><button class="gallery-more-btn" onclick="showAllGallery=true;renderGallery()"><span class="gallery-more-count">Showing 12 of ${sorted.length} schemes</span><span class="gallery-more-label">Reveal the remaining ${remaining} <span class="gallery-more-chevron">&#9662;</span></span></button>`;
      } else {
        moreEl.style.display = 'none';
      }

      grid.innerHTML = limited.map(m => {
        const imgs = (m.images || []).slice(0, 4);
        const imgClass = 'model-images imgs-' + (imgs.length || 0);
        const imgHtml = imgs.length ?
          imgs.map((src, i) => `<img src="${esc(src)}" alt="" loading="lazy" data-index="${i}">`).join('') :
          `<div class="model-no-image">No Images</div>`;

        const colors = (() => {
          const sorted = effectiveColors(m).slice().sort((a, b) => {
            const [ab, an = ''] = a.split('|');
            const [bb, bn = ''] = b.split('|');
            return ab.localeCompare(bb) || an.localeCompare(bn);
          });
          let lastBrand = '';
          return sorted.map(c => {
            const [brand, paintName = c] = c.split('|');
            const label = brand !== lastBrand ?
              `<span class="pill-brand-label">${esc(brand)}</span>` :
              '';
            lastBrand = brand;
            return label + `<span class="color-pill" data-paint="${esc(c)}">${esc(paintName)}</span>`;
          }).join('');
        })();

        const factionHtml = m.faction ?
          `<span class="faction-tag${factionFilter === m.faction ? ' active' : ''}" data-faction="${esc(m.faction)}">${esc(m.faction)}</span>` :
          '';
        const sysHtml = m.system ?
          `<span class="sys-game-badge sys-${sysSlug(m.system)}">${esc(m.system)}</span>` :
          '';
        const dateHtml = m.date ? esc(m.date) : '';
        const metaParts = [factionHtml, sysHtml, dateHtml].filter(Boolean);
        const meta = metaParts.join(' ');

        const r = modelReadiness(m);
        const readyDot = r ? `<span class="ready-dot ${r.state}" title="${
          r.state === 'ready'   ? 'Ready to paint' :
          r.state === 'low'     ? 'Ready \u2014 some paints running low' :
          `${r.blocked} paint${r.blocked > 1 ? 's' : ''} missing or out of stock`
        }"></span>` : '';

        const hasBody = m.description || colors;
        const recipeRefs = renderRecipeRefs(m.recipes);
        const hasBodyExt = hasBody || recipeRefs;
        const metaHtml = meta ? `<div class="model-meta">${meta}</div>` : '';
        const linkBtn = `<button class="model-link-btn" title="Copy link" onclick="copyModelLink(event,'${esc(m.id)}')"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>`;
        const countN = Math.max(1, parseInt(m.count || 1, 10));
        const countBadge = countN > 1 ? `<span class="model-count-badge" title="${countN} miniatures painted under this scheme">&times;${countN}</span>` : '';
        const descHtml = formatDesc(m.description);
        const summaryHtml = (() => {
          const s = m.summary;
          if (!s) return '';
          const rows = [['Finish', s.finish], ['Primary', s.primary], ['Contrast', s.contrast], ['Technique', s.technique]].filter(([, v]) => v);
          if (!rows.length) return '';
          return `<div class="model-summary">${rows.map(([l, v]) => `<span class="model-summary-lbl">${esc(l)}</span><span class="model-summary-val">${esc(v)}</span>`).join('')}</div>`;
        })();
        const codexBadge = m.codex_source ? `<span class="codex-source-badge">${esc(m.codex_source)}</span>` : '';
        const colorsHtml = colors ? (() => {
          const issues = effectiveColors(m).filter(c => { const uk = upgradeKey(c); return !paintOwned.has(uk) || paintStock.get(uk); }).length;
          const badge = issues > 0 ? `<span class="pull-issue-badge">${issues} issue${issues > 1 ? 's' : ''}</span>` : '';
          return `<div class="model-colors">${colors}</div><button class="pull-btn" onclick="openPull('${esc(m.id)}')">Pull list${badge}</button>`;
        })() : '';
        const bodyHtml = hasBodyExt || codexBadge || summaryHtml ? `<div class="model-info">${summaryHtml}${descHtml}${codexBadge}${recipeRefs}${colorsHtml}</div>` : '';
        const stripeHtml = m.theme_hex ? `<div class="model-theme-stripe" style="background:linear-gradient(to right,${esc(m.theme_hex)} 0%,transparent 100%)"></div>` : '';
        return `<div class="model-card" data-id="${esc(m.id)}"><div class="model-header"><div class="model-name">${esc(m.name)}${countBadge}${readyDot}</div>${metaHtml}${linkBtn}</div><div class="${imgClass}">${imgHtml}</div>${stripeHtml}${bodyHtml}</div>`;
      }).join('');
    }

    // Gallery click delegation: lightbox images, faction tags, color pills
    document.getElementById('gallery-grid').addEventListener('click', e => {
      // Lightbox
      const img = e.target.closest('.model-images img');
      if (img) {
        const card = img.closest('.model-card');
        const allImgs = Array.from(card.querySelectorAll('.model-images img')).map(i => i.src);
        openLightbox(allImgs, parseInt(img.dataset.index) || 0);
        return;
      }
      // Faction filter - toggle off if already active
      const ftag = e.target.closest('.faction-tag');
      if (ftag) {
        factionFilter = factionFilter === ftag.dataset.faction ? '' : ftag.dataset.faction;
        showAllGallery = false;
        renderGallery();
        return;
      }
      // Color pill → inventory
      const pill = e.target.closest('.color-pill');
      if (!pill) return;
      const parts = pill.dataset.paint.split('|');
      const brand = parts[0] || '';
      const name = parts[1] || parts[0];
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      document.querySelector('[data-tab="inventory"]').classList.add('active');
      document.getElementById('tab-inventory').classList.add('active');
      filterBrand.value = brand;
      searchEl.value = name;
      sortCol = 'name';
      sortDir = 1;
      render();
    });

    // Gallery search
    document.getElementById('gallery-search').addEventListener('input', e => {
      gallerySearch = e.target.value;
      showAllGallery = false;
      renderGallery();
    });

    // Active faction pill - click to clear
    document.getElementById('active-faction-pill').addEventListener('click', () => {
      factionFilter = '';
      showAllGallery = false;
      renderGallery();
    });

    // Tab switching
    function switchToTab(tabName) {
      const btn = document.querySelector(`.tab-btn[data-tab="${tabName}"]`);
      if (!btn) return false;
      btn.click();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
      return true;
    }
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        if (btn.dataset.tab === 'books' && window._renderBooks) window._renderBooks();
        if (btn.dataset.tab === 'journals' && window._renderJournals) window._renderJournals();
        if (btn.dataset.tab === 'brushes' && window._renderBrushes) window._renderBrushes();
        if (btn.dataset.tab === 'bench' && window._renderBench) window._renderBench();
        if (btn.dataset.tab === 'forces' && window._renderForces) window._renderForces();
        if (btn.dataset.tab === 'shame' && window._renderShame) window._renderShame();
        if (btn.dataset.tab === 'wishlist' && window._renderWishlist) window._renderWishlist();
        if (btn.dataset.tab === 'recipes' && window._renderRecipes) window._renderRecipes();
        if (btn.dataset.tab === 'factions' && window._renderFactions) window._renderFactions();
        fetch('index.php', {
          method: 'POST',
          body: new URLSearchParams({
            action: 'track_tab',
            tab: btn.dataset.tab
          })
        });
        if (typeof gtag !== 'undefined') gtag('event', 'tab_view', { tab_name: btn.dataset.tab });
        const _tabUrl = new URL(location.href);
        if (btn.dataset.tab === 'contents') {
          _tabUrl.searchParams.delete('tab');
        } else {
          _tabUrl.searchParams.set('tab', btn.dataset.tab);
        }
        history.replaceState(null, '', _tabUrl.toString());
      });
    });

    // Contents-page jump entries
    document.querySelectorAll('[data-jump]').forEach(el => {
      el.addEventListener('click', e => {
        e.preventDefault();
        switchToTab(el.dataset.jump);
      });
    });


    // ── Hobby Activity Heatmap ───────────────────────
    (function() {
      const el = document.getElementById('hero-heatmap');
      if (!el) return;
      const act = new Map();
      function bump(d) { if (d && /^\d{4}-\d{2}-\d{2}$/.test(d)) act.set(d, (act.get(d) || 0) + 1); }
      MODELS.forEach(m => bump(m.date));
      if (typeof JOURNAL_DATA !== 'undefined') JOURNAL_DATA.forEach(j => bump(j.date));
      if (typeof BENCH_DATA !== 'undefined') BENCH_DATA.forEach(b => { bump(b.last_touched); (b.history || []).forEach(h => bump(h.date)); });
      const DAYS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
      const MON = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const today = new Date(); today.setHours(0,0,0,0);
      function iso(d) { return d.toISOString().slice(0,10); }
      const gridStart = new Date(today); gridStart.setDate(today.getDate() - today.getDay() - 51 * 7);
      const gridEnd   = new Date(today); gridEnd.setDate(today.getDate() + (6 - today.getDay()));
      const weeks = [];
      const cur = new Date(gridStart);
      while (cur <= gridEnd) { const wk = []; for (let d = 0; d < 7; d++) { wk.push({ iso: iso(cur), count: act.get(iso(cur)) || 0, future: cur > today, dt: new Date(cur) }); cur.setDate(cur.getDate() + 1); } weeks.push(wk); }
      const isoToday = iso(today);
      const isoStart = iso(gridStart);
      const activeDays = [...act.keys()].filter(d => d >= isoStart && d <= isoToday).length;
      function lvl(c, f) { if (f || c === 0) return 0; return c === 1 ? 1 : c === 2 ? 2 : 3; }
      let lastMon = -1;
      const monthRow = weeks.map(wk => { const m = wk[0].dt.getMonth(); const lbl = m !== lastMon ? MON[m] : ''; lastMon = m; return `<span>${lbl}</span>`; }).join('');
      const gridHtml = weeks.map(wk => `<div class="hm-week">${wk.map(day => { const l = lvl(day.count, day.future); const cls = 'hm-day ' + (day.future ? 'hm-future' : `hm-lv${l}`) + (day.iso === isoToday ? ' hm-today' : ''); const tip = day.future ? '' : day.count ? `${DAYS[day.dt.getDay()]} ${MON[day.dt.getMonth()]} ${day.dt.getDate()}, ${day.dt.getFullYear()} · ${day.count} activit${day.count === 1 ? 'y' : 'ies'}` : `${DAYS[day.dt.getDay()]} ${MON[day.dt.getMonth()]} ${day.dt.getDate()}, ${day.dt.getFullYear()}`; return `<div class="${cls.trim()}"${tip ? ` title="${tip}"` : ''}></div>`; }).join('')}</div>`).join('');
      el.innerHTML = `<div class="hm-header">Hobby Activity &middot; <span class="hm-count">${activeDays} active day${activeDays === 1 ? '' : 's'} this year</span></div><div class="hm-scroll"><div class="hm-inner"><div class="hm-months">${monthRow}</div><div class="hm-grid">${gridHtml}</div></div></div><div class="hm-legend"><span>Less</span><div class="hm-lv0"></div><div class="hm-lv1"></div><div class="hm-lv2"></div><div class="hm-lv3"></div><span>More</span></div>`;
      const hmScroll = el.querySelector('.hm-scroll');
      if (hmScroll) hmScroll.scrollLeft = hmScroll.scrollWidth;
    })();

    // ── Lightbox ─────────────────────────────────────
    let lbImages = [],
      lbIdx = 0;
    const lbOverlay = document.getElementById('lightbox');
    const lbImg = document.getElementById('lb-img');
    const lbPrev = document.getElementById('lb-prev');
    const lbNext = document.getElementById('lb-next');
    const lbCounter = document.getElementById('lb-counter');

    function openLightbox(images, startIdx) {
      lbImages = images;
      lbIdx = startIdx;
      showLbSlide();
      lbOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lbOverlay.classList.remove('open');
      document.body.style.overflow = '';
      lbImg.src = '';
    }

    function showLbSlide() {
      lbImg.src = lbImages[lbIdx];
      lbPrev.hidden = lbImages.length <= 1 || lbIdx === 0;
      lbNext.hidden = lbImages.length <= 1 || lbIdx === lbImages.length - 1;
      lbCounter.textContent = lbImages.length > 1 ? (lbIdx + 1) + ' / ' + lbImages.length : '';
    }

    document.getElementById('lb-close').addEventListener('click', closeLightbox);
    lbPrev.addEventListener('click', e => {
      e.stopPropagation();
      if (lbIdx > 0) {
        lbIdx--;
        showLbSlide();
      }
    });
    lbNext.addEventListener('click', e => {
      e.stopPropagation();
      if (lbIdx < lbImages.length - 1) {
        lbIdx++;
        showLbSlide();
      }
    });
    lbOverlay.addEventListener('click', e => {
      if (e.target === lbOverlay) closeLightbox();
    });
    (function() {
      let tx = 0, ty = 0;
      lbOverlay.addEventListener('touchstart', e => { tx = e.touches[0].clientX; ty = e.touches[0].clientY; }, { passive: true });
      lbOverlay.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - tx;
        const dy = e.changedTouches[0].clientY - ty;
        if (Math.abs(dy) > Math.abs(dx) && dy > 60) { closeLightbox(); return; }
        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
          if (dx < 0 && lbIdx < lbImages.length - 1) { lbIdx++; showLbSlide(); }
          else if (dx > 0 && lbIdx > 0) { lbIdx--; showLbSlide(); }
        }
      }, { passive: true });
    })();
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && document.getElementById('notes-overlay').classList.contains('open')) {
        closeNotes();
        return;
      }
      if (e.key === 'Escape' && document.getElementById('used-in-overlay').classList.contains('open')) {
        closeUsedIn();
        return;
      }
      if (document.getElementById('recipe-guide-overlay').classList.contains('open')) {
        if (e.key === 'Escape') { closeRecipeGuide(); return; }
        if (e.key === 'ArrowRight') { stepGuide(1); return; }
        if (e.key === 'ArrowLeft')  { stepGuide(-1); return; }
        return;
      }
      if (e.key === 'Escape' && document.getElementById('pull-overlay').classList.contains('open')) {
        closePull();
        return;
      }
      if (e.key === 'Escape' && document.getElementById('shop-overlay').classList.contains('open')) {
        closeShoppingList();
        return;
      }
      if (!lbOverlay.classList.contains('open')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft' && lbIdx > 0) {
        lbIdx--;
        showLbSlide();
      }
      if (e.key === 'ArrowRight' && lbIdx < lbImages.length - 1) {
        lbIdx++;
        showLbSlide();
      }
    });

    // ── Used In ──
    function showUsedIn(brand, name, layer) {
      const key3 = brand + '|' + name + '|' + (layer || '');
      const schemes = MODELS.filter(m => effectiveColors(m).some(c => upgradeKey(c) === key3));
      document.getElementById('used-in-paint-name').textContent = name;
      document.getElementById('used-in-brand').textContent = brand;
      let html = '';
      if (schemes.length === 0) {
        html = '<div class="used-in-empty">Not used in any documented scheme yet.</div>';
      } else {
        html = schemes.map(m => {
          const meta = [m.faction, m.date].filter(Boolean).join(' \u2014 ');
          return `<div class="used-in-item">
            <div>
              <div class="used-in-model-name">${esc(m.name)}</div>
              ${meta ? `<div class="used-in-model-meta">${esc(meta)}</div>` : ''}
            </div>
            <button class="used-in-goto" onclick="gotoModel('${esc(m.id)}')">View &rarr;</button>
          </div>`;
        }).join('');
      }
      document.getElementById('used-in-content').innerHTML = html;
      document.getElementById('used-in-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeUsedIn() {
      document.getElementById('used-in-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    function gotoModel(id) {
      closeUsedIn();
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      document.querySelector('[data-tab="gallery"]').classList.add('active');
      document.getElementById('tab-gallery').classList.add('active');
      showAllGallery = true;
      factionFilter = '';
      renderGallery();
      const card = document.querySelector(`.model-card[data-id="${id}"]`);
      if (card) {
        card.classList.add('highlight');
        card.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }
    }

    document.getElementById('used-in-overlay').addEventListener('click', e => {
      if (e.target === document.getElementById('used-in-overlay')) closeUsedIn();
    });

    document.getElementById('tbody').addEventListener('click', e => {
      const notesBtn = e.target.closest('.notes-btn');
      if (notesBtn) {
        openNotes(notesBtn.dataset.pid, notesBtn.dataset.notesBrand, notesBtn.dataset.notesName, notesBtn.dataset.stars, notesBtn.dataset.notesText);
        return;
      }
      const starBtn = e.target.closest('.star-rate-btn');
      if (starBtn) {
        openNotes(starBtn.dataset.pid, starBtn.dataset.notesBrand, starBtn.dataset.notesName, starBtn.dataset.stars, starBtn.dataset.notesText);
        return;
      }
      const row = e.target.closest('tr[data-brand]');
      if (!row) return;
      showUsedIn(row.dataset.brand, row.dataset.name, row.dataset.layer);
    });

    // ── Pull Sheet ──
    function renderPullLi(raw, name) {
      const key = upgradeKey(raw);
      const stock = paintStock.get(key) || '';
      if (!paintOwned.has(key)) return `<li>${esc(name)}<span class="pull-flag missing">missing</span></li>`;
      if (stock === 'out') return `<li>${esc(name)}<span class="pull-flag out">out</span></li>`;
      if (stock === 'low') return `<li>${esc(name)}<span class="pull-flag low">low</span></li>`;
      return `<li>${esc(name)}</li>`;
    }

    function populatePullSheet(title, subtitle, colors, recipeIds) {
      document.getElementById('pull-title').textContent = title;
      document.getElementById('pull-faction').textContent = subtitle;

      // Recipe-aware path
      const recipesMap = window._RECIPE_BY_ID;
      const recipes = (recipeIds && recipesMap) ? recipeIds.map(id => recipesMap.get(id)).filter(Boolean) : [];
      const coversColor = new Set();
      let html = '';

      if (recipes.length) {
        recipes.forEach(r => {
          html += `<div class="pull-brand-heading" style="color:#c9a227">${esc(r.name)}${r.category ? ` <span style="font-weight:normal;color:#999">(${esc(r.category)})</span>` : ''}</div>`;
          html += `<ul class="pull-paint-list">`;
          (r.steps || []).forEach((s, i) => {
            const parts = (s.paint || '').split('|');
            const brand = parts[0] || '';
            const name = parts[1] || s.paint || '';
            coversColor.add((s.paint || '').toLowerCase());
            const key = upgradeKey(s.paint || '');
            const stock = paintStock.get(key) || '';
            let flag = '';
            if (!paintOwned.has(key)) flag = `<span class="pull-flag missing">missing</span>`;
            else if (stock === 'out') flag = `<span class="pull-flag out">out</span>`;
            else if (stock === 'low') flag = `<span class="pull-flag low">low</span>`;
            const techLabel = (s.technique || 'special');
            const extras = [s.ratio, s.note].filter(Boolean).map(esc).join(' · ');
            html += `<li><span style="font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:#999;margin-right:6px;font-family:'Cinzel',serif">${i + 1}. ${esc(techLabel)}</span><strong>${esc(name)}</strong>${brand ? ` <span style="color:#aaa;font-size:11px">${esc(brand)}</span>` : ''}${extras ? ` <em style="color:#888;font-size:11px">${extras}</em>` : ''}${flag}</li>`;
          });
          html += `</ul>`;
        });
        // Other paints in colors not covered by recipes
        const leftover = colors.filter(c => !coversColor.has((c || '').toLowerCase()));
        if (leftover.length) {
          html += `<div class="pull-brand-heading" style="color:#999">Other paints</div>`;
          html += `<ul class="pull-paint-list">` + leftover.map(raw => {
            const parts = raw.split('|');
            return renderPullLi(raw, parts[1] || raw);
          }).join('') + `</ul>`;
        }
      } else {
        // Legacy fallback: flat brand-grouped list
        const sorted = colors.slice().sort((a, b) => {
          const [ab, an = ''] = a.split('|');
          const [bb, bn = ''] = b.split('|');
          return ab.localeCompare(bb) || an.localeCompare(bn);
        });
        const groups = {};
        sorted.forEach(c => {
          const parts = c.split('|');
          const brand = parts[0];
          (groups[brand] = groups[brand] || []).push({
            raw: c,
            name: parts[1] || c
          });
        });
        for (const [brand, paints] of Object.entries(groups)) {
          html += `<div class="pull-brand-heading">${esc(brand)}</div>`;
          html += `<ul class="pull-paint-list">` + paints.map(({
            raw,
            name
          }) => renderPullLi(raw, name)).join('') + `</ul>`;
        }
      }

      document.getElementById('pull-content').innerHTML = html;
      document.getElementById('pull-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function openPull(id) {
      const m = MODELS.find(x => x.id === id);
      if (!m || !(m.colors || []).length) return;
      populatePullSheet(
        m.name,
        [m.faction, m.date].filter(Boolean).join(' \u2014 '),
        m.colors,
        m.recipes
      );
    }

    function openFactionPull(faction) {
      const schemes = MODELS.filter(m => (m.faction || '') === faction && (m.colors || []).length);
      if (!schemes.length) return;
      const allColors = [...new Set(schemes.flatMap(m => m.colors))];
      populatePullSheet(
        faction,
        `${allColors.length} unique paint${allColors.length !== 1 ? 's' : ''} across ${schemes.length} scheme${schemes.length !== 1 ? 's' : ''}`,
        allColors
      );
    }

    function closePull() {
      document.getElementById('pull-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    function copyPullList() {
      const title = document.getElementById('pull-title').textContent;
      const faction = document.getElementById('pull-faction').textContent;
      let text = title + (faction ? '\n' + faction : '') + '\n\n';
      document.getElementById('pull-content').querySelectorAll('.pull-brand-heading, .pull-paint-list li').forEach(el => {
        text += el.classList.contains('pull-brand-heading') ? el.textContent + '\n' : '  ' + el.textContent + '\n';
      });
      navigator.clipboard.writeText(text.trim()).then(() => {
        const btn = document.getElementById('pull-copy-btn');
        const prev = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = prev, 2000);
      });
    }

    document.getElementById('pull-overlay').addEventListener('click', e => {
      if (e.target === document.getElementById('pull-overlay')) closePull();
    });

    // ── Planned Tab ──────────────────────────────────
    const plannedSearchEl = document.getElementById('planned-search');
    plannedSearchEl.addEventListener('input', renderPlanned);
    let plannedSystemFilter = '';
    let plannedReadyFilter = '';
    let benchSystemFilter = '';

    function schemeReadiness(pl) {
      const colors = pl.colors || [];
      if (!colors.length) return { level: 'ready', missing: 0, missingNames: [] };
      let missing = 0;
      const missingNames = [];
      colors.forEach(c => {
        const uk = upgradeKey(c);
        const stock = paintStock.get(uk) || '';
        if (!paintOwned.has(uk) || stock === 'out') {
          missing++;
          const parts = c.split('|');
          missingNames.push(parts[1] || c);
        }
      });
      const level = missing === 0 ? 'ready' : missing <= 2 ? 'almost' : 'needs';
      return { level, missing, missingNames };
    }

    function renderPlanned() {
      const grid = document.getElementById('planned-grid');
      const emptyEl = document.getElementById('planned-empty');
      const countEl = document.getElementById('planned-count');
      if (!PLANNED.length) {
        grid.innerHTML = '';
        emptyEl.style.display = 'block';
        countEl.textContent = '';
        return;
      }
      const q = plannedSearchEl.value.trim().toLowerCase();
      let visible = q ?
        PLANNED.filter(pl =>
          (pl.name || '').toLowerCase().includes(q) ||
          (pl.faction || '').toLowerCase().includes(q) ||
          (pl.model || '').toLowerCase().includes(q) ||
          (pl.description || '').toLowerCase().includes(q)
        ) :
        PLANNED.slice();
      if (plannedSystemFilter) {
        visible = visible.filter(pl => (pl.system || '') === plannedSystemFilter);
      }
      if (plannedReadyFilter) {
        visible = visible.filter(pl => schemeReadiness(pl).level === plannedReadyFilter);
      }

      // Sort: READY first when not already filtered by readiness
      if (!plannedReadyFilter) {
        const ORDER = { ready: 0, almost: 1, needs: 2 };
        visible.sort((a, b) => {
          const ra = ORDER[schemeReadiness(a).level];
          const rb = ORDER[schemeReadiness(b).level];
          if (ra !== rb) return ra - rb;
          return (a.name || '').localeCompare(b.name || '');
        });
      }

      emptyEl.style.display = 'none';
      const isFiltered = q || plannedSystemFilter || plannedReadyFilter;
      countEl.textContent = isFiltered ?
        visible.length + ' of ' + PLANNED.length + ' scheme' + (PLANNED.length !== 1 ? 's' : '') :
        PLANNED.length + ' scheme' + (PLANNED.length !== 1 ? 's' : '');

      if (!visible.length) {
        grid.innerHTML = '<div class="grid-empty">No schemes match.</div>';
        return;
      }

      grid.innerHTML = visible.map(pl => {
        const colors = collectAllPaints(pl.colors, pl.recipes);
        let missing = 0,
          low = 0;
        colors.forEach(c => {
          const uk = upgradeKey(c);
          const stock = paintStock.get(uk) || '';
          if (!paintOwned.has(uk) || stock === 'out') missing++;
          else if (stock === 'low') low++;
        });

        const { level: readyLevel, missingNames } = schemeReadiness(pl);
        const readyBadgeLabel = readyLevel === 'ready' ? 'Ready' : readyLevel === 'almost' ? 'Almost' : 'Needs Work';
        const readyBadge = `<span class="ready-badge ${readyLevel}">${readyBadgeLabel}</span>`;

        const shopImpact = (readyLevel === 'almost' && missingNames.length) ?
          `<div class="planned-shop-impact">Buy: ${missingNames.map(n => `<strong>${esc(n)}</strong>`).join(', ')} - then ready</div>` : '';

        const sortedColors = colors.slice().sort((a, b) => {
          const [ab, an = ''] = a.split('|');
          const [bb, bn = ''] = b.split('|');
          return ab.localeCompare(bb) || an.localeCompare(bn);
        });
        let lastBrand = '';
        const pillsHtml = sortedColors.map(c => {
          const [brand, name = c] = c.split('|');
          const uk = upgradeKey(c);
          const stock = paintStock.get(uk) || '';
          let cls = 'pcol-pill owned';
          if (!paintOwned.has(uk) || stock === 'out') cls = 'pcol-pill missing';
          else if (stock === 'low') cls = 'pcol-pill low';
          const label = brand !== lastBrand ? `<span class="pill-brand-label">${esc(brand)}</span>` : '';
          lastBrand = brand;
          return label + `<span class="${cls}" title="${esc(c)}">${esc(name)}</span>`;
        }).join('');

        const statusParts = [];
        if (missing > 0) statusParts.push(`<span style="color:#c94040">${missing} missing</span>`);
        if (low > 0) statusParts.push(`<span style="color:#c97a20">${low} low</span>`);

        const plannedRecipeRefs = renderRecipeRefs(pl.recipes);
        return `<div class="planned-card" data-id="${esc(pl.id || '')}">
          <div class="planned-card-header">
            <div class="planned-card-name">${esc(pl.name)}</div>
            ${pl.model   ? `<div class="planned-card-kit">${esc(pl.model)}</div>` : ''}
            ${pl.faction ? `<div class="planned-card-faction">${esc(pl.faction)}</div>` : ''}
            ${readyBadge}
            ${pl.system ? `<span class="sys-game-badge sys-${sysSlug(pl.system)}">${esc(pl.system)}</span>` : ''}
            ${pl.codex_source ? `<span class="codex-source-badge">${esc(pl.codex_source)}</span>` : ''}
          </div>
          <div class="planned-card-body">
            ${shopImpact}
            ${plannedRecipeRefs}
            ${formatDesc(pl.description)}
            ${colors.length  ? `<div class="planned-colors">${pillsHtml}</div>` : ''}
            <div class="planned-card-footer">
              <span class="planned-card-summary">${colors.length} paint${colors.length !== 1 ? 's' : ''}${statusParts.length ? ' - ' + statusParts.join(', ') : ''}</span>
              ${colors.length ? `<button class="pull-btn planned-pull-btn" onclick="openPlannedPull('${esc(pl.id || '')}')">Pull list${missing > 0 ? ` <span class="pull-issue-badge">${missing} issue${missing !== 1 ? 's' : ''}</span>` : ''}</button>` : ''}
            </div>
          </div>
        </div>`;
      }).join('');
    }

    window.openPlannedPull = function(id) {
      const pl = PLANNED.find(x => x.id === id);
      if (!pl) return;
      const subtitle = [pl.faction, pl.model].filter(Boolean).join(' — ');
      populatePullSheet(pl.name, subtitle, pl.colors || [], pl.recipes);
    };

    document.querySelectorAll('.planned-rp').forEach(pill => {
      pill.addEventListener('click', () => {
        document.querySelectorAll('.planned-rp').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        plannedReadyFilter = pill.dataset.ready === 'all' ? '' : pill.dataset.ready;
        renderPlanned();
      });
    });

    // ── Codices ──────────────────────────────────────────
    <?php if ($hasBooks): ?>
        (function() {
          const blSearchEl = document.getElementById('bl-search');
          const blCountEl  = document.getElementById('bl-count');
          const blListEl   = document.getElementById('bl-list');
          const blEmptyEl  = document.getElementById('bl-empty');
          const BL_TOTAL   = BOOKS_DATA.length;

          function hlBL(text, q) {
            if (!q) return esc(text);
            const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return text.split(re).map((part, i) => i % 2 === 1 ? '<mark>' + esc(part) + '</mark>' : esc(part)).join('');
          }

          function formatBLNotes(raw, q) {
            return raw.replace(/\r/g, '').split('\n').map(line => {
              const isCat = /^\s*[\w &]+:\s*$/.test(line);
              const rendered = hlBL(line, q);
              return isCat ? `<span class="label-cinzel-gold">${rendered}</span>` : rendered;
            }).join('\n');
          }

          function renderBookRow(b, q) {
            const btype   = b.type || 'codex';
            const spine   = btype === 'supplement' ? 'Supplement' : 'Codex';
            const edition = b.series ? `<div class="bl-edition">${hlBL(b.series, q)}</div>` : '';
            const author  = b.author ? `<div class="bl-author">${hlBL(b.author, q)}</div>` : '';
            const notes   = b.notes ? `<div class="bl-notes">${formatBLNotes(b.notes, q)}</div>` : '';
            return `<div class="bl-row bl-row-${esc(btype)}" data-id="${esc(b.id || '')}"><div class="bl-type-spine">${spine}</div><div class="bl-body">${b.faction ? `<div class="bl-codex-faction">${hlBL(b.faction, q)}</div>` : ''}<div class="bl-title">${hlBL(b.title, q)}</div>${edition}${author}${notes}</div></div>`;
          }

          function renderBooks() {
            const q = blSearchEl.value.trim().toLowerCase();
            let filtered = BOOKS_DATA.slice();
            if (q) {
              filtered = filtered.filter(b =>
                (b.title   || '').toLowerCase().includes(q) ||
                (b.faction || '').toLowerCase().includes(q) ||
                (b.series  || '').toLowerCase().includes(q) ||
                (b.author  || '').toLowerCase().includes(q) ||
                (b.notes   || '').toLowerCase().includes(q)
              );
            }
            blCountEl.textContent = filtered.length + ' of ' + BL_TOTAL + ' cod' + (BL_TOTAL !== 1 ? 'ices' : 'ex');
            if (!BL_TOTAL) { blListEl.innerHTML = ''; blEmptyEl.style.display = 'block'; return; }
            blEmptyEl.style.display = 'none';
            if (!filtered.length) { blListEl.innerHTML = '<div class="grid-empty">No codices match.</div>'; return; }
            const sorted = filtered.slice().sort((a, b) => { const fc = (a.faction || '').localeCompare(b.faction || ''); return fc !== 0 ? fc : (a.title || '').localeCompare(b.title || ''); });
            blListEl.innerHTML = sorted.map(b => renderBookRow(b, q)).join('');
          }

          blSearchEl.addEventListener('input', renderBooks);
          window._renderBooks = renderBooks;
          renderBooks();
        })();
    <?php endif; ?>

    // ── Hobby Journal ────────────────────────────────────
    <?php if ($hasJournal): ?>
        (function() {
          const jnSearchEl    = document.getElementById('jn-search');
          const jnCountEl     = document.getElementById('jn-count');
          const jnListEl      = document.getElementById('jn-list');
          const jnEmptyEl     = document.getElementById('jn-empty');
          const jnMonthNavEl  = document.getElementById('jn-month-nav');
          const jnPrevBtn     = document.getElementById('jn-prev');
          const jnNextBtn     = document.getElementById('jn-next');
          const jnMonthLbl    = document.getElementById('jn-month-label');
          const jnYearPicker  = document.getElementById('jn-year-picker');
          const journalData   = JOURNAL_DATA;

          const JN_MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
          const JN_MONTHS_LONG  = ['January','February','March','April','May','June','July','August','September','October','November','December'];

          // current YYYY-MM cursor; null = search mode (all entries)
          const now = new Date();
          let jnCursor = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

          // collect all YYYY-MM values that actually have entries
          const jnMonthsWithData = [...new Set(journalData.map(e => e.date ? e.date.slice(0, 7) : null).filter(Boolean))].sort();

          function fmtCursor(ym) {
            const [y, m] = ym.split('-');
            return JN_MONTHS_LONG[parseInt(m, 10) - 1] + ' ' + y;
          }

          function fmtJnDate(d) {
            if (!d) return '';
            const parts = d.split('-');
            if (parts.length < 3) return d;
            return JN_MONTHS_SHORT[parseInt(parts[1], 10) - 1] + ' ' + parseInt(parts[2], 10) + ', ' + parts[0];
          }

          function stepMonth(ym, delta) {
            let [y, m] = ym.split('-').map(Number);
            m += delta;
            if (m > 12) { m = 1; y++; }
            if (m < 1)  { m = 12; y--; }
            return y + '-' + String(m).padStart(2, '0');
          }

          const currentYM = jnCursor;

          function updateNav() {
            const q = jnSearchEl.value.trim();
            jnMonthNavEl.style.display = q ? 'none' : '';
            if (q) return;
            jnMonthLbl.textContent = fmtCursor(jnCursor);
            jnPrevBtn.disabled = jnCursor <= (jnMonthsWithData[0] || jnCursor);
            jnNextBtn.disabled = jnCursor >= currentYM;
          }

          const MOOD_CLASS = { great: 'jn-mood-great', good: 'jn-mood-good', okay: 'jn-mood-okay', rough: 'jn-mood-rough' };

          function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
          function decodeHtml(s) { const d = document.createElement('div'); d.innerHTML = s; return d.textContent; }

          function mentionBadge(type, id, label) {
            return `<span class="jn-mention jn-mention-${type}" data-mtype="${esc(type)}" data-mid="${esc(id)}" title="${esc(label)}">${esc(label)}</span>`;
          }

          function renderBody(raw) {
            if (!raw) return '';
            const TOKEN = /@\[(\w+):([^\]|]+)\|([^\]]+)\]/g;
            let result = '', last = 0, m;
            while ((m = TOKEN.exec(raw)) !== null) {
              result += esc(raw.slice(last, m.index));
              result += mentionBadge(m[1], m[2], decodeHtml(m[3]));
              last = m.index + m[0].length;
            }
            result += esc(raw.slice(last));
            return result;
          }

          function renderJournals() {
            if (!journalData) return;
            const q = jnSearchEl.value.trim().toLowerCase();
            let filtered;
            if (q) {
              filtered = journalData.filter(e => (e.title || '').toLowerCase().includes(q) || (e.body || '').toLowerCase().includes(q) || (e.date || '').includes(q));
              jnCountEl.textContent = filtered.length + ' entr' + (filtered.length !== 1 ? 'ies' : 'y') + ' matching';
            } else {
              filtered = journalData.filter(e => e.date && e.date.slice(0, 7) === jnCursor);
              jnCountEl.textContent = filtered.length + ' entr' + (filtered.length !== 1 ? 'ies' : 'y');
            }

            updateNav();

            if (!journalData.length) { jnListEl.innerHTML = ''; jnEmptyEl.style.display = 'block'; return; }
            jnEmptyEl.style.display = 'none';

            if (!filtered.length) {
              jnListEl.innerHTML = `<div class="grid-empty">${q ? 'No entries match.' : 'No entries for ' + fmtCursor(jnCursor) + '.'}</div>`;
              return;
            }

            jnListEl.innerHTML = filtered.map(e => {
              const moodCls = e.mood ? (MOOD_CLASS[e.mood] || '') : '';
              const moodBadge = e.mood ? `<span class="jn-mood ${moodCls}">${esc(e.mood.charAt(0).toUpperCase() + e.mood.slice(1))}</span>` : '';
              const titleBit = e.title ? `<span class="jn-title">${esc(e.title)}</span>` : '';
              return `<div class="jn-card"><div class="jn-card-header"><span class="jn-date">${esc(fmtJnDate(e.date))}</span>${moodBadge}${titleBit}</div><div class="jn-body">${renderBody(e.body || '')}</div></div>`;
            }).join('');
          }

          function showYearPicker() {
            if (!jnYearPicker.classList.contains('hidden')) { jnYearPicker.classList.add('hidden'); return; }
            const years = [...new Set(jnMonthsWithData.map(ym => ym.slice(0, 4)))].sort((a, b) => b - a);
            jnYearPicker.innerHTML = years.map(y => `<button class="jn-year-btn${jnCursor.startsWith(y) ? ' active' : ''}" data-year="${y}">${y}</button>`).join('');
            jnYearPicker.classList.remove('hidden');
          }

          jnPrevBtn.addEventListener('click', () => { jnCursor = stepMonth(jnCursor, -1); renderJournals(); });
          jnNextBtn.addEventListener('click', () => { jnCursor = stepMonth(jnCursor, 1); renderJournals(); });

          jnMonthLbl.addEventListener('click', showYearPicker);

          jnYearPicker.addEventListener('click', ev => {
            const btn = ev.target.closest('[data-year]');
            if (!btn) return;
            jnYearPicker.classList.add('hidden');
            // jump to the most recent month in that year that has data, or Jan if none
            const y = btn.dataset.year;
            const monthsInYear = jnMonthsWithData.filter(ym => ym.startsWith(y));
            jnCursor = monthsInYear.length ? monthsInYear[monthsInYear.length - 1] : y + '-01';
            renderJournals();
          });

          jnSearchEl.addEventListener('input', () => { jnYearPicker.classList.add('hidden'); renderJournals(); });

          jnListEl.addEventListener('click', ev => {
            const m = ev.target.closest('[data-mtype]');
            if (!m) return;
            const type = m.dataset.mtype, id = m.dataset.mid;
            if      (type === 'scheme' && window._jumpToScheme)               window._jumpToScheme(id);
            else if (type === 'recipe' && window._jumpToRecipe)               window._jumpToRecipe(id);
            else if (type === 'bench'  && typeof switchToTab !== 'undefined') switchToTab('bench');
          });

          window._renderJournals = renderJournals;
        })();
    <?php endif; ?>

    // ── Brush Inventory ───────────────────────────────
    <?php if ($hasBrushes): ?>
        (function() {
          const brSearchEl = document.getElementById('brush-search');
          const brCountEl = document.getElementById('brush-count');
          const brListEl = document.getElementById('brush-list');
          const brEmptyEl = document.getElementById('brush-empty');
          const BR_TOTAL = BRUSHES_DATA.length;
          let brCondFilter = 'all';

          const BR_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

          function fmtBRMonth(m) {
            if (!m) return '';
            const [y, mo] = m.split('-');
            return (BR_MONTHS[parseInt(mo, 10) - 1] || mo) + ' ' + y;
          }

          const COND_LABEL = {
            prime: 'Prime',
            workhorse: 'Workhorse',
            retired: 'Retired'
          };

          function renderBrushes() {
            const q = brSearchEl.value.trim().toLowerCase();

            let filtered = brCondFilter === 'all' ?
              BRUSHES_DATA.slice() :
              BRUSHES_DATA.filter(b => (b.condition || 'prime') === brCondFilter);

            if (q) {
              filtered = filtered.filter(b =>
                (b.brand || '').toLowerCase().includes(q) ||
                (b.series || '').toLowerCase().includes(q) ||
                (b.size || '').toLowerCase().includes(q) ||
                (b.material || '').toLowerCase().includes(q) ||
                (b.use || '').toLowerCase().includes(q) ||
                (b.notes || '').toLowerCase().includes(q)
              );
            }

            brCountEl.textContent = filtered.length + ' of ' + BR_TOTAL + ' brush' + (BR_TOTAL !== 1 ? 'es' : '');

            if (!BR_TOTAL) {
              brListEl.innerHTML = '';
              brEmptyEl.style.display = 'block';
              return;
            }
            brEmptyEl.style.display = 'none';

            if (!filtered.length) {
              brListEl.innerHTML = '<div class="grid-empty">No brushes match.</div>';
              return;
            }

            const byBrand = new Map();
            filtered.forEach(b => {
              const k = b.brand || '-';
              if (!byBrand.has(k)) byBrand.set(k, []);
              byBrand.get(k).push(b);
            });
            const sortedBrands = [...byBrand.keys()].sort();

            brListEl.innerHTML = sortedBrands.map(brand => {
              const brushes = byBrand.get(brand);
              const entriesHtml = brushes.map(b => {
                const cond = b.condition || 'prime';
                const condLabel = COND_LABEL[cond] || cond;
                const seriesSize = [b.series, b.size].filter(Boolean).join(' · ');
                const matUse = [b.material, b.use].filter(Boolean).join(' · ');
                const date = fmtBRMonth(b.date_start || '');
                const starsN = b.stars || 0;
                const starsHtml = starsN ? `<span class="brush-stars">${Array.from({length:5},(_,i)=>`<span class="br-star${i<starsN?' on':''}">★</span>`).join('')}</span>` : '';
                const seriesHtml = seriesSize ? esc(seriesSize) : '<span style="color:#3a2a10">-</span>';
                const dateHtml = date ? `<span style="font-family:'Cinzel',serif;letter-spacing:.03em">${esc(date)}</span>` : '';
                const notesHtml = b.notes ? `<div class="brush-entry-notes">${esc(b.notes)}</div>` : '';
                return `<div class="brush-entry" data-id="${esc(b.id || '')}"><div class="brush-entry-top"><span class="brush-entry-series">${seriesHtml}</span><span class="brush-entry-right">${starsHtml}<span class="brush-cond-badge cond-${esc(cond)}">${esc(condLabel)}</span></span></div><div class="brush-entry-bottom"><span>${esc(matUse)}</span>${dateHtml}</div>${notesHtml}</div>`;
              }).join('');
              return `<div class="brush-card">
            <div class="brush-card-header">
              <span>${esc(brand)}</span>
              <span class="brush-card-count">${brushes.length} brush${brushes.length !== 1 ? 'es' : ''}</span>
            </div>
            <div class="brush-card-body">${entriesHtml}</div>
          </div>`;
            }).join('');
          }

          document.querySelectorAll('.brush-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
              document.querySelectorAll('.brush-filter-pill').forEach(p => p.classList.remove('active'));
              pill.classList.add('active');
              brCondFilter = pill.dataset.cond;
              renderBrushes();
            });
          });

          brSearchEl.addEventListener('input', renderBrushes);
          window._renderBrushes = renderBrushes;
          renderBrushes();
        })();
    <?php endif; ?>

    // ── Hobby Wishlist ─────────────────────────────────
    <?php if ($hasWishlist): ?>
    (function() {
      const gridEl    = document.getElementById('wishlist-grid');
      const emptyEl   = document.getElementById('wishlist-empty');
      const countEl   = document.getElementById('wishlist-count');
      const searchEl  = document.getElementById('wishlist-search');
      const typePills = document.getElementById('wishlist-type-pills');
      const priPills  = document.getElementById('wishlist-pri-pills');
      if (!gridEl) return;

      const WTYPE_LABEL = {paint:'Paint',model:'Model',brush:'Brush',codex:'Codex'};
      const WTYPE_COLOR = {paint:'#1a4a4a',model:'#1a3a1a',brush:'#3a1a10',codex:'#2a1a4a'};

      let typeFilter   = 'all';
      let priFilter    = 'all';
      let wStatusFilter = 'all';

      const usedTypes = [...new Set(WISHLIST_DATA.map(w => w.type || 'paint'))];
      const allPill = document.createElement('button');
      allPill.className = 'wish-fp active'; allPill.dataset.wtype = 'all'; allPill.textContent = 'All';
      typePills.appendChild(allPill);
      usedTypes.forEach(t => { const b = document.createElement('button'); b.className = 'wish-fp'; b.dataset.wtype = t; b.textContent = WTYPE_LABEL[t] || t; typePills.appendChild(b); });
      const orderedPill = document.createElement('button');
      orderedPill.className = 'wish-fp'; orderedPill.dataset.wtype = '_ordered'; orderedPill.textContent = 'In Transit';
      typePills.appendChild(orderedPill);

      typePills.addEventListener('click', ev => { const b = ev.target.closest('.wish-fp'); if (!b) return; if (b.dataset.wtype === '_ordered') { wStatusFilter = wStatusFilter === 'ordered' ? 'all' : 'ordered'; b.classList.toggle('active', wStatusFilter === 'ordered'); } else { typeFilter = b.dataset.wtype; wStatusFilter = 'all'; orderedPill.classList.remove('active'); typePills.querySelectorAll('.wish-fp:not([data-wtype="_ordered"])').forEach(x => x.classList.toggle('active', x === b)); } renderWishlist(); });
      priPills.addEventListener('click', ev => { const b = ev.target.closest('.wish-pp'); if (!b) return; priFilter = b.dataset.wpri; priPills.querySelectorAll('.wish-pp').forEach(x => x.classList.toggle('active', x === b)); renderWishlist(); });
      searchEl.addEventListener('input', renderWishlist);

      document.getElementById('wishlist-copy-btn').addEventListener('click', copyWishlist);
      document.getElementById('wishlist-print-btn').addEventListener('click', () => { document.body.classList.add('print-wishlist'); window.print(); document.body.classList.remove('print-wishlist'); });

      function stockDot(w) {
        if ((w.type || 'paint') !== 'paint') return '';
        const key = ((w.brand || '') + '|' + (w.name || '')).toLowerCase();
        if (typeof paintByKeyLC === 'undefined') return '';
        const st = paintByKeyLC.get(key);
        if (st === undefined) return '<span class="stock-dot stock-dot-unknown" title="Not in inventory"></span>';
        if (st === 'low')    return '<span class="stock-dot stock-dot-low" title="Low stock"></span>';
        if (st === 'out')    return '<span class="stock-dot stock-dot-out" title="Out of stock"></span>';
        if (st === 'wanted') return '<span class="stock-dot stock-dot-wanted" title="Wanted"></span>';
        return '<span class="stock-dot stock-dot-owned" title="Owned"></span>';
      }

      function cardHtml(w) {
        const t   = w.type || 'paint';
        const pri = w.priority || 'medium';
        const meta = [w.brand, w.faction, w.system].filter(Boolean).join(' · ');
        const urlHtml  = w.url ? `<div style="margin-top:3px"><a href="${esc(w.url)}" target="_blank" rel="noopener" style="font-size:11px;color:#6a8a6a;text-decoration:none">&#128279; Link</a></div>` : '';
        const noteHtml = w.notes ? `<div style="font-size:12px;color:#5a4a28;margin-top:3px">${esc(w.notes)}</div>` : '';
        const metaHtml = meta ? `<div style="font-size:11px;color:#4a3a1a;margin-top:2px">${esc(meta)}</div>` : '';
        const addHtml      = w.added ? `<div style="font-size:10px;color:#3a2a10;margin-top:6px;text-align:right">${esc(w.added)}</div>` : '';
        const orderedHtml  = w.ordered_date ? `<div><span class="wish-ordered-badge">In Transit &middot; ${esc(w.ordered_date)}</span></div>` : '';
        return `<div class="wishlist-card wtype-${esc(t)}" data-id="${esc(w.id||'')}"><div class="wish-spine">${esc(WTYPE_LABEL[t]||t)}</div><div class="wish-body"><div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:6px"><span class="wpri-badge wpri-${esc(pri)}">${esc(pri.charAt(0).toUpperCase()+pri.slice(1))}</span>${stockDot(w)}</div>${orderedHtml}<div style="font-family:'Cinzel',serif;font-size:13px;color:#c4b49a;font-weight:600">${esc(w.name||'')}</div>${metaHtml}${noteHtml}${urlHtml}${addHtml}</div></div>`;
      }

      function copyWishlist() {
        const typeOrder = ['paint','model','brush','codex','wd'];
        const groups = {};
        WISHLIST_DATA.forEach(w => { const t = w.type||'paint'; if (!groups[t]) groups[t]=[]; groups[t].push(w); });
        const lines = [];
        typeOrder.forEach(t => { if (!groups[t]) return; lines.push('=== ' + (WTYPE_LABEL[t]||t).toUpperCase() + 'S ==='); groups[t].forEach(w => { let ln = (w.brand ? w.brand + ' - ' : '') + w.name; if (w.faction) ln += ' (' + w.faction + ')'; if (w.notes) ln += ' - ' + w.notes; if (w.ordered_date) ln += ' (Ordered ' + w.ordered_date + ')'; lines.push('□ ' + ln); }); lines.push(''); });
        const text = 'Hobby Wishlist\n\n' + lines.join('\n');
        const btn = document.getElementById('wishlist-copy-btn');
        const flash = () => { if (btn) { btn.textContent = 'Copied!'; setTimeout(() => btn.textContent = 'Copy', 2000); } };
        if (navigator.clipboard) { navigator.clipboard.writeText(text).then(flash).catch(() => _legacyCopy(text, flash)); } else { _legacyCopy(text, flash); }
      }

      function _legacyCopy(text, cb) { const ta = document.createElement('textarea'); ta.value = text; ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0'; document.body.appendChild(ta); ta.focus(); ta.select(); try { document.execCommand('copy'); if(cb) cb(); } catch(e){} document.body.removeChild(ta); }

      function renderWishlist() {
        const q = (searchEl.value || '').trim().toLowerCase();
        let list = WISHLIST_DATA.slice();
        if (typeFilter !== 'all') list = list.filter(w => (w.type||'paint') === typeFilter);
        if (priFilter  !== 'all') list = list.filter(w => (w.priority||'medium') === priFilter);
        if (wStatusFilter === 'ordered') list = list.filter(w => !!w.ordered_date);
        if (q) list = list.filter(w => [w.name, w.brand, w.faction, w.system, w.notes, w.type, w.ordered_date].filter(Boolean).join(' ').toLowerCase().includes(q));
        const total = WISHLIST_DATA.length;
        countEl.textContent = list.length === total ? total + ' item' + (total !== 1 ? 's' : '') : list.length + ' of ' + total + ' items';
        if (list.length === 0) { gridEl.innerHTML = ''; emptyEl.style.display = 'block'; return; }
        emptyEl.style.display = 'none';
        gridEl.innerHTML = list.map(cardHtml).join('');
      }

      window._renderWishlist = renderWishlist;
      renderWishlist();
    })();
    <?php endif; ?>

    // ── Pile of Shame ────────────────────────────────
    <?php if ($hasShame): ?>
      (function() {
        const gridEl   = document.getElementById('shame-grid');
        const emptyEl  = document.getElementById('shame-empty');
        const summaryEl = document.getElementById('shame-summary');
        const searchEl  = document.getElementById('shame-search');
        const moreEl    = document.getElementById('shame-more');
        if (!gridEl) return;

        const STATUS_LABEL = { sealed:'Sealed', opened:'Opened', partial:'Partial' };

        let filterState   = 'active';
        let showAllShame  = false;

        document.getElementById('shame-filter-pills').addEventListener('click', ev => {
          const fp = ev.target.closest('.shame-fp');
          if (!fp) return;
          filterState  = fp.dataset.filter;
          showAllShame = false;
          document.querySelectorAll('.shame-fp').forEach(b => {
            b.classList.toggle('active', b.dataset.filter === filterState);
          });
          renderShame();
        });

        function sittingSince(acq) {
          if (!acq) return '';
          const [y, m] = acq.split('-').map(Number);
          const now = new Date();
          const months = (now.getFullYear() - y) * 12 + (now.getMonth() + 1 - m);
          if (months < 1) return 'just acquired';
          if (months < 12) return months + ' month' + (months !== 1 ? 's' : '');
          const yrs = Math.floor(months / 12), rem = months % 12;
          return yrs + ' yr' + (yrs !== 1 ? 's' : '') + (rem ? ' ' + rem + ' mo' : '');
        }

        function esc(s) { const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; }

        function renderShame() {
          const q = (searchEl.value || '').trim().toLowerCase();
          let list = SHAME_DATA.slice();
          if (filterState === 'active')   list = list.filter(s => !s.promoted_to);
          if (filterState === 'promoted') list = list.filter(s =>  s.promoted_to);
          if (q) list = list.filter(s => [s.name, s.faction, s.system, s.notes, s.acquired, s.status].filter(Boolean).join(' ').toLowerCase().includes(q));

          // Slim summary line in controls bar
          const active = SHAME_DATA.filter(s => !s.promoted_to);
          const totalModels = active.reduce((n, s) => n + (parseInt(s.count) || 1), 0);
          const oldest = active.filter(s => s.acquired).sort((a, b) => a.acquired.localeCompare(b.acquired))[0];
          const oldestAge = oldest ? sittingSince(oldest.acquired) : null;
          summaryEl.textContent = active.length + ' boxes · ~' + totalModels + ' models' + (oldestAge ? ' · longest wait ' + oldestAge : '');

          emptyEl.style.display = 'none';
          moreEl.innerHTML = '';
          if (!list.length) { gridEl.innerHTML = ''; emptyEl.style.display = 'block'; return; }

          // Pin newest active entry first when in active/all filter and not searching
          const newestId = filterState !== 'promoted' && !q
            ? active.reduce((a, b) => a.id > b.id ? a : b, active[0] || {}).id
            : null;
          if (newestId) {
            const idx = list.findIndex(s => s.id === newestId);
            if (idx > 0) { const [n] = list.splice(idx, 1); list.unshift(n); }
          }

          const PAGE = 12;
          const limited = (!showAllShame && !q && list.length > PAGE) ? list.slice(0, PAGE) : list;

          const SHAME_SYS_SLUG  = {'40k':'40k','30k / HH':'30k','AoS':'AoS','Epic':'Epic','Blood Bowl':'BB','Necromunda':'Necromunda','Kill Team':'KT','OPR':'OPR','Other':'Other'};
          const SHAME_SYS_SHORT = {'40k':'40k','30k / HH':'30k','AoS':'AoS','Epic':'Epic','Blood Bowl':'BB','Necromunda':'Necro','Kill Team':'KT','OPR':'OPR','Other':'Other'};
          function cardHtml(s, isNewest) {
            const sysSlug   = SHAME_SYS_SLUG[s.system] || (s.system || 'Other').replace(/[\s\/]+/g, '');
            const stClass   = s.status || 'sealed';
            const sysLabel  = `<div class="shame-sys-label shame-sys-${sysSlug}">${esc(SHAME_SYS_SHORT[s.system] || s.system || 'Other')}</div>`;
            const stBadge   = `<span class="shame-badge shame-st-${stClass}">${STATUS_LABEL[s.status] || 'Sealed'}</span>`;
            const promoted  = s.promoted_to ? `<span class="shame-promoted">Promoted &rarr; ${s.promoted_to === 'planned' ? 'Planned' : 'Bench'}</span>` : '';
            const metaParts = [s.faction ? esc(s.faction) : '', s.count ? esc(s.count) + ' models' : ''].filter(Boolean).join(' &middot; ');
            const sitting   = sittingSince(s.acquired);
            const sittingHtml = sitting ? `<div class="shame-card-sitting">${sitting === 'just acquired' ? 'just acquired' : 'sitting for ' + sitting}</div>` : '';
            const acqHtml   = s.acquired ? `<span class="shame-acquired">${esc(s.acquired)}</span>` : '';
            const notesHtml = s.notes ? `<div class="shame-notes">${esc(s.notes)}</div>` : '';
            const newestLabel = isNewest ? `<div class="shame-card-newest-label">&#9650; Just added</div>` : '';
            const headerRow = promoted ? `<div style="display:flex;justify-content:flex-end">${promoted}</div>` : '';
            return `<div class="shame-card shame-st-${stClass}${isNewest ? ' shame-card-newest' : ''}" data-id="${esc(s.id)}">${sysLabel}${newestLabel}${headerRow}<div class="shame-card-name">${esc(s.name)}</div>${sittingHtml}<div class="shame-card-meta">${stBadge}${metaParts ? '<span>' + metaParts + '</span>' : ''}${acqHtml}</div>${notesHtml}</div>`;
          }

          gridEl.innerHTML = limited.map(s => cardHtml(s, s.id === newestId)).join('');

          if (!showAllShame && !q && list.length > PAGE) {
            const remaining = list.length - PAGE;
            moreEl.innerHTML = `<div class="gallery-more-fade"></div><button class="gallery-more-btn"><span class="gallery-more-count">Showing ${PAGE} of ${list.length} boxes</span><span class="gallery-more-label">Reveal the remaining ${remaining} <span class="gallery-more-chevron">&#9662;</span></span></button>`;
          } else {
            moreEl.innerHTML = '';
          }
        }

        moreEl.addEventListener('click', e => {
          if (e.target.closest('.gallery-more-btn')) { showAllShame = true; renderShame(); }
        });
        searchEl.addEventListener('input', () => { showAllShame = false; renderShame(); });
        window._renderShame = renderShame;
      })();
    <?php endif; ?>

    // ── On the Bench ─────────────────────────────────
    <?php if ($hasBench): ?>
        (function() {
          const STAGES = ['built', 'primed', 'basecoated', 'washed', 'highlighted', 'based', 'varnished', 'done'];
          const STAGE_LABEL = {
            built: 'Built',
            primed: 'Primed',
            basecoated: 'Basecoated',
            washed: 'Washed',
            highlighted: 'Highlighted',
            based: 'Based',
            varnished: 'Varnished',
            done: 'Done'
          };
          const BR_LOOKUP = new Map((typeof BRUSHES_DATA !== 'undefined' ? BRUSHES_DATA : []).map(b => [b.id, b]));
          const BN_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

          function fmtBnDate(d) {
            if (!d) return '';
            const parts = d.split('-');
            if (parts.length < 2) return d;
            const m = BN_MONTHS[parseInt(parts[1], 10) - 1] || parts[1];
            return parts[2] ? m + ' ' + parseInt(parts[2], 10) + ', ' + parts[0] : m + ' ' + parts[0];
          }
          function daysAgoStr(d) {
            if (!d) return '';
            const days = Math.floor((Date.now() - new Date(d).getTime()) / 86400000);
            if (days <= 0) return 'today';
            if (days === 1) return 'yesterday';
            if (days < 7) return days + ' days ago';
            if (days < 30) { const w = Math.floor(days / 7); return w + ' wk' + (w !== 1 ? 's' : '') + ' ago'; }
            const mo = Math.floor(days / 30); return mo + ' mo ago';
          }
          const grid = document.getElementById('bench-grid');
          const emptyEl = document.getElementById('bench-empty');
          const countEl = document.getElementById('bench-count');
          const searchEl = document.getElementById('bench-search');
          let stageFilter = 'all';

          function renderBench() {
            const TOTAL = BENCH_DATA.length;
            if (!TOTAL) {
              grid.innerHTML = '';
              emptyEl.style.display = 'block';
              countEl.textContent = '';
              return;
            }
            emptyEl.style.display = 'none';
            const q = searchEl.value.trim().toLowerCase();
            let filtered = BENCH_DATA.slice();
            if (stageFilter === 'active') filtered = filtered.filter(b => (b.stage || 'built') !== 'done');
            else if (stageFilter === 'done') filtered = filtered.filter(b => (b.stage || 'built') === 'done');
            if (benchSystemFilter) filtered = filtered.filter(b => (b.system || '') === benchSystemFilter);
            if (q) {
              filtered = filtered.filter(b =>
                (b.name || '').toLowerCase().includes(q) ||
                (b.faction || '').toLowerCase().includes(q) ||
                (b.notes || '').toLowerCase().includes(q)
              );
            }
            countEl.textContent = filtered.length + ' of ' + TOTAL + ' project' + (TOTAL !== 1 ? 's' : '');
            if (!filtered.length) {
              grid.innerHTML = '<div class="grid-empty">No projects match.</div>';
              return;
            }
            grid.innerHTML = filtered.map(b => {
              const stage = b.stage || 'built';
              const stageIdx = STAGES.indexOf(stage);
              const progress = Math.round((stageIdx / (STAGES.length - 1)) * 100);
              const colors = collectAllPaints(b.colors, b.recipes);
              let missing = 0,
                low = 0;
              colors.forEach(c => {
                const uk = upgradeKey(c);
                const stock = paintStock.get(uk) || '';
                if (!paintOwned.has(uk) || stock === 'out') missing++;
                else if (stock === 'low') low++;
              });
              const pillsHtml = colors.slice(0, 12).map(c => {
                const [, name = c] = c.split('|');
                const uk = upgradeKey(c);
                const stock = paintStock.get(uk) || '';
                let cls = 'pcol-pill owned';
                if (!paintOwned.has(uk) || stock === 'out') cls = 'pcol-pill missing';
                else if (stock === 'low') cls = 'pcol-pill low';
                return `<span class="${cls}" title="${esc(c)}">${esc(name)}</span>`;
              }).join('');
              const overflow = colors.length > 12 ?
                `<span class="pcol-pill" style="background:#0a0a0a;border:1px solid #1a1a1a;color:#3a2a10">+${colors.length - 12} more</span>` :
                '';
              const statusParts = [];
              if (missing > 0) statusParts.push(`<span style="color:#c94040">${missing} missing</span>`);
              if (low > 0) statusParts.push(`<span style="color:#c97a20">${low} low</span>`);
              const brushList = (b.brushes || []).map(id => BR_LOOKUP.get(id)).filter(Boolean);
              const brushHtml = brushList.length ?
                `<div class="bench-brush-row">${brushList.map(br => {
                const lbl = [br.brand, br.series, br.size].filter(Boolean).join(' \u00b7 ');
                return `<span class="bench-brush-pill">${esc(lbl)}</span>`;
              }).join('')}</div>` :
                '';
              const imgs = b.wip_images || [];
              const imgsHtml = imgs.length ?
                `<div class="bench-wip-row">${imgs.map((p, i) => `<img src="${esc(p)}" loading="lazy" onclick="openLightbox(${esc(JSON.stringify(imgs))}, ${i})" alt="WIP">`).join('')}</div>` :
                '';
              const benchRecipeRefs = renderRecipeRefs(b.recipes);
              const nextStep = getBenchNextStep(b);
              return `<div class="bench-card stage-${esc(stage)}" data-id="${esc(b.id || '')}">
            <div class="bench-card-header">
            <div class="bench-card-name">${esc(b.name)}</div>
            ${b.faction ? `<div class="bench-card-faction">${esc(b.faction)}</div>` : ''}
            ${b.system ? `<span class="sys-game-badge sys-${sysSlug(b.system)}">${esc(b.system)}</span>` : ''}
            ${b.codex_source ? `<span class="codex-source-badge">${esc(b.codex_source)}</span>` : ''}${benchRecipeRefs}
            </div>
            <div class="bench-card-body">
            <div class="bench-stage-row">
              <span class="bench-stage-label stage-${esc(stage)}">${esc(STAGE_LABEL[stage] || stage)}</span>
              ${b.last_touched ? `<span class="bench-touched">touched ${esc(daysAgoStr(b.last_touched))}</span>` : ''}
            </div>
            ${nextStep ? `<div class="bench-next-step">Next: ${esc(nextStep)}</div>` : ''}
            <div class="bench-progress"><div class="bench-progress-fill" style="width:${progress}%"></div></div>
            ${imgsHtml}
            ${b.notes ? `<div class="bench-card-notes">${esc(b.notes).replace(/\r?\n/g, '<br>')}</div>` : ''}
            ${colors.length ? `<div class="bench-colors">${pillsHtml}${overflow}</div>` : ''}
            ${brushHtml}
            <div class="planned-card-footer">
              <span class="planned-card-summary">${colors.length} paint${colors.length !== 1 ? 's' : ''}${statusParts.length ? ' - ' + statusParts.join(', ') : ''}</span>
              ${colors.length ? `<button class="pull-btn planned-pull-btn" onclick="openBenchPull('${esc(b.id || '')}')">Pull list${missing > 0 ? ` <span class="pull-issue-badge">${missing} issue${missing !== 1 ? 's' : ''}</span>` : ''}</button>` : ''}
            </div>
            ${(b.history && b.history.length) ? `<button class="bench-hist-toggle" onclick="const n=this.nextElementSibling;const o=n.classList.toggle('bench-hist-open');this.textContent=o?'Timeline ↑':'Timeline ↓'">Timeline ↓</button><div class="bench-hist-list">${[...b.history].reverse().map(h=>`<div class="bench-hist-row"><span>${esc(STAGE_LABEL[h.from]||h.from)}</span><span class="bench-hist-arrow">→</span><span>${esc(STAGE_LABEL[h.to]||h.to)}</span><span class="bench-hist-when">${esc(daysAgoStr(h.date)||h.date)}</span></div>`).join('')}</div>` : ''}
            ${(b.sessions && b.sessions.length) ? `<button class="bench-sess-toggle" onclick="const n=this.nextElementSibling;const o=n.classList.toggle('bench-sess-open');this.textContent=o?'Sessions ↑ (${b.sessions.length})':'Sessions ↓ (${b.sessions.length})'">Sessions ↓ (${b.sessions.length})</button><div class="bench-sess-list">${b.sessions.map(s=>`<div class="bench-sess-row"><span class="bench-sess-date">${esc(s.date)}</span>${s.duration?`<span class="bench-sess-dur">${s.duration>=60?Math.floor(s.duration/60)+'h'+(s.duration%60?' '+s.duration%60+'m':''):s.duration+'m'}</span>`:''} ${s.note?`<span class="bench-sess-note">${esc(s.note)}</span>`:''}</div>`).join('')}</div>` : ''}
            </div>
          </div>`;
            }).join('');
          }

          window.openBenchPull = function openBenchPull(id) {
            const b = BENCH_DATA.find(x => x.id === id);
            if (!b) return;
            const STAGES_LIST = ['built','primed','basecoated','washed','highlighted','based','varnished','done'];
            const stage = b.stage || 'built';
            const nextIdx = STAGES_LIST.indexOf(stage) + 1;
            const nextStage = nextIdx < STAGES_LIST.length ? STAGES_LIST[nextIdx] : null;
            const subtitle = [b.faction, 'Stage: ' + (STAGE_LABEL[stage] || stage) + (nextStage ? ' - Next: ' + (STAGE_LABEL[nextStage] || nextStage) : '')].filter(Boolean).join(' — ');
            populatePullSheet(b.name, subtitle, b.colors || [], b.recipes);
          }

          function getBenchNextStep(b) {
            if (!b.recipes || !b.recipes.length || !window._RECIPE_BY_ID) return '';
            const recipe = window._RECIPE_BY_ID.get(b.recipes[0]);
            if (!recipe || !(recipe.steps || []).length) return '';
            const step = recipe.steps[0];
            const parts = (step.paint || '').split('|');
            const paintName = parts[1] || step.paint || '';
            const technique = step.technique || '';
            return (technique ? technique + ': ' : '') + paintName;
          }

          document.querySelectorAll('.bench-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
              document.querySelectorAll('.bench-filter-pill').forEach(p => p.classList.remove('active'));
              pill.classList.add('active');
              stageFilter = pill.dataset.stage;
              renderBench();
            });
          });
          searchEl.addEventListener('input', renderBench);
          window._renderBench = renderBench;
          renderBench();
        })();
    <?php endif; ?>

    // ── Recipes ─────────────────────────────────────

    // ── Recipes ─────────────────────────────────────
    <?php if ($hasRecipes): ?>
        (function() {
          const RECIPE_BY_ID = new Map(RECIPES_DATA.map(r => [r.id, r]));
          window._RECIPE_BY_ID = RECIPE_BY_ID; // used by card badges

          // Backfill paintUsage: recipe step paints count toward scheme usage when the scheme references that recipe
          MODELS.forEach(m => {
            if (!m.recipes || !m.recipes.length) return;
            const mc = Math.max(1, parseInt(m.count || 1, 10));
            const seen = new Set((m.colors || []).map(c => upgradeKey(c)));
            for (const rid of m.recipes) {
              const r = RECIPE_BY_ID.get(rid);
              if (!r) continue;
              for (const step of (r.steps || [])) {
                if (step.paint) {
                  const k = upgradeKey(step.paint);
                  if (!seen.has(k)) { seen.add(k); paintUsage.set(k, (paintUsage.get(k) || 0) + mc); }
                }
                if (step.mix_paint) {
                  const mk = upgradeKey(step.mix_paint);
                  if (!seen.has(mk)) { seen.add(mk); paintUsage.set(mk, (paintUsage.get(mk) || 0) + mc); }
                }
              }
            }
          });

          // Build "used in" index: recipeId -> [{kind, name, id}]
          const USED_IN = new Map();
          const tally = (arr, kind) => arr.forEach(e => {
            (e.recipes || []).forEach(rid => {
              if (!USED_IN.has(rid)) USED_IN.set(rid, []);
              USED_IN.get(rid).push({
                kind,
                name: e.name,
                id: e.id
              });
            });
          });
          tally(MODELS, 'gallery');
          tally(PLANNED, 'planned');
          if (typeof BENCH_DATA !== 'undefined') tally(BENCH_DATA, 'bench');

          const TECH_LABEL = {
            basecoat: 'Basecoat',
            wash: 'Wash',
            shade: 'Shade',
            layer: 'Layer',
            edge: 'Edge',
            highlight: 'Highlight',
            glaze: 'Glaze',
            drybrush: 'Drybrush',
            stipple: 'Stipple',
            blend: 'Blend',
            special: 'Special'
          };

          function paintStatusCls(paintKey) {
            const uk = upgradeKey(paintKey);
            const stock = paintStock.get(uk) || '';
            if (!paintOwned.has(uk) || stock === 'out') return 'missing';
            if (stock === 'low') return 'low';
            return 'owned';
          }

          function paintHex(paintKey) {
            const uk = upgradeKey(paintKey);
            for (const p of PAINTS) {
              const k = (p.brand + '|' + p.name + '|' + (p.layer || '')).toLowerCase();
              if (k === uk) return p.hex || '';
            }
            return '';
          }

          function renderSteps(steps) {
            return '<ol class="recipe-steps-list">' + steps.map((s, i) => {
              const parts = (s.paint || '').split('|');
              const brand = parts[0] || '';
              const name = parts[1] || s.paint || '';
              const layer = parts[2] || '';
              const statusCls = paintStatusCls(s.paint || '');
              const hex = paintHex(s.paint || '');
              const swatch = hex ? `<span class="recipe-step-swatch" style="background:${esc(hex)}"></span>` : '';
              let mixHtml = '';
              if (s.mix_paint) {
                const mp = s.mix_paint.split('|');
                const mName = mp[1] || s.mix_paint;
                const mStatusCls = paintStatusCls(s.mix_paint);
                const mHex = paintHex(s.mix_paint);
                const mSwatch = mHex ? `<span class="recipe-step-swatch" style="background:${esc(mHex)}"></span>` : '';
                mixHtml = ` <span class="rc-mix-sep">+</span> <span class="recipe-step-paint ${mStatusCls}">${mSwatch}${esc(mName)}</span>`;
              }
              const meta = [];
              if (s.ratio) meta.push(`<span class="rc-ratio">${esc(s.ratio)}</span>`);
              if (s.note) meta.push(esc(s.note));
              if (s.brush) {
                const b = (typeof BRUSHES_DATA !== 'undefined') ? BRUSHES_DATA.find(x => x.id === s.brush) : null;
                if (b) {
                  const bl = [b.brand, b.series, b.size].filter(Boolean).join(' \u00b7 ');
                  meta.push(`<span class="rc-brush">${esc(bl)}</span>`);
                }
              }
              const tech = s.technique || 'special';
              return `<li class="recipe-step-row">
            <span class="recipe-step-num">${i + 1}.</span>
            <div class="recipe-step-body">
              <div class="recipe-step-line">
                <span class="recipe-step-tech recipe-tech-${esc(tech)}">${esc(TECH_LABEL[tech] || tech)}</span>
                <span class="recipe-step-paint ${statusCls}">${swatch}${esc(name)}${brand ? ` <span style="color:#4a3a1a;font-size:9px">${esc(brand)}${layer ? ' · ' + esc(layer) : ''}</span>` : ''}</span>${mixHtml}
              </div>
              ${meta.length ? `<div class="recipe-step-meta">${meta.join(' &middot; ')}</div>` : ''}
            </div>
          </li>`;
            }).join('') + '</ol>';
          }

          function renderUsedIn(rid) {
            const uses = USED_IN.get(rid) || [];
            if (!uses.length) return '';
            const links = uses.slice(0, 8).map(u => {
              const label = esc(u.name);
              if (u.kind === 'gallery') return `<a href="#" onclick="_jumpToScheme('${esc(u.id)}');return false;">${label}</a>`;
              return `<span style="color:#6a5a30">${label}</span>`;
            }).join(', ');
            const more = uses.length > 8 ? ` +${uses.length - 8}` : '';
            return `<div class="recipe-used-in">Used in: ${links}${more}</div>`;
          }

          const searchEl = document.getElementById('recipes-search');
          const countEl = document.getElementById('recipes-count');
          const grid = document.getElementById('recipes-grid');
          const emptyEl = document.getElementById('recipes-empty');
          const pillsEl = document.getElementById('recipes-filter-pills');
          let catFilter = 'all';
          let orphanFilter = false;

          // Build category pills dynamically
          const cats = [...new Set(RECIPES_DATA.map(r => r.category).filter(Boolean))].sort();
          cats.forEach(cat => {
            const btn = document.createElement('button');
            btn.className = 'recipes-filter-pill';
            btn.dataset.cat = cat;
            btn.textContent = cat;
            // Insert before the Unused pill (last child)
            pillsEl.insertBefore(btn, pillsEl.lastElementChild);
          });
          pillsEl.addEventListener('click', e => {
            const btn = e.target.closest('.recipes-filter-pill');
            if (!btn) return;
            pillsEl.querySelectorAll('.recipes-filter-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            catFilter = btn.dataset.cat;
            orphanFilter = catFilter === '__orphan__';
            renderRecipes();
          });

          function renderRecipes() {
            const TOTAL = RECIPES_DATA.length;
            if (!TOTAL) {
              grid.innerHTML = '';
              emptyEl.style.display = 'block';
              countEl.textContent = '';
              return;
            }
            emptyEl.style.display = 'none';
            const q = searchEl.value.trim().toLowerCase();
            let list = RECIPES_DATA.slice();
            if (orphanFilter) list = list.filter(r => !USED_IN.has(r.id));
            else if (catFilter !== 'all') list = list.filter(r => (r.category || '') === catFilter);
            if (q) {
              list = list.filter(r => {
                if ((r.name || '').toLowerCase().includes(q)) return true;
                if ((r.description || '').toLowerCase().includes(q)) return true;
                if ((r.faction || '').toLowerCase().includes(q)) return true;
                if ((r.notes || '').toLowerCase().includes(q)) return true;
                return (r.steps || []).some(s =>
                  (s.paint || '').toLowerCase().includes(q) ||
                  (s.note || '').toLowerCase().includes(q) ||
                  (s.technique || '').toLowerCase().includes(q)
                );
              });
            }
            countEl.textContent = orphanFilter
              ? list.length + ' unused recipe' + (list.length !== 1 ? 's' : '')
              : list.length + ' of ' + TOTAL + ' recipe' + (TOTAL !== 1 ? 's' : '');
            if (!list.length) {
              grid.innerHTML = '<div class="grid-empty">No recipes match.</div>';
              return;
            }
            grid.innerHTML = list.map(r => `
          <div class="recipe-card" id="recipe-${esc(r.id)}">
            <button class="recipe-link-btn" title="Copy link" onclick="copyRecipeLink(event,'${esc(r.id)}')"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
            <div class="recipe-card-header">
              <div class="recipe-card-header-left"><span class="recipe-card-name">${esc(r.name)}</span>${r.category ? `<span class="recipe-cat-badge">${esc(r.category)}</span>` : ''}${r.faction ? `<span class="recipe-faction-badge">${esc(r.faction)}</span>` : ''}</div>
              ${r.image ? `<img class="recipe-thumb" src="${esc(r.image)}" alt="" onclick="openLightbox(['${esc(r.image)}'],0)">` : ''}
            </div>
            <div class="recipe-card-body">
            ${r.description ? `<div class="recipe-card-desc">${esc(r.description)}</div>` : ''}
            ${(r.steps && r.steps.length) ? renderSteps(r.steps) : '<div style="color:#3a2a10;font-style:italic;font-size:11px;padding:6px 0">No steps defined yet.</div>'}
            ${r.notes ? `<div class="recipe-notes">${esc(r.notes)}</div>` : ''}
            ${renderUsedIn(r.id)}
            ${(r.steps && r.steps.length) ? `<div class="recipe-card-footer"><button class="recipe-guide-btn" onclick="openRecipeGuide('${esc(r.id)}')">&#9654; Guide</button></div>` : ''}
            </div>
          </div>
        `).join('');
          }

          searchEl.addEventListener('input', renderRecipes);
          window._renderRecipes = renderRecipes;
          window._jumpToRecipe = function(rid) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            const tab = document.querySelector('[data-tab="recipes"]');
            if (tab) tab.classList.add('active');
            document.getElementById('tab-recipes').classList.add('active');
            renderRecipes();
            const el = document.getElementById('recipe-' + rid);
            if (el) {
              el.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });
              el.classList.remove('highlight');
              void el.offsetWidth;
              el.classList.add('highlight');
            }
          };
          window._jumpToScheme = function(mid) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            const tab = document.querySelector('[data-tab="gallery"]');
            if (tab) tab.classList.add('active');
            document.getElementById('tab-gallery').classList.add('active');
            if (typeof showAllGallery !== 'undefined') {
              showAllGallery = true;
              if (typeof renderGallery === 'function') renderGallery();
            }
            setTimeout(() => {
              const el = document.querySelector('.model-card[data-id="' + mid + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 120);
          };

          renderRecipes();

          // ── Step-by-step Recipe Guide ──
          (function() {
          let _gr = null, _gi = 0;

          function renderGuideStep() {
            const steps = _gr.steps || [];
            const s = steps[_gi];
            const total = steps.length;
            document.getElementById('recipe-guide-title').textContent = _gr.name;
            document.getElementById('recipe-guide-counter').textContent = 'Step ' + (_gi + 1) + ' of ' + total;

            const tech = s.technique || 'special';
            const parts = (s.paint || '').split('|');
            const paintName = parts[1] || s.paint || '';
            const brand = parts[0] || '';
            const layer = parts[2] || '';
            const hex = paintHex(s.paint || '');
            const statusCls = paintStatusCls(s.paint || '');

            let html = `<div class="recipe-guide-tech recipe-tech-${esc(tech)}">${esc(TECH_LABEL[tech] || tech)}</div>`;
            html += `<div class="recipe-guide-paint-row">${hex ? `<span class="recipe-guide-swatch" style="background:${esc(hex)}"></span>` : ''}<span class="recipe-guide-paint-name ${statusCls}">${esc(paintName)}</span></div>`;
            if (brand) html += `<div class="recipe-guide-brand">${esc(brand)}${layer ? ' · ' + esc(layer) : ''}</div>`;
            const meta = [];
            if (s.ratio) meta.push(esc(s.ratio));
            if (s.note)  meta.push(esc(s.note));
            if (meta.length) html += `<div class="recipe-guide-meta">${meta.join(' · ')}</div>`;
            if (s.brush && typeof BRUSHES_DATA !== 'undefined') {
              const b = BRUSHES_DATA.find(x => x.id === s.brush);
              if (b) html += `<div class="recipe-guide-brush">${esc([b.brand, b.series, b.size].filter(Boolean).join(' · '))}</div>`;
            }
            document.getElementById('recipe-guide-step-content').innerHTML = html;

            document.getElementById('recipe-guide-dots').innerHTML = steps.map((_, i) =>
              `<span class="recipe-guide-dot${i === _gi ? ' active' : i < _gi ? ' done' : ''}"></span>`
            ).join('');

            const prevBtn = document.getElementById('recipe-guide-prev');
            const nextBtn = document.getElementById('recipe-guide-next');
            prevBtn.disabled = _gi === 0;
            const isLast = _gi === total - 1;
            nextBtn.textContent = isLast ? '✓ Done' : '›';
            nextBtn.classList.toggle('done-btn', isLast);
          }

          window.openRecipeGuide = function(rid) {
            const r = RECIPES_DATA.find(x => x.id === rid);
            if (!r || !r.steps || !r.steps.length) return;
            _gr = r; _gi = 0;
            renderGuideStep();
            document.getElementById('recipe-guide-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
          };

          window.closeRecipeGuide = function() {
            document.getElementById('recipe-guide-overlay').classList.remove('open');
            document.body.style.overflow = '';
          };

          window.stepGuide = function(dir) {
            if (!_gr) return;
            const steps = _gr.steps || [];
            if (dir > 0 && _gi >= steps.length - 1) { closeRecipeGuide(); return; }
            _gi = Math.max(0, Math.min(steps.length - 1, _gi + dir));
            renderGuideStep();
          };

          // Swipe support
          let _touchX = 0;
          const overlay = document.getElementById('recipe-guide-overlay');
          overlay.addEventListener('touchstart', e => { _touchX = e.touches[0].clientX; }, { passive: true });
          overlay.addEventListener('touchend', e => {
            const dx = e.changedTouches[0].clientX - _touchX;
            if (Math.abs(dx) > 50) stepGuide(dx < 0 ? 1 : -1);
          }, { passive: true });
          overlay.addEventListener('click', e => { if (e.target === overlay) closeRecipeGuide(); });
        })();

        })(); // end recipes IIFE

    <?php else: ?>
      window._jumpToRecipe = function() {};
      window.openRecipeGuide = function() {};
      window.closeRecipeGuide = function() {};
      window.stepGuide = function() {};
    <?php endif; ?>

    // ── Factions Overview ────────────────────────────
    (function() {
      const wrap     = document.getElementById('factions-wrap');
      const searchEl = document.getElementById('factions-search');
      const countEl  = document.getElementById('factions-count');
      const emptyEl  = document.getElementById('factions-empty');
      if (!wrap || !searchEl) return;

      const hasRecipes = typeof RECIPES_DATA !== 'undefined';
      const hasBench   = typeof BENCH_DATA   !== 'undefined';

      // Build faction index: factionName -> { schemes, recipes, bench, planned, palette }
      const INDEX = new Map();
      function bucket(name) {
        const key = (name || '').trim();
        if (!key) return null;
        if (!INDEX.has(key)) INDEX.set(key, { name: key, schemes: [], recipes: [], bench: [], planned: [], palette: new Map() });
        return INDEX.get(key);
      }
      function addColorsToPalette(b, colors) {
        (colors || []).forEach(c => {
          const uk = upgradeKey(c);
          b.palette.set(uk, (b.palette.get(uk) || 0) + 1);
        });
      }

      MODELS.forEach(m => { const b = bucket(m.faction); if (!b) return; b.schemes.push(m); addColorsToPalette(b, effectiveColors(m)); });
      PLANNED.forEach(p => { const b = bucket(p.faction); if (!b) return; b.planned.push(p); addColorsToPalette(b, p.colors); });
      if (hasBench)   BENCH_DATA.forEach(x => { const b = bucket(x.faction); if (!b) return; b.bench.push(x); addColorsToPalette(b, x.colors); });
      if (hasRecipes) RECIPES_DATA.forEach(r => { const b = bucket(r.faction); if (!b) return; b.recipes.push(r); (r.steps || []).forEach(s => { if (s.paint) addColorsToPalette(b, [s.paint]); }); });

      const FACTIONS = Array.from(INDEX.values()).sort((a, b) => a.name.localeCompare(b.name));

      // Paint lookup for palette pills (brand/name/layer + hex)
      const PAINT_BY_KEY = new Map(PAINTS.map(p => [p.brand + '|' + p.name + '|' + (p.layer || ''), p]));

      function paintStatusCls(uk) {
        if (!paintOwned.has(uk)) return 'missing';
        const s = paintStock.get(uk);
        if (s === 'low') return 'low';
        if (s === 'out') return 'out';
        if (s === 'wanted') return 'wanted';
        return 'owned';
      }

      function schemeThumb(m) {
        const img = (m.images || [])[0] || '';
        const imgContent = img
          ? `<img src="${esc(img)}" loading="lazy" alt="${esc(m.name)}">`
          : 'NO PHOTO';
        return `<a class="faction-scheme-mini" onclick="_jumpToScheme('${esc(m.id)}')" title="${esc(m.name)}"><div class="faction-scheme-mini-img">${imgContent}</div><div class="faction-scheme-mini-name">${esc(m.name)}</div></a>`;
      }

      function recipeChip(r) {
        return `<span class="faction-chip" onclick="_jumpToRecipe('${esc(r.id)}')" title="${esc(r.description || '')}"><span class="faction-chip-kind kind-recipe">Doctrine</span>${esc(r.name)}</span>`;
      }

      function benchChip(b) {
        const stage = b.stage || 'built';
        return `<span class="faction-chip" data-bench-id="${esc(b.id)}" title="${esc(stage)}"><span class="faction-chip-kind kind-bench">${esc(stage)}</span>${esc(b.name)}</span>`;
      }

      function plannedChip(p) {
        return `<span class="faction-chip" data-planned-id="${esc(p.id)}"><span class="faction-chip-kind kind-planned">Pending</span>${esc(p.name)}</span>`;
      }

      function palettePill(uk, count) {
        const parts = uk.split('|');
        const name  = parts[1] || uk;
        const brand = parts[0] || '';
        const paint = PAINT_BY_KEY.get(uk);
        const hex   = paint && /^#[0-9a-fA-F]{6}$/.test(paint.hex || '') ? paint.hex : '#1a1408';
        const cls   = paintStatusCls(uk);
        const countBadge = count > 1 ? `<span class="faction-palette-count">&times;${count}</span>` : '';
        return `<span class="faction-palette-pill" data-paint-name="${esc(name)}" title="${esc(brand)} ${esc(name)} - used in ${count} entr${count === 1 ? 'y' : 'ies'}"><span class="faction-palette-swatch" style="background:${esc(hex)}"></span><span class="faction-palette-dot ${cls}"></span>${esc(name)}${countBadge}</span>`;
      }

      function renderFaction(f) {
        const schemeCount  = f.schemes.length;
        const recipeCount  = f.recipes.length;
        const benchCount   = f.bench.length;
        const plannedCount = f.planned.length;
        const modelCount   = f.schemes.reduce((n, m) => n + Math.max(1, parseInt(m.count || 1, 10)), 0);

        const summaryParts = [];
        if (schemeCount)  summaryParts.push(`<strong>${schemeCount}</strong> painted`);
        if (modelCount > schemeCount) summaryParts.push(`<strong>${modelCount}</strong> models`);
        if (benchCount)   summaryParts.push(`<strong>${benchCount}</strong> in progress`);
        if (plannedCount) summaryParts.push(`<strong>${plannedCount}</strong> planned`);
        if (recipeCount)  summaryParts.push(`<strong>${recipeCount}</strong> recipe${recipeCount === 1 ? '' : 's'}`);

        const schemesBlock = schemeCount
          ? `<div class="faction-section"><div class="faction-section-title">Field Record</div><div class="faction-scheme-grid">${f.schemes.map(schemeThumb).join('')}</div></div>`
          : '';

        const recipesBlock = recipeCount
          ? `<div class="faction-section"><div class="faction-section-title">Doctrine</div><div class="faction-chips">${f.recipes.map(recipeChip).join('')}</div></div>`
          : '';

        const flightChips = [...f.bench.map(benchChip), ...f.planned.map(plannedChip)].join('');
        const flightBlock = flightChips
          ? `<div class="faction-section"><div class="faction-section-title">Active Operations</div><div class="faction-chips">${flightChips}</div></div>`
          : '';

        let paletteBlock = '';
        if (f.palette.size) {
          const sorted = [...f.palette.entries()].sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]));
          const pills = sorted.map(([uk, n]) => palettePill(uk, n)).join('');
          paletteBlock = `<div class="faction-section"><div class="faction-section-title">Materiel &middot; ${sorted.length}</div><div class="faction-palette">${pills}</div></div>`;
        }

        return `<div class="faction-card" id="faction-${esc(f.name.toLowerCase().replace(/[^a-z0-9]+/g, '-'))}"><div class="faction-header"><div class="faction-name">${esc(f.name)}</div><div class="faction-summary">${summaryParts.join(' &middot; ')}</div></div><div class="faction-body">${schemesBlock}${recipesBlock}${flightBlock}${paletteBlock}</div></div>`;
      }

      function render() {
        const q = searchEl.value.trim().toLowerCase();
        const filtered = q ? FACTIONS.filter(f => f.name.toLowerCase().includes(q)) : FACTIONS;
        if (!FACTIONS.length) {
          wrap.innerHTML = '';
          emptyEl.style.display = 'block';
          countEl.textContent = '';
          return;
        }
        emptyEl.style.display = 'none';
        countEl.textContent = filtered.length + ' of ' + FACTIONS.length + ' faction' + (FACTIONS.length === 1 ? '' : 's');
        wrap.innerHTML = filtered.length ? filtered.map(renderFaction).join('') : '<div style="padding:40px 16px;text-align:center;font-family:Georgia,serif;font-style:italic;color:#4a3a10">No files match.</div>';
      }

      // Delegation: paint palette pills → inventory filter, bench/planned chips → their tabs with pulse
      wrap.addEventListener('click', e => {
        const paintEl = e.target.closest('[data-paint-name]');
        if (paintEl) {
          switchToTab('inventory');
          const s = document.getElementById('search');
          if (s) { s.value = paintEl.dataset.paintName; s.dispatchEvent(new Event('input')); }
          return;
        }
        const b = e.target.closest('[data-bench-id]');
        if (b) { switchToTab('bench'); setTimeout(() => { const el = document.querySelector('.bench-card[data-id="' + b.dataset.benchId + '"]'); if (el) { el.scrollIntoView({behavior:'smooth',block:'start'}); el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight'); } }, 150); return; }
        const p = e.target.closest('[data-planned-id]');
        if (p) { switchToTab('planned'); setTimeout(() => { const el = document.querySelector('.planned-card[data-id="' + p.dataset.plannedId + '"]'); if (el) { el.scrollIntoView({behavior:'smooth',block:'start'}); el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight'); } }, 150); }
      });

      searchEl.addEventListener('input', render);
      window._renderFactions = render;
      render();
    })();

    // ── Shopping List ─────────────────────────────────
    function openShoppingList() {
      const mustBuy = {};
      const restock = {};

      PLANNED.forEach(pl => {
        (pl.colors || []).forEach(c => {
          const [brand, name = c] = c.split('|');
          const uk = upgradeKey(c);
          const stock = paintStock.get(uk) || '';
          if (!paintOwned.has(uk) || stock === 'out') {
            if (!mustBuy[brand]) mustBuy[brand] = {};
            if (!mustBuy[brand][name]) mustBuy[brand][name] = [];
            if (!mustBuy[brand][name].includes(pl.name)) mustBuy[brand][name].push(pl.name);
          } else if (stock === 'low') {
            if (!restock[brand]) restock[brand] = {};
            if (!restock[brand][name]) restock[brand][name] = [];
            if (!restock[brand][name].includes(pl.name)) restock[brand][name].push(pl.name);
          }
        });
      });

      const mustBrands = Object.keys(mustBuy).sort();
      const restockBrands = Object.keys(restock).sort();
      const totalMust = mustBrands.reduce((n, b) => n + Object.keys(mustBuy[b]).length, 0);
      const totalRestock = restockBrands.reduce((n, b) => n + Object.keys(restock[b]).length, 0);

      let html = '';
      if (totalMust === 0 && totalRestock === 0) {
        html = '<div class="shop-all-good">All set \u2014 no paints needed!</div>';
      } else {
        if (totalMust > 0) {
          html += `<div class="shop-section-heading shop-must">Must Buy \u2014 ${totalMust} paint${totalMust !== 1 ? 's' : ''}</div>`;
          for (const brand of mustBrands) {
            html += `<div class="shop-brand">${esc(brand)}</div><ul class="shop-paint-list">`;
            for (const [name, schemes] of Object.entries(mustBuy[brand]).sort()) {
              html += `<li><span class="shop-paint-name">${esc(name)}</span><span class="shop-schemes">${esc(schemes.join(', '))}</span></li>`;
            }
            html += '</ul>';
          }
        }
        if (totalRestock > 0) {
          html += `<div class="shop-section-heading shop-consider">Consider Restocking \u2014 ${totalRestock} paint${totalRestock !== 1 ? 's' : ''}</div>`;
          for (const brand of restockBrands) {
            html += `<div class="shop-brand">${esc(brand)}</div><ul class="shop-paint-list">`;
            for (const [name, schemes] of Object.entries(restock[brand]).sort()) {
              html += `<li><span class="shop-paint-name">${esc(name)}</span><span class="shop-schemes">${esc(schemes.join(', '))}</span></li>`;
            }
            html += '</ul>';
          }
        }
      }

      const schemeCount = PLANNED.filter(p => (p.colors || []).length > 0).length;
      document.getElementById('shop-subtitle').textContent = schemeCount + ' scheme' + (schemeCount !== 1 ? 's' : '');
      document.getElementById('shop-content').innerHTML = html;
      document.getElementById('shop-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeShoppingList() {
      document.getElementById('shop-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    function printShopList() {
      document.body.classList.add('print-shop');
      window.print();
      document.body.classList.remove('print-shop');
    }

    function copyShoppingList() {
      let text = 'Shopping List\n\n';
      document.getElementById('shop-content').querySelectorAll('.shop-section-heading, .shop-brand, .shop-paint-list li').forEach(el => {
        if (el.classList.contains('shop-section-heading')) text += '\n' + el.textContent.trim() + '\n';
        else if (el.classList.contains('shop-brand')) text += '\n' + el.textContent.trim() + '\n';
        else {
          const name = el.querySelector('.shop-paint-name')?.textContent || '';
          const schemes = el.querySelector('.shop-schemes')?.textContent || '';
          text += '  \u25a1 ' + name + (schemes ? '  \u2192 ' + schemes : '') + '\n';
        }
      });
      navigator.clipboard.writeText(text.trim()).then(() => {
        const btn = document.getElementById('shop-copy-btn');
        const prev = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = prev, 2000);
      });
    }

    document.getElementById('shop-overlay').addEventListener('click', e => {
      if (e.target === document.getElementById('shop-overlay')) closeShoppingList();
    });


    // ── Paint Notes Drawer ───────────────────────────────
    function drawerStarSet(n) {
      document.querySelectorAll('#notes-star-picker .nsp-star').forEach(s => {
        s.classList.toggle('on', parseInt(s.dataset.val) <= n);
      });
    }

    function openNotes(pid, brand, name, stars, notes) {
      document.getElementById('notes-paint-name').textContent = name;
      document.getElementById('notes-paint-brand').textContent = brand;
      const body = document.getElementById('notes-body');
      body.innerHTML = notes ?
        `<div class="notes-body">${esc(notes)}</div>` :
        `<div class="notes-empty">No notes yet - add them in admin.</div>`;
      drawerStarSet(parseInt(stars) || 0);
      document.getElementById('notes-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeNotes() {
      document.getElementById('notes-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    document.getElementById('notes-overlay').addEventListener('click', e => {
      if (e.target === document.getElementById('notes-overlay')) closeNotes();
    });

    // ── Forces & Rosters ────────────────────────────────
    <?php if ($hasForces): ?>
    (function() {
      const grid    = document.getElementById('forces-grid');
      const emptyEl = document.getElementById('forces-empty');
      const countEl = document.getElementById('forces-count');
      const searchEl = document.getElementById('forces-search');

      // Build a lookup from model ID → model object
      const MODEL_BY_ID = new Map(MODELS.map(m => [m.id, m]));

      function renderForces() {
        const q = searchEl.value.trim().toLowerCase();
        let list = FORCES_DATA.slice();
        if (q) {
          list = list.filter(f =>
            (f.name || '').toLowerCase().includes(q) ||
            (f.faction || '').toLowerCase().includes(q) ||
            (f.system || '').toLowerCase().includes(q) ||
            (f.notes || '').toLowerCase().includes(q)
          );
        }
        list.sort((a, b) => (b.pinned ? 1 : 0) - (a.pinned ? 1 : 0));

        countEl.textContent = q ?
          list.length + ' of ' + FORCES_DATA.length + ' force' + (FORCES_DATA.length !== 1 ? 's' : '') :
          FORCES_DATA.length + ' force' + (FORCES_DATA.length !== 1 ? 's' : '');

        if (!list.length) {
          grid.innerHTML = '';
          emptyEl.style.display = 'block';
          return;
        }
        emptyEl.style.display = 'none';

        grid.innerHTML = list.map(f => {
          const painted = (f.models || []).reduce((sum, mid) => {
            const m = MODEL_BY_ID.get(mid);
            return sum + Math.max(1, parseInt((m && m.count) || 1, 10));
          }, 0);
          const target  = f.target_count || 0;
          const pct     = target ? Math.min(100, Math.round(painted / target * 100)) : 0;
          const sysHtml = f.system ?
            `<span class="sys-game-badge sys-${sysSlug(f.system)}">${esc(f.system)}</span>` : '';
          const metaParts = [
            f.faction ? esc(f.faction) : '',
            painted + ' painted' + (target ? ' / ' + target + ' target' : ''),
            f.target_points ? f.target_points + ' pts' : '',
          ].filter(Boolean);

          // First image from any linked scheme, used as full-width hero
          let heroMid = null, heroImg = null;
          for (const mid of (f.models || [])) {
            const m = MODEL_BY_ID.get(mid);
            const img = m && (m.images || [])[0];
            if (img) { heroMid = mid; heroImg = img; break; }
          }
          const heroHtml = heroImg
            ? `<img class="force-card-hero" src="${esc(heroImg)}" loading="lazy" alt="${esc(f.name)}" onclick="_jumpToScheme('${esc(heroMid)}')">`
            : '';

          const progressBar = target ?
            `<div class="force-progress"><div class="force-progress-fill" style="width:${pct}%"></div></div>` : '';

          const isPinned = !!f.pinned;
          const forceBodyContent = progressBar + (f.notes ? `<div class="force-card-notes">${esc(f.notes)}</div>` : '') + (f.roster_url ? `<a class="force-roster-link" href="${esc(f.roster_url)}" target="_blank" rel="noopener">View Roster ↗</a>` : '');
          return `<div class="force-card${isPinned ? ' force-card-pinned' : ''}" data-id="${esc(f.id)}">
            ${heroHtml}
            <div class="force-card-header">
              <div class="force-card-name"><span>${esc(f.name)}</span>${isPinned ? '<span class="force-pin-indicator" title="Pinned">★</span>' : ''}</div>
              <div class="force-card-meta">${sysHtml}${metaParts.map(p => `<span>${p}</span>`).join('')}</div>
            </div>
            ${forceBodyContent ? `<div class="force-card-body">${forceBodyContent}</div>` : ''}
          </div>`;
        }).join('');
      }

      searchEl.addEventListener('input', renderForces);
      window._renderForces = renderForces;
      renderForces();
    })();
    <?php endif; ?>

    // ── Equivalency Search ──────────────────────────────
    const equivSearchEl = document.getElementById('equiv-search');
    const equivCountEl = document.getElementById('equiv-count');
    const equivTbody = document.getElementById('equiv-tbody');
    const equivEmptyEl = document.getElementById('equiv-empty');

    function equivStatus(brand, name) {
      const key = (brand + '|' + name).toLowerCase();
      if (!paintByKeyLC.has(key)) return 'missing';
      const s = paintByKeyLC.get(key);
      if (s === 'out') return 'out';
      if (s === 'low') return 'low';
      if (s === 'wanted') return 'wanted';
      return 'owned';
    }

    function equivDot(brand, name) {
      const st = equivStatus(brand, name);
      const labels = {
        owned: 'Owned',
        low: 'Low stock',
        out: 'Out of stock',
        wanted: 'Wanted',
        missing: 'Not in inventory'
      };
      return `<span class="eq-dot ${st}" title="${labels[st]}"></span>`;
    }

    function matchDot(quality) {
      if (!quality) return '';
      const q = quality.toLowerCase();
      if (q === 'near identical') return `<span class="eq-match-dot eq-match-near" title="Near identical"></span>`;
      if (q === 'avoid') return `<span class="eq-match-dot eq-match-avoid" title="Avoid"></span>`;
      if (q === 'usable') return `<span class="eq-match-dot eq-match-usable" title="Usable"></span>`;
      return '';
    }

    function renderEquiv() {
      const q = equivSearchEl.value.trim().toLowerCase();
      const rows = q ?
        CONVERSIONS_DATA.filter(r =>
          r.citadel.toLowerCase().includes(q) ||
          (r.vallejo && r.vallejo.toLowerCase().includes(q)) ||
          (r.proAcryl && r.proAcryl.toLowerCase().includes(q)) ||
          (r.ttc && r.ttc.toLowerCase().includes(q)) ||
          (r.valMatch && r.valMatch.toLowerCase().includes(q)) ||
          (r.paMatch && r.paMatch.toLowerCase().includes(q)) ||
          (r.ttcMatch && r.ttcMatch.toLowerCase().includes(q))
        ) :
        CONVERSIONS_DATA;

      equivCountEl.textContent = rows.length + ' of ' + CONVERSIONS_DATA.length + ' equivalencies';

      if (!rows.length) {
        equivTbody.innerHTML = '';
        equivEmptyEl.style.display = 'block';
        return;
      }
      equivEmptyEl.style.display = 'none';

      equivTbody.innerHTML = rows.map(r => {
        const citCell = `<td><div class="eq-cell">${equivDot('Citadel', r.citadel)}<span>${esc(r.citadel)}</span></div></td>`;
        const valCell = r.vallejo ?
          `<td><div class="eq-cell">${matchDot(r.valMatch)}${equivDot('Vallejo', r.vallejo)}<span>${esc(r.vallejo)}</span></div></td>` :
          `<td><span class="eq-nil">-</span></td>`;
        const paCell = r.proAcryl ?
          `<td><div class="eq-cell">${matchDot(r.paMatch)}${equivDot('Pro Acryl', r.proAcryl)}<span>${esc(r.proAcryl)}</span></div></td>` :
          `<td><span class="eq-nil">-</span></td>`;
        const ttcCell = r.ttc ?
          `<td><div class="eq-cell">${matchDot(r.ttcMatch)}${equivDot('Two Thin Coats', r.ttc)}<span>${esc(r.ttc)}</span></div></td>` :
          `<td><span class="eq-nil">-</span></td>`;
        return `<tr>${citCell}${valCell}${paCell}${ttcCell}</tr>`;
      }).join('');
    }

    equivSearchEl.addEventListener('input', renderEquiv);

    document.querySelectorAll('.equiv-bp').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.equiv-bp').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const panel = document.getElementById('tab-equiv');
        panel.classList.remove('compare-pa', 'compare-ttc');
        if (btn.dataset.compare === 'pa') panel.classList.add('compare-pa');
        if (btn.dataset.compare === 'ttc') panel.classList.add('compare-ttc');
      });
    });

    renderGallery();
    renderPlanned();
    renderEquiv();

    // Direct model links: ?model=<id>
    function copyModelLink(e, id) {
      e.stopPropagation();
      const url = location.origin + location.pathname + '?model=' + id;
      navigator.clipboard.writeText(url).then(() => {
        const btn = e.currentTarget;
        const prev = btn.title;
        btn.title = 'Copied!';
        setTimeout(() => btn.title = prev, 2000);
      });
    }

    function copyRecipeLink(e, id) {
      e.stopPropagation();
      const url = location.origin + location.pathname + '?recipe=' + id;
      navigator.clipboard.writeText(url).then(() => {
        const btn = e.currentTarget;
        const prev = btn.title;
        btn.title = 'Copied!';
        setTimeout(() => btn.title = prev, 2000);
      });
    }

    const urlTab = new URLSearchParams(location.search).get('tab');
    if (urlTab) switchToTab(urlTab);

    function copyTabLink(e, tabKey) {
      e.preventDefault();
      const el = e.currentTarget;
      const url = location.origin + location.pathname + '?tab=' + tabKey;
      navigator.clipboard.writeText(url).then(() => {
        const prev = el.textContent;
        el.textContent = '✓';
        setTimeout(() => { el.textContent = prev; }, 1500);
      });
    }

    const urlModel = new URLSearchParams(location.search).get('model');
    if (urlModel) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      document.querySelector('[data-tab="gallery"]').classList.add('active');
      document.getElementById('tab-gallery').classList.add('active');
      showAllGallery = true;
      renderGallery();
      const card = document.querySelector(`.model-card[data-id="${urlModel}"]`);
      if (card) {
        card.classList.add('highlight');
        card.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }
    }

    const urlRecipe = new URLSearchParams(location.search).get('recipe');
    if (urlRecipe && typeof window._jumpToRecipe === 'function') {
      window._jumpToRecipe(urlRecipe);
    }

    // Track whichever tab is active on page load (covers default Paint Inventory + direct links)
    (function() {
      const active = document.querySelector('.tab-btn.active');
      if (active) fetch('index.php', {
        method: 'POST',
        body: new URLSearchParams({
          action: 'track_tab',
          tab: active.dataset.tab
        })
      });
    })();

    // ── Global Search ────────────────────────────────
    (function() {
      const trigger = document.getElementById('gs-trigger');
      const overlay = document.getElementById('gs-overlay');
      const input   = document.getElementById('gs-input');
      const listEl  = document.getElementById('gs-results');
      if (!trigger || !overlay || !input || !listEl) return;

      const hasRecipes = typeof RECIPES_DATA !== 'undefined';
      const hasBench   = typeof BENCH_DATA   !== 'undefined';
      const hasBrushes = typeof BRUSHES_DATA !== 'undefined';
      const hasBooks   = typeof BOOKS_DATA   !== 'undefined';
      const hasShame   = typeof SHAME_DATA   !== 'undefined';
      const hasForces  = typeof FORCES_DATA  !== 'undefined';

      const WTYPE_LABEL_GS = {paint:'Paint',model:'Model',brush:'Brush',codex:'Codex'};
      const TYPE_LABEL = {
        paint: 'Paint', scheme: 'Scheme', recipe: 'Recipe',
        planned: 'Planned', bench: 'On Bench', brush: 'Brush',
        book: 'Codex', shame: 'Shame Pile', force: 'Force', wish: 'Wishlist'
      };
      const TYPE_ORDER = ['scheme', 'recipe', 'paint', 'planned', 'shame', 'bench', 'force', 'brush', 'book', 'wish'];
      const PER_TYPE_CAP = 8;

      let selectedIdx = 0;
      let flatResults = [];

      function runSearch(q) {
        q = q.trim().toLowerCase();
        if (!q) return [];
        const out = [];
        const match = (hay) => hay && hay.toLowerCase().indexOf(q) !== -1;

        PAINTS.forEach(p => {
          const hay = [p.brand, p.name, p.hue, p.layer, p.color].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'paint',
            key: p.brand + '|' + p.name + '|' + (p.layer || ''),
            name: p.name,
            meta: [p.brand, p.layer].filter(Boolean).join(' · ')
          });
        });

        MODELS.forEach(m => {
          const hay = [m.name, m.faction, m.description, (m.colors || []).join(' ')].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'scheme',
            key: m.id,
            name: m.name,
            meta: [m.faction, m.date].filter(Boolean).join(' · ')
          });
        });

        PLANNED.forEach(pl => {
          const hay = [pl.name, pl.model, pl.faction, pl.description, (pl.colors || []).join(' ')].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'planned',
            key: pl.id,
            name: pl.name,
            meta: [pl.faction, pl.model].filter(Boolean).join(' · ')
          });
        });

        if (hasRecipes) RECIPES_DATA.forEach(r => {
          const stepHay = (r.steps || []).map(s => [s.paint, s.technique, s.note, s.ratio].filter(Boolean).join(' ')).join(' ');
          const hay = [r.name, r.category, r.faction, r.description, r.notes, stepHay].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'recipe',
            key: r.id,
            name: r.name,
            meta: [r.category, r.faction].filter(Boolean).join(' · ')
          });
        });

        if (hasBench) BENCH_DATA.forEach(b => {
          const hay = [b.name, b.faction, b.notes, (b.colors || []).join(' ')].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'bench',
            key: b.id,
            name: b.name,
            meta: [b.faction, b.stage].filter(Boolean).join(' · ')
          });
        });

        if (hasBrushes) BRUSHES_DATA.forEach(br => {
          const hay = [br.brand, br.series, br.size, br.material, br.use, br.notes].filter(Boolean).join(' ');
          if (match(hay)) {
            const nameParts = [br.brand, br.series].filter(Boolean).join(' ');
            out.push({
              type: 'brush',
              key: br.id,
              name: nameParts || br.brand,
              meta: [br.size, br.material, br.use].filter(Boolean).join(' · ')
            });
          }
        });

        if (hasBooks) BOOKS_DATA.forEach(b => {
          const hay = [b.title, b.author, b.series, b.faction, b.notes].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'book',
            key: b.id,
            name: b.title,
            meta: [b.faction, b.series].filter(Boolean).join(' · ')
          });
        });

        if (hasShame) SHAME_DATA.forEach(s => {
          const hay = [s.name, s.faction, s.system, s.notes, s.acquired].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'shame',
            key: s.id,
            name: s.name,
            meta: [s.system, s.faction, s.status].filter(Boolean).join(' · ')
          });
        });

        if (hasForces) FORCES_DATA.forEach(f => {
          const hay = [f.name, f.faction, f.system, f.notes].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'force',
            key: f.id,
            name: f.name,
            meta: [f.system, f.faction].filter(Boolean).join(' · ')
          });
        });

        if (typeof WISHLIST_DATA !== 'undefined') WISHLIST_DATA.forEach(w => {
          const hay = [w.name, w.brand, w.faction, w.system, w.notes, w.type].filter(Boolean).join(' ');
          if (match(hay)) out.push({type:'wish', key:w.id, name:w.name, meta:[WTYPE_LABEL_GS[w.type||'paint'], w.brand||w.faction].filter(Boolean).join(' · ')});
        });

        return out;
      }

      function render(q) {
        const results = runSearch(q);
        if (!q) {
          listEl.innerHTML = '<div class="gs-empty">Type to search paints, schemes, recipes, bench projects, brushes, and more.</div>';
          flatResults = [];
          return;
        }
        if (!results.length) {
          listEl.innerHTML = `<div class="gs-empty">No matches for "${esc(q)}".</div>`;
          flatResults = [];
          return;
        }
        const groups = {};
        results.forEach(r => { (groups[r.type] = groups[r.type] || []).push(r); });

        let html = '';
        flatResults = [];
        TYPE_ORDER.forEach(t => {
          const g = groups[t];
          if (!g || !g.length) return;
          const shown = g.slice(0, PER_TYPE_CAP);
          const extra = g.length - shown.length;
          html += `<div class="gs-group-title">${TYPE_LABEL[t]}s &middot; ${g.length}${extra > 0 ? ` <span style="color:#4a3a10">(showing ${shown.length})</span>` : ''}</div>`;
          shown.forEach(r => {
            const idx = flatResults.length;
            flatResults.push(r);
            html += `<button type="button" class="gs-result" data-idx="${idx}"><span class="gs-result-type gs-type-${r.type}">${TYPE_LABEL[t]}</span><span class="gs-result-name">${esc(r.name)}</span><span class="gs-result-meta">${esc(r.meta || '')}</span></button>`;
          });
        });
        listEl.innerHTML = html;
        selectedIdx = 0;
        highlightSelected();
      }

      function highlightSelected() {
        const rows = listEl.querySelectorAll('.gs-result');
        rows.forEach((r, i) => r.classList.toggle('selected', i === selectedIdx));
        const sel = rows[selectedIdx];
        if (sel) sel.scrollIntoView({ block: 'nearest' });
      }

      function open() {
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        input.value = '';
        render('');
        setTimeout(() => input.focus(), 10);
      }

      function close() {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }

      function jump(r) {
        close();
        setTimeout(() => {
          if (r.type === 'paint') {
            switchToTab('inventory');
            const searchEl = document.getElementById('search');
            const parts = r.key.split('|');
            if (searchEl) {
              searchEl.value = parts[1] || '';
              searchEl.dispatchEvent(new Event('input'));
            }
          } else if (r.type === 'scheme') {
            if (typeof _jumpToScheme === 'function') _jumpToScheme(r.key);
          } else if (r.type === 'recipe') {
            if (typeof _jumpToRecipe === 'function') _jumpToRecipe(r.key);
          } else if (r.type === 'planned') {
            switchToTab('planned');
            setTimeout(() => {
              const el = document.querySelector('.planned-card[data-id="' + r.key + '"]');
              if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight'); }
            }, 120);
          } else if (r.type === 'bench') {
            switchToTab('bench');
            setTimeout(() => {
              const el = document.querySelector('.bench-card[data-id="' + r.key + '"]');
              if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight'); }
            }, 150);
          } else if (r.type === 'brush') {
            switchToTab('brushes');
            setTimeout(() => {
              const el = document.querySelector('.brush-entry[data-id="' + r.key + '"]');
              if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight'); }
            }, 150);
          } else if (r.type === 'book') {
            switchToTab('books');
            setTimeout(() => {
              const el = document.querySelector('.bl-row[data-id="' + r.key + '"]');
              if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight'); }
            }, 150);
          } else if (r.type === 'shame') {
            switchToTab('shame');
            setTimeout(() => {
              const el = document.querySelector('.shame-card[data-id="' + r.key + '"]');
              if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight'); }
            }, 150);
          } else if (r.type === 'force') {
            switchToTab('forces');
            setTimeout(() => {
              const el = document.querySelector('.force-card[data-id="' + r.key + '"]');
              if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight'); }
            }, 150);
          } else if (r.type === 'wish') {
            switchToTab('wishlist');
            setTimeout(() => {
              const el = document.querySelector('.wishlist-card[data-id="' + r.key + '"]');
              if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.style.transition = 'background .3s'; el.style.background = '#3a2a08'; setTimeout(() => el.style.background = '', 700); }
            }, 200);
          }
        }, 40);
      }

      trigger.addEventListener('click', open);
      overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
      input.addEventListener('input', () => render(input.value));

      input.addEventListener('keydown', e => {
        if (e.key === 'Escape') { e.preventDefault(); close(); return; }
        if (!flatResults.length) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          selectedIdx = Math.min(flatResults.length - 1, selectedIdx + 1);
          highlightSelected();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          selectedIdx = Math.max(0, selectedIdx - 1);
          highlightSelected();
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (flatResults[selectedIdx]) jump(flatResults[selectedIdx]);
        }
      });

      listEl.addEventListener('click', e => {
        const btn = e.target.closest('.gs-result');
        if (!btn) return;
        const idx = parseInt(btn.dataset.idx, 10);
        if (flatResults[idx]) jump(flatResults[idx]);
      });

      // Keyboard shortcuts: Ctrl/Cmd+K or "/" to open, Esc to close
      document.addEventListener('keydown', e => {
        const isOpen = overlay.classList.contains('open');
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
          e.preventDefault();
          isOpen ? close() : open();
          return;
        }
        if (e.key === '/' && !isOpen) {
          const t = e.target;
          const typing = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable);
          if (typing) return;
          e.preventDefault();
          open();
          return;
        }
        if (e.key === 'Escape' && isOpen) {
          e.preventDefault();
          close();
        }
      });
    })();

    // ── PWA Install Prompt ────────────────────────────
    let deferredInstallPrompt = null;
    const installBanner = document.getElementById('install-banner');
    const installBtn = document.getElementById('install-btn');
    const installDismiss = document.getElementById('install-dismiss');

    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      deferredInstallPrompt = e;
      installBanner.classList.add('visible');
    });

    installBtn.addEventListener('click', async () => {
      if (!deferredInstallPrompt) return;
      deferredInstallPrompt.prompt();
      const {
        outcome
      } = await deferredInstallPrompt.userChoice;
      deferredInstallPrompt = null;
      installBanner.classList.remove('visible');
    });

    installDismiss.addEventListener('click', () => {
      installBanner.classList.remove('visible');
      deferredInstallPrompt = null;
    });

    window.addEventListener('appinstalled', () => {
      installBanner.classList.remove('visible');
      deferredInstallPrompt = null;
    });

    // Register service worker
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js');
    }

  </script>

  <div id="install-banner">
    <div class="install-banner-text">
      <strong>Install App</strong>Add to your home screen for quick access
    </div>
    <button id="install-btn">Install</button>
    <button id="install-dismiss" title="Dismiss">&times;</button>
  </div>

  <button id="back-to-top" title="Back to top">↑</button>
  <script>
    (function() {
      var btn = document.getElementById('back-to-top');
      window.addEventListener('scroll', function() {
        btn.style.display = window.scrollY > 200 ? 'flex' : 'none';
      }, { passive: true });
      btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    })();
  </script>
</body>

</html>