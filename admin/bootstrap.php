<?php
@ini_set('display_errors', '0');
require_once __DIR__ . '/../config.php';
if (!defined('ADMIN_FILENAME')) define('ADMIN_FILENAME', 'admin.php');
if (!function_exists('mb_substr'))    { function mb_substr($s,$start,$len=null)  { return $len===null ? substr($s,$start) : substr($s,$start,$len); } }
if (!function_exists('mb_strlen'))    { function mb_strlen($s)                   { return strlen($s); } }
if (!function_exists('mb_strimwidth')){ function mb_strimwidth($s,$start,$w,$t=''){ $r=substr($s,$start,$w); return (strlen($s)-$start>$w) ? $r.$t : $r; } }
define('MODELS_FILE',    __DIR__ . '/../data/models.json');
define('IMAGES_DIR',     __DIR__ . '/../img/models/');
define('IMAGES_WEB',     'img/models/');           // web-relative prefix stored in JSON
define('MAX_FILE_BYTES', 25 * 1024 * 1024); // 25 MB - server resizes on upload
define('PAINTS_FILE',    __DIR__ . '/../data/paints.json');
define('PLANNED_FILE',   __DIR__ . '/../data/planned.json');
define('BOOKS_FILE',     __DIR__ . '/../data/books.json');
define('BRUSHES_FILE',   __DIR__ . '/../data/brushes.json');
define('SUPPLIES_FILE',  __DIR__ . '/../data/supplies.json');
define('BENCH_FILE',     __DIR__ . '/../data/bench.json');
define('BENCH_IMG_DIR',  __DIR__ . '/../img/bench/');
define('BENCH_IMG_WEB',  'img/bench/');
define('RECIPES_FILE',   __DIR__ . '/../data/recipes.json');
define('RECIPE_IMG_DIR', __DIR__ . '/../img/recipes/');
define('RECIPE_IMG_WEB', 'img/recipes/');
define('JOURNAL_FILE',   __DIR__ . '/../data/journal.json');
define('SHAME_FILE',    __DIR__ . '/../data/shame.json');
define('RESCUES_FILE',    __DIR__ . '/../data/rescues.json');
define('RESCUES_IMG_DIR', __DIR__ . '/../img/rescues/');
define('RESCUES_IMG_WEB', 'img/rescues/');
define('RESCUES_MAX_IMAGES', 4);
define('RESCUE_STAGES', ['bidding', 'in_transit', 'received', 'stripping', 'prepped']);
define('FORCES_FILE',    __DIR__ . '/../data/forces.json');
define('WISHLIST_FILE',  __DIR__ . '/../data/wishlist.json');
define('BATTLES_FILE',   __DIR__ . '/../data/battles.json');
define('GOALS_FILE',     __DIR__ . '/../data/goals.json');

const BENCH_STAGES    = ['built', 'primed', 'basecoated', 'washed', 'highlighted', 'based', 'varnished', 'done'];
const BENCH_MAX_IMAGES = 8;
const RECIPE_TECHNIQUES = ['basecoat', 'wash', 'shade', 'layer', 'edge', 'highlight', 'glaze', 'drybrush', 'stipple', 'blend', 'special'];

