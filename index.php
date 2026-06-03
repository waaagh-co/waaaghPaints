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

$suppliesFile     = __DIR__ . '/data/supplies.json';
$hasSupplies      = file_exists($suppliesFile);
$suppliesData     = $hasSupplies ? (json_decode(file_get_contents($suppliesFile), true) ?? []) : [];
$suppliesDataJson = $hasSupplies ? json_encode($suppliesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]';

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
foreach ($models   as $m) {
  if (!empty($m['faction']))  $factionSet[trim($m['faction'])]  = true;
}
foreach ($planned  as $pl) {
  if (!empty($pl['faction'])) $factionSet[trim($pl['faction'])] = true;
}
if ($hasBench)   foreach ($benchData   as $b) {
  if (!empty($b['faction']))  $factionSet[trim($b['faction'])]  = true;
}
if ($hasRecipes) foreach ($recipesData as $r) {
  if (!empty($r['faction']))  $factionSet[trim($r['faction'])]  = true;
}
$factionCount = count($factionSet);
$hasFactions  = $factionCount > 0;

$wishlistFile     = __DIR__ . '/data/wishlist.json';
$hasWishlist      = file_exists($wishlistFile);
$wishlistData     = $hasWishlist ? (json_decode(file_get_contents($wishlistFile), true) ?? []) : [];
$wishlistDataJson = $hasWishlist ? json_encode($wishlistData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : '[]';

$battlesFile     = __DIR__ . '/data/battles.json';
$hasBattles      = file_exists($battlesFile);
$battlesData     = $hasBattles ? (json_decode(file_get_contents($battlesFile), true) ?? []) : [];
$battlesDataJson = $hasBattles ? json_encode($battlesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) : '[]';
$cnt_battles     = count($battlesData);
$latestBattle    = ($hasBattles && $cnt_battles > 0) ? $battlesData[0] : null;
$battleRecord    = ['w' => 0, 'l' => 0, 'd' => 0];
foreach ($battlesData as $_bh) { $battleRecord[$_bh['result'] === 'win' ? 'w' : ($_bh['result'] === 'loss' ? 'l' : 'd')]++; }

$rescuesFile  = __DIR__ . '/data/rescues.json';
$hasRescues   = file_exists($rescuesFile);
$rescuesData  = $hasRescues ? (json_decode(file_get_contents($rescuesFile), true) ?? []) : [];
$rescuesActive     = array_values(array_filter($rescuesData, fn($r) => empty($r['promoted_to'])));
$cnt_rescues       = count($rescuesActive);
$cnt_rescue_units  = array_sum(array_map(fn($r) => max(1, (int)($r['count'] ?? 1)), $rescuesActive));

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

// Waaagh! Meter: rolling 7-day momentum score
$_mWkAgo = date('Y-m-d', strtotime('-6 days'));
$_mScore = 0;
$_mModels = 0;
foreach ($models as $_mm) {
  foreach ($_mm['sessions'] ?? [] as $_ms) {
    if (($_ms['date'] ?? '') >= $_mWkAgo) $_mModels += max(1, (int)($_ms['count'] ?? 1));
  }
}
if ($_mModels > 0) { $_mScore += 3; if ($_mModels >= 3) $_mScore += 1; }
$_mBench = 0;
if ($hasBench) {
  foreach ($benchData as $_mb) {
    foreach ($_mb['sessions'] ?? [] as $_bs) {
      if (($_bs['date'] ?? '') >= $_mWkAgo) $_mBench++;
    }
  }
}
if ($_mBench > 0) { $_mScore += 2; if ($_mBench >= 3) $_mScore += 1; }
$hobbyMinutes = 0;
$pbMins = 0; $pbWeekLabel = '';
$hobbyStreak = 0;
if ($hasBench) {
  $_dailyMins = [];
  foreach ($benchData as $_mb2) {
    foreach ($_mb2['sessions'] ?? [] as $_bs2) {
      if (empty($_bs2['date'])) continue;
      $_ts = strtotime($_bs2['date']);
      if (($_bs2['date']) >= $_mWkAgo && isset($_bs2['duration'])) {
        $hobbyMinutes += (int)$_bs2['duration'];
      }
      $_m = isset($_bs2['duration']) ? (int)$_bs2['duration']
        : (in_array((int)date('w', $_ts), [0,6]) ? 180 : 90);
      $_dailyMins[$_bs2['date']] = ($_dailyMins[$_bs2['date']] ?? 0) + $_m;
    }
  }
  foreach ($_dailyMins as $_end => $_endMins) {
    $_wStart = date('Y-m-d', strtotime($_end . ' -6 days'));
    $_wTot = 0;
    foreach ($_dailyMins as $_d => $_dm) {
      if ($_d >= $_wStart && $_d <= $_end) $_wTot += $_dm;
    }
    if ($_wTot > $pbMins) {
      $pbMins = $_wTot;
      $pbWeekLabel = date('j M Y', strtotime($_wStart));
    }
  }
  unset($_dailyMins, $_end, $_endMins, $_wStart, $_wTot, $_d, $_dm, $_ts, $_m);
  $_sDates = [];
  foreach ($benchData as $_sb) { foreach ($_sb['sessions'] ?? [] as $_ss) { if (!empty($_ss['date'])) $_sDates[$_ss['date']] = true; } }
  $hobbyStreak = 0;
  $_sCheck = isset($_sDates[date('Y-m-d')]) ? date('Y-m-d') : date('Y-m-d', strtotime('-1 day'));
  while (isset($_sDates[$_sCheck])) { $hobbyStreak++; $_sCheck = date('Y-m-d', strtotime($_sCheck . ' -1 day')); }
  unset($_sDates, $_sCheck, $_sb, $_ss);
}
if ($hasJournal) {
  foreach ($journalData as $_mj) {
    if (($_mj['date'] ?? '') >= $_mWkAgo) { $_mScore += 1; break; }
  }
}
if ($hasBench && !empty($benchData)) {
  $_mActive = array_values(array_filter($benchData, fn($b) => ($b['stage'] ?? '') !== 'done'));
  if (!empty($_mActive)) {
    usort($_mActive, fn($a, $b) => strcmp($b['last_touched'] ?? '', $a['last_touched'] ?? ''));
    $_mTs = strtotime($_mActive[0]['last_touched'] ?? '');
    if ($_mTs) {
      $_mDays = floor((time() - $_mTs) / 86400);
      if      ($_mDays <= 1) $_mScore += 2;
      elseif  ($_mDays <= 3) $_mScore += 1;
      elseif  ($_mDays > 7)  $_mScore -= 1;
    }
  }
}
$meterScore = max(0, min(10, $_mScore));
if      ($meterScore <= 1) { $meterState = "DOZIN'";         $meterClass = 'meter-s0'; $meterPips = 1; }
elseif  ($meterScore <= 3) { $meterState = "STIRRIN'";       $meterClass = 'meter-s1'; $meterPips = 2; }
elseif  ($meterScore <= 5) { $meterState = "ON DA WARPATH";  $meterClass = 'meter-s2'; $meterPips = 3; }
elseif  ($meterScore <= 7) { $meterState = "WAAAGH!";        $meterClass = 'meter-s3'; $meterPips = 4; }
else                        { $meterState = "FULL WAAAGH!!"; $meterClass = 'meter-s4'; $meterPips = 5; }
// Build Dis Week summary for sidebar
$_mNotes   = 0;
$_mBattles = 0;
if ($hasJournal)  foreach ($journalData as $_mj2) { if (($_mj2['date']  ?? '') >= $_mWkAgo) $_mNotes++; }
if ($hasBattles)  foreach ($battlesData as $_mbh) { if (($_mbh['date']  ?? '') >= $_mWkAgo) $_mBattles++; }
$sidebarWkParts = [];
if ($_mModels  > 0) $sidebarWkParts[] = $_mModels  . ' model'         . ($_mModels  !== 1 ? 's' : '') . ' painted';
if ($_mBench   > 0) $sidebarWkParts[] = $_mBench   . ' bench session' . ($_mBench   !== 1 ? 's' : '');
if ($_mBattles > 0) $sidebarWkParts[] = $_mBattles . ' battle'        . ($_mBattles !== 1 ? 's' : '') . ' fought';
if ($_mNotes   > 0) $sidebarWkParts[] = $_mNotes   . ' note'          . ($_mNotes   !== 1 ? 's' : '') . ' scribbled';
$sidebarWkActive = !empty($sidebarWkParts);
unset($_mWkAgo, $_mScore, $_mModels, $_mBench, $_mm, $_ms, $_mb, $_bs, $_mj, $_mActive, $_mTs, $_mDays, $_mNotes, $_mBattles, $_mj2, $_mbh);

// === Wots Next? recommendations ===
$_wtnOwned = [];
foreach ($paints as $_wp) {
  if (($_wp['stock'] ?? '') !== 'out' && ($_wp['stock'] ?? '') !== 'wanted') {
    $_wtnOwned[strtolower($_wp['brand'] . '|' . $_wp['name'] . '|' . ($_wp['layer'] ?? ''))] = true;
    $_wtnOwned[strtolower($_wp['brand'] . '|' . $_wp['name'])] = true;
  }
}
// Card 1: RESCUE THIS - most neglected active bench project
$_wtnRescue = null;
if ($hasBench) {
  $_bActive = array_values(array_filter($benchData, fn($b) => ($b['stage'] ?? '') !== 'done' && empty($b['promoted_to'])));
  if (!empty($_bActive)) {
    usort($_bActive, fn($a, $b) => strcmp($a['last_touched'] ?? '', $b['last_touched'] ?? ''));
    $_b = $_bActive[0];
    $_days = max(0, (int)floor((strtotime('today') - strtotime($_b['last_touched'] ?? date('Y-m-d'))) / 86400));
    $_wtnRescue = ['type' => 'rescue', 'name' => $_b['name'], 'faction' => $_b['faction'] ?? '', 'stage' => $_b['stage'] ?? 'built', 'days' => $_days, 'thumb' => $_b['wip_images'][0] ?? null];
  }
}
// Card 2: READY TO START - planned scheme with all paints owned
$_wtnReady = null;
if (!empty($planned)) {
  foreach ($planned as $_pl) {
    if (!empty($_pl['promoted_to'])) continue;
    $_miss = 0;
    foreach (($_pl['colors'] ?? []) as $_c) { if (!isset($_wtnOwned[strtolower($_c)])) $_miss++; }
    if ($_miss === 0 && !empty($_pl['colors'])) {
      $_wtnReady = ['type' => 'ready', 'name' => $_pl['name'], 'faction' => $_pl['faction'] ?? '', 'count' => count($_pl['colors'])];
      break;
    }
  }
}
// Card 3: ALMOST THERE - force closest to target model count
$_wtnForce = null;
if ($hasForces) {
  $_mById = [];
  foreach ($models as $_m2) $_mById[$_m2['id']] = $_m2;
  $_bestPct = -1;
  foreach ($forcesData as $_f) {
    if (empty($_f['target_count'])) continue;
    $_painted = 0;
    foreach (($_f['models'] ?? []) as $_mid) {
      if (isset($_mById[$_mid])) $_painted += max(1, (int)($_mById[$_mid]['count'] ?? 1));
    }
    $_target = max(1, (int)$_f['target_count']);
    $_pct = min(100, (int)round($_painted / $_target * 100));
    if ($_pct < 100 && $_pct > $_bestPct) {
      $_bestPct = $_pct;
      $_fThumb = null;
      foreach (($_f['models'] ?? []) as $_fmid) {
        if (isset($_mById[$_fmid]['images'][0])) { $_fThumb = $_mById[$_fmid]['images'][0]; break; }
      }
      $_wtnForce = ['type' => 'force', 'name' => $_f['name'], 'painted' => $_painted, 'target' => $_target, 'pct' => $_bestPct, 'thumb' => $_fThumb];
    }
  }
}
// Card 4a: ONE SHOP AWAY - planned scheme with 1-2 missing paints (only when no ready scheme)
$_wtnAlmost = null;
if (!empty($planned) && $_wtnReady === null) {
  foreach ($planned as $_pl2) {
    if (!empty($_pl2['promoted_to'])) continue;
    $_missNames = [];
    foreach (($_pl2['colors'] ?? []) as $_c2) {
      if (!isset($_wtnOwned[strtolower($_c2)])) {
        $_parts2 = explode('|', $_c2);
        $_missNames[] = count($_parts2) >= 2 ? $_parts2[1] : $_c2;
      }
    }
    if (count($_missNames) >= 1 && count($_missNames) <= 2) {
      $_wtnAlmost = ['type' => 'almost', 'name' => $_pl2['name'], 'faction' => $_pl2['faction'] ?? '', 'missing' => $_missNames];
      break;
    }
  }
}
// Card 4b: BUILD IT - oldest pile of shame entry (fallback when no almost-ready scheme)
$_wtnShame = null;
if ($hasShame && $_wtnAlmost === null) {
  $_sActive = array_values(array_filter($shameData, fn($s) => empty($s['promoted_to'])));
  if (!empty($_sActive)) {
    usort($_sActive, fn($a, $b) => strcmp($a['acquired'] ?? '9999', $b['acquired'] ?? '9999'));
    $_s = $_sActive[0];
    $_acq = $_s['acquired'] ?? '';
    $_sLabel = '';
    if ($_acq) {
      $_sdiff = (date('Y') - (int)substr($_acq, 0, 4)) * 12 + (date('n') - (int)substr($_acq, 5, 2));
      $_sLabel = $_sdiff >= 12 ? floor($_sdiff / 12) . ' yr' . (floor($_sdiff / 12) > 1 ? 's' : '') : $_sdiff . ' mo';
    }
    $_wtnShame = ['type' => 'shame', 'name' => $_s['name'], 'faction' => $_s['faction'] ?? '', 'system' => $_s['system'] ?? '', 'sitting' => $_sLabel];
  }
}
// Card 4c: PRIME IT - oldest prepped rescue not yet promoted (replaces almost/shame when present)
$_wtnPrepped = null;
if ($hasRescues) {
  $_rPrepped = array_values(array_filter($rescuesActive, fn($r) => ($r['stage'] ?? '') === 'prepped'));
  if (!empty($_rPrepped)) {
    usort($_rPrepped, fn($a, $b) => strcmp($a['acquired'] ?? '9999', $b['acquired'] ?? '9999'));
    $_rp = $_rPrepped[0];
    $_wtnPrepped = ['type' => 'prepped', 'name' => $_rp['name'], 'faction' => $_rp['faction'] ?? '', 'count' => max(1, (int)($_rp['count'] ?? 1)), 'thumb' => $_rp['before_images'][0] ?? null];
  }
}
$wtnCards = array_values(array_filter([$_wtnRescue, $_wtnReady, $_wtnForce, $_wtnPrepped ?? ($_wtnAlmost ?? $_wtnShame)]));
$hasWtn = !empty($wtnCards);
unset($_wtnOwned, $_wp, $_bActive, $_b, $_days, $_pl, $_miss, $_c, $_mById, $_m2, $_bestPct, $_f, $_painted, $_target, $_pct, $_fThumb, $_fmid, $_mid, $_pl2, $_missNames, $_c2, $_parts2, $_sActive, $_s, $_acq, $_sLabel, $_sdiff, $_rPrepped, $_rp, $_wtnRescue, $_wtnReady, $_wtnForce, $_wtnAlmost, $_wtnShame, $_wtnPrepped);

// === COMMISSAR INTELLIGENCE ===
$ciShame = null; $ciSessions = null; $ciBottleneck = null;
$ciBattles = null; $ciForces = null; $ciSupply = []; $ciNextOrders = [];
$ci_paints_owned = 0; $ci_wanted = 0; $ci_low_out = 0; $ci_by_brand = []; $ci_top_paints = [];
if (!defined('SHOW_COMMISSAR') || SHOW_COMMISSAR) {
$_ciOwned = [];
foreach ($paints as $_p) {
  if (($_p['stock'] ?? '') !== 'wanted') {
    $_ciOwned[$_p['brand'].'|'.$_p['name'].'|'.($_p['layer'] ?? '')] = true;
    $_ciOwned[$_p['brand'].'|'.$_p['name']] = true;
  }
}
unset($_p);

$ci_paints_owned = count(array_filter($paints, fn($p) => ($p['stock'] ?? '') !== 'wanted'));
$ci_wanted       = count(array_filter($paints, fn($p) => ($p['stock'] ?? '') === 'wanted'));
$ci_low_out      = count(array_filter($paints, fn($p) => in_array($p['stock'] ?? '', ['low','out'])));
$ci_by_brand = [];
foreach ($paints as $_p2) {
  if (($_p2['stock'] ?? '') !== 'wanted') $ci_by_brand[$_p2['brand']] = ($ci_by_brand[$_p2['brand']] ?? 0) + 1;
}
arsort($ci_by_brand);
unset($_p2);
$_ciUsage = [];
foreach ($models as $_m3) foreach ($_m3['colors'] ?? [] as $_ck) $_ciUsage[$_ck] = ($_ciUsage[$_ck] ?? 0) + 1;
arsort($_ciUsage);
$ci_top_paints = array_slice($_ciUsage, 0, 8, true);
foreach (array_slice($_ciUsage, 0, 30, true) as $_sk => $_scnt) {
  $_sparts = explode('|', $_sk); $_sbrand = $_sparts[0] ?? ''; $_sname = $_sparts[1] ?? '';
  foreach ($paints as $_sp) {
    if ($_sp['brand'] === $_sbrand && $_sp['name'] === $_sname) {
      if (in_array($_sp['stock'] ?? '', ['low','out'])) $ciSupply[] = ['key'=>$_sk,'name'=>$_sname,'brand'=>$_sbrand,'uses'=>$_scnt,'stock'=>$_sp['stock']];
      break;
    }
  }
}
unset($_ciUsage, $_m3, $_ck, $_sk, $_scnt, $_sparts, $_sbrand, $_sname, $_sp);

if ($hasShame) {
  $_asArr = array_values(array_filter($shameData, fn($s) => empty($s['promoted_to'])));
  $_abBoxes = count($_asArr);
  $_abUnits = array_sum(array_map(fn($s) => max(1,(int)($s['count'] ?? 1)), $_asArr));
  $_allAcq = [];
  foreach ($shameData as $_ss2) { if (!empty($_ss2['acquired'])) $_allAcq[] = substr($_ss2['acquired'],0,7); }
  if (!empty($_allAcq)) {
    sort($_allAcq);
    $_span = max(1,(int)round(date_diff(date_create($_allAcq[0].'-01'),date_create(date('Y-m').'-01'))->days / 30.44));
    $_acqR = round(count($shameData) / $_span, 1);
  } else { $_span = 1; $_acqR = 0; }
  $_cmpR = round(count($models) / $_span, 1);
  $_netV = round($_acqR - $_cmpR, 1);
  $_mtc  = ($_netV <= 0 && $_cmpR > 0) ? (int)ceil($_abBoxes / $_cmpR) : null;
  $_stC  = ['sealed'=>0,'opened'=>0,'partial'=>0];
  foreach ($_asArr as $_sas) $_stC[$_sas['status'] ?? 'sealed']++;
  $ciShame = ['active_boxes'=>$_abBoxes,'active_units'=>$_abUnits,'acq_rate'=>$_acqR,'comp_rate'=>$_cmpR,'net_velocity'=>$_netV,'months_to_clear'=>$_mtc,'status'=>$_stC,'growing'=>$_netV > 0];
  unset($_asArr,$_abBoxes,$_abUnits,$_allAcq,$_ss2,$_span,$_acqR,$_cmpR,$_netV,$_mtc,$_stC,$_sas);
}

if ($hasBench) {
  $_sdow = array_fill(0,7,[]); $_cut30 = date('Y-m-d',strtotime('-30 days')); $_cut60 = date('Y-m-d',strtotime('-60 days'));
  $_r30c = 0; $_r30m = 0; $_p30c = 0;
  foreach ($benchData as $_sb3) {
    foreach ($_sb3['sessions'] ?? [] as $_bss) {
      if (empty($_bss['date']) || !isset($_bss['duration'])) continue;
      $_sdow[(int)date('w',strtotime($_bss['date']))][] = (int)$_bss['duration'];
      if ($_bss['date'] >= $_cut30) { $_r30c++; $_r30m += (int)$_bss['duration']; }
      elseif ($_bss['date'] >= $_cut60) $_p30c++;
    }
  }
  $_dn = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; $_da = [];
  foreach ($_sdow as $d => $mins) $_da[$d] = count($mins) > 0 ? (int)round(array_sum($mins)/count($mins)) : 0;
  $_mxA = max($_da ?: [0]); $_bdD = (int)array_search($_mxA, $_da);
  $ciSessions = ['dow_avgs'=>$_da,'dow_names'=>$_dn,'best_dow'=>$_bdD,'best_dow_name'=>$_dn[$_bdD],'best_dow_avg'=>$_mxA,'recent_count'=>$_r30c,'recent_mins'=>$_r30m,'prev_count'=>$_p30c,'trend_up'=>$_r30c >= $_p30c];
  unset($_sdow,$_cut30,$_cut60,$_r30c,$_r30m,$_p30c,$_sb3,$_bss,$_dn,$_da,$d,$mins,$_mxA,$_bdD);
  $_stOrd = ['built','primed','basecoated','washed','highlighted','based','varnished','done'];
  $_sDL = array_fill_keys($_stOrd,[]); $_sCurr = array_fill_keys($_stOrd,0);
  foreach ($benchData as $_bsb) {
    $stg = $_bsb['stage'] ?? 'built'; if (isset($_sCurr[$stg])) $_sCurr[$stg]++;
    $hist2 = $_bsb['history'] ?? []; if (empty($hist2)) continue;
    $pd = $_bsb['date_start'] ?? null; $ps = 'built';
    foreach ($hist2 as $_hh) {
      if ($pd && !empty($_hh['date'])) { $dys = max(0,(int)round((strtotime($_hh['date'])-strtotime($pd))/86400)); if ($dys < 365 && isset($_sDL[$ps])) $_sDL[$ps][] = $dys; }
      $pd = $_hh['date'] ?? $pd; $ps = $_hh['to'] ?? $ps;
    }
  }
  $_sAvg = []; foreach ($_sDL as $st2 => $dl) $_sAvg[$st2] = count($dl) > 0 ? round(array_sum($dl)/count($dl),1) : null;
  $_sAvgF = array_filter($_sAvg, fn($v) => $v !== null); arsort($_sAvgF);
  $_worst = !empty($_sAvgF) ? array_key_first($_sAvgF) : null;
  // Implied: avg days since date_start for projects currently at each stage (useful when no history)
  $_sImpl = array_fill_keys(['built','primed','basecoated','washed','highlighted','based','varnished','done'], []);
  $_tod = date('Y-m-d');
  foreach ($benchData as $_bib) {
    $stg2 = $_bib['stage'] ?? 'built'; $ds2 = $_bib['date_start'] ?? null;
    if ($ds2 && isset($_sImpl[$stg2])) { $di = max(0,(int)round((strtotime($_tod)-strtotime($ds2))/86400)); if ($di < 730) $_sImpl[$stg2][] = $di; }
  }
  $_sImplAvg = []; foreach ($_sImpl as $si => $dli) $_sImplAvg[$si] = count($dli) > 0 ? (int)round(array_sum($dli)/count($dli)) : null;
  $_sCurrActive = array_diff_key($_sCurr, ['done' => true]);
  arsort($_sCurrActive); $_modeStage = !empty($_sCurrActive) && max($_sCurrActive) > 0 ? array_key_first($_sCurrActive) : null;
  // Stalled: non-done projects untouched >= 7 days
  $_stalled = [];
  foreach ($benchData as $_bst) {
    if (($_bst['stage'] ?? 'built') === 'done' || !empty($_bst['promoted_to'])) continue;
    $lt2 = $_bst['last_touched'] ?? null;
    if (!$lt2) continue;
    $dstall = max(0,(int)round((strtotime($_tod)-strtotime($lt2))/86400));
    if ($dstall >= 7) $_stalled[] = ['name'=>$_bst['name'],'stage'=>$_bst['stage']??'built','days'=>$dstall];
  }
  usort($_stalled, fn($a,$b) => $b['days']-$a['days']);
  $ciBottleneck = ['stage_avgs'=>$_sAvg,'worst_stage'=>$_worst,'worst_days'=>$_worst ? ($_sAvgF[$_worst] ?? null) : null,'current_counts'=>$_sCurr,'implied_avgs'=>$_sImplAvg,'mode_stage'=>$_modeStage,'stalled'=>array_slice($_stalled,0,4)];
  unset($_stOrd,$_sDL,$_sCurr,$_bsb,$stg,$hist2,$pd,$ps,$_hh,$dys,$_sAvg,$st2,$dl,$_sAvgF,$_worst,$_sImpl,$_tod,$_bib,$stg2,$ds2,$di,$_sImplAvg,$si,$dli,$_sCurrActive,$_modeStage,$_stalled,$_bst,$lt2,$dstall);
}

if ($hasBattles && !empty($battlesData)) {
  $_bBA = []; $_bVA = [];
  foreach ($battlesData as $_bt2) {
    $rk2 = $_bt2['result'] === 'win' ? 'w' : ($_bt2['result'] === 'loss' ? 'l' : 'd');
    $arm2 = $_bt2['my_army'] ?? 'Unknown'; if (!isset($_bBA[$arm2])) $_bBA[$arm2] = ['w'=>0,'l'=>0,'d'=>0]; $_bBA[$arm2][$rk2]++;
    $opp2 = $_bt2['opponent_army'] ?? 'Unknown'; if (!isset($_bVA[$opp2])) $_bVA[$opp2] = ['w'=>0,'l'=>0,'d'=>0]; $_bVA[$opp2][$rk2]++;
  }
  $_aWR = [];
  foreach ($_bBA as $ak => $rec) { $tot = array_sum($rec); $_aWR[$ak] = $tot > 0 ? round($rec['w']/$tot*100) : 0; }
  arsort($_aWR);
  $ciBattles = ['total'=>$cnt_battles,'record'=>$battleRecord,'by_army'=>$_bBA,'vs_army'=>$_bVA,'best_army'=>!empty($_aWR) ? array_key_first($_aWR) : null,'army_wr'=>$_aWR];
  unset($_bBA,$_bVA,$_bt2,$rk2,$arm2,$opp2,$_aWR,$ak,$rec,$tot);
}

if ($hasForces && !empty($forcesData)) {
  $_fmM = []; foreach ($models as $_fm) $_fmM[$_fm['id']] = $_fm;
  $_fcC = date('Y-m-d',strtotime('-30 days')); $_fcR = 0;
  foreach ($models as $_fm2) { foreach ($_fm2['sessions'] ?? [] as $_fms) { if (($_fms['date'] ?? '') >= $_fcC) $_fcR += max(1,(int)($_fms['count'] ?? 1)); } }
  $_mpw = max(0.1, $_fcR / 4.3);
  $ciForces = [];
  foreach ($forcesData as $_ffc) {
    $_tgt2 = (int)($_ffc['target_count'] ?? 0); if ($_tgt2 <= 0) continue;
    $_pnt2 = 0; foreach ($_ffc['models'] ?? [] as $_fid) { if (isset($_fmM[$_fid])) $_pnt2 += max(1,(int)($_fmM[$_fid]['count'] ?? 1)); }
    $_rem2 = max(0,$_tgt2-$_pnt2); $_pct3 = min(100,(int)round($_pnt2/$_tgt2*100));
    $_wl2 = $_mpw > 0 ? (int)ceil($_rem2/$_mpw) : null; $_cd2 = $_wl2 ? date('j M Y',strtotime("+{$_wl2} weeks")) : null;
    $ciForces[] = ['id'=>$_ffc['id'],'name'=>$_ffc['name'],'painted'=>$_pnt2,'target'=>$_tgt2,'remaining'=>$_rem2,'pct'=>$_pct3,'weeks_left'=>$_wl2,'completion_date'=>$_cd2];
  }
  usort($ciForces, fn($a,$b) => $b['pct'] - $a['pct']);
  unset($_fmM,$_fm,$_fcC,$_fcR,$_fm2,$_fms,$_mpw,$_ffc,$_tgt2,$_pnt2,$_fid,$_rem2,$_pct3,$_wl2,$_cd2);
}

$_stSc = ['varnished'=>10,'based'=>8,'highlighted'=>6,'washed'=>4,'basecoated'=>3,'primed'=>2,'built'=>1,'done'=>0];
if ($hasBench) {
  foreach ($benchData as $_bno) {
    if (!empty($_bno['promoted_to'])) continue;
    $noSc = $_stSc[$_bno['stage'] ?? 'built'] ?? 1; $noRs = [];
    if ($noSc >= 8) $noRs[] = 'nearly done';
    $_noM = 0;
    foreach ($_bno['colors'] ?? [] as $_nc) { $_nc2 = implode('|',array_slice(explode('|',$_nc),0,2)); if (!isset($_ciOwned[$_nc]) && !isset($_ciOwned[$_nc2])) $_noM++; }
    if ($_noM === 0 && !empty($_bno['colors'])) { $noSc += 5; $noRs[] = 'all paints owned'; } elseif ($_noM > 0) $noSc -= 2;
    $_dS = !empty($_bno['last_touched']) ? max(0,(int)floor((time()-strtotime($_bno['last_touched']))/86400)) : 0;
    if ($_dS > 30) { $noSc += 3; $noRs[] = 'neglected '.$_dS.'d'; } elseif ($_dS < 7) { $noSc += 1; $noRs[] = 'active'; }
    $ciNextOrders[] = ['type'=>'bench','id'=>$_bno['id'],'name'=>$_bno['name'],'stage'=>$_bno['stage']??'built','score'=>$noSc,'reasons'=>$noRs];
  }
}
foreach ($planned as $_pno) {
  if (!empty($_pno['promoted_to']) || empty($_pno['colors'])) continue;
  $_pnM = 0; foreach ($_pno['colors'] as $_nc3) { $_nc4 = implode('|',array_slice(explode('|',$_nc3),0,2)); if (!isset($_ciOwned[$_nc3]) && !isset($_ciOwned[$_nc4])) $_pnM++; }
  if ($_pnM > 2) continue;
  $pnSc = $_pnM === 0 ? 8 : ($_pnM === 1 ? 5 : 3); $pnRs = $_pnM === 0 ? ['fully stocked'] : [$_pnM.' paint'.($_pnM>1?'s':'').' away'];
  $ciNextOrders[] = ['type'=>'planned','id'=>$_pno['id'],'name'=>$_pno['name'],'stage'=>'planned','score'=>$pnSc,'reasons'=>$pnRs];
}
usort($ciNextOrders, fn($a,$b) => $b['score'] - $a['score']);
$ciNextOrders = array_slice($ciNextOrders,0,5);
unset($_ciOwned,$_stSc,$_bno,$noSc,$noRs,$_noM,$_nc,$_nc2,$_dS,$_pno,$_pnM,$_nc3,$_nc4,$pnSc,$pnRs);
} // end SHOW_COMMISSAR

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

if (($_POST['action'] ?? '') === 'track_tab') {
  header('Content-Type: application/json');
  $tab     = trim($_POST['tab'] ?? '');
  $allowed = ['contents', 'inventory', 'brushes', 'supplies', 'gallery', 'factions', 'equiv', 'recipes', 'planned', 'bench', 'shame', 'wd', 'books', 'journals'];
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
  <script>
  (function() {
    var GA_ID = '<?= htmlspecialchars(GA4_ID) ?>';
    function loadGA4() {
      var s = document.createElement('script');
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
      document.head.appendChild(s);
      window.dataLayer = window.dataLayer || [];
      window.gtag = function(){ dataLayer.push(arguments); };
      gtag('js', new Date());
      gtag('config', GA_ID);
    }
    var consent = localStorage.getItem('ga_consent');
    if (consent === 'yes') { loadGA4(); }
    else if (consent !== 'no') {
      document.addEventListener('DOMContentLoaded', function() {
        var b = document.getElementById('cookie-banner');
        if (b) b.style.display = 'flex';
      });
    }
    window._cookieAccept = function() {
      localStorage.setItem('ga_consent', 'yes');
      document.getElementById('cookie-banner').style.display = 'none';
      loadGA4();
    };
    window._cookieDecline = function() {
      localStorage.setItem('ga_consent', 'no');
      document.getElementById('cookie-banner').style.display = 'none';
    };
  })();
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
  <meta name="description" content="Personal Warhammer hobby manager - paint inventory with restock tracking, painted scheme gallery, step-by-step recipes, workbench progress, battle log, and force rosters. Flat-file PHP, no database, self-hostable.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars(SITE_URL) ?>">
  <meta property="og:title" content="Waaagh! Paint Collection">
  <meta property="og:description" content="Personal Warhammer hobby manager - paint inventory with restock tracking, painted scheme gallery, step-by-step recipes, workbench progress, battle log, and force rosters. Flat-file PHP, no database, self-hostable.">
  <meta property="og:image" content="<?= htmlspecialchars(SITE_URL) ?>img/logo_sm.png">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="Waaagh! Paint Collection">
  <meta name="twitter:description" content="Personal Warhammer hobby manager - paint inventory with restock tracking, painted scheme gallery, step-by-step recipes, workbench progress, battle log, and force rosters. Flat-file PHP, no database, self-hostable.">
  <meta name="twitter:image" content="<?= htmlspecialchars(SITE_URL) ?>img/logo_sm.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Caveat:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=112">
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Waaagh! Paint Collection",
      "url": "<?= htmlspecialchars(SITE_URL) ?>",
      "description": "Personal Warhammer 40k hobby paint collection tracker - model gallery, recipes, workbench, and codex reference library."
    }
  </script>
</head>

<body class="has-sidebar">

  <!-- Mobile nav toggle -->
  <button id="sidebar-toggle" aria-label="Open navigation">&#9776;</button>
  <div id="sidebar-backdrop"></div>

  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
      <a href="#" id="sidebar-logo-link" aria-label="Go to home"><img src="img/logo_sm.png" alt="Waaagh! Paint" class="sidebar-logo"></a>
      <button class="sidebar-collapse-btn" id="sidebar-collapse-btn" aria-label="Collapse sidebar">&#8249;</button>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-home">
        <a href="#" data-tab="contents" class="active">Looted Knowledge</a>
      </div>

      <div class="sidebar-group" data-group="pipeline">
        <button class="sg-header" type="button"><span class="sg-arrow">&#9660;</span>The Pipeline</button>
        <ul class="sg-items">
          <?php if ($hasRecipes): ?><li class="sg-item"><a href="#" data-tab="recipes">Recipes</a></li><?php endif; ?>
          <li class="sg-item"><a href="#" data-tab="gallery">Paint Schemes</a></li>
          <li class="sg-item"><a href="#" data-tab="planned">Planned</a></li>
          <?php if ($hasBench): ?><li class="sg-item"><a href="#" data-tab="bench">On the Bench</a></li><?php endif; ?>
          <?php if ($hasRescues): ?><li class="sg-item"><a href="#" data-tab="rescues">Rescue Tracker</a></li><?php endif; ?>
        </ul>
      </div>

      <div class="sidebar-group" data-group="armies">
        <button class="sg-header" type="button"><span class="sg-arrow">&#9660;</span>Your Armies</button>
        <ul class="sg-items">
          <li class="sg-item"><a href="#" data-tab="factions">Factions</a></li>
          <?php if ($hasForces): ?><li class="sg-item"><a href="#" data-tab="forces">Forces</a></li><?php endif; ?>
          <?php if ($hasBattles): ?><li class="sg-item"><a href="#" data-tab="battles">Battle Honours</a></li><?php endif; ?>
          <?php if (!defined('SHOW_COMMISSAR') || SHOW_COMMISSAR): ?><li class="sg-item"><a href="#" data-tab="commissar">&#9876; The Commissar</a></li><?php endif; ?>
        </ul>
      </div>

      <div class="sidebar-group" data-group="workbench">
        <button class="sg-header" type="button"><span class="sg-arrow">&#9660;</span>The Workbench</button>
        <ul class="sg-items">
          <li class="sg-item"><a href="#" data-tab="inventory">Paint Inventory</a></li>
          <?php if ($hasBrushes): ?><li class="sg-item"><a href="#" data-tab="brushes">Brushes</a></li><?php endif; ?>
          <?php if ($hasSupplies): ?><li class="sg-item"><a href="#" data-tab="supplies">Supplies</a></li><?php endif; ?>
          <?php if ($hasShame): ?><li class="sg-item"><a href="#" data-tab="shame">Pile of Shame</a></li><?php endif; ?>
          <?php if ($hasWishlist): ?><li class="sg-item"><a href="#" data-tab="wishlist">Wishlist</a></li><?php endif; ?>
        </ul>
      </div>

      <div class="sidebar-group" data-group="library">
        <button class="sg-header" type="button"><span class="sg-arrow">&#9660;</span>The Library</button>
        <ul class="sg-items">
          <li class="sg-item"><a href="#" data-tab="equiv">Equivalency</a></li>
          <?php if ($hasBooks): ?><li class="sg-item"><a href="#" data-tab="books">Codices</a></li><?php endif; ?>
          <?php if ($hasJournal): ?><li class="sg-item"><a href="#" data-tab="journals">Scrap Notes</a></li><?php endif; ?>
        </ul>
      </div>
      <div class="nav-fade" id="nav-fade"></div>
    </nav>
    <?php if (defined('TIP_JAR_URL') && TIP_JAR_URL !== ''): ?>
    <a class="sidebar-tipjar" href="<?= htmlspecialchars(TIP_JAR_URL) ?>" target="_blank" rel="noopener noreferrer">
      <img src="img/tipz.png" alt="★ Chuck a Teef in da Pot">
    </a>
    <?php endif; ?>
    <div class="sidebar-footer">
      <button id="gs-trigger" type="button" title="Search everything (Ctrl+K or /)" aria-label="Open global search">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7" />
          <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
        <span class="gs-label">Search All</span>
        <span class="gs-hint">Ctrl+K</span>
      </button>
      <p class="sidebar-disclaimer">Personal hobby journal &mdash; not an official or commercial resource.</p>
    </div>
  </aside>

  <!-- Main content -->
  <div class="main-content">

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

  <?php if ($hasSupplies): ?>
    <div id="tab-supplies" class="tab-panel">
      <div id="supply-controls">
        <a class="tab-label" href="#" onclick="copyTabLink(event,'supplies')" title="Copy link to this tab">Supplies</a>
        <input type="search" id="supply-search" class="tab-search" placeholder="Search name, brand, type, notes&hellip;" autocomplete="off">
        <div class="supply-filter-pills" id="supply-filter-pills">
          <button class="supply-filter-pill active" data-cond="all">All</button>
          <button class="supply-filter-pill" data-cond="prime">Prime</button>
          <button class="supply-filter-pill" data-cond="workhorse">Workhorse</button>
          <button class="supply-filter-pill" data-cond="retired">Retired</button>
        </div>
        <span id="supply-count"></span>
      </div>
      <p class="tab-blurb">Palettes, mats, lamps, and the rest of the kit. Everything that keeps the session running.</p>
      <div id="supply-list"></div>
      <div id="supply-empty" class="hidden">No supplies yet - add one in admin.</div>
    </div><!-- #tab-supplies -->
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
        <option value="Old World">Old World</option>
        <option value="Kill Team">Kill Team</option>
        <option value="Blood Bowl">Blood Bowl</option>
        <option value="Necromunda">Necromunda</option>
        <option value="Epic">Epic</option>
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
        <img src="<?= htmlspecialchars(defined('HERO_IMAGE') ? HERO_IMAGE : 'img/looted.png') ?>" alt="Looted Knowledge" class="contents-title-img">
        <p class="contents-tagline"><?= htmlspecialchars(defined('SITE_TAGLINE') ? SITE_TAGLINE : "Dis is not a blog. It's a paintin' tool. Use it. Steal from it. Ignore half of it.") ?></p>
      </div>

      <?php
      // Counts for the contents page (computed inline; cheap)
      $cnt_paints   = count($paints);
      $cnt_owned    = count(array_filter($paints, fn($p) => ($p['stock'] ?? '') !== 'wanted'));
      $cnt_low_out  = count(array_filter($paints, fn($p) => in_array($p['stock'] ?? '', ['low', 'out'], true)));
      $cnt_wanted   = count(array_filter($paints, fn($p) => ($p['stock'] ?? '') === 'wanted'));
      $cnt_models   = count($models);
      $cnt_models_painted = array_sum(array_map(fn($m) => max(1, (int)($m['count'] ?? 1)), $models));
      $cnt_planned  = count($planned);
      $cnt_brushes   = $hasBrushes  ? count(array_filter($brushesData,  fn($b) => ($b['condition'] ?? 'prime') !== 'retired')) : 0;
      $cnt_supplies  = $hasSupplies ? count(array_filter($suppliesData, fn($s) => ($s['condition'] ?? 'prime') !== 'retired')) : 0;
      $cnt_bench    = $hasBench   ? count(array_filter($benchData, fn($b) => ($b['stage'] ?? 'built') !== 'done')) : 0;
      $cnt_recipes  = $hasRecipes ? count($recipesData) : 0;
      $cnt_books    = $hasBooks   ? count($booksData) : 0;
      $cnt_journal  = $hasJournal ? count($journalData) : 0;
      $cnt_hex      = count(array_filter($paints, fn($p) => !empty($p['hex'])));
      $cnt_eq       = count($conversionsData);

      // === MILESTONES ===
      $_msTotalSessions = 0;
      if ($hasBench) { foreach ($benchData as $_msb) { $_msTotalSessions += count($_msb['sessions'] ?? []); } }
      $_msShameAll   = $hasShame   ? count($shameData)   : 0;
      $_msBattleAll  = $hasBattles ? count($battlesData) : 0;
      $_msRecipeAll  = $hasRecipes ? count($recipesData) : 0;
      $_msBenchDone  = $hasBench   ? count(array_filter($benchData, fn($b) => ($b['stage'] ?? '') === 'done' || !empty($b['promoted_to']))) : 0;
      $_msForceAll   = $hasForces  ? count($forcesData)  : 0;
      $_msJournalAll = $hasJournal ? count($journalData) : 0;
      $_msBookAll    = $hasBooks   ? count($booksData)   : 0;
      $_msRescueAll  = isset($rescuesData) ? count($rescuesData) : 0;
      $_msDefs = [
        // Models painted
        ['cat'=>'brush',   'name'=>'First Coat',            'req'=>$cnt_models_painted, 'val'=>1,   'flavor'=>'Every legend begins with a single brushstroke.'],
        ['cat'=>'brush',   'name'=>'Squad Ready',            'req'=>$cnt_models_painted, 'val'=>10,  'flavor'=>'A fire team, blooded and ready.'],
        ['cat'=>'brush',   'name'=>'Platoon Strength',       'req'=>$cnt_models_painted, 'val'=>25,  'flavor'=>"They've started calling you Sergeant."],
        ['cat'=>'brush',   'name'=>'Full Company',           'req'=>$cnt_models_painted, 'val'=>50,  'flavor'=>'The muster rolls grow longer. Good.'],
        ['cat'=>'brush',   'name'=>'Crusade Force',          'req'=>$cnt_models_painted, 'val'=>100, 'flavor'=>"The Emperor's work is never done."],
        ['cat'=>'brush',   'name'=>'Legionary',              'req'=>$cnt_models_painted, 'val'=>250, 'flavor'=>'Numbers that would make a Space Marine jealous.'],
        ['cat'=>'brush',   'name'=>'Daemon Prince',          'req'=>$cnt_models_painted, 'val'=>500, 'flavor'=>'You have ascended beyond mortal patience.'],
        // Bench sessions
        ['cat'=>'time',    'name'=>'First Blood',            'req'=>$_msTotalSessions,   'val'=>1,   'flavor'=>'The brushes are wet. Begin.'],
        ['cat'=>'time',    'name'=>'On Campaign',            'req'=>$_msTotalSessions,   'val'=>10,  'flavor'=>"You don't just talk about painting."],
        ['cat'=>'time',    'name'=>'Veteran Painter',        'req'=>$_msTotalSessions,   'val'=>25,  'flavor'=>'Your palms have permanent paint stains. Worthy.'],
        ['cat'=>'time',    'name'=>'Iron Will',              'req'=>$_msTotalSessions,   'val'=>50,  'flavor'=>'Fifty sessions. No excuses. No quarter.'],
        ['cat'=>'time',    'name'=>'Legendary Hobbyist',     'req'=>$_msTotalSessions,   'val'=>100, 'flavor'=>'A hundred sessions. The hobby gods have noticed.'],
        // Bench completions
        ['cat'=>'done',    'name'=>'First Done',             'req'=>$_msBenchDone,       'val'=>1,   'flavor'=>'One project sees the table. The rest watch jealously.'],
        ['cat'=>'done',    'name'=>'Workshop Foreman',       'req'=>$_msBenchDone,       'val'=>5,   'flavor'=>'Five finished. The bench respects you now.'],
        ['cat'=>'done',    'name'=>'Relentless',             'req'=>$_msBenchDone,       'val'=>10,  'flavor'=>'Ten projects to the table. The pile fears you.'],
        ['cat'=>'done',    'name'=>'Obsessed',               'req'=>$_msBenchDone,       'val'=>25,  'flavor'=>'Twenty-five done. There is no cure.'],
        // Pile of Shame
        ['cat'=>'shame',   'name'=>'Acquired Targets',       'req'=>$_msShameAll,        'val'=>1,   'flavor'=>"It'll get painted. Eventually."],
        ['cat'=>'shame',   'name'=>'Notorious Collector',    'req'=>$_msShameAll,        'val'=>10,  'flavor'=>"The pile has developed its own personality."],
        ['cat'=>'shame',   'name'=>'Hoarder Extraordinaire', 'req'=>$_msShameAll,        'val'=>25,  'flavor'=>"Your wallet has filed a formal complaint."],
        // Rescues
        ['cat'=>'rescue',  'name'=>'First Rescue',           'req'=>$_msRescueAll,       'val'=>1,   'flavor'=>'Saved from eBay. Honour restored.'],
        ['cat'=>'rescue',  'name'=>'Salvage Expert',         'req'=>$_msRescueAll,       'val'=>5,   'flavor'=>'Five rescues. The recycling continues.'],
        // Recipes
        ['cat'=>'recipe',  'name'=>'Codified Doctrine',      'req'=>$_msRecipeAll,       'val'=>1,   'flavor'=>"If it isn't written down, it didn't happen."],
        ['cat'=>'recipe',  'name'=>'Artisan',                'req'=>$_msRecipeAll,       'val'=>5,   'flavor'=>'Five recipes. Method over madness.'],
        ['cat'=>'recipe',  'name'=>'Master Artificer',       'req'=>$_msRecipeAll,       'val'=>10,  'flavor'=>'Your methods are legend. Teach them to no one.'],
        // Battles
        ['cat'=>'battle',  'name'=>'Deployed',               'req'=>$_msBattleAll,       'val'=>1,   'flavor'=>'The dice have been cast. No retreat.'],
        ['cat'=>'battle',  'name'=>'Blooded',                'req'=>$_msBattleAll,       'val'=>10,  'flavor'=>"Ten engagements. You're not new anymore."],
        ['cat'=>'battle',  'name'=>'Field Commander',        'req'=>$_msBattleAll,       'val'=>25,  'flavor'=>'Twenty-five battles. Tactics forged in blood.'],
        // Forces
        ['cat'=>'force',   'name'=>'First Muster',           'req'=>$_msForceAll,        'val'=>1,   'flavor'=>'The roster is open. Fill it.'],
        ['cat'=>'force',   'name'=>'Fully Deployed',         'req'=>$_msForceAll,        'val'=>3,   'flavor'=>'Three forces ready to take the field.'],
        // Scrap Notes
        ['cat'=>'journal', 'name'=>'Field Notes',            'req'=>$_msJournalAll,      'val'=>1,   'flavor'=>'The campaign log begins.'],
        ['cat'=>'journal', 'name'=>'War Chronicler',         'req'=>$_msJournalAll,      'val'=>25,  'flavor'=>"Twenty-five entries. The lore grows."],
        // Codices
        ['cat'=>'book',    'name'=>'Scholar',                'req'=>$_msBookAll,         'val'=>1,   'flavor'=>'Knowledge is power. Guard it well.'],
        ['cat'=>'book',    'name'=>'Librarian',              'req'=>$_msBookAll,         'val'=>5,   'flavor'=>'Five volumes. A proper collection.'],
        // Paints owned
        ['cat'=>'paint',   'name'=>'Well Stocked',           'req'=>$cnt_owned,          'val'=>50,  'flavor'=>'A respectable armoury.'],
        ['cat'=>'paint',   'name'=>'Paint Addict',           'req'=>$cnt_owned,          'val'=>100, 'flavor'=>'You tell yourself you have enough. You are wrong.'],
        ['cat'=>'paint',   'name'=>'Full Armoury',           'req'=>$cnt_owned,          'val'=>200, 'flavor'=>'Your pigment collection rivals a manufactorum.'],
        ['cat'=>'paint',   'name'=>'Impossible Standard',    'req'=>$cnt_owned,          'val'=>300, 'flavor'=>'No one needs this many yellows. No one.'],
      ];
      $milestones   = array_map(fn($d) => $d + ['unlocked' => $d['req'] >= $d['val']], $_msDefs);
      $_msUnlocked  = count(array_filter($milestones, fn($m) => $m['unlocked']));
      unset($_msDefs, $_msb, $_msTotalSessions, $_msShameAll, $_msBattleAll, $_msRecipeAll,
            $_msBenchDone, $_msForceAll, $_msJournalAll, $_msBookAll, $_msRescueAll);

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
        if (empty($_pl['colors'])) {
          $cnt_ready++;
          continue;
        }
        $allOwned = true;
        foreach ($_pl['colors'] as $_c) {
          if (!isset($ownedKeys[$_c])) {
            $parts2 = explode('|', $_c);
            $k2 = $parts2[0] . '|' . ($parts2[1] ?? '');
            if (!isset($ownedKeys[$k2])) {
              $allOwned = false;
              break;
            }
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
          elseif ($days === 1.0) $touchedAgo = 'Touched yesterday';
          elseif ($days < 7)    $touchedAgo = 'Touched ' . (int)$days . ' days ago';
          elseif ($days < 30)   $touchedAgo = 'Touched ' . (int)floor($days / 7) . ' week' . (floor($days / 7) !== 1.0 ? 's' : '') . ' ago';
          else                  $touchedAgo = 'Touched ' . (int)floor($days / 30) . ' month' . (floor($days / 30) !== 1.0 ? 's' : '') . ' ago';
        }
      }

      $bnImg   = '';
      $bnStage = 'built';
      if ($latestBench) {
        $bnImgs  = $latestBench['wip_images'] ?? [];
        $bnImg   = !empty($bnImgs) ? $bnImgs[count($bnImgs) - 1] : '';
        $bnStage = $latestBench['stage'] ?? 'built';
      }

      $benchStageLabels = [
        'built' => 'Built',
        'primed' => 'Primed',
        'basecoated' => 'Basecoated',
        'washed' => 'Washed',
        'highlighted' => 'Highlighted',
        'based' => 'Based',
        'varnished' => 'Varnished',
        'done' => 'Done',
      ];

      $jnSnipFull     = '';
      $showNotesPanel = false;
      if ($hasJournal && !empty($journalData)) {
        $_jnRaw     = preg_replace('/@\[(?:\w+):[^\]|]+\|([^\]]+)\]/', '$1', $journalData[0]['body'] ?? '');
        $jnSnipFull = function_exists('mb_strimwidth') ? mb_strimwidth($_jnRaw, 0, 230, '…') : (strlen($_jnRaw) > 230 ? substr($_jnRaw, 0, 230) . '…' : $_jnRaw);
        $showNotesPanel = !empty($jnSnipFull);
        unset($_jnRaw);
      }
      ?>

      <div class="dct-panel" id="daily-colour-card" style="display:none"></div>

      <div class="pipeline-notes-row">
        <div class="pipeline-band">
          <?php if ($curYearGoal > 0): ?>
            <div class="hero-goal-strip pipeline-goal-strip">
              <span class="hero-goal-label"><?= $curYear ?> Goal</span>
              <div class="hero-goal-bar-wrap">
                <div class="hero-goal-bar-fill" style="width:<?= $goalPct ?>%"></div>
              </div>
              <span class="hero-goal-num"><?= $curYearCount ?> / <?= $curYearGoal ?></span>
              <span class="hero-goal-pct<?= $goalPct >= 100 ? ' goal-complete' : '' ?>"><?= $goalPct ?>%<?= $goalPct >= 100 ? ' &#10003;' : '' ?></span>
            </div>
          <?php endif; ?>
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
            <?php if ($hasRescues && $cnt_rescue_units > 0): ?>
              <div class="pipeline-arrow">&#8594;</div>
              <a class="pipeline-node" data-jump="rescues">
                <div class="pipeline-node-name">Rescue</div>
                <div class="pipeline-node-num"><?= $cnt_rescue_units ?></div>
                <div class="pipeline-node-blurb">unit<?= $cnt_rescue_units !== 1 ? 's' : '' ?> in prep</div>
              </a>
            <?php endif; ?>
            <?php if ($latestBench): ?>
              <a class="pipeline-node pipeline-bench-node" data-jump="bench" title="Jump to On the Bench">
                <div class="pipeline-node-name">Under da Brush</div>
                <div class="pipeline-bench-main">
                  <?php if ($bnImg): ?><div class="pipeline-bench-thumb" style="background-image:url('<?= htmlspecialchars($bnImg, ENT_QUOTES) ?>')"></div><?php endif; ?>
                  <div class="pipeline-bench-info">
                    <div class="pipeline-bench-pname"><?= htmlspecialchars($latestBench['name'], ENT_QUOTES) ?></div>
                    <?php if (!empty($latestBench['faction'])): ?><div class="pipeline-bench-faction"><?= htmlspecialchars($latestBench['faction'], ENT_QUOTES) ?></div><?php endif; ?>
                    <div class="pipeline-bench-meta"><span class="bench-stage-label stage-<?= htmlspecialchars($bnStage, ENT_QUOTES) ?>"><?= htmlspecialchars($benchStageLabels[$bnStage] ?? $bnStage, ENT_QUOTES) ?></span><?php if ($touchedAgo): ?><span class="hero-bench-touched"><?= htmlspecialchars($touchedAgo, ENT_QUOTES) ?></span><?php endif; ?></div>
                  </div>
                </div>
              </a>
            <?php endif; ?>
          </div>
          <?php if ($latestBattle): ?>
            <?php $bhRes = $latestBattle['result'] ?? 'draw'; ?>
            <a class="pipeline-battle-strip" data-jump="battles" title="Jump to Battle Honours">
              <span class="pipeline-battle-label">Last Battle<?= !empty($latestBattle['date']) ? ' &middot; ' . htmlspecialchars($latestBattle['date']) : '' ?></span>
              <span class="bh-result-badge bh-result-<?= $bhRes ?>"><?= ucfirst($bhRes) ?></span>
              <?php $bhOpp = !empty($latestBattle['opponent']) ? $latestBattle['opponent'] : (!empty($latestBattle['opponent_army']) ? $latestBattle['opponent_army'] : null); ?>
              <?php if (!empty($latestBattle['my_army']) || $bhOpp): ?>
                <span class="pipeline-battle-matchup"><?= htmlspecialchars($latestBattle['my_army'] ?? '?') ?> vs <?= htmlspecialchars($bhOpp ?? '?') ?></span>
              <?php endif; ?>
              <span class="pipeline-battle-record"><?= $battleRecord['w'] ?>W <?= $battleRecord['l'] ?>L <?= $battleRecord['d'] ?>D</span>
            </a>
          <?php endif; ?>
        </div>
        <?php if ($showNotesPanel): ?>
          <a class="hero-notes-panel" data-jump="journals" title="Open Scrap Notes">
            <div class="hero-notes-text"><?= htmlspecialchars($jnSnipFull) ?></div>
          </a>
        <?php endif; ?>
      </div>

      <?php if ($hasWtn): ?>
      <div class="wtn-panel">
        <div class="wtn-header">
          <span class="wtn-title">Wots Next?</span>
          <span class="wtn-sub">based on everything you've got on</span>
        </div>
        <div class="wtn-cards">
          <?php foreach ($wtnCards as $_wc): ?>
          <?php if ($_wc['type'] === 'rescue'): ?>
          <a class="wtn-card wtn-card-rescue" data-jump="bench">
            <?php if (!empty($_wc['thumb'])): ?><div class="wtn-thumb" style="background-image:url('<?= htmlspecialchars($_wc['thumb']) ?>')"></div><?php endif; ?>
            <div class="wtn-body">
              <div class="wtn-type">Rescue This</div>
              <div class="wtn-name"><?= htmlspecialchars($_wc['name']) ?></div>
              <?php if (!empty($_wc['faction'])): ?><div class="wtn-faction"><?= htmlspecialchars($_wc['faction']) ?></div><?php endif; ?>
              <div class="wtn-reason"><?= htmlspecialchars(ucfirst($_wc['stage'])) ?> &middot; untouched <?= (int)$_wc['days'] ?> day<?= (int)$_wc['days'] !== 1 ? 's' : '' ?></div>
            </div>
          </a>
          <?php elseif ($_wc['type'] === 'ready'): ?>
          <a class="wtn-card wtn-card-ready" data-jump="planned">
            <div class="wtn-body">
              <div class="wtn-type">Ready to Start</div>
              <div class="wtn-name"><?= htmlspecialchars($_wc['name']) ?></div>
              <?php if (!empty($_wc['faction'])): ?><div class="wtn-faction"><?= htmlspecialchars($_wc['faction']) ?></div><?php endif; ?>
              <div class="wtn-reason"><?= (int)$_wc['count'] ?> paint<?= (int)$_wc['count'] !== 1 ? 's' : '' ?> &middot; all in stock</div>
            </div>
          </a>
          <?php elseif ($_wc['type'] === 'force'): ?>
          <a class="wtn-card wtn-card-force" data-jump="forces">
            <?php if (!empty($_wc['thumb'])): ?><div class="wtn-thumb" style="background-image:url('<?= htmlspecialchars($_wc['thumb']) ?>')"></div><?php endif; ?>
            <div class="wtn-body">
              <div class="wtn-type">Almost There</div>
              <div class="wtn-name"><?= htmlspecialchars($_wc['name']) ?></div>
              <div class="wtn-reason"><?= (int)$_wc['painted'] ?> / <?= (int)$_wc['target'] ?> models painted</div>
              <div class="wtn-progress"><div class="wtn-progress-fill" style="width:<?= (int)$_wc['pct'] ?>%"></div></div>
            </div>
          </a>
          <?php elseif ($_wc['type'] === 'almost'): ?>
          <a class="wtn-card wtn-card-almost" data-jump="planned">
            <div class="wtn-body">
              <div class="wtn-type">One Shop Away</div>
              <div class="wtn-name"><?= htmlspecialchars($_wc['name']) ?></div>
              <?php if (!empty($_wc['faction'])): ?><div class="wtn-faction"><?= htmlspecialchars($_wc['faction']) ?></div><?php endif; ?>
              <div class="wtn-reason">Buy: <?= htmlspecialchars(implode(', ', $_wc['missing'])) ?></div>
            </div>
          </a>
          <?php elseif ($_wc['type'] === 'shame'): ?>
          <a class="wtn-card wtn-card-shame" data-jump="shame">
            <div class="wtn-body">
              <div class="wtn-type">Build It</div>
              <div class="wtn-name"><?= htmlspecialchars($_wc['name']) ?></div>
              <?php if (!empty($_wc['faction'])): ?><div class="wtn-faction"><?= htmlspecialchars($_wc['faction']) ?></div><?php endif; ?>
              <div class="wtn-reason">Sitting<?= !empty($_wc['sitting']) ? ' ' . htmlspecialchars($_wc['sitting']) : '' ?> unbuilt</div>
            </div>
          </a>
          <?php elseif ($_wc['type'] === 'prepped'): ?>
          <a class="wtn-card wtn-card-prepped" data-jump="rescues">
            <?php if (!empty($_wc['thumb'])): ?><div class="wtn-thumb" style="background-image:url('<?= htmlspecialchars($_wc['thumb']) ?>')"></div><?php endif; ?>
            <div class="wtn-body">
              <div class="wtn-type">Prime It</div>
              <div class="wtn-name"><?= htmlspecialchars($_wc['name']) ?></div>
              <?php if (!empty($_wc['faction'])): ?><div class="wtn-faction"><?= htmlspecialchars($_wc['faction']) ?></div><?php endif; ?>
              <div class="wtn-reason"><?= (int)$_wc['count'] ?> unit<?= (int)$_wc['count'] !== 1 ? 's' : '' ?> ready to prime</div>
            </div>
          </a>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="contents-grid">
        <div class="armies-workbench-row">
          <?php if ($hasFactions || $hasForces || $hasBattles): ?>
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
              <?php if ($hasBattles): ?>
                <a class="contents-entry" data-jump="battles">
                  <div class="contents-entry-name">Battle Honours</div>
                  <div class="contents-entry-blurb">Every game logged - result, opponent, army, mission. The record of war, win or lose.</div>
                  <div class="contents-entry-count"><?= $cnt_battles ?> battle<?= $cnt_battles !== 1 ? 's' : '' ?> recorded</div>
                </a>
              <?php endif; ?>
              <?php if ($hasBench): ?>
                <div class="nixie-display">
                  <div class="nixie-frame">
                    <div class="nixie-tubes"><?php
                      $nixieStr = str_pad(min((int)$hobbyMinutes, 99999), 5, '0', STR_PAD_LEFT);
                      foreach (str_split($nixieStr) as $nd): ?><img class="nixie-digit" src="img/nixie_digits/digit_<?= $nd ?>.png" alt="<?= $nd ?>"><?php endforeach; ?>
                    </div>
                    <img src="img/nixie_hollow.png" class="nixie-frame-img" alt="Minutes painted this week">
                  </div>
                  <?php if ($pbMins > 0): ?><p class="nixie-pb">PB &nbsp;<?= $pbMins ?> mins &middot; w/c <?= $pbWeekLabel ?></p><?php endif; ?>
                  <?php if ($hobbyStreak > 0): ?><p class="nixie-pb nixie-streak"><span class="streak-flame">🔥</span> <strong><?= $hobbyStreak ?></strong> day streak</p><?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="workbench-section">
            <div class="contents-section-title">The Workbench</div>
            <a class="contents-entry" data-jump="inventory">
              <div class="contents-entry-name">Paint Inventory</div>
              <div class="contents-entry-blurb">Every paint catalogued with stock status. Know exactly what to grab before you sit down.</div>
              <div class="contents-entry-count"><?= $cnt_owned ?> owned<?php $flagTotal = $cnt_low_out + $cnt_wanted; if ($flagTotal > 0): ?> &middot; <button class="restock-trigger" onclick="event.stopPropagation();openRestockList()"><?= $flagTotal ?> flagged &rsaquo;</button><?php endif; ?></div>
            </a>
            <?php if ($hasBrushes): ?>
              <a class="contents-entry" data-jump="brushes">
                <div class="contents-entry-name">Brushes</div>
                <div class="contents-entry-blurb">Prime, workhorse, retired - the tools that actually touch the paint.</div>
                <div class="contents-entry-count"><?= $cnt_brushes ?> active</div>
              </a>
            <?php endif; ?>
            <?php if ($hasSupplies): ?>
              <a class="contents-entry" data-jump="supplies">
                <div class="contents-entry-name">Supplies</div>
                <div class="contents-entry-blurb">Palettes, mats, lamps, and the rest of the kit. The infrastructure of the hobby.</div>
                <div class="contents-entry-count"><?= $cnt_supplies ?> active</div>
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

      <?php if (!defined('SHOW_WC_NEWS') || SHOW_WC_NEWS): ?>
      <div class="wc-news-section" id="wc-news-section" style="display:none">
        <div class="wc-news-heading">Latest from Warhammer Community</div>
        <div class="wc-news-sub">warhammer-community.com &mdash; updated every 4 hours</div>
        <div class="wc-news-grid" id="wc-news-grid"></div>
      </div>
      <?php endif; ?>

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
      <button id="filter-flagged" class="inv-flagged-btn" title="Show low / out / wanted paints only">Flagged</button>
      <button class="inv-restock-btn" onclick="openRestockList()" title="Open full restock shopping list">Restock List</button>
      <button class="inv-wheel-btn" id="inv-wheel-btn" title="Colour wheel view">&#11044; Wheel</button>
      <button class="inv-harmony-btn" id="inv-harmony-btn" title="Colour harmony advisor">&#9678; Harmony</button>
      <button class="inv-wheel-copy" id="inv-wheel-copy" title="Copy link to wheel view">&#128279; Copy link</button>
      <span id="count"></span>
    </div>
    <p class="tab-blurb">The armoury laid bare. Know your stock before you commit colour to plastic. (Click &#9998; for notes or ★ for rating.)</p>
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

    <div id="wheel-view" style="display:none">
      <div class="wheel-harmony-wrap">
        <div id="wheel-svg-wrap" class="wheel-svg-wrap"></div>
        <div id="harmony-panel" class="harmony-panel" style="display:none"></div>
      </div>
      <div id="wheel-achromatic" class="wheel-achromatic"></div>
    </div>
    <div id="wheel-tooltip"></div>

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

  <?php if ($hasRescues): ?>
  <div id="tab-rescues" class="tab-panel">
    <div id="rescues-controls">
      <a class="tab-label" href="#" onclick="copyTabLink(event,'rescues')" title="Copy link to this tab">Rescue Tracker</a>
      <input id="rescues-search" type="search" class="tab-search" placeholder="Search rescues...">
      <div id="rescues-filter-pills" class="pill-row">
        <button class="rsc-fp active" data-filter="active">Active</button>
        <button class="rsc-fp" data-filter="promoted">Promoted</button>
        <button class="rsc-fp" data-filter="all">All</button>
      </div>
      <span id="rescues-summary"></span>
    </div>
    <p class="tab-blurb">eBay finds, traded minis, job lots - stripped and reborn.</p>
    <div class="rescues-grid" id="rescues-grid"></div>
    <div id="rescues-empty" class="tab-empty hidden">No rescues match.</div>
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
        <option value="Old World">Old World</option>
        <option value="Kill Team">Kill Team</option>
        <option value="Blood Bowl">Blood Bowl</option>
        <option value="Necromunda">Necromunda</option>
        <option value="Epic">Epic</option>
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
          <button class="bench-filter-pill" data-stage="all">All</button>
          <button class="bench-filter-pill active" data-stage="active">Active</button>
          <button class="bench-filter-pill" data-stage="done">Done</button>
        </div>
        <select id="bench-sys-select" onchange="benchSystemFilter=this.value;window._renderBench&&window._renderBench()" class="mini-select">
          <option value="">All systems</option>
          <option value="40k">40k</option>
          <option value="30k / HH">30k / HH</option>
          <option value="AoS">AoS</option>
          <option value="Old World">Old World</option>
          <option value="Kill Team">Kill Team</option>
          <option value="Blood Bowl">Blood Bowl</option>
          <option value="Necromunda">Necromunda</option>
          <option value="Epic">Epic</option>
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

  <?php if ($hasBattles): ?>
    <div id="tab-battles" class="tab-panel">
      <div id="battles-controls">
        <a class="tab-label" href="#" onclick="copyTabLink(event,'battles')" title="Copy link to this tab">Battle Honours</a>
        <input type="search" id="bh-search" class="tab-search" placeholder="Search opponent, army, mission&hellip;" autocomplete="off">
        <div id="bh-filter-pills" class="pill-row">
          <button class="bh-fp active" data-bhr="">All</button>
          <button class="bh-fp" data-bhr="win">Win</button>
          <button class="bh-fp" data-bhr="loss">Loss</button>
          <button class="bh-fp" data-bhr="draw">Draw</button>
        </div>
        <span id="bh-count"></span>
      </div>
      <p class="tab-blurb">Every battle logged, every opponent faced. The record of war, for glory and for shame.</p>
      <div class="battles-grid" id="battles-grid"></div>
      <div id="battles-empty" class="tab-empty hidden">No battles logged yet.</div>
    </div><!-- #tab-battles -->
  <?php endif; ?>

  <?php if (!defined('SHOW_COMMISSAR') || SHOW_COMMISSAR): ?>
  <div id="tab-commissar" class="tab-panel">
    <div class="ci-wrap">
      <div class="ci-hero">
        <div class="ci-hero-text">
          <div class="ci-hero-eyebrow">Astra Militarum &middot; Intelligence Division</div>
          <h2 class="ci-hero-title">The Commissar&rsquo;s Dossier</h2>
          <div class="ci-hero-sub">Rolling assessment &middot; <?= date('j M Y') ?></div>
        </div>
        <div class="ci-hero-portrait"><img src="img/models/1777377286_1.jpg" alt="The Commissar" loading="lazy"></div>
      </div>

      <?php if (!defined('SHOW_HEATMAP') || SHOW_HEATMAP): ?>
      <div class="ci-heatmap-section">
        <div id="hero-heatmap" class="hero-heatmap"></div>
      </div>
      <?php endif; ?>

      <div class="ci-grid">
        <div class="ci-card ci-full">
          <div class="ci-card-hd">&#9876; Next Orders</div>
          <?php if (empty($ciNextOrders)): ?>
            <p class="ci-sub">No active bench projects or ready planned schemes found.</p>
          <?php else: ?>
            <ol class="ci-orders">
              <?php foreach ($ciNextOrders as $_oi => $_ord): ?>
                <li class="ci-order">
                  <span class="ci-order-rank"><?= $_oi + 1 ?>.</span>
                  <span class="ci-order-name"><?= htmlspecialchars($_ord['name']) ?></span>
                  <span class="ci-badge-type <?= $_ord['type'] === 'bench' ? 'ci-type-bench' : 'ci-type-planned' ?>"><?= $_ord['type'] === 'bench' ? ucfirst(htmlspecialchars($_ord['stage'])) : 'Planned' ?></span>
                  <span class="ci-order-reasons"><?php foreach ($_ord['reasons'] as $_r): ?><span class="ci-reason"><?= htmlspecialchars($_r) ?></span><?php endforeach; ?></span>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </div>
        <?php if ($ciShame): ?>
          <div class="ci-card <?= $ciShame['growing'] ? 'ci-alert' : '' ?>">
            <div class="ci-card-hd">Shame Debt</div>
            <div class="ci-big"><?= $ciShame['active_boxes'] ?></div>
            <div class="ci-la bel"><?= $ciShame['active_boxes'] === 1 ? 'box' : 'boxes' ?> &middot; ~<?= $ciShame['active_units'] ?> units</div>
            <div class="ci-sub">+<?= $ciShame['acq_rate'] ?>/mo in &nbsp;&middot;&nbsp; &minus;<?= $ciShame['comp_rate'] ?>/mo out &nbsp;&middot;&nbsp; <strong class="<?= $ciShame['growing'] ? 'ci-warn' : '' ?>">net <?= $ciShame['net_velocity'] > 0 ? '+' : '' ?><?= $ciShame['net_velocity'] ?></strong></div>
            <?php if ($ciShame['months_to_clear']): ?><div class="ci-sub">At current rate, clear in ~<?= $ciShame['months_to_clear'] ?> months</div>
            <?php elseif ($ciShame['growing']): ?><div class="ci-sub ci-warn">Pile growing faster than you paint</div><?php endif; ?>
            <div style="display:flex;gap:6px;margin-top:8px;font-size:.62rem;color:#5a4828;">
              <span><?= $ciShame['status']['sealed'] ?> sealed</span><span>&middot;</span><span><?= $ciShame['status']['opened'] ?> opened</span><span>&middot;</span><span><?= $ciShame['status']['partial'] ?> partial</span>
            </div>
          </div>
        <?php else: ?>
          <div class="ci-card"><div class="ci-card-hd">Shame Debt</div><p class="ci-sub">No Pile of Shame data.</p></div>
        <?php endif; ?>
        <?php if ($ciSessions): ?>
          <?php $maxDow = max($ciSessions['dow_avgs'] ?: [1]); ?>
          <div class="ci-card">
            <div class="ci-card-hd">Session Patterns</div>
            <div class="ci-big"><?= $ciSessions['best_dow_name'] ?></div>
            <div class="ci-label">best day &middot; avg <?= $ciSessions['best_dow_avg'] ?> mins</div>
            <div class="ci-dow-chart">
              <?php foreach ($ciSessions['dow_avgs'] as $d => $avg): $barH = $maxDow > 0 ? max(2,(int)round($avg/$maxDow*40)) : 2; ?>
                <div class="ci-dow-bar-wrap">
                  <div class="ci-dow-bar <?= $d === $ciSessions['best_dow'] ? 'best' : '' ?>" style="height:<?= $barH ?>px;"></div>
                  <div class="ci-dow-lbl"><?= substr($ciSessions['dow_names'][$d],0,2) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="ci-sub">Last 30d: <?= $ciSessions['recent_count'] ?> sessions &middot; <?= $ciSessions['recent_mins'] ?> mins <?= $ciSessions['trend_up'] ? '<span style="color:#4a8a4a">&#9650;</span>' : '<span style="color:#8a2020">&#9660;</span>' ?></div>
          </div>
        <?php else: ?>
          <div class="ci-card"><div class="ci-card-hd">Session Patterns</div><p class="ci-sub">No bench session data.</p></div>
        <?php endif; ?>
        <?php if ($ciBottleneck): ?>
          <div class="ci-card">
            <div class="ci-card-hd">Stage Bottleneck</div>
            <?php if ($ciBottleneck['worst_stage']): ?>
              <div class="ci-big" style="font-size:1.1rem;text-transform:uppercase;"><?= htmlspecialchars($ciBottleneck['worst_stage']) ?></div>
              <div class="ci-label">avg <?= $ciBottleneck['worst_days'] ?> days &middot; longest stage (from transition history)</div>
            <?php elseif ($ciBottleneck['mode_stage']): ?>
              <?php $mStg = $ciBottleneck['mode_stage']; $mCnt = $ciBottleneck['current_counts'][$mStg] ?? 0; $mDays = $ciBottleneck['implied_avgs'][$mStg] ?? null; ?>
              <div class="ci-big" style="font-size:1.1rem;text-transform:uppercase;"><?= htmlspecialchars(ucfirst($mStg)) ?></div>
              <div class="ci-label"><?= $mCnt ?> project<?= $mCnt !== 1 ? 's' : '' ?> currently here<?= $mDays !== null ? ' &middot; avg ' . $mDays . ' days since start' : '' ?></div>
              <div class="ci-sub" style="margin-top:6px">Duration tracking activates as you cycle stages in admin.</div>
            <?php else: ?><div class="ci-sub">No active bench projects.</div><?php endif; ?>
            <?php
              $_stageShort = ['built'=>'Blt','primed'=>'Pri','basecoated'=>'Bsc','washed'=>'Wsh','highlighted'=>'Hlt','based'=>'Bsd','varnished'=>'Vrn'];
              $_barStages = array_keys($_stageShort);
              $_barAvgs = array_filter(array_intersect_key($ciBottleneck['stage_avgs'] ?? [], array_flip($_barStages)), fn($v) => $v !== null);
              $_maxBar = !empty($_barAvgs) ? max($_barAvgs) : 0;
              $_pipeTotal = array_sum($_barAvgs);
            ?>
            <?php if ($_maxBar > 0): ?>
            <div class="ci-dow-chart" style="margin:12px 0 4px">
              <?php foreach ($_barStages as $_sb):
                $bav = $ciBottleneck['stage_avgs'][$_sb] ?? null;
                $bpx = $bav !== null ? max(4,(int)round($bav / $_maxBar * 44)) : 2;
                $bisWorst = $_sb === $ciBottleneck['worst_stage'];
              ?>
              <div class="ci-dow-bar-wrap" title="<?= ucfirst($_sb) ?>: <?= $bav !== null ? $bav.'d avg' : 'no data' ?>">
                <div class="ci-dow-bar <?= $bisWorst ? 'best' : '' ?>" style="height:<?= $bpx ?>px;<?= $bav === null ? 'opacity:.15' : '' ?>"></div>
                <div class="ci-dow-lbl"><?= $_stageShort[$_sb] ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if ($_pipeTotal > 0): ?>
            <div class="ci-sub" style="margin-bottom:10px">Est. <?= (int)$_pipeTotal ?>d built &rarr; varnished based on history</div>
            <?php endif; ?>
            <?php endif; ?>
            <div class="ci-stage-dist">
              <?php foreach (['built','primed','basecoated','washed','highlighted','based','varnished','done'] as $_stpip): $cnt3 = $ciBottleneck['current_counts'][$_stpip] ?? 0; ?>
                <span class="ci-stage-pip <?= $cnt3 > 0 ? 'has-models' : '' ?>"><?= ucfirst($_stpip) ?><?= $cnt3 > 0 ? ' <strong>'.$cnt3.'</strong>' : '' ?></span>
              <?php endforeach; ?>
            </div>
            <?php if (!empty($ciBottleneck['stalled'])): ?>
            <div style="margin-top:10px;border-top:1px solid #1a1408;padding-top:8px">
              <div style="font-size:.58rem;letter-spacing:.1em;color:#5a4020;text-transform:uppercase;margin-bottom:6px">Untouched Projects</div>
              <?php foreach ($ciBottleneck['stalled'] as $_stl): ?>
              <div class="ci-risk-item">
                <span style="flex:1;font-size:.68rem"><?= htmlspecialchars($_stl['name']) ?></span>
                <span style="font-size:.6rem;color:#4a3a18;margin-right:6px"><?= htmlspecialchars(ucfirst($_stl['stage'])) ?></span>
                <span class="<?= $_stl['days'] >= 30 ? 'ci-risk-out' : 'ci-risk-low' ?>"><?= $_stl['days'] ?>d</span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="ci-card"><div class="ci-card-hd">Stage Bottleneck</div><p class="ci-sub">No bench data.</p></div>
        <?php endif; ?>
        <div class="ci-card">
          <div class="ci-card-hd">Force Forecasts</div>
          <?php if (!empty($ciForces)): ?>
            <?php foreach ($ciForces as $_ffc2): ?>
              <div class="ci-force-row">
                <div class="ci-force-name"><?= htmlspecialchars($_ffc2['name']) ?></div>
                <div class="ci-force-bar-wrap"><div class="ci-force-bar-fill" style="width:<?= $_ffc2['pct'] ?>%;"></div></div>
                <div class="ci-force-meta"><?= $_ffc2['painted'] ?>/<?= $_ffc2['target'] ?> models<?php if ($_ffc2['pct'] >= 100): ?> &middot; <span style="color:#4a8a4a">Complete</span><?php elseif ($_ffc2['completion_date']): ?> &middot; est. <?= $_ffc2['completion_date'] ?><?php endif; ?></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?><p class="ci-sub">No forces with target model counts set.</p><?php endif; ?>
        </div>
        <div class="ci-card">
          <div class="ci-card-hd">Battle Dossier</div>
          <?php if ($ciBattles): ?>
            <div class="ci-wld">
              <div><div class="ci-wld-w"><?= $ciBattles['record']['w'] ?></div><div class="ci-label">W</div></div>
              <div><div class="ci-wld-l"><?= $ciBattles['record']['l'] ?></div><div class="ci-label">L</div></div>
              <div><div class="ci-wld-d"><?= $ciBattles['record']['d'] ?></div><div class="ci-label">D</div></div>
            </div>
            <?php if ($ciBattles['best_army']): ?><div class="ci-sub">Best army: <strong><?= htmlspecialchars($ciBattles['best_army']) ?></strong> (<?= $ciBattles['army_wr'][$ciBattles['best_army']] ?>% win rate)</div><?php endif; ?>
            <?php foreach (array_slice($ciBattles['army_wr'],0,4,true) as $_bka => $_bkwr): ?>
              <div style="display:flex;justify-content:space-between;font-size:.68rem;color:#7a6a3a;padding:3px 0;border-bottom:1px solid #1a1408;"><span><?= htmlspecialchars($_bka) ?></span><span><?= $ciBattles['by_army'][$_bka]['w'] ?>W <?= $ciBattles['by_army'][$_bka]['l'] ?>L &middot; <?= $_bkwr ?>%</span></div>
            <?php endforeach; ?>
          <?php else: ?><p class="ci-sub">No battle data.</p><?php endif; ?>
        </div>
        <div class="ci-card">
          <div class="ci-card-hd">Supply Risk</div>
          <?php if (empty($ciSupply)): ?>
            <div class="ci-sub" style="color:#4a8a4a;">&#10003; All critical paints stocked</div>
          <?php else: ?>
            <?php foreach ($ciSupply as $_srp): ?>
              <div class="ci-risk-item"><span style="flex:1"><?= htmlspecialchars($_srp['name']) ?></span><span class="ci-risk-uses"><?= $_srp['uses'] ?>&#215;</span><span class="<?= $_srp['stock'] === 'out' ? 'ci-risk-out' : 'ci-risk-low' ?>"><?= strtoupper($_srp['stock']) ?></span></div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="ci-divider">Historical Record</div>
      <div class="ci-legacy">
        <div class="ci-legacy-stat-grid">
          <div class="ci-legacy-stat"><div class="ci-legacy-stat-num"><?= $ci_paints_owned ?></div><div class="ci-legacy-stat-lbl">Paints Owned</div></div>
          <div class="ci-legacy-stat"><div class="ci-legacy-stat-num"><?= count($models) ?></div><div class="ci-legacy-stat-lbl">Schemes</div></div>
          <div class="ci-legacy-stat"><div class="ci-legacy-stat-num"><?= count($planned) ?></div><div class="ci-legacy-stat-lbl">Planned</div></div>
          <?php if ($ci_wanted > 0): ?><div class="ci-legacy-stat"><div class="ci-legacy-stat-num"><?= $ci_wanted ?></div><div class="ci-legacy-stat-lbl">Wanted</div></div><?php endif; ?>
          <?php if ($ci_low_out > 0): ?><div class="ci-legacy-stat"><div class="ci-legacy-stat-num"><?= $ci_low_out ?></div><div class="ci-legacy-stat-lbl">Low/Out</div></div><?php endif; ?>
          <?php if ($hasBattles): ?><div class="ci-legacy-stat"><div class="ci-legacy-stat-num"><?= $cnt_battles ?></div><div class="ci-legacy-stat-lbl">Battles</div></div><?php endif; ?>
        </div>
        <?php if (!empty($ci_by_brand)): $maxBrand = max($ci_by_brand); ?>
          <div style="margin-bottom:16px;">
            <div class="ci-card-hd" style="margin-bottom:8px;">Collection by Brand</div>
            <?php foreach ($ci_by_brand as $brand => $bCnt): ?>
              <div style="margin-bottom:6px;"><div style="display:flex;justify-content:space-between;font-size:.68rem;color:#7a6a3a;margin-bottom:2px;"><span><?= htmlspecialchars($brand) ?></span><span><?= $bCnt ?></span></div><div style="background:#1a1508;height:4px;border-radius:2px;"><div style="height:100%;background:#c9a227;border-radius:2px;width:<?= (int)round($bCnt/$maxBrand*100) ?>%;"></div></div></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($ci_top_paints)): $ciPaintRank = 1; ?>
          <div style="padding-bottom:8px;">
            <div class="ci-card-hd" style="margin-bottom:8px;">Most Used Paints</div>
            <?php foreach ($ci_top_paints as $pk => $pCnt): $_pkP = explode('|',$pk); ?>
              <div style="display:flex;align-items:center;gap:8px;padding:4px 0;border-bottom:1px solid #1a1408;font-size:.7rem;color:#c4b49a;"><span style="color:#4a3a18;min-width:16px;"><?= $ciPaintRank++ ?></span><span style="flex:1"><?= htmlspecialchars($_pkP[1] ?? $pk) ?></span><span style="color:#5a4828;font-size:.62rem;"><?= htmlspecialchars($_pkP[0] ?? '') ?></span><span style="color:#c9a227;font-family:'Cinzel',serif;"><?= $pCnt ?>&#215;</span></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($milestones)): ?>
      <div class="ms-strip ms-commissar">
        <div class="ms-strip-hd">Campaign Honours &mdash; <?= $_msUnlocked ?> of <?= count($milestones) ?> earned</div>
        <div class="ms-badges">
          <?php foreach ($milestones as $_ms): ?>
          <div class="ms-badge<?= $_ms['unlocked'] ? ' ms-unlocked ms-cat-' . $_ms['cat'] : ' ms-locked' ?>">
            <span class="ms-name"><?= htmlspecialchars($_ms['name']) ?></span>
            <?php if ($_ms['unlocked']): ?><span class="ms-flavor"><?= htmlspecialchars($_ms['flavor']) ?></span><?php endif; ?>
          </div>
          <?php endforeach; unset($_ms); ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div><!-- #tab-commissar -->
  <?php endif; // SHOW_COMMISSAR ?>

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
      <p>All equivalences are compared against Citadel (now Warhammer Paints) colours. Base mappings sourced from official conversion charts published by each manufacturer where available, then verified against personal real-world results - coverage and colour on a model, not theory. Washes, Contrast, and technical paints are approximations only.</p>
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
    <div class="footer-oss"><a href="https://github.com/waaagh-co/waaaghPaints" target="_blank" rel="noopener">Open source on GitHub</a> &nbsp;&middot;&nbsp; Polyform Noncommercial License</div>
  </footer>

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

  <div class="used-in-overlay" id="used-in-overlay">
    <div class="used-in-sheet">
      <div class="used-in-paint-name" id="used-in-paint-name"></div>
      <div class="used-in-brand" id="used-in-brand"></div>
      <div id="used-in-content"></div>
      <button class="used-in-close" onclick="closeUsedIn()">Close</button>
    </div>
  </div>

  <div class="pull-overlay" id="scheme-doctor-modal" style="display:none" onclick="if(event.target===this)closeSchemeDoctor()">
    <div class="pull-sheet sd-modal">
      <div class="pull-title" style="display:flex;align-items:center;justify-content:space-between">
        <span style="font-family:'Cinzel',serif;letter-spacing:.08em">&#9678; Colour Harmony</span>
        <button class="pull-close" onclick="closeSchemeDoctor()">&#x2715;</button>
      </div>
      <div id="sd-content" style="overflow-y:auto;max-height:calc(90vh - 60px)"></div>
    </div>
  </div>

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

  <div class="restock-overlay" id="restock-overlay">
    <div class="shop-sheet">
      <div class="shop-title">Restock List</div>
      <div class="shop-subtitle" id="restock-subtitle"></div>
      <div id="restock-content"></div>
      <div class="shop-actions">
        <button onclick="printRestockList()">Print</button>
        <button id="restock-copy-btn" onclick="copyRestockList()">Copy</button>
        <button onclick="closeRestockList()">Close</button>
      </div>
    </div>
  </div>

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

  <div class="lightbox-overlay" id="lightbox">
    <span class="lb-close" id="lb-close">&times;</span>
    <span class="lb-arrow lb-prev" id="lb-prev">&#8249;</span>
    <img class="lb-img" id="lb-img" src="" alt="">
    <span class="lb-arrow lb-next" id="lb-next">&#8250;</span>
    <span class="lb-counter" id="lb-counter"></span>
  </div>

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

  <div class="warpaint-overlay" id="warpaint-overlay">
    <div class="wp-image-panel" id="wp-image-panel">
      <div class="wp-main-img-wrap" id="wp-main-img-wrap">
        <img class="wp-main-img" id="wp-main-img" src="" alt="">
        <div class="wp-no-img" id="wp-no-img">&#9876;</div>
      </div>
      <div class="wp-thumbs" id="wp-thumbs"></div>
      <div class="wp-img-overlay">
        <div class="wp-scheme-name" id="wp-scheme-name"></div>
        <div class="wp-badges" id="wp-badges"></div>
      </div>
    </div>
    <div class="wp-content-panel" id="wp-content-panel">
      <div class="wp-actions">
        <button class="wp-pull-btn" id="wp-pull-btn">Pull list</button>
        <button class="wp-close-btn" onclick="closeWarpaint()">&times;</button>
      </div>
      <div class="wp-content-body" id="wp-content-body"></div>
    </div>
  </div>

  </div><!-- /.main-content -->

  <div id="gs-overlay" class="gs-overlay" role="dialog" aria-modal="true">
    <div class="gs-modal">
      <div class="gs-input-wrap">
        <svg class="gs-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7" />
          <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
        <input type="search" id="gs-input" placeholder="Search paints, schemes, recipes, anything&hellip;" autocomplete="off">
        <kbd class="gs-esc">Esc</kbd>
      </div>
      <div id="gs-results" class="gs-results"></div>
      <div class="gs-foot"><kbd>&uarr;</kbd><kbd>&darr;</kbd> navigate · <kbd>Enter</kbd> open · <kbd>Esc</kbd> close</div>
    </div>
  </div>

  <script>
    const PAINTS = <?= $paintsJson ?>;
    const PLANNED = <?= $plannedJson ?>;
    const CONVERSIONS_DATA = <?= $conversionsDataJson ?>;
    const BOOKS_DATA = <?= $hasBooks ? $booksDataJson : 'null' ?>;
    const BRUSHES_DATA = <?= $hasBrushes ? $brushesDataJson : 'null' ?>;
    const SUPPLIES_DATA = <?= $hasSupplies ? $suppliesDataJson : 'null' ?>;
    const BENCH_DATA = <?= $hasBench ? $benchDataJson : 'null' ?>;
    const RECIPES_DATA = <?= $hasRecipes ? $recipesDataJson : 'null' ?>;
    const FORCES_DATA = <?= $hasForces ? $forcesDataJson : 'null' ?>;
    const WISHLIST_DATA = <?= $hasWishlist ? json_encode($wishlistData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) : 'null' ?>;
    const SHAME_DATA = <?= $hasShame ? json_encode($shameData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) : 'null' ?>;
    const JOURNAL_DATA = <?= $hasJournal ? json_encode($journalData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) : 'null' ?>;
    const BATTLES_DATA = <?= $hasBattles ? $battlesDataJson : 'null' ?>;
    const RESCUE_DATA = <?= $hasRescues ? json_encode($rescuesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) : 'null' ?>;
    const MODELS = <?= $modelsJson ?>;
  </script>
  <script src="js/index.js?v=21"></script>

  <?php if (!defined('SHOW_WC_NEWS') || SHOW_WC_NEWS): ?>
  <script>
  (function() {
    var sec = document.getElementById('wc-news-section');
    var grid = document.getElementById('wc-news-grid');
    if (!sec || !grid) return;
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    fetch('wc_proxy.php')
      .then(function(r) { return r.ok ? r.json() : null; })
      .then(function(data) {
        if (!data || !data.length) return;
        var html = '';
        data.forEach(function(a) {
          var ork = a.ork ? ' wc-ork' : '';
          var badge = a.ork ? '<span class="wc-ork-badge">WAAAGH!</span>' : '';
          var dateStr = a.mod ? new Date(a.mod).toLocaleDateString('en-GB', {day:'numeric',month:'short'}) : '';
          var datePart = dateStr ? '<div class="wc-news-date">' + esc(dateStr) + '</div>' : '';
          html += '<a class="wc-news-card' + ork + '" href="' + esc(a.url) + '" target="_blank" rel="noopener">' +
            badge +
            '<div class="wc-news-title">' + esc(a.title) + '</div>' +
            datePart +
            '</a>';
        });
        grid.innerHTML = html;
        sec.style.display = '';
      })
      .catch(function() {});
  })();
  </script>
  <?php endif; ?>

  <div id="install-banner">
    <div class="install-banner-text">
      <strong>Install App</strong>Add to your home screen for quick access
    </div>
    <button id="install-btn">Install</button>
    <button id="install-dismiss" title="Dismiss">&times;</button>
  </div>

  <button id="back-to-top" title="Back to top">↑</button>

  <?php if (defined('GA4_ID') && GA4_ID !== ''): ?>
  <div id="cookie-banner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:500;background:#0a0804;border-top:2px solid #3a2a08;padding:12px 20px;align-items:center;gap:16px;flex-wrap:wrap;">
    <div style="flex:1;min-width:220px;">
      <span style="font-family:'Cinzel',serif;font-size:11px;letter-spacing:.08em;color:#c9a227;text-transform:uppercase;margin-right:8px;">OI, HUMIE.</span><span style="font-size:12px;color:#7a6a4a;">Google Analytics is watchin' who comes 'ere - I just wanna know if any gits from da Tau Empire is snoopin' around. No funny bizness, just head-countin'.</span>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0;">
      <button onclick="_cookieAccept()" style="font-family:'Cinzel',serif;font-size:10px;letter-spacing:.06em;padding:6px 14px;background:#0e0a04;border:1px solid #c9a227;color:#c9a227;cursor:pointer;border-radius:2px;">WAAAGH, FINE</button>
      <button onclick="_cookieDecline()" style="font-family:'Cinzel',serif;font-size:10px;letter-spacing:.06em;padding:6px 14px;background:#0e0a04;border:1px solid #3a2a08;color:#5a4a28;cursor:pointer;border-radius:2px;">ZOG OFF</button>
    </div>
  </div>
  <?php endif; ?>
</body>

</html>