function e(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function loadPaintsFromCsvs(): array
{
  $paints = [];
  $seen   = [];
  foreach (glob(__DIR__ . '/../inventory/*.csv') ?: [] as $path) {
    if (basename($path) === 'conversions.csv') continue;
    $fh = fopen($path, 'r');
    if (!$fh) continue;
    while (($line = fgets($fh)) !== false) {
      $fields = array_map('trim', explode('|', $line));
      if (count($fields) < 5 || $fields[0] === '' || $fields[1] === '') continue;
      $key = $fields[0] . '|' . $fields[1];
      if (isset($seen[$key])) continue;
      $seen[$key] = true;
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
  usort($paints, fn($a, $b) => strcmp($a['brand'] . $a['name'], $b['brand'] . $b['name']));
  return $paints;
}

function swatchColor(string $color): string
{
  return match (strtolower(trim($color))) {
    'white'                    => '#f0ede8',
    'grey'                     => '#9ca3af',
    'black'                    => '#2d2d2d',
    'flesh'                    => '#e8b49a',
    'red'                      => '#b91c1c',
    'green'                    => '#15803d',
    'blue'                     => '#1d4ed8',
    'yellow'                   => '#ca8a04',
    'orange'                   => '#c2410c',
    'brown'                    => '#92400e',
    'purple'                   => '#7e22ce',
    'pink'                     => '#be185d',
    'metallic'                 => '#94a3b8',
    'wash', 'shade'            => '#475569',
    'contrast'                 => '#8b5cf6',
    'transparent'              => '#0ea5e9',
    'fluorescent'              => '#84cc16',
    'special'                  => '#f97316',
    'ink'                      => '#64748b',
    default                    => '#6b7280',
  };
}

function brandSlug(string $brand): string
{
  return strtolower(preg_replace('/[^a-z0-9]/i', '', $brand));
}

function saveModelImage(string $tmpPath, string $destPath, string $mime, int $maxDim = 1000, int $quality = 90): bool
{
  if (!extension_loaded('gd')) {
    return move_uploaded_file($tmpPath, $destPath);
  }
  $src = match ($mime) {
    'image/jpeg' => @imagecreatefromjpeg($tmpPath),
    'image/png'  => @imagecreatefrompng($tmpPath),
    'image/webp' => @imagecreatefromwebp($tmpPath),
    default      => @imagecreatefromgif($tmpPath),
  };
  if (!$src) return false;
  $w     = imagesx($src);
  $h     = imagesy($src);
  $scale = min(1.0, $maxDim / max($w, $h));
  $nw    = max(1, (int) round($w * $scale));
  $nh    = max(1, (int) round($h * $scale));
  $dst   = imagecreatetruecolor($nw, $nh);
  imagefilledrectangle($dst, 0, 0, $nw, $nh, imagecolorallocate($dst, 255, 255, 255));
  imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
  imagedestroy($src);
  $ok = imagejpeg($dst, $destPath, $quality);
  imagedestroy($dst);
  return $ok;
}

function imageExt(string $mime): string
{
  return extension_loaded('gd') ? 'jpg' : match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    default => 'gif',
  };
}

function layerBadge(string $layer): string
{
  $cls = 'pb-' . preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '', $layer));
  return '<span class="paint-badge ' . $cls . '">' . e($layer) . '</span>';
}

function readConversionsCsv(): array
{
  $rows = [];
  $path = __DIR__ . '/../inventory/conversions.csv';
  $fh   = fopen($path, 'r');
  if (!$fh) return $rows;
  while (($line = fgets($fh)) !== false) {
    $f = array_map('trim', explode('|', $line));
    if (count($f) < 4 || $f[0] === '' || $f[0] === 'Citadel') continue;
    $rows[] = array_pad($f, 7, '');
  }
  fclose($fh);
  usort($rows, fn($a, $b) => strcasecmp($a[0], $b[0]));
  return $rows;
}

function writeConversionsCsv(array $rows): void
{
  usort($rows, fn($a, $b) => strcasecmp($a[0], $b[0]));
  $out = ['Citadel | Vallejo Paint | Pro Acryl Paint | Two Thin Coats | Vallejo Match | Pro Acryl Match | Two Thin Coats Match'];
  foreach ($rows as $r) {
    $val  = ($r[1] !== '' && $r[1] !== '-') ? $r[1] : '-';
    $pa   = ($r[2] !== '' && $r[2] !== '-') ? $r[2] : '-';
    $ttc  = ($r[3] !== '' && $r[3] !== '-') ? $r[3] : '-';
    $valQ = ($val !== '-') ? ($r[4] ?? '') : '-';
    $paQ  = ($pa  !== '-') ? ($r[5] ?? '') : '-';
    $ttcQ = ($ttc !== '-') ? ($r[6] ?? '') : '-';
    $out[] = implode(' | ', [$r[0], $val, $pa, $ttc, $valQ, $paQ, $ttcQ]);
  }
  file_put_contents(
    __DIR__ . '/../inventory/conversions.csv',
    implode("\n", $out) . "\n",
    LOCK_EX
  );
}

function dedupeColors(array $colors): array {
  $has3 = [];
  foreach ($colors as $c) {
    if (substr_count($c, '|') >= 2) { [$b,$n] = explode('|', $c, 3); $has3[$b.'|'.$n] = true; }
  }
  return array_values(array_filter($colors, function($c) use ($has3) {
    if (substr_count($c, '|') < 2) { [$b,$n] = explode('|', $c, 2); return !isset($has3[$b.'|'.$n]); }
    return true;
  }));
}

function benchSort(array &$arr): void
{
  $rank = array_flip(BENCH_STAGES);
  usort($arr, function ($a, $b) use ($rank) {
    $ad = ($a['stage'] ?? 'built') === 'done';
    $bd = ($b['stage'] ?? 'built') === 'done';
    if ($ad !== $bd) return $ad ? 1 : -1;
    $la = $a['last_touched'] ?? $a['date_start'] ?? '';
    $lb = $b['last_touched'] ?? $b['date_start'] ?? '';
    if ($la !== $lb) return strcmp($lb, $la);
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
}

function shameSort(array &$arr): void
{
  usort($arr, function ($a, $b) {
    $aq = $a['acquired'] ?? '';
    $bq = $b['acquired'] ?? '';
    if ($aq === '' && $bq !== '') return 1;
    if ($aq !== '' && $bq === '') return -1;
    $cmp = strcmp($aq, $bq);
    return $cmp !== 0 ? $cmp : strcmp($a['id'] ?? '', $b['id'] ?? '');
  });
}

// Ensure writable directories exist
foreach ([__DIR__ . '/../data', __DIR__ . '/../img/models', __DIR__ . '/../img/bench', __DIR__ . '/../img/recipes'] as $dir) {
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    die('Cannot create directory: ' . htmlspecialchars($dir));
  }
}

session_start([
  'cookie_httponly' => true,
  'cookie_samesite' => 'Lax',
  'use_strict_mode' => true,
]);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

$authError = '';
if (isset($_POST['password'])) {
  if ($_POST['password'] === ADMIN_PASSWORD) {
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
  } else {
    sleep(1);
    $authError = 'Incorrect password.';
  }
}
if (isset($_POST['logout'])) {
  session_destroy();
  header('Location: ' . ADMIN_FILENAME);
  exit;
}
$authed = !empty($_SESSION['admin']);

$paints = [];
if (file_exists(PAINTS_FILE)) {
  $paints = json_decode(file_get_contents(PAINTS_FILE), true) ?? [];
} else {
  $paints = loadPaintsFromCsvs();
}

$planned = file_exists(PLANNED_FILE) ? (json_decode(file_get_contents(PLANNED_FILE), true) ?? []) : [];
$hasBooks    = file_exists(BOOKS_FILE);
$booksData   = $hasBooks ? (json_decode(file_get_contents(BOOKS_FILE), true) ?? []) : [];
$codexOptions = [];
foreach ($booksData as $b) {
  $t = $b['type'] ?? 'novel';
  if ($t === 'codex' || $t === 'supplement') {
    $label = $b['title'];
    if (!empty($b['series'])) $label .= ' (' . $b['series'] . ')';
    $codexOptions[] = ['value' => $b['title'], 'label' => $label];
  }
}
usort($codexOptions, fn($a, $b) => strcmp($a['label'], $b['label']));
$hasJournal  = file_exists(JOURNAL_FILE);
$journalData = $hasJournal ? (json_decode(file_get_contents(JOURNAL_FILE), true) ?? []) : [];
$hasShame    = file_exists(SHAME_FILE);
$shameData   = $hasShame ? (json_decode(file_get_contents(SHAME_FILE), true) ?? []) : [];
$hasRescues   = file_exists(RESCUES_FILE);
$rescuesData  = $hasRescues ? (json_decode(file_get_contents(RESCUES_FILE), true) ?? []) : [];
if (!is_dir(RESCUES_IMG_DIR)) @mkdir(RESCUES_IMG_DIR, 0775, true);
$hasBrushes   = file_exists(BRUSHES_FILE);
$brushesData  = $hasBrushes ? (json_decode(file_get_contents(BRUSHES_FILE), true) ?? []) : [];
$hasSupplies  = file_exists(SUPPLIES_FILE);
$suppliesData = $hasSupplies ? (json_decode(file_get_contents(SUPPLIES_FILE), true) ?? []) : [];
$hasBench    = file_exists(BENCH_FILE);
$benchData   = $hasBench ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
$goalsData   = file_exists(GOALS_FILE) ? (json_decode(file_get_contents(GOALS_FILE), true) ?? []) : [];
$hasRecipes  = file_exists(RECIPES_FILE);
$recipesData = $hasRecipes ? (json_decode(file_get_contents(RECIPES_FILE), true) ?? []) : [];
$hasForces    = file_exists(FORCES_FILE);
$forcesData   = $hasForces ? (json_decode(file_get_contents(FORCES_FILE), true) ?? []) : [];
$hasWishlist  = file_exists(WISHLIST_FILE);
$wishlistData = $hasWishlist ? (json_decode(file_get_contents(WISHLIST_FILE), true) ?? []) : [];
$hasBattles   = file_exists(BATTLES_FILE);
$battlesData  = $hasBattles ? (json_decode(file_get_contents(BATTLES_FILE), true) ?? []) : [];
$tabStatsFile = __DIR__ . '/../data/tab_stats.json';
$tabStats     = file_exists($tabStatsFile) ? (json_decode(file_get_contents($tabStatsFile), true) ?? []) : [];

// Starts from CSVs so we can look up color/layer for paints not yet in inventory
$_csvPaints   = loadPaintsFromCsvs();
$masterPaints = [];
foreach ($_csvPaints as $p) {
  $masterPaints[strtolower($p['brand'] . '|' . $p['name'])] = ['c' => $p['color'], 'l' => $p['layer']];
}
// Overlay manual-only paints that are in inventory (AK Interactive, Scale75, etc.)
foreach ($paints as $p) {
  $key = strtolower($p['brand'] . '|' . $p['name']);
  if (!isset($masterPaints[$key])) {
    $masterPaints[$key] = ['c' => $p['color'] ?? '', 'l' => $p['layer'] ?? ''];
  }
}
$masterPaintsJson = json_encode($masterPaints, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Format: Citadel Name | Vallejo Name | Pro Acryl Name | Match Quality
// Builds bidirectional map: 'brand|name' → [{brand, name, quality}]
$conversions = [];
$convPath    = __DIR__ . '/../inventory/conversions.csv';
if (file_exists($convPath)) {
  $fh = fopen($convPath, 'r');
  if ($fh) {
    while (($line = fgets($fh)) !== false) {
      $f = array_map('trim', explode('|', $line));
      if (count($f) < 4 || $f[0] === '' || $f[0] === 'Citadel') continue; // skip blank + header
      $f      = array_pad($f, 7, '');
      [$citName, $valName, $paName, $ttcName, $valQ, $paQ, $ttcQ] = $f;
      $ck = 'citadel|' . strtolower($citName);
      if ($valName !== '' && $valName !== '-' && strtolower($valQ) !== 'avoid') {
        $vk = 'vallejo|' . strtolower($valName);
        $conversions[$ck][] = ['brand' => 'Vallejo',  'name' => $valName, 'q' => $valQ];
        $conversions[$vk][] = ['brand' => 'Citadel',  'name' => $citName, 'q' => $valQ];
      }
      if ($paName !== '' && $paName !== '-' && strtolower($paQ) !== 'avoid') {
        $pk = 'pro acryl|' . strtolower($paName);
        $conversions[$ck][] = ['brand' => 'Pro Acryl', 'name' => $paName, 'q' => $paQ];
        $conversions[$pk][] = ['brand' => 'Citadel',   'name' => $citName, 'q' => $paQ];
      }
      if ($ttcName !== '' && $ttcName !== '-' && strtolower($ttcQ) !== 'avoid') {
        $tk = 'two thin coats|' . strtolower($ttcName);
        $conversions[$ck][] = ['brand' => 'Two Thin Coats', 'name' => $ttcName, 'q' => $ttcQ];
        $conversions[$tk][] = ['brand' => 'Citadel',        'name' => $citName, 'q' => $ttcQ];
      }
    }
    fclose($fh);
  }
}
$conversionsJson = json_encode($conversions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

define('HEX_SEED_FILE', __DIR__ . '/../data/paint_hex_seed.json');

$successMsg = '';
$formError  = '';

if (!empty($_SESSION['flash'])) {
  $successMsg = $_SESSION['flash'];
  unset($_SESSION['flash']);
}
