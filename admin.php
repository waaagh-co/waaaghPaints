<?php
@ini_set('display_errors', '0');
require_once __DIR__ . '/config.php';
if (!defined('ADMIN_FILENAME')) define('ADMIN_FILENAME', 'admin.php');
if (!function_exists('mb_substr'))    { function mb_substr($s,$start,$len=null)  { return $len===null ? substr($s,$start) : substr($s,$start,$len); } }
if (!function_exists('mb_strlen'))    { function mb_strlen($s)                   { return strlen($s); } }
if (!function_exists('mb_strimwidth')){ function mb_strimwidth($s,$start,$w,$t=''){ $r=substr($s,$start,$w); return (strlen($s)-$start>$w) ? $r.$t : $r; } }
define('MODELS_FILE',    __DIR__ . '/data/models.json');
define('IMAGES_DIR',     __DIR__ . '/img/models/');
define('IMAGES_WEB',     'img/models/');           // web-relative prefix stored in JSON
define('MAX_FILE_BYTES', 25 * 1024 * 1024); // 25 MB - server resizes on upload
define('PAINTS_FILE',    __DIR__ . '/data/paints.json');
define('PLANNED_FILE',   __DIR__ . '/data/planned.json');
define('BOOKS_FILE',     __DIR__ . '/data/books.json');
define('BRUSHES_FILE',   __DIR__ . '/data/brushes.json');
define('SUPPLIES_FILE',  __DIR__ . '/data/supplies.json');
define('BENCH_FILE',     __DIR__ . '/data/bench.json');
define('BENCH_IMG_DIR',  __DIR__ . '/img/bench/');
define('BENCH_IMG_WEB',  'img/bench/');
define('RECIPES_FILE',   __DIR__ . '/data/recipes.json');
define('RECIPE_IMG_DIR', __DIR__ . '/img/recipes/');
define('RECIPE_IMG_WEB', 'img/recipes/');
define('JOURNAL_FILE',   __DIR__ . '/data/journal.json');
define('SHAME_FILE',    __DIR__ . '/data/shame.json');
define('RESCUES_FILE',    __DIR__ . '/data/rescues.json');
define('RESCUES_IMG_DIR', __DIR__ . '/img/rescues/');
define('RESCUES_IMG_WEB', 'img/rescues/');
define('RESCUES_MAX_IMAGES', 4);
define('RESCUE_STAGES', ['bidding', 'in_transit', 'received', 'stripping', 'prepped']);
define('FORCES_FILE',    __DIR__ . '/data/forces.json');
define('WISHLIST_FILE',  __DIR__ . '/data/wishlist.json');
define('BATTLES_FILE',   __DIR__ . '/data/battles.json');
define('GOALS_FILE',     __DIR__ . '/data/goals.json');

const BENCH_STAGES    = ['built', 'primed', 'basecoated', 'washed', 'highlighted', 'based', 'varnished', 'done'];
const BENCH_MAX_IMAGES = 8;

function e(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function loadPaintsFromCsvs(): array
{
  $paints = [];
  $seen   = [];
  foreach (glob(__DIR__ . '/inventory/*.csv') ?: [] as $path) {
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
  $path = __DIR__ . '/inventory/conversions.csv';
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
    __DIR__ . '/inventory/conversions.csv',
    implode("\n", $out) . "\n",
    LOCK_EX
  );
}

// Ensure writable directories exist
foreach ([__DIR__ . '/data', __DIR__ . '/img/models', __DIR__ . '/img/bench', __DIR__ . '/img/recipes'] as $dir) {
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
$tabStatsFile = __DIR__ . '/data/tab_stats.json';
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
$convPath    = __DIR__ . '/inventory/conversions.csv';
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

define('HEX_SEED_FILE', __DIR__ . '/data/paint_hex_seed.json');

if ($authed && ($_POST['action'] ?? '') === 'apply_hex_seed') {
  $seedRaw = file_exists(HEX_SEED_FILE) ? json_decode(file_get_contents(HEX_SEED_FILE), true) : null;
  if (is_array($seedRaw) && file_exists(PAINTS_FILE)) {
    $all = json_decode(file_get_contents(PAINTS_FILE), true) ?? [];
    $applied = 0;
    foreach ($all as &$p) {
      $key = $p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? '');
      if (isset($seedRaw[$key]) && empty($p['hex'])) {
        $p['hex'] = $seedRaw[$key];
        $applied++;
      }
    }
    unset($p);
    file_put_contents(PAINTS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = "Seeded $applied paint hex values.";
  } else {
    $_SESSION['flash'] = 'No seed file or paints file found.';
  }
  header('Location: ' . ADMIN_FILENAME);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'import_paints') {
  $imported = loadPaintsFromCsvs();
  file_put_contents(PAINTS_FILE, json_encode($imported, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  $_SESSION['flash'] = 'Imported ' . count($imported) . ' paints from CSV files.';
  header('Location: ' . ADMIN_FILENAME);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'add_paint') {
  $brand = trim($_POST['brand'] ?? '');
  $name  = trim($_POST['name']  ?? '');
  $color = trim($_POST['color'] ?? '');
  $hue   = trim($_POST['hue']   ?? '');
  $layer = trim($_POST['layer'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $hex   = strtolower(trim($_POST['hex'] ?? ''));
  $stars = min(5, max(0, (int)($_POST['p_stars'] ?? 0)));
  if (!preg_match('/^#[0-9a-f]{6}$/', $hex)) $hex = '';
  if ($brand !== '' && $name !== '') {
    $all  = file_exists(PAINTS_FILE) ? (json_decode(file_get_contents(PAINTS_FILE), true) ?? []) : [];
    $new  = compact('brand', 'name', 'color', 'hue', 'layer');
    if ($notes !== '') $new['notes'] = $notes;
    if ($hex   !== '') $new['hex']   = $hex;
    if ($stars  >  0) $new['stars'] = $stars;
    $all[] = $new;
    usort($all, fn($a, $b) => strcmp($a['brand'] . $a['name'], $b['brand'] . $b['name']));
    file_put_contents(PAINTS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Paint "' . htmlspecialchars($name) . '" added.';
  }
  header('Location: ' . ADMIN_FILENAME);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'edit_paint') {
  $pid   = trim($_POST['paint_id'] ?? '');
  $brand = trim($_POST['brand'] ?? '');
  $name  = trim($_POST['name']  ?? '');
  $color = trim($_POST['color'] ?? '');
  $hue   = trim($_POST['hue']   ?? '');
  $layer = trim($_POST['layer'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $hex   = strtolower(trim($_POST['hex'] ?? ''));
  $stars = min(5, max(0, (int)($_POST['p_stars'] ?? 0)));
  if (!preg_match('/^#[0-9a-f]{6}$/', $hex)) $hex = '';
  if ($pid !== '' && $brand !== '' && $name !== '') {
    $all = file_exists(PAINTS_FILE) ? (json_decode(file_get_contents(PAINTS_FILE), true) ?? []) : [];
    foreach ($all as &$p) {
      if ($p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? '') === $pid) {
        $existing_stock = $p['stock'] ?? null;
        $p = compact('brand', 'name', 'color', 'hue', 'layer');
        if ($notes !== '')            $p['notes'] = $notes;
        if ($hex   !== '')            $p['hex']   = $hex;
        if ($stars  >  0)            $p['stars'] = $stars;
        if ($existing_stock !== null) $p['stock'] = $existing_stock;
        break;
      }
    }
    unset($p);
    usort($all, fn($a, $b) => strcmp($a['brand'] . $a['name'], $b['brand'] . $b['name']));
    file_put_contents(PAINTS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Paint "' . htmlspecialchars($name) . '" updated.';
  }
  header('Location: ' . ADMIN_FILENAME);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_paint') {
  $pid = trim($_POST['paint_id'] ?? '');
  if ($pid !== '') {
    $all = file_exists(PAINTS_FILE) ? (json_decode(file_get_contents(PAINTS_FILE), true) ?? []) : [];
    $all = array_values(array_filter($all, fn($p) => $p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? '') !== $pid));
    file_put_contents(PAINTS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Paint deleted.';
  }
  header('Location: ' . ADMIN_FILENAME);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'set_stock') {
  header('Content-Type: application/json');
  $pid   = trim($_POST['paint_id'] ?? '');
  $stock = trim($_POST['stock']    ?? '');
  if ($pid !== '' && in_array($stock, ['', 'low', 'out', 'wanted'], true)) {
    $all = file_exists(PAINTS_FILE) ? (json_decode(file_get_contents(PAINTS_FILE), true) ?? []) : [];
    foreach ($all as &$p) {
      if (($p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? '')) === $pid) {
        if ($stock === '') unset($p['stock']);
        else $p['stock'] = $stock;
        break;
      }
    }
    unset($p);
    file_put_contents(PAINTS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true, 'stock' => $stock]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'add_planned') {
  $name        = trim($_POST['pl_name']        ?? '');
  $model        = trim($_POST['pl_model']        ?? '');
  $faction      = trim($_POST['pl_faction']      ?? '');
  $system       = trim($_POST['pl_system']       ?? '');
  $description  = trim($_POST['pl_description']  ?? '');
  $codex_source = trim($_POST['pl_codex_source'] ?? '');
  $colors       = array_values(array_filter($_POST['planned_colors'] ?? []));
  $recipes      = array_values(array_filter($_POST['planned_recipes'] ?? []));
  if ($name !== '') {
    $all   = file_exists(PLANNED_FILE) ? (json_decode(file_get_contents(PLANNED_FILE), true) ?? []) : [];
    $entry = ['id' => (string)time(), 'name' => $name];
    if ($model)        $entry['model']        = $model;
    if ($faction)      $entry['faction']      = $faction;
    if ($system)       $entry['system']       = $system;
    if ($description)  $entry['description']  = $description;
    if ($codex_source) $entry['codex_source'] = $codex_source;
    if ($colors)       $entry['colors']       = $colors;
    if ($recipes)      $entry['recipes']      = $recipes;
    $all[] = $entry;
    usort($all, fn($a, $b) => strcmp($a['name'], $b['name']));
    file_put_contents(PLANNED_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Planned scheme "' . htmlspecialchars($name) . '" added.';
  }
  header('Location: ' . ADMIN_FILENAME);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'edit_planned') {
  $pid          = trim($_POST['planned_id']      ?? '');
  $name         = trim($_POST['pl_name']         ?? '');
  $model        = trim($_POST['pl_model']        ?? '');
  $faction      = trim($_POST['pl_faction']      ?? '');
  $system       = trim($_POST['pl_system']       ?? '');
  $description  = trim($_POST['pl_description']  ?? '');
  $codex_source = trim($_POST['pl_codex_source'] ?? '');
  $colors       = array_values(array_filter($_POST['planned_colors'] ?? []));
  $recipes      = array_values(array_filter($_POST['planned_recipes'] ?? []));
  if ($pid !== '' && $name !== '') {
    $all = file_exists(PLANNED_FILE) ? (json_decode(file_get_contents(PLANNED_FILE), true) ?? []) : [];
    foreach ($all as &$p) {
      if ($p['id'] === $pid) {
        $p = ['id' => $pid, 'name' => $name];
        if ($model)        $p['model']        = $model;
        if ($faction)      $p['faction']      = $faction;
        if ($system)       $p['system']       = $system;
        if ($description)  $p['description']  = $description;
        if ($codex_source) $p['codex_source'] = $codex_source;
        if ($colors)       $p['colors']       = $colors;
        if ($recipes)      $p['recipes']      = $recipes;
        break;
      }
    }
    unset($p);
    usort($all, fn($a, $b) => strcmp($a['name'], $b['name']));
    file_put_contents(PLANNED_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Planned scheme "' . htmlspecialchars($name) . '" updated.';
  }
  header('Location: ' . ADMIN_FILENAME);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_planned') {
  $pid = trim($_POST['planned_id'] ?? '');
  if ($pid !== '') {
    $all = file_exists(PLANNED_FILE) ? (json_decode(file_get_contents(PLANNED_FILE), true) ?? []) : [];
    $all = array_values(array_filter($all, fn($p) => $p['id'] !== $pid));
    file_put_contents(PLANNED_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Planned scheme deleted.';
  }
  header('Location: ' . ADMIN_FILENAME);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'promote_planned') {
  header('Content-Type: application/json');
  register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
      while (ob_get_level()) ob_end_clean();
      echo json_encode(['ok' => false, 'error' => 'fatal: ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line']]);
    }
  });
  ob_start();
  $pid = trim($_POST['planned_id'] ?? '');
  if (!$pid) { ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'no id']); exit; }
  $all = file_exists(PLANNED_FILE) ? (json_decode(file_get_contents(PLANNED_FILE), true) ?? []) : [];
  $found = false;
  $eName = $eFaction = $eSystem = '';
  $eColors = $eRecipes = [];
  foreach ($all as $p) {
    if ($p['id'] === $pid && empty($p['promoted_to'])) {
      $eName    = $p['name']    ?? '';
      $eFaction = $p['faction'] ?? '';
      $eSystem  = $p['system']  ?? '';
      $eColors  = $p['colors']  ?? [];
      $eRecipes = $p['recipes'] ?? [];
      $found = true;
      break;
    }
  }
  if (!$found) { ob_end_clean(); echo json_encode(['ok' => false, 'error' => 'not found or already promoted']); exit; }
  $newId = (string)(time() + rand(0, 9));
  $bench = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
  $entry = ['id' => $newId, 'name' => $eName, 'stage' => 'built', 'last_touched' => date('Y-m-d'), 'date_start' => date('Y-m-d')];
  if ($eFaction) $entry['faction'] = $eFaction;
  if ($eSystem)  $entry['system']  = $eSystem;
  if ($eColors)  $entry['colors']  = $eColors;
  if ($eRecipes) $entry['recipes'] = $eRecipes;
  $bench[] = $entry;
  benchSort($bench);
  $benchJson = json_encode(array_values($bench), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if ($benchJson === false || file_put_contents(BENCH_FILE, $benchJson, LOCK_EX) === false) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'error' => 'bench write failed: ' . json_last_error_msg()]);
    exit;
  }
  foreach ($all as &$p) {
    if ($p['id'] === $pid) { $p['promoted_to'] = 'bench'; break; }
  }
  unset($p);
  file_put_contents(PLANNED_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  ob_end_clean();
  echo json_encode(['ok' => true]);
  exit;
}

function bookSort(array &$arr): void
{
  usort($arr, fn($a, $b) => ($a['faction'] ?? '') <=> ($b['faction'] ?? '') ?: ($a['title'] ?? '') <=> ($b['title'] ?? ''));
}

if ($authed && ($_POST['action'] ?? '') === 'create_books_file') {
  file_put_contents(BOOKS_FILE, '[]', LOCK_EX);
  $_SESSION['flash'] = 'Codex Library started.';
  header('Location: ' . ADMIN_FILENAME . '#section-books');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'create_journal_file') {
  file_put_contents(JOURNAL_FILE, '[]', LOCK_EX);
  $_SESSION['flash'] = 'Scrap Notes started.';
  header('Location: ' . ADMIN_FILENAME . '#section-journal');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'add_book') {
  $title   = trim($_POST['bk_title']   ?? '');
  $author  = trim($_POST['bk_author']  ?? '');
  $series  = trim($_POST['bk_series']  ?? '');
  $faction = trim($_POST['bk_faction'] ?? '');
  $notes   = trim($_POST['bk_notes']   ?? '');
  $bktype  = trim($_POST['bk_type']    ?? 'codex');
  if (!in_array($bktype, ['codex', 'supplement'], true)) $bktype = 'codex';
  if ($title !== '') {
    $all   = file_exists(BOOKS_FILE) ? (json_decode(file_get_contents(BOOKS_FILE), true) ?? []) : [];
    $entry = ['id' => (string)time(), 'title' => $title, 'type' => $bktype];
    if ($author)  $entry['author']  = $author;
    if ($series)  $entry['series']  = $series;
    if ($faction) $entry['faction'] = $faction;
    if ($notes)   $entry['notes']   = $notes;
    $all[] = $entry;
    bookSort($all);
    file_put_contents(BOOKS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = '"' . htmlspecialchars($title) . '" added.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-books');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'edit_book') {
  $bid     = trim($_POST['bk_id']      ?? '');
  $title   = trim($_POST['bk_title']   ?? '');
  $author  = trim($_POST['bk_author']  ?? '');
  $series  = trim($_POST['bk_series']  ?? '');
  $faction = trim($_POST['bk_faction'] ?? '');
  $notes   = trim($_POST['bk_notes']   ?? '');
  $bktype  = trim($_POST['bk_type']    ?? 'codex');
  if (!in_array($bktype, ['codex', 'supplement'], true)) $bktype = 'codex';
  if ($bid !== '' && $title !== '') {
    $all = file_exists(BOOKS_FILE) ? (json_decode(file_get_contents(BOOKS_FILE), true) ?? []) : [];
    foreach ($all as &$b) {
      if ($b['id'] === $bid) {
        $b = ['id' => $bid, 'title' => $title, 'type' => $bktype];
        if ($author)  $b['author']  = $author;
        if ($series)  $b['series']  = $series;
        if ($faction) $b['faction'] = $faction;
        if ($notes)   $b['notes']   = $notes;
        break;
      }
    }
    unset($b);
    bookSort($all);
    file_put_contents(BOOKS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = '"' . htmlspecialchars($title) . '" updated.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-books');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_book') {
  $bid = trim($_POST['bk_id'] ?? '');
  if ($bid !== '') {
    $all = file_exists(BOOKS_FILE) ? (json_decode(file_get_contents(BOOKS_FILE), true) ?? []) : [];
    $all = array_values(array_filter($all, fn($b) => $b['id'] !== $bid));
    file_put_contents(BOOKS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Book deleted.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-books');
  exit;
}

function journalSort(array &$entries): void
{
  usort($entries, function ($a, $b) {
    $cmp = strcmp($b['date'] ?? '', $a['date'] ?? '');
    return $cmp !== 0 ? $cmp : strcmp($b['id'] ?? '', $a['id'] ?? '');
  });
}

if ($authed && in_array($_POST['action'] ?? '', ['add_journal', 'edit_journal'], true)) {
  $jid   = trim($_POST['jn_id']    ?? '');
  $date  = trim($_POST['jn_date']  ?? '');
  $title = trim($_POST['jn_title'] ?? '');
  $mood  = trim($_POST['jn_mood']  ?? '');
  $body  = trim($_POST['jn_body']  ?? '');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
  if (!in_array($mood, ['great', 'good', 'okay', 'rough'], true)) $mood = '';
  if ($body !== '') {
    $all = file_exists(JOURNAL_FILE) ? (json_decode(file_get_contents(JOURNAL_FILE), true) ?? []) : [];
    if ($jid !== '') {
      foreach ($all as &$jentry) {
        if ($jentry['id'] === $jid) {
          $jentry = ['id' => $jid, 'date' => $date, 'body' => $body];
          if ($title) $jentry['title'] = $title;
          if ($mood)  $jentry['mood']  = $mood;
          break;
        }
      }
      unset($jentry);
      $_SESSION['flash'] = 'Journal entry updated.';
    } else {
      $jentry = ['id' => (string)time(), 'date' => $date, 'body' => $body];
      if ($title) $jentry['title'] = $title;
      if ($mood)  $jentry['mood']  = $mood;
      $all[] = $jentry;
      $_SESSION['flash'] = 'Journal entry added.';
    }
    journalSort($all);
    file_put_contents(JOURNAL_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  }
  header('Location: ' . ADMIN_FILENAME . '#section-journal');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_journal') {
  $jid = trim($_POST['jn_id'] ?? '');
  if ($jid !== '') {
    $all = file_exists(JOURNAL_FILE) ? (json_decode(file_get_contents(JOURNAL_FILE), true) ?? []) : [];
    $all = array_values(array_filter($all, fn($e) => $e['id'] !== $jid));
    file_put_contents(JOURNAL_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Journal entry deleted.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-journal');
  exit;
}

function rescueSort(array &$arr): void
{
  $stageOrder = array_flip(RESCUE_STAGES);
  usort($arr, function ($a, $b) use ($stageOrder) {
    $aP = !empty($a['promoted_to']);
    $bP = !empty($b['promoted_to']);
    if ($aP !== $bP) return $aP ? 1 : -1;
    $as = $stageOrder[$a['stage'] ?? 'received'] ?? 99;
    $bs = $stageOrder[$b['stage'] ?? 'received'] ?? 99;
    if ($as !== $bs) return $as - $bs;
    $aq = $a['acquired'] ?? '';
    $bq = $b['acquired'] ?? '';
    if ($aq === '' && $bq !== '') return 1;
    if ($aq !== '' && $bq === '') return -1;
    $cmp = strcmp($aq, $bq);
    return $cmp !== 0 ? $cmp : strcmp($a['id'] ?? '', $b['id'] ?? '');
  });
}

if ($authed && ($_POST['action'] ?? '') === 'create_rescues_file') {
  file_put_contents(RESCUES_FILE, '[]', LOCK_EX);
  header('Location: ' . ADMIN_FILENAME . '#section-rescues');
  exit;
}

if ($authed && in_array($_POST['action'] ?? '', ['add_rescue', 'edit_rescue'], true)) {
  $rid       = trim($_POST['rc_id']        ?? '');
  $name      = trim($_POST['rc_name']      ?? '');
  $system    = trim($_POST['rc_system']    ?? '');
  $faction   = trim($_POST['rc_faction']   ?? '');
  $count     = (int)($_POST['rc_count']    ?? 0);
  $source    = trim($_POST['rc_source']    ?? '');
  $condition = trim($_POST['rc_condition'] ?? '');
  $acquired  = trim($_POST['rc_acquired']  ?? '');
  $stage     = trim($_POST['rc_stage']     ?? 'received');
  $notes     = trim($_POST['rc_notes']     ?? '');
  if (!$name) { header('Location: ' . ADMIN_FILENAME . '#section-rescues'); exit; }
  if (!in_array($system, ['40k', '30k / HH', 'AoS', 'Old World', 'Epic', 'Blood Bowl', 'Necromunda', 'Kill Team', 'OPR', 'Other'], true)) $system = '';
  if (!in_array($stage, RESCUE_STAGES, true)) $stage = 'received';
  if (!in_array($condition, ['bare', 'primed_only', 'light', 'medium', 'heavy'], true)) $condition = '';
  if (!in_array($source, ['eBay', 'Trade', 'LGS', 'Gift', 'Other'], true)) $source = '';
  if ($acquired && !preg_match('/^\d{4}-\d{2}$/', $acquired)) $acquired = '';

  $all = file_exists(RESCUES_FILE) ? (json_decode(file_get_contents(RESCUES_FILE), true) ?? []) : [];
  $existing = null;
  $isEdit = $rid !== '';
  $id = $rid;
  if ($isEdit) {
    foreach ($all as $e2) { if ($e2['id'] === $rid) { $existing = $e2; break; } }
  } else {
    $ts = (string)time();
    $id = $ts;
    $n  = 1;
    $ids = array_column($all, 'id');
    while (in_array($id, $ids, true)) { $id = $ts . $n++; }
  }

  $slotImages = array_pad(array_values($existing['before_images'] ?? []), RESCUES_MAX_IMAGES, null);
  for ($slot = 1; $slot <= RESCUES_MAX_IMAGES; $slot++) {
    if (($_POST['delete_rc_img_' . $slot] ?? '0') === '1' && $slotImages[$slot - 1] !== null) {
      $fp = __DIR__ . '/' . $slotImages[$slot - 1];
      if (file_exists($fp)) @unlink($fp);
      $slotImages[$slot - 1] = null;
    }
    $key = 'rc_image' . $slot;
    if (empty($_FILES[$key]['name'])) continue;
    $file = $_FILES[$key];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_FILE_BYTES) continue;
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) continue;
    $filename = $id . '_' . $slot . '_' . substr(uniqid(), -6) . '.' . imageExt($mime);
    if (saveModelImage($file['tmp_name'], RESCUES_IMG_DIR . $filename, $mime)) {
      $slotImages[$slot - 1] = RESCUES_IMG_WEB . $filename;
    }
  }
  $images = array_values(array_filter($slotImages, fn($i) => $i !== null));

  $entry = ['id' => $id, 'name' => $name, 'stage' => $stage];
  if ($system)    $entry['system']    = $system;
  if ($faction)   $entry['faction']   = $faction;
  if ($count > 0) $entry['count']     = $count;
  if ($source)    $entry['source']    = $source;
  if ($condition) $entry['condition'] = $condition;
  if ($acquired)  $entry['acquired']  = $acquired;
  if ($notes)     $entry['notes']     = $notes;
  if ($images)    $entry['before_images'] = $images;
  if ($isEdit && !empty($existing['promoted_to'])) {
    $entry['promoted_to'] = $existing['promoted_to'];
    if (!empty($existing['promoted_id'])) $entry['promoted_id'] = $existing['promoted_id'];
  }

  if ($isEdit) {
    foreach ($all as &$e3) { if ($e3['id'] === $rid) { $e3 = $entry; break; } }
    unset($e3);
  } else {
    $all[] = $entry;
  }
  rescueSort($all);
  file_put_contents(RESCUES_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  header('Location: ' . ADMIN_FILENAME . '#section-rescues');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_rescue') {
  $rid = trim($_POST['rc_id'] ?? '');
  if ($rid !== '') {
    $all = file_exists(RESCUES_FILE) ? (json_decode(file_get_contents(RESCUES_FILE), true) ?? []) : [];
    foreach ($all as $e4) {
      if ($e4['id'] === $rid) {
        foreach ($e4['before_images'] ?? [] as $img) {
          if ($img) { $fp = __DIR__ . '/' . $img; if (file_exists($fp)) @unlink($fp); }
        }
        break;
      }
    }
    $all = array_values(array_filter($all, fn($e) => $e['id'] !== $rid));
    file_put_contents(RESCUES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  }
  header('Location: ' . ADMIN_FILENAME . '#section-rescues');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'set_rescue_stage') {
  header('Content-Type: application/json');
  $rid = trim($_POST['rc_id'] ?? '');
  if (!$rid) { echo json_encode(['ok' => false]); exit; }
  $all = file_exists(RESCUES_FILE) ? (json_decode(file_get_contents(RESCUES_FILE), true) ?? []) : [];
  $newStage = null;
  foreach ($all as &$e5) {
    if ($e5['id'] === $rid) {
      $stages = RESCUE_STAGES;
      $cur = array_search($e5['stage'] ?? 'received', $stages, true);
      $next = ($cur !== false && $cur < count($stages) - 1) ? $stages[$cur + 1] : ($cur !== false ? $stages[$cur] : 'received');
      $e5['stage'] = $next;
      $newStage = $next;
      break;
    }
  }
  unset($e5);
  if ($newStage === null) { echo json_encode(['ok' => false]); exit; }
  rescueSort($all);
  file_put_contents(RESCUES_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  echo json_encode(['ok' => true, 'stage' => $newStage]);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'promote_rescue') {
  header('Content-Type: application/json');
  $rid  = trim($_POST['rc_id']      ?? '');
  $dest = trim($_POST['promote_to'] ?? '');
  if (!$rid || !in_array($dest, ['bench', 'shame'], true)) { echo json_encode(['ok' => false]); exit; }
  $all = file_exists(RESCUES_FILE) ? (json_decode(file_get_contents(RESCUES_FILE), true) ?? []) : [];
  $found = false; $eName = ''; $eFaction = ''; $eSystem = ''; $eCount = 1; $eAcq = '';
  foreach ($all as &$e6) {
    if ($e6['id'] === $rid) {
      $eName    = $e6['name']    ?? '';
      $eFaction = $e6['faction'] ?? '';
      $eSystem  = $e6['system']  ?? '';
      $eCount   = max(1, (int)($e6['count'] ?? 1));
      $eAcq     = $e6['acquired'] ?? '';
      $newId    = (string)(time() + rand(0, 9));
      $e6['promoted_to'] = $dest;
      $e6['promoted_id'] = $newId;
      $found = true;
      break;
    }
  }
  unset($e6);
  if (!$found) { echo json_encode(['ok' => false]); exit; }
  rescueSort($all);
  file_put_contents(RESCUES_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  if ($dest === 'bench') {
    $bench = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
    $entry = ['id' => $newId, 'name' => $eName, 'stage' => 'built', 'last_touched' => date('Y-m-d')];
    if ($eFaction) $entry['faction'] = $eFaction;
    if ($eSystem)  $entry['system']  = $eSystem;
    if ($eCount > 1) $entry['count'] = $eCount;
    $bench[] = $entry;
    usort($bench, function ($a, $b) {
      $ad = ($a['stage'] ?? 'built') === 'done';
      $bd = ($b['stage'] ?? 'built') === 'done';
      if ($ad !== $bd) return $ad ? 1 : -1;
      $la = $a['last_touched'] ?? '';
      $lb = $b['last_touched'] ?? '';
      if ($la !== $lb) return strcmp($lb, $la);
      return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
    file_put_contents(BENCH_FILE, json_encode(array_values($bench), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  } else {
    $shame = file_exists(SHAME_FILE) ? (json_decode(file_get_contents(SHAME_FILE), true) ?? []) : [];
    $entry = ['id' => $newId, 'name' => $eName, 'status' => 'opened'];
    if ($eFaction) $entry['faction']  = $eFaction;
    if ($eSystem)  $entry['system']   = $eSystem;
    if ($eCount > 1) $entry['count']  = $eCount;
    if ($eAcq)     $entry['acquired'] = $eAcq;
    $shame[] = $entry;
    shameSort($shame);
    file_put_contents(SHAME_FILE, json_encode(array_values($shame), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  }
  echo json_encode(['ok' => true, 'promoted_to' => $dest]);
  exit;
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

if ($authed && ($_POST['action'] ?? '') === 'create_shame_file') {
  file_put_contents(SHAME_FILE, '[]', LOCK_EX);
  header('Location: ' . ADMIN_FILENAME . '#section-shame');
  exit;
}

if ($authed && in_array($_POST['action'] ?? '', ['add_shame', 'edit_shame'], true)) {
  $sid     = trim($_POST['sh_id']       ?? '');
  $name    = trim($_POST['sh_name']     ?? '');
  $system  = trim($_POST['sh_system']   ?? '');
  $faction = trim($_POST['sh_faction']  ?? '');
  $count   = (int)($_POST['sh_count']   ?? 0);
  $status  = trim($_POST['sh_status']   ?? 'sealed');
  $acq     = trim($_POST['sh_acquired'] ?? '');
  $notes   = trim($_POST['sh_notes']    ?? '');
  if (!$name) {
    header('Location: ' . ADMIN_FILENAME . '#section-shame');
    exit;
  }
  if (!in_array($system, ['40k', '30k / HH', 'AoS', 'Old World', 'Epic', 'Blood Bowl', 'Necromunda', 'Kill Team', 'OPR', 'Other'], true)) $system = 'Other';
  if (!in_array($status, ['sealed', 'opened', 'partial'], true)) $status = 'sealed';
  if ($acq && !preg_match('/^\d{4}-\d{2}$/', $acq)) $acq = '';
  $all = file_exists(SHAME_FILE) ? (json_decode(file_get_contents(SHAME_FILE), true) ?? []) : [];
  if ($sid !== '') {
    foreach ($all as &$e) {
      if ($e['id'] === $sid) {
        $e['name'] = $name;
        $e['system'] = $system;
        $e['status'] = $status;
        if ($faction) $e['faction'] = $faction;
        else unset($e['faction']);
        if ($count > 0) $e['count'] = $count;
        else unset($e['count']);
        if ($acq) $e['acquired'] = $acq;
        else unset($e['acquired']);
        if ($notes) $e['notes'] = $notes;
        else unset($e['notes']);
        break;
      }
    }
    unset($e);
  } else {
    $entry = ['id' => (string)time(), 'name' => $name, 'system' => $system, 'status' => $status];
    if ($faction) $entry['faction']  = $faction;
    if ($count > 0) $entry['count']  = $count;
    if ($acq)     $entry['acquired'] = $acq;
    if ($notes)   $entry['notes']    = $notes;
    $all[] = $entry;
  }
  shameSort($all);
  file_put_contents(SHAME_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  header('Location: ' . ADMIN_FILENAME . '#section-shame');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_shame') {
  $sid = trim($_POST['sh_id'] ?? '');
  if ($sid !== '') {
    $all = file_exists(SHAME_FILE) ? (json_decode(file_get_contents(SHAME_FILE), true) ?? []) : [];
    $all = array_values(array_filter($all, fn($e) => $e['id'] !== $sid));
    file_put_contents(SHAME_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  }
  header('Location: ' . ADMIN_FILENAME . '#section-shame');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'promote_shame') {
  header('Content-Type: application/json');
  $sid  = trim($_POST['sh_id']      ?? '');
  $dest = trim($_POST['promote_to'] ?? '');
  if (!$sid || !in_array($dest, ['planned', 'bench'], true)) {
    echo json_encode(['ok' => false]);
    exit;
  }
  $all = file_exists(SHAME_FILE) ? (json_decode(file_get_contents(SHAME_FILE), true) ?? []) : [];
  $found = false;
  foreach ($all as &$e) {
    if ($e['id'] === $sid) {
      $e['promoted_to'] = $dest;
      $eName    = $e['name']    ?? '';
      $eFaction = $e['faction'] ?? '';
      $found = true;
      break;
    }
  }
  unset($e);
  if (!$found) {
    echo json_encode(['ok' => false]);
    exit;
  }
  shameSort($all);
  file_put_contents(SHAME_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  $newId = (string)(time() + rand(0, 9));
  if ($dest === 'planned') {
    $planned = file_exists(PLANNED_FILE) ? (json_decode(file_get_contents(PLANNED_FILE), true) ?? []) : [];
    $entry = ['id' => $newId, 'name' => $eName];
    if ($eFaction) $entry['faction'] = $eFaction;
    $planned[] = $entry;
    usort($planned, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
    file_put_contents(PLANNED_FILE, json_encode(array_values($planned), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  } else {
    $bench = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
    $entry = ['id' => $newId, 'name' => $eName, 'stage' => 'built', 'last_touched' => date('Y-m-d')];
    if ($eFaction) $entry['faction'] = $eFaction;
    $bench[] = $entry;
    usort($bench, function ($a, $b) {
      $ad = ($a['stage'] ?? 'built') === 'done';
      $bd = ($b['stage'] ?? 'built') === 'done';
      if ($ad !== $bd) return $ad ? 1 : -1;
      $la = $a['last_touched'] ?? $a['date_start'] ?? '';
      $lb = $b['last_touched'] ?? $b['date_start'] ?? '';
      if ($la !== $lb) return strcmp($lb, $la);
      return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
    $benchJson = json_encode(array_values($bench), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($benchJson === false || file_put_contents(BENCH_FILE, $benchJson, LOCK_EX) === false) {
      echo json_encode(['ok' => false, 'error' => 'bench_write_failed: ' . json_last_error_msg()]);
      exit;
    }
  }
  echo json_encode(['ok' => true, 'promoted_to' => $dest]);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'promote_wishlist') {
  header('Content-Type: application/json');
  $wid = trim($_POST['wl_id'] ?? '');
  if (!$wid) { echo json_encode(['ok' => false]); exit; }
  $all = file_exists(WISHLIST_FILE) ? (json_decode(file_get_contents(WISHLIST_FILE), true) ?? []) : [];
  $found = false;
  $eName = $eFaction = $eSystem = '';
  foreach ($all as &$w) {
    if ($w['id'] === $wid && ($w['type'] ?? '') === 'model' && empty($w['promoted_to'])) {
      $w['promoted_to'] = 'shame';
      $eName    = $w['name']    ?? '';
      $eFaction = $w['faction'] ?? '';
      $eSystem  = $w['system']  ?? '';
      $found = true;
      break;
    }
  }
  unset($w);
  if (!$found) { echo json_encode(['ok' => false]); exit; }
  file_put_contents(WISHLIST_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  $newId = (string)(time() + rand(0, 9));
  $shame = file_exists(SHAME_FILE) ? (json_decode(file_get_contents(SHAME_FILE), true) ?? []) : [];
  $entry = ['id' => $newId, 'name' => $eName, 'status' => 'sealed', 'acquired' => date('Y-m')];
  if ($eFaction) $entry['faction'] = $eFaction;
  if ($eSystem)  $entry['system']  = $eSystem;
  $shame[] = $entry;
  shameSort($shame);
  file_put_contents(SHAME_FILE, json_encode(array_values($shame), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  echo json_encode(['ok' => true]);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'add_conversion') {
  $cit  = trim($_POST['cv_citadel'] ?? '');
  $val  = trim($_POST['cv_vallejo'] ?? '');
  $pa   = trim($_POST['cv_pa']      ?? '');
  $ttc  = trim($_POST['cv_ttc']     ?? '');
  $valQ = trim($_POST['cv_val_q']   ?? '');
  $paQ  = trim($_POST['cv_pa_q']    ?? '');
  $ttcQ = trim($_POST['cv_ttc_q']   ?? '');
  if ($cit !== '') {
    $rows   = readConversionsCsv();
    $rows[] = [$cit, $val, $pa, $ttc, $valQ, $paQ, $ttcQ];
    writeConversionsCsv($rows);
    $_SESSION['flash'] = '"' . htmlspecialchars($cit) . '" added to conversions.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-conversions');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'edit_conversion') {
  $orig = trim($_POST['cv_orig']    ?? '');
  $cit  = trim($_POST['cv_citadel'] ?? '');
  $val  = trim($_POST['cv_vallejo'] ?? '');
  $pa   = trim($_POST['cv_pa']      ?? '');
  $ttc  = trim($_POST['cv_ttc']     ?? '');
  $valQ = trim($_POST['cv_val_q']   ?? '');
  $paQ  = trim($_POST['cv_pa_q']    ?? '');
  $ttcQ = trim($_POST['cv_ttc_q']   ?? '');
  if ($orig !== '' && $cit !== '') {
    $rows = readConversionsCsv();
    foreach ($rows as &$r) {
      if (strcasecmp($r[0], $orig) === 0) {
        $r = [$cit, $val, $pa, $ttc, $valQ, $paQ, $ttcQ];
        break;
      }
    }
    unset($r);
    writeConversionsCsv($rows);
    $_SESSION['flash'] = '"' . htmlspecialchars($cit) . '" updated.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-conversions');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_conversion') {
  $orig = trim($_POST['cv_orig'] ?? '');
  if ($orig !== '') {
    $rows = readConversionsCsv();
    $rows = array_values(array_filter($rows, fn($r) => strcasecmp($r[0], $orig) !== 0));
    writeConversionsCsv($rows);
    $_SESSION['flash'] = '"' . htmlspecialchars($orig) . '" removed from conversions.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-conversions');
  exit;
}

function brushSort(array &$arr): void
{
  $rank = ['prime' => 0, 'workhorse' => 1, 'retired' => 2];
  usort($arr, function ($a, $b) use ($rank) {
    $ra = $rank[$a['condition'] ?? 'prime'] ?? 0;
    $rb = $rank[$b['condition'] ?? 'prime'] ?? 0;
    if ($ra !== $rb) return $ra - $rb;
    return strcmp(
      strtolower(($a['brand'] ?? '') . ($a['series'] ?? '') . ($a['size'] ?? '')),
      strtolower(($b['brand'] ?? '') . ($b['series'] ?? '') . ($b['size'] ?? ''))
    );
  });
}

if ($authed && ($_POST['action'] ?? '') === 'create_brushes_file') {
  file_put_contents(BRUSHES_FILE, '[]', LOCK_EX);
  $_SESSION['flash'] = 'Brush inventory started.';
  header('Location: ' . ADMIN_FILENAME . '#section-brushes');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'add_brush') {
  $brand      = trim($_POST['br_brand']      ?? '');
  $series     = trim($_POST['br_series']     ?? '');
  $size       = trim($_POST['br_size']       ?? '');
  $material   = trim($_POST['br_material']   ?? '');
  $use        = trim($_POST['br_use']        ?? '');
  $condition  = trim($_POST['br_condition']  ?? 'prime');
  $date_start = trim($_POST['br_date_start'] ?? '');
  $notes      = trim($_POST['br_notes']      ?? '');
  $stars      = (int)($_POST['br_stars']     ?? 0);
  if (!in_array($condition, ['prime', 'workhorse', 'retired'], true)) $condition = 'prime';
  if ($stars < 1 || $stars > 5) $stars = 0;
  if ($brand !== '') {
    $all  = file_exists(BRUSHES_FILE) ? (json_decode(file_get_contents(BRUSHES_FILE), true) ?? []) : [];
    $ts   = (string)time();
    $id   = $ts;
    $n    = 1;
    $ids  = array_column($all, 'id');
    while (in_array($id, $ids)) {
      $id = $ts . $n++;
    }
    $entry = ['id' => $id, 'brand' => $brand, 'condition' => $condition];
    if ($series)     $entry['series']     = $series;
    if ($size)       $entry['size']       = $size;
    if ($material)   $entry['material']   = $material;
    if ($use)        $entry['use']        = $use;
    if ($stars)      $entry['stars']      = $stars;
    if ($date_start) $entry['date_start'] = $date_start;
    if ($notes)      $entry['notes']      = $notes;
    $all[] = $entry;
    brushSort($all);
    file_put_contents(BRUSHES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Brush added.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-brushes');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'edit_brush') {
  $bid        = trim($_POST['brush_id']      ?? '');
  $brand      = trim($_POST['br_brand']      ?? '');
  $series     = trim($_POST['br_series']     ?? '');
  $size       = trim($_POST['br_size']       ?? '');
  $material   = trim($_POST['br_material']   ?? '');
  $use        = trim($_POST['br_use']        ?? '');
  $condition  = trim($_POST['br_condition']  ?? 'prime');
  $date_start = trim($_POST['br_date_start'] ?? '');
  $notes      = trim($_POST['br_notes']      ?? '');
  $stars      = (int)($_POST['br_stars']     ?? 0);
  if (!in_array($condition, ['prime', 'workhorse', 'retired'], true)) $condition = 'prime';
  if ($stars < 1 || $stars > 5) $stars = 0;
  if ($bid !== '' && $brand !== '') {
    $all = file_exists(BRUSHES_FILE) ? (json_decode(file_get_contents(BRUSHES_FILE), true) ?? []) : [];
    foreach ($all as &$b) {
      if ($b['id'] === $bid) {
        $b = ['id' => $bid, 'brand' => $brand, 'condition' => $condition];
        if ($series)     $b['series']     = $series;
        if ($size)       $b['size']       = $size;
        if ($material)   $b['material']   = $material;
        if ($use)        $b['use']        = $use;
        if ($stars)      $b['stars']      = $stars;
        if ($date_start) $b['date_start'] = $date_start;
        if ($notes)      $b['notes']      = $notes;
        break;
      }
    }
    unset($b);
    brushSort($all);
    file_put_contents(BRUSHES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Brush updated.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-brushes');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_brush') {
  $bid = trim($_POST['brush_id'] ?? '');
  if ($bid !== '') {
    $all = file_exists(BRUSHES_FILE) ? (json_decode(file_get_contents(BRUSHES_FILE), true) ?? []) : [];
    $all = array_values(array_filter($all, fn($b) => $b['id'] !== $bid));
    file_put_contents(BRUSHES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Brush deleted.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-brushes');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'set_brush_condition') {
  header('Content-Type: application/json');
  $bid  = trim($_POST['brush_id']  ?? '');
  $cond = trim($_POST['condition'] ?? '');
  if ($bid !== '' && in_array($cond, ['prime', 'workhorse', 'retired'], true)) {
    $all = file_exists(BRUSHES_FILE) ? (json_decode(file_get_contents(BRUSHES_FILE), true) ?? []) : [];
    foreach ($all as &$b) {
      if ($b['id'] === $bid) {
        $b['condition'] = $cond;
        break;
      }
    }
    unset($b);
    brushSort($all);
    file_put_contents(BRUSHES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true, 'condition' => $cond]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

function suppliesSort(array &$arr): void
{
  $rank = ['prime' => 0, 'workhorse' => 1, 'retired' => 2];
  usort($arr, function ($a, $b) use ($rank) {
    $ra = $rank[$a['condition'] ?? 'prime'] ?? 0;
    $rb = $rank[$b['condition'] ?? 'prime'] ?? 0;
    if ($ra !== $rb) return $ra - $rb;
    $ta = strtolower($a['type'] ?? '');
    $tb = strtolower($b['type'] ?? '');
    if ($ta !== $tb) return strcmp($ta, $tb);
    return strcmp(strtolower($a['name'] ?? ''), strtolower($b['name'] ?? ''));
  });
}

if ($authed && ($_POST['action'] ?? '') === 'create_supplies_file') {
  file_put_contents(SUPPLIES_FILE, '[]', LOCK_EX);
  $_SESSION['flash'] = 'Supplies inventory started.';
  header('Location: ' . ADMIN_FILENAME . '#section-supplies');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'add_supply') {
  $name      = trim($_POST['sp_name']      ?? '');
  $brand     = trim($_POST['sp_brand']     ?? '');
  $type      = trim($_POST['sp_type']      ?? '');
  $condition = trim($_POST['sp_condition'] ?? 'prime');
  $acquired  = trim($_POST['sp_acquired']  ?? '');
  $notes     = trim($_POST['sp_notes']     ?? '');
  if (!in_array($condition, ['prime', 'workhorse', 'retired'], true)) $condition = 'prime';
  if ($name !== '') {
    $all = file_exists(SUPPLIES_FILE) ? (json_decode(file_get_contents(SUPPLIES_FILE), true) ?? []) : [];
    $ts  = (string)time();
    $id  = $ts;
    $n   = 1;
    $ids = array_column($all, 'id');
    while (in_array($id, $ids)) {
      $id = $ts . $n++;
    }
    $entry = ['id' => $id, 'name' => $name, 'condition' => $condition];
    if ($brand)    $entry['brand']    = $brand;
    if ($type)     $entry['type']     = $type;
    if ($acquired) $entry['acquired'] = $acquired;
    if ($notes)    $entry['notes']    = $notes;
    $all[] = $entry;
    suppliesSort($all);
    file_put_contents(SUPPLIES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Supply added.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-supplies');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'edit_supply') {
  $sid       = trim($_POST['supply_id']    ?? '');
  $name      = trim($_POST['sp_name']      ?? '');
  $brand     = trim($_POST['sp_brand']     ?? '');
  $type      = trim($_POST['sp_type']      ?? '');
  $condition = trim($_POST['sp_condition'] ?? 'prime');
  $acquired  = trim($_POST['sp_acquired']  ?? '');
  $notes     = trim($_POST['sp_notes']     ?? '');
  if (!in_array($condition, ['prime', 'workhorse', 'retired'], true)) $condition = 'prime';
  if ($sid !== '' && $name !== '') {
    $all = file_exists(SUPPLIES_FILE) ? (json_decode(file_get_contents(SUPPLIES_FILE), true) ?? []) : [];
    foreach ($all as &$s) {
      if ($s['id'] === $sid) {
        $s = ['id' => $sid, 'name' => $name, 'condition' => $condition];
        if ($brand)    $s['brand']    = $brand;
        if ($type)     $s['type']     = $type;
        if ($acquired) $s['acquired'] = $acquired;
        if ($notes)    $s['notes']    = $notes;
        break;
      }
    }
    unset($s);
    suppliesSort($all);
    file_put_contents(SUPPLIES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Supply updated.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-supplies');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_supply') {
  $sid = trim($_POST['supply_id'] ?? '');
  if ($sid !== '') {
    $all = file_exists(SUPPLIES_FILE) ? (json_decode(file_get_contents(SUPPLIES_FILE), true) ?? []) : [];
    $all = array_values(array_filter($all, fn($s) => $s['id'] !== $sid));
    file_put_contents(SUPPLIES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Supply deleted.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-supplies');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'set_supply_condition') {
  header('Content-Type: application/json');
  $sid  = trim($_POST['supply_id']  ?? '');
  $cond = trim($_POST['condition']  ?? '');
  if ($sid !== '' && in_array($cond, ['prime', 'workhorse', 'retired'], true)) {
    $all = file_exists(SUPPLIES_FILE) ? (json_decode(file_get_contents(SUPPLIES_FILE), true) ?? []) : [];
    foreach ($all as &$s) {
      if ($s['id'] === $sid) {
        $s['condition'] = $cond;
        break;
      }
    }
    unset($s);
    suppliesSort($all);
    file_put_contents(SUPPLIES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true, 'condition' => $cond]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
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

if ($authed && ($_POST['action'] ?? '') === 'create_bench_file') {
  file_put_contents(BENCH_FILE, '[]', LOCK_EX);
  $_SESSION['flash'] = 'Workbench started.';
  header('Location: ' . ADMIN_FILENAME . '#section-bench');
  exit;
}

if ($authed && in_array($_POST['action'] ?? '', ['add_bench', 'edit_bench'], true)) {
  $isEdit       = $_POST['action'] === 'edit_bench';
  $bid          = trim($_POST['bench_id']       ?? '');
  $name         = trim($_POST['bn_name']        ?? '');
  $faction      = trim($_POST['bn_faction']     ?? '');
  $system       = trim($_POST['bn_system']      ?? '');
  $stage        = trim($_POST['bn_stage']       ?? 'built');
  $date_start   = trim($_POST['bn_date_start']  ?? '');
  $notes        = trim($_POST['bn_notes']       ?? '');
  $codex_source = trim($_POST['bn_codex_source'] ?? '');
  $colors       = array_values(array_filter($_POST['bench_colors'] ?? []));
  $brushes      = array_values(array_filter($_POST['bench_brushes'] ?? []));
  $recipes      = array_values(array_filter($_POST['bench_recipes'] ?? []));
  if (!in_array($stage, BENCH_STAGES, true)) $stage = 'built';

  if ($name !== '' && (!$isEdit || $bid !== '')) {
    $all = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];

    // Pick or generate ID
    if ($isEdit) {
      $existing = null;
      foreach ($all as $b) if ($b['id'] === $bid) {
        $existing = $b;
        break;
      }
      $id = $bid;
    } else {
      $ts = (string)time();
      $id = $ts;
      $n = 1;
      $ids = array_column($all, 'id');
      while (in_array($id, $ids, true)) {
        $id = $ts . $n++;
      }
      $existing = null;
    }

    // Build positional image slots starting from existing
    $slotImages = array_pad(array_values($existing['wip_images'] ?? []), BENCH_MAX_IMAGES, null);
    for ($slot = 1; $slot <= BENCH_MAX_IMAGES; $slot++) {
      if (($_POST['delete_bn_img_' . $slot] ?? '0') === '1' && $slotImages[$slot - 1] !== null) {
        $fp = __DIR__ . '/' . $slotImages[$slot - 1];
        if (file_exists($fp)) @unlink($fp);
        $slotImages[$slot - 1] = null;
      }
      $key = 'bn_image' . $slot;
      if (empty($_FILES[$key]['name'])) continue;
      $file = $_FILES[$key];
      if ($file['error'] !== UPLOAD_ERR_OK) continue;
      if ($file['size'] > MAX_FILE_BYTES) continue;
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
      if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) continue;
      $filename = $id . '_' . $slot . '_' . substr(uniqid(), -6) . '.' . imageExt($mime);
      if (saveModelImage($file['tmp_name'], BENCH_IMG_DIR . $filename, $mime)) {
        $slotImages[$slot - 1] = BENCH_IMG_WEB . $filename;
      }
    }
    $images = array_values(array_filter($slotImages, fn($i) => $i !== null));

    $entry = [
      'id'           => $id,
      'name'         => $name,
      'stage'        => $stage,
      'last_touched' => date('Y-m-d'),
    ];
    if ($faction)      $entry['faction']      = $faction;
    if ($system)       $entry['system']       = $system;
    if ($date_start)   $entry['date_start']   = $date_start;
    if ($notes)        $entry['notes']        = $notes;
    if ($codex_source) $entry['codex_source'] = $codex_source;
    if ($colors)       $entry['colors']       = $colors;
    if ($brushes)    $entry['brushes']    = $brushes;
    if ($recipes)    $entry['recipes']    = $recipes;
    if ($images)     $entry['wip_images'] = $images;
    if (!empty($existing['history']))  $entry['history']  = $existing['history'];
    if (!empty($existing['sessions'])) $entry['sessions'] = $existing['sessions'];

    if ($isEdit) {
      foreach ($all as &$b) if ($b['id'] === $bid) {
        $b = $entry;
        break;
      }
      unset($b);
    } else {
      $all[] = $entry;
    }
    benchSort($all);
    file_put_contents(BENCH_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = $isEdit ? 'Bench entry updated.' : 'Bench entry added.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-bench');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_bench') {
  $bid = trim($_POST['bench_id'] ?? '');
  if ($bid !== '') {
    $all = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
    foreach ($all as $b) {
      if ($b['id'] === $bid) {
        foreach (($b['wip_images'] ?? []) as $img) {
          $fp = __DIR__ . '/' . $img;
          if (file_exists($fp)) @unlink($fp);
        }
        break;
      }
    }
    $all = array_values(array_filter($all, fn($b) => $b['id'] !== $bid));
    file_put_contents(BENCH_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Bench entry deleted.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-bench');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'promote_bench') {
  $bid = trim($_POST['bench_id'] ?? '');
  if ($bid !== '') {
    $bench = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
    $found = false;
    $newId = (string)time();
    $eName = $eFaction = $eSystem = '';
    $eColors = $eRecipes = [];
    foreach ($bench as &$b) {
      if ($b['id'] === $bid && empty($b['promoted_to'])) {
        $b['promoted_to'] = 'gallery';
        $b['promoted_id'] = $newId;
        $eName    = $b['name']    ?? '';
        $eFaction = $b['faction'] ?? '';
        $eSystem  = $b['system']  ?? '';
        $eColors  = $b['colors']  ?? [];
        $eRecipes = $b['recipes'] ?? [];
        $found = true;
        break;
      }
    }
    unset($b);
    if ($found) {
      file_put_contents(BENCH_FILE, json_encode(array_values($bench), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
      $models = file_exists(MODELS_FILE) ? (json_decode(file_get_contents(MODELS_FILE), true) ?? []) : [];
      $entry = array_filter([
        'id'      => $newId,
        'name'    => $eName,
        'faction' => $eFaction,
        'system'  => $eSystem,
        'date'    => date('Y-m-d'),
        'colors'  => $eColors,
        'recipes' => $eRecipes,
      ], fn($v) => $v !== '' && $v !== []);
      $models[] = $entry;
      file_put_contents(MODELS_FILE, json_encode(array_values($models), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
      header('Location: ' . ADMIN_FILENAME . '?edit=' . $newId . '#section-gallery');
      exit;
    }
  }
  header('Location: ' . ADMIN_FILENAME . '#section-bench');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'set_bench_stage') {
  header('Content-Type: application/json');
  $bid   = trim($_POST['bench_id'] ?? '');
  $stage = trim($_POST['stage']    ?? '');
  if ($bid !== '' && in_array($stage, BENCH_STAGES, true)) {
    $all = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
    foreach ($all as &$b) {
      if ($b['id'] === $bid) {
        $prev = $b['stage'] ?? '';
        $b['stage'] = $stage;
        $b['last_touched'] = date('Y-m-d');
        $b['history'][] = ['from' => $prev, 'to' => $stage, 'date' => date('Y-m-d')];
        break;
      }
    }
    unset($b);
    benchSort($all);
    file_put_contents(BENCH_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true, 'stage' => $stage]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'log_bench_session') {
  header('Content-Type: application/json');
  $bid      = trim($_POST['bench_id']       ?? '');
  $sessDate = trim($_POST['sess_date']      ?? '');
  $sessDur  = (int)($_POST['sess_duration'] ?? 0);
  $sessNote = trim($_POST['sess_note']      ?? '');
  if ($bid !== '' && $sessDate !== '') {
    $all = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
    foreach ($all as &$b) {
      if ($b['id'] === $bid) {
        $sess = ['date' => $sessDate];
        if ($sessDur > 0)    $sess['duration'] = $sessDur;
        if ($sessNote !== '') $sess['note']    = $sessNote;
        $b['sessions'][] = $sess;
        $b['last_touched'] = date('Y-m-d');
        break;
      }
    }
    unset($b);
    file_put_contents(BENCH_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'set_goal') {
  header('Content-Type: application/json');
  $year   = trim($_POST['goal_year']   ?? '');
  $target = (int)($_POST['goal_target'] ?? 0);
  $seed   = (int)($_POST['goal_seed']   ?? 0);
  if (preg_match('/^\d{4}$/', $year)) {
    $goals = file_exists(GOALS_FILE) ? (json_decode(file_get_contents(GOALS_FILE), true) ?? []) : [];
    if ($target > 0) {
      $goals[$year] = ['target' => $target, 'seed' => max(0, $seed)];
    } else {
      unset($goals[$year]);
    }
    file_put_contents(GOALS_FILE, json_encode($goals, JSON_PRETTY_PRINT), LOCK_EX);
    echo json_encode(['ok' => true]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'log_gallery_session') {
  header('Content-Type: application/json');
  $mid      = trim($_POST['model_id']       ?? '');
  $sessDate = trim($_POST['sess_date']      ?? '');
  $sessCount = (int)($_POST['sess_count']   ?? 0);
  $sessNote = trim($_POST['sess_note']      ?? '');
  if ($mid !== '' && $sessDate !== '' && $sessCount > 0) {
    $all = file_exists(MODELS_FILE) ? (json_decode(file_get_contents(MODELS_FILE), true) ?? []) : [];
    foreach ($all as &$m) {
      if (($m['id'] ?? '') === $mid) {
        $sess = ['date' => $sessDate, 'count' => $sessCount];
        if ($sessNote !== '') $sess['note'] = $sessNote;
        $m['sessions'][] = $sess;
        $existing = isset($m['count']) ? (int)$m['count'] : 0;
        $newCount = $existing + $sessCount;
        if ($newCount > 1) { $m['count'] = $newCount; }
        elseif (isset($m['count'])) { unset($m['count']); }
        break;
      }
    }
    unset($m);
    file_put_contents(MODELS_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_gallery_session') {
  header('Content-Type: application/json');
  $mid = trim($_POST['model_id'] ?? '');
  $idx = (int)($_POST['sess_idx'] ?? -1);
  if ($mid !== '' && $idx >= 0) {
    $all = file_exists(MODELS_FILE) ? (json_decode(file_get_contents(MODELS_FILE), true) ?? []) : [];
    foreach ($all as &$m) {
      if (($m['id'] ?? '') === $mid) {
        if (isset($m['sessions'][$idx])) {
          $oldCount = (int)($m['sessions'][$idx]['count'] ?? 0);
          array_splice($m['sessions'], $idx, 1);
          if (empty($m['sessions'])) unset($m['sessions']);
          $newModelCount = max(0, (int)($m['count'] ?? 1) - $oldCount);
          if ($newModelCount > 1) $m['count'] = $newModelCount;
          else unset($m['count']);
        }
        break;
      }
    }
    unset($m);
    file_put_contents(MODELS_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'edit_gallery_session') {
  header('Content-Type: application/json');
  $mid      = trim($_POST['model_id']  ?? '');
  $idx      = (int)($_POST['sess_idx'] ?? -1);
  $sessDate = trim($_POST['sess_date'] ?? '');
  $sessCount = (int)($_POST['sess_count'] ?? 0);
  $sessNote = trim($_POST['sess_note'] ?? '');
  if ($mid !== '' && $idx >= 0 && $sessDate !== '' && $sessCount > 0) {
    $all = file_exists(MODELS_FILE) ? (json_decode(file_get_contents(MODELS_FILE), true) ?? []) : [];
    foreach ($all as &$m) {
      if (($m['id'] ?? '') === $mid) {
        if (isset($m['sessions'][$idx])) {
          $oldCount = (int)($m['sessions'][$idx]['count'] ?? 0);
          $sess = ['date' => $sessDate, 'count' => $sessCount];
          if ($sessNote !== '') $sess['note'] = $sessNote;
          $m['sessions'][$idx] = $sess;
          $newModelCount = max(0, (int)($m['count'] ?? 1) + ($sessCount - $oldCount));
          if ($newModelCount > 1) $m['count'] = $newModelCount;
          else unset($m['count']);
        }
        break;
      }
    }
    unset($m);
    file_put_contents(MODELS_FILE, json_encode(array_values($all), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true]);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

const RECIPE_TECHNIQUES = ['basecoat', 'wash', 'shade', 'layer', 'edge', 'highlight', 'glaze', 'drybrush', 'stipple', 'blend', 'special'];

function recipeSort(array &$arr): void
{
  usort($arr, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
}

function buildRecipeSteps(array $post): array
{
  $paints      = $post['step_paint']      ?? [];
  $mixPaints   = $post['step_mix_paint']  ?? [];
  $techniques  = $post['step_technique']  ?? [];
  $ratios      = $post['step_ratio']      ?? [];
  $notes       = $post['step_note']       ?? [];
  $brushes     = $post['step_brush']      ?? [];
  $steps = [];
  $n = count($paints);
  for ($i = 0; $i < $n; $i++) {
    $paint = trim($paints[$i] ?? '');
    $tech  = trim($techniques[$i] ?? '');
    if ($paint === '') continue;
    if (!in_array($tech, RECIPE_TECHNIQUES, true)) $tech = 'special';
    $s = ['paint' => $paint, 'technique' => $tech];
    $mp = trim($mixPaints[$i] ?? '');
    if ($mp !== '') $s['mix_paint'] = $mp;
    $r = trim($ratios[$i] ?? '');
    if ($r !== '') $s['ratio'] = $r;
    $nt = trim($notes[$i] ?? '');
    if ($nt !== '') $s['note']  = $nt;
    $br = trim($brushes[$i] ?? '');
    if ($br !== '') $s['brush'] = $br;
    $steps[] = $s;
  }
  return $steps;
}

if ($authed && ($_POST['action'] ?? '') === 'create_recipes_file') {
  file_put_contents(RECIPES_FILE, '[]', LOCK_EX);
  $_SESSION['flash'] = 'Recipe library started.';
  header('Location: ' . ADMIN_FILENAME . '#section-recipes');
  exit;
}

if ($authed && in_array($_POST['action'] ?? '', ['add_recipe', 'edit_recipe'], true)) {
  $isEdit      = $_POST['action'] === 'edit_recipe';
  $rid         = trim($_POST['recipe_id']   ?? '');
  $name        = trim($_POST['rc_name']     ?? '');
  $category    = trim($_POST['rc_category'] ?? '');
  $faction     = trim($_POST['rc_faction']  ?? '');
  $description = trim($_POST['rc_description'] ?? '');
  $notes       = trim($_POST['rc_notes']    ?? '');
  $steps       = buildRecipeSteps($_POST);

  if ($name !== '' && (!$isEdit || $rid !== '')) {
    $all = file_exists(RECIPES_FILE) ? (json_decode(file_get_contents(RECIPES_FILE), true) ?? []) : [];
    if ($isEdit) {
      $id = $rid;
    } else {
      $ts = (string)time();
      $id = $ts;
      $n = 1;
      $ids = array_column($all, 'id');
      while (in_array($id, $ids, true)) {
        $id = $ts . $n++;
      }
    }
    // Preserve existing image on edit unless replaced or deleted
    $existingImage = '';
    if ($isEdit) {
      foreach ($all as $r) if ($r['id'] === $rid) { $existingImage = $r['image'] ?? ''; break; }
    }
    $image = $existingImage;
    if (($_POST['delete_rc_image'] ?? '0') === '1' && $existingImage !== '') {
      $fp = __DIR__ . '/' . $existingImage;
      if (file_exists($fp)) @unlink($fp);
      $image = '';
    } elseif (!empty($_FILES['rc_image']['name']) && $_FILES['rc_image']['error'] === UPLOAD_ERR_OK) {
      $file = $_FILES['rc_image'];
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
      if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']) && $file['size'] <= MAX_FILE_BYTES) {
        $filename = $id . '_1.' . imageExt($mime);
        if (saveModelImage($file['tmp_name'], RECIPE_IMG_DIR . $filename, $mime)) {
          if ($existingImage !== '' && $existingImage !== RECIPE_IMG_WEB . $filename) {
            $fp = __DIR__ . '/' . $existingImage;
            if (file_exists($fp)) @unlink($fp);
          }
          $image = RECIPE_IMG_WEB . $filename;
        }
      }
    }

    $entry = ['id' => $id, 'name' => $name];
    if ($category)    $entry['category']    = $category;
    if ($faction)     $entry['faction']     = $faction;
    if ($description) $entry['description'] = $description;
    $entry['steps'] = $steps;
    if ($notes)       $entry['notes']       = $notes;
    if ($image !== '') $entry['image']      = $image;

    if ($isEdit) {
      foreach ($all as &$r) if ($r['id'] === $rid) {
        $r = $entry;
        break;
      }
      unset($r);
    } else {
      $all[] = $entry;
    }
    recipeSort($all);
    file_put_contents(RECIPES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = $isEdit ? 'Recipe updated.' : 'Recipe added.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-recipes');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_recipe') {
  $rid = trim($_POST['recipe_id'] ?? '');
  if ($rid !== '') {
    $all = file_exists(RECIPES_FILE) ? (json_decode(file_get_contents(RECIPES_FILE), true) ?? []) : [];
    foreach ($all as $r) {
      if ($r['id'] === $rid && !empty($r['image'])) {
        $fp = __DIR__ . '/' . $r['image'];
        if (file_exists($fp)) @unlink($fp);
      }
    }
    $all = array_values(array_filter($all, fn($r) => $r['id'] !== $rid));
    file_put_contents(RECIPES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Recipe deleted.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-recipes');
  exit;
}

$successMsg = '';
$formError  = '';

if (!empty($_SESSION['flash'])) {
  $successMsg = $_SESSION['flash'];
  unset($_SESSION['flash']);
}

if ($authed && isset($_POST['action']) && $_POST['action'] === 'export_backup') {
  $bundle = [
    '_meta' => [
      'app'         => 'Waaagh! Paint',
      'exported_at' => date('c'),
      'version'     => 1,
    ],
  ];
  $files = [
    'paints'    => __DIR__ . '/data/paints.json',
    'models'    => __DIR__ . '/data/models.json',
    'planned'   => __DIR__ . '/data/planned.json',
    'brushes'   => __DIR__ . '/data/brushes.json',
    'bench'     => __DIR__ . '/data/bench.json',
    'recipes'   => __DIR__ . '/data/recipes.json',
    'books'     => __DIR__ . '/data/books.json',
    'journal'   => __DIR__ . '/data/journal.json',
    'shame'     => __DIR__ . '/data/shame.json',
    'wishlist'  => __DIR__ . '/data/wishlist.json',
    'forces'    => __DIR__ . '/data/forces.json',
    'battles'   => __DIR__ . '/data/battles.json',
    'supplies'  => __DIR__ . '/data/supplies.json',
    'rescues'   => __DIR__ . '/data/rescues.json',
    'goals'     => __DIR__ . '/data/goals.json',
    'tab_stats' => __DIR__ . '/data/tab_stats.json',
  ];
  foreach ($files as $key => $path) {
    if (file_exists($path)) {
      $bundle[$key] = json_decode(file_get_contents($path), true) ?? [];
    }
  }
  while (ob_get_level()) ob_end_clean();
  $filename = 'waaagh-paint-backup-' . date('Y-m-d') . '.json';
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

if ($authed && isset($_POST['action']) && $_POST['action'] === 'import_backup') {
  $result = ['ok' => false, 'msg' => '', 'restored' => []];
  $upload = $_FILES['backup_file'] ?? null;
  if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
    $result['msg'] = 'No file uploaded or upload error.';
  } else {
    $raw = file_get_contents($upload['tmp_name']);
    $data = $raw ? json_decode($raw, true) : null;
    if (!$data || !isset($data['_meta']['app']) || $data['_meta']['app'] !== 'Waaagh! Paint') {
      $result['msg'] = 'Invalid backup file - not a Waaagh! Paint backup.';
    } else {
      $allowed = ['paints','models','planned','brushes','bench','recipes','books','journal','shame','wishlist','forces','battles','supplies','rescues','goals','tab_stats'];
      foreach ($allowed as $key) {
        if (!array_key_exists($key, $data)) continue;
        $path = __DIR__ . '/data/' . $key . '.json';
        $encoded = json_encode($data[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($path, $encoded, LOCK_EX) !== false) {
          $result['restored'][] = $key;
        }
      }
      if ($result['restored']) {
        $result['ok'] = true;
        $result['msg'] = 'Restored ' . count($result['restored']) . ' file' . (count($result['restored']) !== 1 ? 's' : '') . ': ' . implode(', ', $result['restored']) . '. Backup exported at ' . ($data['_meta']['exported_at'] ?? 'unknown date') . '.';
      } else {
        $result['msg'] = 'Backup parsed but contained no restorable data files.';
      }
    }
  }
  $_SESSION['flash'] = ($result['ok'] ? 'ok|' : 'err|') . $result['msg'];
  header('Location: admin.php#section-stats');
  exit;
}

if ($authed && isset($_POST['action']) && $_POST['action'] === 'add_model') {
  $name         = trim($_POST['model_name']    ?? '');
  $faction      = trim($_POST['faction']       ?? '');
  $system       = trim($_POST['system']        ?? '');
  $date         = trim($_POST['date']          ?? '');
  $description  = trim($_POST['description']   ?? '');
  $codex_source = trim($_POST['codex_source']  ?? '');
  $count        = max(1, (int)($_POST['model_count'] ?? 1));
  $colors       = $_POST['colors'] ?? [];
  $recipes      = array_values(array_filter($_POST['gallery_recipes'] ?? []));
  $theme_hex    = preg_match('/^#[0-9a-fA-F]{6}$/', trim($_POST['theme_hex'] ?? '')) ? strtolower(trim($_POST['theme_hex'])) : '';
  $summary      = array_filter([
    'finish'    => trim($_POST['summary_finish']    ?? ''),
    'primary'   => trim($_POST['summary_primary']   ?? ''),
    'contrast'  => trim($_POST['summary_contrast']  ?? ''),
    'technique' => trim($_POST['summary_technique'] ?? ''),
  ]);

  if ($name === '') {
    $formError = 'Model name is required.';
  } else {
    $id     = (string)time();
    $images = [];

    for ($i = 1; $i <= 4; $i++) {
      $key = 'image' . $i;
      if (empty($_FILES[$key]['name'])) continue;
      $file = $_FILES[$key];
      if ($file['error'] !== UPLOAD_ERR_OK) continue;
      if ($file['size'] > MAX_FILE_BYTES) {
        $formError = "Image $i exceeds 25 MB limit.";
        break;
      }
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
      if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
        $formError = "Image $i is not a supported image type.";
        break;
      }
      $ext      = imageExt($mime);
      $filename = $id . '_' . $i . '.' . $ext;
      $dest     = IMAGES_DIR . $filename;
      if (saveModelImage($file['tmp_name'], $dest, $mime)) {
        $images[] = IMAGES_WEB . $filename;
      } else {
        $formError = "Image $i could not be saved. Check folder permissions on img/models/.";
        break;
      }
    }

    if ($formError === '') {
      $models   = file_exists(MODELS_FILE) ? (json_decode(file_get_contents(MODELS_FILE), true) ?? []) : [];
      $entry = array_filter([
        'id'           => $id,
        'name'         => $name,
        'faction'      => $faction,
        'date'         => $date,
        'description'  => $description,
        'codex_source' => $codex_source,
        'images'       => $images,
        'colors'       => array_values(array_filter($colors)),
        'recipes'      => $recipes,
      ], fn($v) => $v !== '' && $v !== []);
      if (!empty($summary))   $entry['summary']    = $summary;
      if ($theme_hex !== '')  $entry['theme_hex']  = $theme_hex;
      if ($system !== '')     $entry['system']     = $system;
      if ($count > 1)         $entry['count']      = $count;
      $models[] = $entry;
      file_put_contents(MODELS_FILE, json_encode(array_values($models), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
      $successMsg = 'Model "' . htmlspecialchars($name) . '" added successfully.';
    }
  }
}

if ($authed && isset($_POST['action']) && $_POST['action'] === 'cleanup_scheme_colors') {
  $models      = file_exists(MODELS_FILE)   ? (json_decode(file_get_contents(MODELS_FILE),   true) ?? []) : [];
  $recipesData = file_exists(RECIPES_FILE)  ? (json_decode(file_get_contents(RECIPES_FILE),  true) ?? []) : [];
  $recipeMap   = [];
  foreach ($recipesData as $r) $recipeMap[$r['id']] = $r;
  $removed = 0;
  foreach ($models as &$m) {
    if (empty($m['recipes']) || empty($m['colors'])) continue;
    $covered = [];
    foreach ($m['recipes'] as $rid) {
      if (empty($recipeMap[$rid])) continue;
      foreach ($recipeMap[$rid]['steps'] ?? [] as $step) {
        if (!empty($step['paint'])) $covered[strtolower($step['paint'])] = true;
      }
    }
    if (!$covered) continue;
    $before = count($m['colors']);
    $m['colors'] = array_values(array_filter($m['colors'], fn($c) => !isset($covered[strtolower($c)])));
    $removed += $before - count($m['colors']);
  }
  unset($m);
  file_put_contents(MODELS_FILE, json_encode(array_values($models), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
  $successMsg = "Removed $removed duplicate paint reference" . ($removed !== 1 ? 's' : '') . " from scheme color lists.";
}

if ($authed && isset($_POST['action']) && $_POST['action'] === 'delete_model') {
  $delId  = $_POST['model_id'] ?? '';
  $models = file_exists(MODELS_FILE) ? (json_decode(file_get_contents(MODELS_FILE), true) ?? []) : [];
  $models = array_values(array_filter($models, fn($m) => ($m['id'] ?? '') !== $delId));
  file_put_contents(MODELS_FILE, json_encode($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
  $successMsg = 'Entry deleted.';
}

if ($authed && isset($_POST['action']) && $_POST['action'] === 'edit_model') {
  $editId       = trim($_POST['model_id']      ?? '');
  $name         = trim($_POST['model_name']    ?? '');
  $faction      = trim($_POST['faction']       ?? '');
  $system       = trim($_POST['system']        ?? '');
  $date         = trim($_POST['date']          ?? '');
  $description  = trim($_POST['description']   ?? '');
  $codex_source = trim($_POST['codex_source']  ?? '');
  $count        = max(1, (int)($_POST['model_count'] ?? 1));
  $colors       = $_POST['colors'] ?? [];
  $recipes      = array_values(array_filter($_POST['gallery_recipes'] ?? []));
  $theme_hex    = preg_match('/^#[0-9a-fA-F]{6}$/', trim($_POST['theme_hex'] ?? '')) ? strtolower(trim($_POST['theme_hex'])) : '';
  $summary      = array_filter([
    'finish'    => trim($_POST['summary_finish']    ?? ''),
    'primary'   => trim($_POST['summary_primary']   ?? ''),
    'contrast'  => trim($_POST['summary_contrast']  ?? ''),
    'technique' => trim($_POST['summary_technique'] ?? ''),
  ]);

  if ($name === '') {
    $formError = 'Model name is required.';
  } elseif ($editId === '') {
    $formError = 'Invalid model ID.';
  } else {
    $models = file_exists(MODELS_FILE) ? (json_decode(file_get_contents(MODELS_FILE), true) ?? []) : [];
    $idx    = null;
    foreach ($models as $i => $m) {
      if (($m['id'] ?? '') === $editId) {
        $idx = $i;
        break;
      }
    }
    if ($idx === null) {
      $formError = 'Model not found.';
    } else {
      $slotImages = array_pad(array_values($models[$idx]['images'] ?? []), 4, null);
      for ($slot = 1; $slot <= 4; $slot++) {
        if (($_POST['delete_img_' . $slot] ?? '0') === '1' && $slotImages[$slot - 1] !== null) {
          $filePath = __DIR__ . '/' . $slotImages[$slot - 1];
          if (file_exists($filePath)) @unlink($filePath);
          $slotImages[$slot - 1] = null;
        }
        $key = 'image' . $slot;
        if (empty($_FILES[$key]['name'])) continue;
        $file = $_FILES[$key];
        if ($file['error'] !== UPLOAD_ERR_OK) continue;
        if ($file['size'] > MAX_FILE_BYTES) {
          $formError = "Image $slot exceeds 25 MB limit.";
          break;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
          $formError = "Image $slot is not a supported image type.";
          break;
        }
        $filename = $editId . '_' . $slot . '_' . substr(uniqid(), -6) . '.' . imageExt($mime);
        if (saveModelImage($file['tmp_name'], IMAGES_DIR . $filename, $mime)) {
          $slotImages[$slot - 1] = IMAGES_WEB . $filename;
        } else {
          $formError = "Image $slot could not be saved.";
          break;
        }
      }
      if ($formError === '') {
        $entry = ['id' => $editId, 'name' => $name];
        if ($faction !== '')      $entry['faction']      = $faction;
        if ($date !== '')         $entry['date']         = $date;
        if ($description !== '')  $entry['description']  = $description;
        if ($codex_source !== '') $entry['codex_source'] = $codex_source;
        $imgs = array_values(array_filter($slotImages));
        if (!empty($imgs))       $entry['images']      = $imgs;
        $cols = array_values(array_filter($colors));
        if (!empty($cols))       $entry['colors']      = $cols;
        if (!empty($recipes))    $entry['recipes']     = $recipes;
        if (!empty($summary))    $entry['summary']     = $summary;
        if ($theme_hex !== '')   $entry['theme_hex']   = $theme_hex;
        if ($system !== '')      $entry['system']      = $system;
        if ($count > 1)          $entry['count']       = $count;
        if (!empty($models[$idx]['sessions'])) $entry['sessions'] = $models[$idx]['sessions'];
        $models[$idx] = $entry;
        file_put_contents(MODELS_FILE, json_encode(array_values($models), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        $_SESSION['flash'] = 'Model "' . htmlspecialchars($name) . '" updated successfully.';
        header('Location: ' . ADMIN_FILENAME);
        exit;
      }
    }
  }
}

$models    = file_exists(MODELS_FILE) ? (json_decode(file_get_contents(MODELS_FILE), true) ?? []) : [];
$convRows  = readConversionsCsv();

if ($authed && ($_POST['action'] ?? '') === 'create_forces_file') {
  if (!file_exists(FORCES_FILE)) file_put_contents(FORCES_FILE, '[]', LOCK_EX);
  $_SESSION['flash'] = 'Forces & Rosters started.';
  header('Location: ' . ADMIN_FILENAME . '#section-forces');
  exit;
}

if ($authed && in_array($_POST['action'] ?? '', ['add_force', 'edit_force'], true)) {
  $isEdit  = $_POST['action'] === 'edit_force';
  $fid     = trim($_POST['force_id']      ?? '');
  $name    = trim($_POST['fo_name']       ?? '');
  $system  = trim($_POST['fo_system']     ?? '');
  $faction = trim($_POST['fo_faction']    ?? '');
  $target_count  = max(0, (int)($_POST['fo_target_count']  ?? 0));
  $target_points = max(0, (int)($_POST['fo_target_points'] ?? 0));
  $notes      = trim($_POST['fo_notes']      ?? '');
  $roster_url = trim($_POST['fo_roster_url'] ?? '');
  $fmodels = array_values(array_filter($_POST['force_models'] ?? []));
  if ($name !== '' && (!$isEdit || $fid !== '')) {
    $all = file_exists(FORCES_FILE) ? (json_decode(file_get_contents(FORCES_FILE), true) ?? []) : [];
    $id  = $isEdit ? $fid : (string)time();
    $entry = ['id' => $id, 'name' => $name];
    if ($system)         $entry['system']        = $system;
    if ($faction)        $entry['faction']       = $faction;
    if ($target_count)   $entry['target_count']  = $target_count;
    if ($target_points)  $entry['target_points'] = $target_points;
    if ($notes)          $entry['notes']         = $notes;
    if ($roster_url)     $entry['roster_url']    = $roster_url;
    if ($fmodels)        $entry['models']        = $fmodels;
    if ($isEdit) {
      foreach ($all as &$f) if ($f['id'] === $fid) {
        if (!empty($f['pinned'])) $entry['pinned'] = true;
        $f = $entry;
        break;
      }
      unset($f);
    } else {
      $all[] = $entry;
    }
    usort($all, fn($a, $b) => strcmp($a['name'], $b['name']));
    file_put_contents(FORCES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $forcesData = $all;
    $hasForces  = true;
    $_SESSION['flash'] = '"' . htmlspecialchars($name) . '" ' . ($isEdit ? 'updated.' : 'added.');
  }
  header('Location: ' . ADMIN_FILENAME . '#section-forces');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'toggle_force_pin') {
  header('Content-Type: application/json');
  $fid = trim($_POST['force_id'] ?? '');
  if (!$fid || !file_exists(FORCES_FILE)) {
    echo json_encode(['ok' => false]);
    exit;
  }
  $all = json_decode(file_get_contents(FORCES_FILE), true) ?? [];
  $targetIdx = -1;
  foreach ($all as $i => $f) {
    if ($f['id'] === $fid) {
      $targetIdx = $i;
      break;
    }
  }
  if ($targetIdx === -1) {
    echo json_encode(['ok' => false]);
    exit;
  }
  $nowPinned = empty($all[$targetIdx]['pinned']);
  $unpinnedId = null;
  if ($nowPinned) {
    $pinned = array_values(array_filter($all, fn($x) => !empty($x['pinned']) && $x['id'] !== $fid));
    if (count($pinned) >= 2) {
      $unpinnedId = $pinned[0]['id'];
      foreach ($all as &$f2) {
        if ($f2['id'] === $unpinnedId) {
          unset($f2['pinned']);
          break;
        }
      }
      unset($f2);
    }
  }
  if ($nowPinned) $all[$targetIdx]['pinned'] = true;
  else unset($all[$targetIdx]['pinned']);
  file_put_contents(FORCES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
  echo json_encode(['ok' => true, 'pinned' => $nowPinned, 'unpinned_id' => $unpinnedId]);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_force') {
  $fid = trim($_POST['force_id'] ?? '');
  if ($fid !== '' && file_exists(FORCES_FILE)) {
    $all = json_decode(file_get_contents(FORCES_FILE), true) ?? [];
    $all = array_values(array_filter($all, fn($f) => $f['id'] !== $fid));
    file_put_contents(FORCES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Force deleted.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-forces');
  exit;
}

function wishlistSort(array &$arr): void
{
  $rank = ['high' => 0, 'medium' => 1, 'low' => 2];
  usort(
    $arr,
    fn($a, $b) => ($rank[$a['priority'] ?? 'medium'] <=> $rank[$b['priority'] ?? 'medium']) ?:
      strcmp($a['type'] ?? '', $b['type'] ?? '') ?:
      strcmp($a['name'] ?? '', $b['name'] ?? '')
  );
}

function battlesSort(array &$arr): void
{
  usort($arr, fn($x, $y) => strcmp($y['date'] . $y['id'], $x['date'] . $x['id']));
}

if ($authed && ($_POST['action'] ?? '') === 'create_battles_file') {
  if (!file_exists(BATTLES_FILE)) file_put_contents(BATTLES_FILE, '[]', LOCK_EX);
  $hasBattles = true;
  header('Location: ' . ADMIN_FILENAME . '#section-battles');
  exit;
}

if ($authed && in_array($_POST['action'] ?? '', ['add_battle', 'edit_battle'], true)) {
  $isEdit  = $_POST['action'] === 'edit_battle';
  $bid     = trim($_POST['bh_id'] ?? '');
  $bdate   = trim($_POST['bh_date'] ?? '');
  $bresult = in_array($_POST['bh_result'] ?? '', ['win', 'loss', 'draw']) ? $_POST['bh_result'] : 'draw';
  if ($bdate !== '') {
    $all = file_exists(BATTLES_FILE) ? (json_decode(file_get_contents(BATTLES_FILE), true) ?? []) : [];
    $entry = ['id' => $isEdit ? $bid : (string)time(), 'date' => $bdate, 'result' => $bresult];
    $bforce   = trim($_POST['bh_force_id']      ?? ''); if ($bforce   !== '') $entry['force_id']      = $bforce;
    $bsys     = trim($_POST['bh_system']         ?? ''); if ($bsys     !== '') $entry['system']         = $bsys;
    $bpts     = trim($_POST['bh_points']         ?? ''); if ($bpts     !== '' && is_numeric($bpts)) $entry['points'] = (int)$bpts;
    $barmy    = trim($_POST['bh_my_army']        ?? ''); if ($barmy    !== '') $entry['my_army']        = $barmy;
    $bopp     = trim($_POST['bh_opponent']       ?? ''); if ($bopp     !== '') $entry['opponent']       = $bopp;
    $bopparmy = trim($_POST['bh_opponent_army']  ?? ''); if ($bopparmy !== '') $entry['opponent_army']  = $bopparmy;
    $bmission = trim($_POST['bh_mission']        ?? ''); if ($bmission !== '') $entry['mission']        = $bmission;
    $bnotes   = trim($_POST['bh_notes']          ?? ''); if ($bnotes   !== '') $entry['notes']          = $bnotes;
    if ($isEdit) {
      foreach ($all as &$b) { if ($b['id'] === $bid) { $entry['id'] = $bid; $b = $entry; break; } }
      unset($b);
    } else {
      $all[] = $entry;
    }
    battlesSort($all);
    file_put_contents(BATTLES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = $isEdit ? 'Battle updated.' : 'Battle logged.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-battles');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_battle') {
  $bid = trim($_POST['bh_id'] ?? '');
  if ($bid !== '' && file_exists(BATTLES_FILE)) {
    $all = json_decode(file_get_contents(BATTLES_FILE), true) ?? [];
    $all = array_values(array_filter($all, fn($b) => $b['id'] !== $bid));
    file_put_contents(BATTLES_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Battle deleted.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-battles');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'create_wishlist_file') {
  if (!file_exists(WISHLIST_FILE)) file_put_contents(WISHLIST_FILE, '[]', LOCK_EX);
  $hasWishlist = true;
  header('Location: ' . ADMIN_FILENAME . '#section-wishlist');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'add_wishlist_item') {
  $wtype = in_array($_POST['wl_type'] ?? '', ['paint', 'model', 'brush', 'codex', 'wd']) ? $_POST['wl_type'] : 'paint';
  $wname = trim($_POST['wl_name'] ?? '');
  if ($wname !== '') {
    $all = file_exists(WISHLIST_FILE) ? (json_decode(file_get_contents(WISHLIST_FILE), true) ?? []) : [];
    $entry = ['id' => (string)time(), 'type' => $wtype, 'name' => $wname];
    $wbrand   = trim($_POST['wl_brand']   ?? '');
    if ($wbrand   !== '') $entry['brand']    = $wbrand;
    $wfaction = trim($_POST['wl_faction'] ?? '');
    if ($wfaction !== '') $entry['faction']  = $wfaction;
    $wsystem  = trim($_POST['wl_system']  ?? '');
    if ($wsystem  !== '') $entry['system']   = $wsystem;
    $wpri = in_array($_POST['wl_priority'] ?? '', ['high', 'medium', 'low']) ? $_POST['wl_priority'] : 'medium';
    if ($wpri !== 'medium') $entry['priority'] = $wpri;
    $wnotes = trim($_POST['wl_notes'] ?? '');
    if ($wnotes !== '') $entry['notes'] = $wnotes;
    $wurl   = trim($_POST['wl_url']   ?? '');
    if ($wurl   !== '') $entry['url']   = $wurl;
    $wordered = trim($_POST['wl_ordered_date'] ?? '');
    if ($wordered !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $wordered)) $entry['ordered_date'] = $wordered;
    $entry['added'] = date('Y-m-d');
    $all[] = $entry;
    wishlistSort($all);
    file_put_contents(WISHLIST_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Added to wishlist.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-wishlist');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'edit_wishlist_item') {
  $wid   = trim($_POST['wl_id']   ?? '');
  $wname = trim($_POST['wl_name'] ?? '');
  $wtype = in_array($_POST['wl_type'] ?? '', ['paint', 'model', 'brush', 'codex', 'wd']) ? $_POST['wl_type'] : 'paint';
  if ($wid !== '' && $wname !== '' && file_exists(WISHLIST_FILE)) {
    $all = json_decode(file_get_contents(WISHLIST_FILE), true) ?? [];
    foreach ($all as &$w) {
      if ($w['id'] === $wid) {
        $w['type'] = $wtype;
        $w['name'] = $wname;
        $wbrand   = trim($_POST['wl_brand']   ?? '');
        if ($wbrand   !== '') $w['brand']    = $wbrand;
        else unset($w['brand']);
        $wfaction = trim($_POST['wl_faction'] ?? '');
        if ($wfaction !== '') $w['faction']  = $wfaction;
        else unset($w['faction']);
        $wsystem  = trim($_POST['wl_system']  ?? '');
        if ($wsystem  !== '') $w['system']   = $wsystem;
        else unset($w['system']);
        $wpri = in_array($_POST['wl_priority'] ?? '', ['high', 'medium', 'low']) ? $_POST['wl_priority'] : 'medium';
        if ($wpri !== 'medium') $w['priority'] = $wpri;
        else unset($w['priority']);
        $wnotes = trim($_POST['wl_notes'] ?? '');
        if ($wnotes !== '') $w['notes'] = $wnotes;
        else unset($w['notes']);
        $wurl   = trim($_POST['wl_url']   ?? '');
        if ($wurl   !== '') $w['url']   = $wurl;
        else unset($w['url']);
        $wordered = trim($_POST['wl_ordered_date'] ?? '');
        if ($wordered !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $wordered)) $w['ordered_date'] = $wordered;
        else unset($w['ordered_date']);
        break;
      }
    }
    unset($w);
    wishlistSort($all);
    file_put_contents(WISHLIST_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Wishlist entry updated.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-wishlist');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'delete_wishlist_item') {
  $wid = trim($_POST['wl_id'] ?? '');
  if ($wid !== '' && file_exists(WISHLIST_FILE)) {
    $all = json_decode(file_get_contents(WISHLIST_FILE), true) ?? [];
    $all = array_values(array_filter($all, fn($w) => $w['id'] !== $wid));
    file_put_contents(WISHLIST_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Removed from wishlist.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-wishlist');
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'set_wishlist_ordered') {
  $wid      = trim($_POST['wl_id']           ?? '');
  $wordered = trim($_POST['wl_ordered_date'] ?? '');
  if ($wid !== '' && file_exists(WISHLIST_FILE)) {
    $all = json_decode(file_get_contents(WISHLIST_FILE), true) ?? [];
    foreach ($all as &$w) {
      if ($w['id'] === $wid) {
        if ($wordered !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $wordered)) {
          $w['ordered_date'] = $wordered;
        } else {
          unset($w['ordered_date']);
        }
        break;
      }
    }
    unset($w);
    file_put_contents(WISHLIST_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
  } else {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'not found']);
  }
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'seed_wishlist_from_planned') {
  $planned  = file_exists(PLANNED_FILE)  ? (json_decode(file_get_contents(PLANNED_FILE),  true) ?? []) : [];
  $existing = file_exists(WISHLIST_FILE) ? (json_decode(file_get_contents(WISHLIST_FILE), true) ?? []) : [];

  // Build owned set (stock !== 'wanted' and stock !== 'out') keyed brand|name lowercase
  $owned = [];
  foreach ($paints as $p) {
    $st = $p['stock'] ?? '';
    if ($st !== 'wanted' && $st !== 'out') {
      $owned[strtolower(($p['brand'] ?? '') . '|' . ($p['name'] ?? ''))] = true;
    }
  }
  // Build existing wishlist set to avoid duplicates
  $inWishlist = [];
  foreach ($existing as $w) {
    $inWishlist[strtolower(($w['brand'] ?? '') . '|' . ($w['name'] ?? ''))] = true;
  }

  $added = 0;
  foreach ($planned as $scheme) {
    foreach (($scheme['colors'] ?? []) as $colorKey) {
      // color key is brand|name or brand|name|layer
      $parts = explode('|', $colorKey);
      if (count($parts) < 2) continue;
      $brand = trim($parts[0]);
      $name  = trim($parts[1]);
      $lc    = strtolower($brand . '|' . $name);
      if (!isset($owned[$lc]) && !isset($inWishlist[$lc]) && $brand !== '' && $name !== '') {
        $existing[] = ['id' => (string)(time() + $added), 'type' => 'paint', 'name' => $name, 'brand' => $brand, 'priority' => 'medium', 'added' => date('Y-m-d')];
        $inWishlist[$lc] = true;
        $added++;
      }
    }
  }

  if ($added > 0) {
    wishlistSort($existing);
    file_put_contents(WISHLIST_FILE, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $_SESSION['flash'] = 'Added ' . $added . ' paint' . ($added !== 1 ? 's' : '') . ' from Planned schemes.';
  } else {
    $_SESSION['flash'] = 'Nothing new to add - all missing Planned paints are already in your wishlist.';
  }
  header('Location: ' . ADMIN_FILENAME . '#section-wishlist');
  exit;
}

$editModel = null;
if ($authed && isset($_GET['edit'])) {
  $reqEditId = $_GET['edit'];
  foreach ($models as $m) {
    if (($m['id'] ?? '') === $reqEditId) {
      $editModel = $m;
      break;
    }
  }
}

$editForce = null;
if ($authed && isset($_GET['edit_force'])) {
  $reqFid = $_GET['edit_force'];
  foreach ($forcesData as $f) {
    if (($f['id'] ?? '') === $reqFid) {
      $editForce = $f;
      break;
    }
  }
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
  <title>Admin - Waaagh! Paint</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css?v=12">
</head>

<body<?php if ($authed && $editModel): ?> data-open-section="section-gallery" <?php elseif ($authed && $editForce): ?> data-open-section="section-forces" <?php endif; ?>>

  <?php if (!$authed): ?>
  <header>
    <a href="index.php"><img src="img/logo_sm.png" alt="Waaagh! Paint" class="logo"></a>
    <p>Gallery Admin</p>
  </header>
  <?php endif; ?>

  <?php if ($authed): ?>
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="as-header">
      <a href="index.php"><img src="img/logo_sm.png" alt="Waaagh! Paint" class="as-logo"></a>
      <div class="as-badge">Admin Panel</div>
      <button class="as-collapse-btn" id="as-collapse-btn" aria-label="Collapse sidebar">&#8249;</button>
    </div>
    <nav class="as-nav">
      <div class="as-group">
        <div class="as-group-label">The Work</div>
        <a href="#section-bench" class="as-link">On the Bench</a>
        <a href="#section-gallery" class="as-link">Add Scheme</a>
        <a href="#section-entries" class="as-link">Edit Schemes</a>
        <a href="#section-planned" class="as-link">Planned</a>
        <?php if ($hasShame): ?><a href="#section-shame" class="as-link">Pile of Shame</a><?php endif; ?>
        <?php if ($hasRescues): ?><a href="#section-rescues" class="as-link">Rescue Tracker</a><?php endif; ?>
        <?php if ($hasForces): ?><a href="#section-forces" class="as-link">Forces</a><?php endif; ?>
        <?php if ($hasBattles): ?><a href="#section-battles" class="as-link">Battle Honours</a><?php endif; ?>
        <?php if ($hasRecipes): ?><a href="#section-recipes" class="as-link">Recipes</a><?php endif; ?>
        <?php if ($hasJournal): ?><a href="#section-journal" class="as-link">Scrap Notes</a><?php endif; ?>
      </div>
      <div class="as-group">
        <div class="as-group-label">Collection</div>
        <a href="#section-inventory" class="as-link">Paint Inventory</a>
        <?php if ($hasBrushes): ?><a href="#section-brushes" class="as-link">Brushes</a><?php endif; ?>
        <?php if ($hasSupplies): ?><a href="#section-supplies" class="as-link">Supplies</a><?php endif; ?>
        <?php if ($hasWishlist): ?><a href="#section-wishlist" class="as-link">Wishlist</a><?php endif; ?>
        <?php if ($hasBooks): ?><a href="#section-books" class="as-link">Codices</a><?php endif; ?>
      </div>
      <div class="as-group">
        <div class="as-group-label">Tools</div>
        <a href="#section-stats" class="as-link">Hobby Stats</a>
        <a href="#section-checker" class="as-link">Paint Checker</a>
        <a href="#section-conversions" class="as-link">Equivalency</a>
        <a href="guide.php" target="_blank" class="as-link">User Guide &#8599;</a>
      </div>
      <div class="asnav-fade" id="asnav-fade"></div>
    </nav>
    <div class="as-footer">
      <a href="index.php" class="as-back-link">&#8592; Back to site</a>
      <form method="post" style="margin:0"><input type="hidden" name="action" value="export_backup"><button type="submit" class="btn btn-sm" title="Download a JSON backup of every data file">Export Backup</button></form>
      <button class="btn btn-sm" onclick="document.getElementById('import-backup-panel').style.display=document.getElementById('import-backup-panel').style.display==='none'?'flex':'none'" title="Restore from a previously exported backup JSON">Import Backup</button>
      <form method="post" style="margin:0"><button name="logout" value="1" class="btn btn-sm">Log out</button></form>
    </div>
  <div id="import-backup-panel" style="display:none;flex-direction:column;gap:6px;padding:10px 12px;border-top:1px solid #2a1e08;background:#0a0806;">
    <div style="font-family:var(--font-heading);font-size:.58rem;letter-spacing:.12em;color:#8a3020;text-transform:uppercase;">Restore from Backup</div>
    <div style="font-size:.62rem;color:#5a4828;">This overwrites existing data files. Images are not included in backups.</div>
    <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:6px;">
      <input type="hidden" name="action" value="import_backup">
      <input type="file" name="backup_file" accept=".json" required style="font-size:.65rem;color:#c4b49a;">
      <button type="submit" class="btn btn-sm" style="border-color:#8a2020;color:#c47a6a;" onclick="return confirm('This will overwrite your current data with the backup. Continue?')">Restore</button>
    </form>
  </div>
  </aside>
  <button class="admin-sidebar-toggle" id="admin-sidebar-toggle" aria-label="Toggle navigation">&#9776;</button>
  <div class="admin-main">
  <?php endif; ?>

  <div class="admin-wrap">

    <?php if (!$authed): ?>
      <div class="auth-box">
        <h2>Admin Access</h2>
        <?php if ($authError): ?><div class="alert alert-error"><?= e($authError) ?></div><?php endif; ?>
        <form method="post">
          <label for="pw">Password</label>
          <input type="password" id="pw" name="password" autofocus style="margin-bottom:14px">
          <button type="submit" class="btn" style="width:100%">Enter</button>
        </form>
      </div>

    <?php else: ?>
      <?php if ($successMsg): ?><div class="alert alert-success"><?= e($successMsg) ?></div><?php endif; ?>
      <?php if ($formError):   ?><div class="alert alert-error"><?= e($formError) ?></div><?php endif; ?>

      <h2 id="section-stats">Hobby Stats</h2>
      <p style="font-size:.72rem;color:#5a4828;margin:0 0 12px;font-style:italic;">Full intelligence dossier &rarr; <a href="index.php?tab=commissar" target="_blank" style="color:#7a6a3a;">The Commissar tab</a></p>
      <?php
      $ownedPaints = array_values(array_filter($paints, fn($p) => ($p['stock'] ?? '') !== 'wanted'));
      $wantedCount = count($paints) - count($ownedPaints);
      $lowOutCount = count(array_filter($paints, fn($p) => in_array($p['stock'] ?? '', ['low', 'out'], true)));
      $ownedKeys = array_flip(array_map(fn($p) => strtolower($p['brand'] . '|' . $p['name']), $ownedPaints));
      $missingPlanned = 0;
      $seenMissing = [];
      foreach ($planned as $scheme) {
        foreach ($scheme['colors'] ?? [] as $key) {
          $lc = strtolower($key);
          if (!isset($ownedKeys[$lc]) && !isset($seenMissing[$lc])) {
            $seenMissing[$lc] = true;
            $missingPlanned++;
          }
        }
      }

      $byBrand = [];
      foreach ($ownedPaints as $p) {
        $byBrand[$p['brand']] = ($byBrand[$p['brand']] ?? 0) + 1;
      }
      arsort($byBrand);
      $maxBrand = $byBrand ? max(array_values($byBrand)) : 1;

      $usageCount = [];
      foreach ($models as $m) {
        $mc = max(1, (int)($m['count'] ?? 1));
        foreach ($m['colors'] ?? [] as $c) {
          $usageCount[$c] = ($usageCount[$c] ?? 0) + $mc;
        }
      }
      arsort($usageCount);
      $topPaints = array_slice($usageCount, 0, 8, true);

      $byFaction = [];
      foreach ($models as $m) {
        if (!empty($m['faction'])) {
          $byFaction[$m['faction']] = ($byFaction[$m['faction']] ?? 0) + max(1, (int)($m['count'] ?? 1));
        }
      }
      arsort($byFaction);
      $noFaction = count(array_filter($models, fn($m) => empty($m['faction'])));

      $byYear = [];
      foreach ($models as $m) {
        $yr = !empty($m['date']) ? substr($m['date'], 0, 4) : 'Undated';
        $byYear[$yr] = ($byYear[$yr] ?? 0) + max(1, (int)($m['count'] ?? 1));
      }
      krsort($byYear);

      $sessionsByYear = [];
      foreach ($models as $m) {
        foreach ($m['sessions'] ?? [] as $s) {
          $yr = !empty($s['date']) ? substr($s['date'], 0, 4) : null;
          if ($yr) $sessionsByYear[$yr] = ($sessionsByYear[$yr] ?? 0) + max(1, (int)($s['count'] ?? 1));
        }
      }

      $totalSessions = 0; $totalMinutes = 0;
      foreach ($benchData as $b) {
        foreach ($b['sessions'] ?? [] as $s) {
          $totalSessions++;
          $totalMinutes += (int)($s['duration'] ?? 0);
        }
      }

      // Waaagh! Meter computation (same rolling 7-day logic as index.php)
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
      $adMeterScore = max(0, min(10, $_mScore));
      if      ($adMeterScore <= 1) { $adMeterState = "DOZIN'";         $adMeterClass = 'meter-s0'; }
      elseif  ($adMeterScore <= 3) { $adMeterState = "STIRRIN'";       $adMeterClass = 'meter-s1'; }
      elseif  ($adMeterScore <= 5) { $adMeterState = "ON DA WARPATH";  $adMeterClass = 'meter-s2'; }
      elseif  ($adMeterScore <= 7) { $adMeterState = "WAAAGH!";        $adMeterClass = 'meter-s3'; }
      else                          { $adMeterState = "FULL WAAAGH!!"; $adMeterClass = 'meter-s4'; }
      $_mNotes = 0; $_mBattles = 0;
      if ($hasJournal)  foreach ($journalData as $_mj2) { if (($_mj2['date']  ?? '') >= $_mWkAgo) $_mNotes++; }
      if ($hasBattles)  foreach ($battlesData as $_mbh) { if (($_mbh['date']  ?? '') >= $_mWkAgo) $_mBattles++; }
      $adWkParts = [];
      if ($_mModels  > 0) $adWkParts[] = $_mModels  . ' model'         . ($_mModels  !== 1 ? 's' : '') . ' painted';
      if ($_mBench   > 0) $adWkParts[] = $_mBench   . ' bench session' . ($_mBench   !== 1 ? 's' : '');
      if ($_mBattles > 0) $adWkParts[] = $_mBattles . ' battle'        . ($_mBattles !== 1 ? 's' : '') . ' fought';
      if ($_mNotes   > 0) $adWkParts[] = $_mNotes   . ' note'          . ($_mNotes   !== 1 ? 's' : '') . ' scribbled';
      $adWkActive = !empty($adWkParts);
      unset($_mWkAgo, $_mScore, $_mModels, $_mBench, $_mm, $_ms, $_mb, $_bs, $_mj, $_mActive, $_mTs, $_mDays, $_mNotes, $_mBattles, $_mj2, $_mbh);
      ?>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-num"><?= count($ownedPaints) ?></div>
          <div class="stat-label">Paints Owned</div>
        </div>
        <div class="stat-card">
          <div class="stat-num"><?= count($models) ?></div>
          <div class="stat-label">Recorded Schemes</div>
        </div>
        <?php
        $totalPainted = array_sum(array_map(fn($m) => max(1, (int)($m['count'] ?? 1)), $models));
        ?>
        <?php if ($totalPainted > count($models)): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $totalPainted ?></div>
            <div class="stat-label">Models Painted</div>
          </div>
        <?php endif; ?>
        <div class="stat-card">
          <div class="stat-num"><?= count($planned) ?></div>
          <div class="stat-label">Planned</div>
        </div>
        <?php if ($hasBooks): ?>
          <div class="stat-card">
            <div class="stat-num"><?= count($booksData) ?></div>
            <div class="stat-label">Codex Library</div>
          </div>
        <?php endif; ?>
        <?php if ($hasBrushes): ?>
          <div class="stat-card">
            <div class="stat-num"><?= count(array_filter($brushesData, fn($b) => ($b['condition'] ?? 'prime') !== 'retired')) ?></div>
            <div class="stat-label">Active Brushes</div>
          </div>
        <?php endif; ?>
        <?php if ($hasRecipes): ?>
          <div class="stat-card">
            <div class="stat-num"><?= count($recipesData) ?></div>
            <div class="stat-label">Recipes</div>
          </div>
        <?php endif; ?>
        <?php if ($totalSessions > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $totalSessions ?></div>
            <div class="stat-label">Sessions</div>
          </div>
        <?php endif; ?>
        <?php if ($totalMinutes > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= floor($totalMinutes / 60) ?>h <?= $totalMinutes % 60 ?>m</div>
            <div class="stat-label">Hobby Hours</div>
          </div>
        <?php endif; ?>
        <?php if ($wantedCount > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $wantedCount ?></div>
            <div class="stat-label">Wanted</div>
          </div>
        <?php endif; ?>
        <?php if ($lowOutCount > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $lowOutCount ?></div>
            <div class="stat-label">Low / Out</div>
          </div>
        <?php endif; ?>
        <?php if ($missingPlanned > 0): ?>
          <div class="stat-card stat-card-clickable" onclick="openPlannedShopModal()">
            <div class="stat-num"><?= $missingPlanned ?></div>
            <div class="stat-label">Missing (Planned)</div>
          </div>
        <?php endif; ?>
        <?php if ($hasBattles && count($battlesData)): ?>
          <?php
            $bw = count(array_filter($battlesData, fn($b) => ($b['result'] ?? '') === 'win'));
            $bl = count(array_filter($battlesData, fn($b) => ($b['result'] ?? '') === 'loss'));
            $bd = count(array_filter($battlesData, fn($b) => ($b['result'] ?? '') === 'draw'));
          ?>
          <div class="stat-card">
            <div class="stat-num"><?= count($battlesData) ?></div>
            <div class="stat-label">Battles (<?= $bw ?>W <?= $bl ?>L <?= $bd ?>D)</div>
          </div>
        <?php endif; ?>
        <?php if ($hasRescues):
          $cntRescuesActive = count(array_filter($rescuesData, fn($r) => empty($r['promoted_to'])));
          $cntRescueUnits   = array_sum(array_map(fn($r) => max(1, (int)($r['count'] ?? 1)), array_filter($rescuesData, fn($r) => empty($r['promoted_to']))));
        ?>
        <?php if ($cntRescuesActive > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $cntRescuesActive ?></div>
            <div class="stat-label">Active Rescues</div>
          </div>
          <?php if ($cntRescueUnits > $cntRescuesActive): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $cntRescueUnits ?></div>
            <div class="stat-label">Rescue Units</div>
          </div>
          <?php endif; ?>
        <?php endif; ?>
        <?php
          $cntShameActive = array_sum(array_map(fn($s) => max(1, (int)($s['count'] ?? 1)), array_filter($shameData, fn($s) => empty($s['promoted_to']))));
          $cntBenchActive = array_sum(array_map(fn($b) => max(1, (int)($b['count'] ?? 1)), array_filter($benchData, fn($b) => empty($b['promoted_to']))));
          $_totalUnits = $totalPainted + $cntBenchActive + $cntShameActive + $cntRescueUnits;
          if ($_totalUnits > $totalPainted):
        ?>
          <div class="stat-card">
            <div class="stat-num"><?= $_totalUnits ?></div>
            <div class="stat-label">Total Units Tracked</div>
          </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="stats-two-col">
        <div>
          <div class="stats-sub-heading">Collection by Brand</div>
          <?php foreach ($byBrand as $brand => $cnt): ?>
            <div class="stats-bar-row">
              <span class="stats-bar-label-text"><?= e($brand) ?></span>
              <div class="stats-bar-track">
                <div class="stats-bar-fill" style="width:<?= round($cnt / $maxBrand * 100) ?>%"></div>
              </div>
              <span class="stats-bar-count"><?= $cnt ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div>
          <?php if ($models): ?>
            <div class="stats-sub-heading">Gallery by Faction</div>
            <?php foreach ($byFaction as $faction => $cnt): ?>
              <div class="stats-faction-row">
                <span class="stats-faction-name"><?= e($faction) ?></span>
                <span class="stats-faction-count"><?= $cnt ?></span>
              </div>
            <?php endforeach; ?>
            <?php if ($noFaction): ?>
              <div class="stats-faction-row">
                <span class="stats-faction-name" style="color:#3a2a10;font-style:italic">Untagged</span>
                <span class="stats-faction-count"><?= $noFaction ?></span>
              </div>
            <?php endif; ?>
            <?php
              $curYr = date('Y');
              $displayYears = $byYear;
              if (!array_key_exists($curYr, $displayYears)) $displayYears[$curYr] = 0;
              krsort($displayYears);
            ?>
            <?php if (count($displayYears) > 1 || (count($displayYears) === 1 && !array_key_exists('Undated', $displayYears))): ?>
              <div class="stats-sub-heading" style="margin-top:18px">By Year</div>
              <?php foreach ($displayYears as $year => $cnt):
                if ($year === 'Undated') {
                  ?>
                  <div class="stats-faction-row">
                    <span class="stats-faction-name" style="color:#3a2a10;font-style:italic">Undated</span>
                    <span class="stats-faction-count"><?= $cnt ?></span>
                  </div>
                  <?php continue;
                }
                $rawGoal = $goalsData[$year] ?? null;
                $target  = is_array($rawGoal) ? (int)($rawGoal['target'] ?? 0) : (int)($rawGoal ?? 0);
                $seed    = is_array($rawGoal) ? (int)($rawGoal['seed']   ?? 0) : 0;
                $sessCount = $seed + ($sessionsByYear[$year] ?? 0);
                $displayCount = $target > 0 ? $sessCount : $cnt;
                $pct    = $target > 0 ? min(100, (int)round($sessCount / $target * 100)) : 0;
              ?>
                <div class="stats-year-row" id="year-row-<?= e($year) ?>">
                  <div class="stats-year-main">
                    <span class="stats-year-label"><?= e($year) ?></span>
                    <span class="stats-year-count"><?= $displayCount ?><?= $target > 0 ? ' / ' . $target : '' ?></span>
                    <button class="stats-goal-btn" onclick="toggleGoalForm('<?= e($year) ?>')"><?= $target > 0 ? '&#9998;' : '+ Goal' ?></button>
                    <?php if ($target > 0): ?>
                      <button class="stats-goal-del" onclick="deleteGoal('<?= e($year) ?>')" title="Remove goal">&times;</button>
                    <?php endif; ?>
                  </div>
                  <?php if ($target > 0): ?>
                    <div class="stats-goal-bar-wrap"><div class="stats-goal-bar-fill" style="width:<?= $pct ?>%"></div></div>
                    <?php if ($pct >= 100): ?><div class="stats-goal-complete">Goal reached!</div><?php endif; ?>
                  <?php endif; ?>
                  <div class="stats-goal-form" id="goal-form-<?= e($year) ?>" style="display:none">
                    <input type="number" class="stats-goal-input" id="goal-input-<?= e($year) ?>" min="1" placeholder="target" value="<?= $target ?: '' ?>">
                    <input type="number" class="stats-goal-input" id="goal-seed-<?= e($year) ?>" min="0" placeholder="baseline (already painted)" value="<?= $seed ?: '' ?>" style="margin-left:6px">
                    <button onclick="saveGoal('<?= e($year) ?>')" class="btn btn-sm">Save</button>
                    <button onclick="toggleGoalForm('<?= e($year) ?>')" class="btn btn-sm" style="background:#1a1a1a">Cancel</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          <?php else: ?>
            <div style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em">No recorded schemes yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="admin-wm-widget <?= $adMeterClass ?>">
        <img src="img/waaagh.png" class="wm-title-img" alt="Waaagh! Index">
        <div class="wm-gauge">
          <div class="wm-fill"></div>
          <img src="img/gauge.png" class="wm-gauge-img" alt="">
          <div class="wm-needle"></div>
          <div class="wm-pivot"></div>
        </div>
        <div class="wm-state"><?= htmlspecialchars($adMeterState) ?></div>
        <div class="wm-week"><?php if ($adWkActive): ?><?= implode(' &middot; ', $adWkParts) ?><?php else: ?>Nowt done yet&hellip;<?php endif; ?></div>
      </div>

      <?php if ($topPaints): ?>
        <div class="stats-sub-heading" style="margin-top:20px">Most Used Paints</div>
        <div class="stats-top-paints">
          <?php $rank = 1;
          foreach ($topPaints as $key => $cnt): ?>
            <?php $parts = explode('|', $key);
            $pName = $parts[1] ?? $key;
            $pBrand = $parts[0] ?? ''; ?>
            <div class="stats-top-row">
              <span class="stats-top-rank"><?= $rank++ ?></span>
              <span class="stats-top-name"><?= e($pName) ?></span>
              <span class="stats-top-brand"><?= e($pBrand) ?></span>
              <span class="stats-top-count"><?= $cnt ?>×</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($tabStats):
        $tabLabels = [
          'inventory' => 'Paint Inventory',
          'gallery'   => 'Paint Schemes',
          'equiv'     => 'Equivalency',
          'planned'   => 'Planned',
          'brushes'   => 'Brushes',
          'books'     => 'Codices',
        ];
        arsort($tabStats);
        $maxTabCount = max(array_values($tabStats));
      ?>
        <div class="stats-sub-heading" style="margin-top:20px">Tab Visits</div>
        <?php foreach ($tabStats as $tabKey => $tabCnt): ?>
          <div class="stats-bar-row">
            <span class="stats-bar-label-text"><?= e($tabLabels[$tabKey] ?? $tabKey) ?></span>
            <div class="stats-bar-track">
              <div class="stats-bar-fill" style="width:<?= round($tabCnt / $maxTabCount * 100) ?>%"></div>
            </div>
            <span class="stats-bar-count"><?= $tabCnt ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($editModel): ?>
        <h2 id="section-gallery">Edit: <?= e($editModel['name']) ?></h2>
      <?php else: ?>
        <h2 id="section-gallery" style="margin-top:40px">Add a Scheme</h2>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <?php if ($editModel): ?>
          <input type="hidden" name="action" value="edit_model">
          <input type="hidden" name="model_id" value="<?= e($editModel['id']) ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="add_model">
        <?php endif; ?>

        <div class="form-grid">
          <div>
            <label for="model_name">Model Name *</label>
            <input type="text" id="model_name" name="model_name"
              value="<?= $editModel ? e($editModel['name']) : '' ?>"
              placeholder="e.g. Ultramarines Sergeant" required>
          </div>
          <div>
            <label for="faction">Faction / Army</label>
            <input type="text" id="faction" name="faction"
              value="<?= $editModel ? e($editModel['faction'] ?? '') : '' ?>"
              placeholder="e.g. Space Marines">
          </div>
          <div>
            <label for="sys_game">Game System</label>
            <select id="sys_game" name="system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
              <option value="">- none -</option>
              <?php foreach (['40k' => 'Warhammer 40,000', '30k / HH' => 'Horus Heresy / 30k', 'AoS' => 'Age of Sigmar', 'Old World' => 'The Old World', 'Kill Team' => 'Kill Team', 'Blood Bowl' => 'Blood Bowl', 'Necromunda' => 'Necromunda', 'Epic' => 'Epic Scale', 'OPR' => 'One Page Rules', 'Other' => 'Other'] as $sv => $sl): ?>
                <option value="<?= e($sv) ?>" <?= ($editModel['system'] ?? '') === $sv ? ' selected' : '' ?>><?= e($sl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="date">Date Completed</label>
            <input type="date" id="date" name="date"
              value="<?= e($editModel ? ($editModel['date'] ?? '') : date('Y-m-d')) ?>">
          </div>
          <div>
            <label for="model_count">Models Painted</label>
            <input type="number" id="model_count" name="model_count" min="1" step="1"
              value="<?= e($editModel ? (int)($editModel['count'] ?? 1) : 1) ?>"
              title="How many miniatures were painted under this scheme (e.g. 20 for a full Boyz mob)">
          </div>
          <div class="form-full">
            <label for="description">Notes / Description</label>
            <textarea id="description" name="description"
              placeholder="Techniques used, basing, conversions…"><?= $editModel ? e($editModel['description'] ?? '') : '' ?></textarea>
            <small style="display:block;margin-top:5px;color:#4a3a1a;font-size:10px;line-height:1.6">
              <strong style="color:#6a5020;letter-spacing:.04em">Format:</strong>
              ALL CAPS line = section header &nbsp;&middot;&nbsp;
              <code style="background:#1a1408;padding:1px 4px;border-radius:2px">- Label: value</code> = step row &nbsp;&middot;&nbsp;
              <code style="background:#1a1408;padding:1px 4px;border-radius:2px">&nbsp;&nbsp;- item</code> = sub-bullet (2 spaces)<br>
              <span style="color:#3a2e10">e.g. &nbsp;BASE / FLESH / ARMOUR (Red) / OSL (Glow) &nbsp;&rarr;&nbsp; - Prime: Black &nbsp;&rarr;&nbsp; - Base: Mephiston Red</span>
            </small>
          </div>
          <div>
            <label for="summary_finish">Finish</label>
            <input type="text" id="summary_finish" name="summary_finish"
              placeholder="e.g. Worn, field-used"
              value="<?= e($editModel ? ($editModel['summary']['finish'] ?? '') : '') ?>">
          </div>
          <div>
            <label for="summary_primary">Primary</label>
            <input type="text" id="summary_primary" name="summary_primary"
              placeholder="e.g. Muted green over dark base"
              value="<?= e($editModel ? ($editModel['summary']['primary'] ?? '') : '') ?>">
          </div>
          <div>
            <label for="summary_contrast">Contrast</label>
            <input type="text" id="summary_contrast" name="summary_contrast"
              placeholder="e.g. Grey/red camo, dark tracks"
              value="<?= e($editModel ? ($editModel['summary']['contrast'] ?? '') : '') ?>">
          </div>
          <div>
            <label for="summary_technique">Technique Bias</label>
            <input type="text" id="summary_technique" name="summary_technique"
              placeholder="e.g. Sponge texture, oil wash, pigments"
              value="<?= e($editModel ? ($editModel['summary']['technique'] ?? '') : '') ?>">
          </div>
          <div>
            <label for="theme_hex">Card Stripe Colour</label>
            <div style="display:flex;gap:6px;align-items:center">
              <input type="color" id="theme_hex_picker" style="width:42px;height:30px;padding:0;border:1px solid #2a2010;background:#130f08;cursor:pointer;border-radius:3px"
                value="<?= e($editModel ? ($editModel['theme_hex'] ?? '#000000') : '#000000') ?>"
                oninput="document.getElementById('theme_hex').value = this.value">
              <input type="text" id="theme_hex" name="theme_hex"
                placeholder="e.g. #a02020 (leave blank to hide stripe)"
                pattern="^#[0-9a-fA-F]{6}$" maxlength="7" style="flex:1"
                value="<?= e($editModel ? ($editModel['theme_hex'] ?? '') : '') ?>"
                oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('theme_hex_picker').value = this.value.toLowerCase()">
            </div>
          </div>
          <div>
            <label for="codex_source">Codex Reference</label>
            <select id="codex_source" name="codex_source">
              <option value="">- none -</option>
              <?php foreach ($codexOptions as $opt): $sel = ($editModel && ($editModel['codex_source'] ?? '') === $opt['value']) ? ' selected' : ''; ?>
                <option value="<?= e($opt['value']) ?>" <?= $sel ?>><?= e($opt['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Images -->
          <div class="form-full">
            <label>Photos (up to 4)</label>
            <div class="image-slots">
              <?php for ($i = 1; $i <= 4; $i++):
                $slotImg = $editModel ? ($editModel['images'][$i - 1] ?? null) : null; ?>
                <div class="image-slot">
                  <label>Image <?= $i ?></label>
                  <?php if ($slotImg): ?>
                    <div class="slot-preview" id="preview_<?= $i ?>">
                      <img src="<?= e($slotImg) ?>" alt="">
                      <button type="button" class="slot-delete-btn" onclick="clearSlot(<?= $i ?>)">&times;</button>
                    </div>
                    <div class="slot-cleared" id="cleared_<?= $i ?>" style="display:none">Will be removed</div>
                    <input type="hidden" name="delete_img_<?= $i ?>" id="delete_img_<?= $i ?>" value="0">
                  <?php endif; ?>
                  <input type="file" name="image<?= $i ?>" accept="image/*" id="file_<?= $i ?>" <?= $slotImg ? ' style="display:none"' : '' ?>>
                  <?php if ($slotImg): ?>
                    <div style="font-size:10px;color:#4a3a1a;margin-top:2px" id="keep_hint_<?= $i ?>">Leave blank to keep</div>
                  <?php endif; ?>
                </div>
              <?php endfor; ?>
            </div>
          </div>

          <!-- Colors -->
          <div class="form-full">
            <label>Colours Used</label>
            <input type="text" class="color-search" id="colorSearch" placeholder="Filter paints…" autocomplete="off">
            <div class="color-list" id="colorList"></div>
            <div class="selected-colors" id="selectedCount">0 colours selected</div>
            <div id="colorInputs"></div>
          </div>

          <?php if ($hasRecipes && $recipesData): ?>
            <div class="form-full">
              <label>Recipes (optional)</label>
              <?php $editRecipes = $editModel['recipes'] ?? []; ?>
              <div class="rc-pill-picker" id="galleryRecipePicker" data-form="gallery">
                <?php foreach ($recipesData as $rc): ?>
                  <span class="rc-pill<?= in_array($rc['id'], $editRecipes, true) ? ' selected' : '' ?>" data-id="<?= e($rc['id']) ?>">
                    <?= e($rc['name']) ?><?php if (!empty($rc['category'])): ?> <small>(<?= e($rc['category']) ?>)</small><?php endif; ?>
                  </span>
                <?php endforeach; ?>
              </div>
              <div id="galleryRecipeInputs">
                <?php foreach ($editRecipes as $rid): ?>
                  <input type="hidden" name="gallery_recipes[]" value="<?= e($rid) ?>">
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div style="margin-top:20px;display:flex;gap:10px;align-items:center">
          <button type="submit" class="btn"><?= $editModel ? 'Save Changes' : 'Add to Gallery' ?></button>
          <?php if ($editModel): ?>
            <a href="<?= ADMIN_FILENAME ?>" class="btn btn-sm" style="text-decoration:none">Cancel</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if ($models): ?>
        <h2 id="section-entries" style="margin-top:40px">Edit Scheme
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($models) ?> entr<?= count($models) !== 1 ? 'ies' : 'y' ?></span>
        </h2>
        <?php if ($hasRecipes): ?>
          <form method="post" style="margin-bottom:14px" onsubmit="return confirm('Remove paints from scheme color lists that are already covered by a linked recipe? This cannot be undone.')">
            <input type="hidden" name="action" value="cleanup_scheme_colors">
            <button type="submit" class="btn btn-sm">Clean up duplicate recipe colors</button>
            <span style="font-size:11px;color:#5a4a28;margin-left:8px">Removes paints from scheme color lists that are already in a linked recipe</span>
          </form>
        <?php endif; ?>
        <div class="model-list">
          <?php foreach (array_reverse($models) as $m): ?>
            <div class="model-row">
              <?php if (!empty($m['images'][0])): ?>
                <img class="model-row-thumb" src="<?= e($m['images'][0]) ?>" alt="">
              <?php else: ?>
                <div class="model-row-thumb" style="display:flex;align-items:center;justify-content:center;color:#2a2010;font-size:10px">-</div>
              <?php endif; ?>
              <div class="model-row-info">
                <div class="model-row-name">
                  <?= e($m['name']) ?>
                  <?php $cnt = max(1, (int)($m['count'] ?? 1));
                  if ($cnt > 1): ?>
                    <span class="model-count-badge" title="<?= $cnt ?> miniatures painted under this scheme">&times;<?= $cnt ?></span>
                  <?php endif; ?>
                </div>
                <div class="model-row-meta">
                  <?= e($m['faction'] ?? '') ?>
                  <?php if (!empty($m['faction']) && !empty($m['date'])): ?> - <?php endif; ?>
                  <?= e($m['date'] ?? '') ?>
                  &nbsp;&nbsp;<?= count($m['colors'] ?? []) ?> colour<?= count($m['colors'] ?? []) !== 1 ? 's' : '' ?>
                  &nbsp;&nbsp;<?= count($m['images'] ?? []) ?> image<?= count($m['images'] ?? []) !== 1 ? 's' : '' ?>
                </div>
                <?php if (!empty($m['sessions'])): ?>
                  <div class="model-sessions">
                    <?php foreach ($m['sessions'] as $si => $s): ?>
                      <span class="ms-row"><span class="ms-date"><?= e($s['date']) ?></span><span class="ms-count">&times;<?= (int)$s['count'] ?></span><?php if (!empty($s['note'])): ?><span class="ms-note"><?= e($s['note']) ?></span><?php endif; ?><button type="button" class="ms-btn" data-mid="<?= e($m['id']) ?>" data-mname="<?= e($m['name']) ?>" data-idx="<?= $si ?>" data-date="<?= e($s['date']) ?>" data-count="<?= (int)$s['count'] ?>" data-note="<?= e($s['note'] ?? '') ?>" onclick="openGallerySessionEdit(this)">edit</button><button type="button" class="ms-btn ms-del" data-mid="<?= e($m['id']) ?>" data-idx="<?= $si ?>" onclick="deleteGallerySession(this)">del</button></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <button type="button" class="btn btn-sm"
                data-mid="<?= e($m['id'] ?? '') ?>"
                data-mname="<?= e($m['name'] ?? '') ?>"
                onclick="openGallerySessionModal(this)">+ Log</button>
              <a href="<?= ADMIN_FILENAME ?>?edit=<?= e($m['id'] ?? '') ?>" class="btn btn-sm" style="text-decoration:none;<?= ($editModel && ($editModel['id'] ?? '') === ($m['id'] ?? '')) ? 'border-color:#c9a227;' : '' ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this entry?')">
                <input type="hidden" name="action" value="delete_model">
                <input type="hidden" name="model_id" value="<?= e($m['id'] ?? '') ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2 id="section-inventory" style="margin-top:40px">Paint Inventory
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($paints) ?> paints</span>
      </h2>

      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap">
        <?php if (!file_exists(PAINTS_FILE)): ?>
          <form method="post" style="margin:0">
            <input type="hidden" name="action" value="import_paints">
            <button type="submit" class="btn btn-sm">Import <?= count($paints) ?> Paints from CSVs</button>
          </form>
        <?php endif; ?>
        <?php if (!file_exists(PAINTS_FILE)): ?>
          <span style="font-size:11px;color:#4a3a1a">Import first to enable add / edit / delete.</span>
        <?php endif; ?>
        <?php
        $hexCovered = count(array_filter($paints, fn($p) => !empty($p['hex'])));
        $seedExists = file_exists(HEX_SEED_FILE);
        ?>
        <?php if ($seedExists && file_exists(PAINTS_FILE)): ?>
          <form method="post" style="margin:0" onsubmit="return confirm('Seed hex values from data/paint_hex_seed.json into paints.json? This only fills paints that don\'t already have a hex value.');">
            <input type="hidden" name="action" value="apply_hex_seed">
            <button type="submit" class="btn btn-sm">Apply Hex Seed</button>
          </form>
        <?php endif; ?>
        <?php if (file_exists(PAINTS_FILE)): ?>
          <span style="font-size:11px;color:#4a3a1a"><?= $hexCovered ?> of <?= count($paints) ?> paints have hex values</span>
        <?php endif; ?>
      </div>

      <?php if (file_exists(PAINTS_FILE)): ?>
        <!-- Add / Edit form -->
        <div class="paint-form-wrap" id="paintFormWrap" style="display:none">
          <div class="paint-form-title" id="paintFormTitle">Add Paint</div>
          <form method="post" id="paintForm">
            <input type="hidden" name="action" value="add_paint" id="paintAction">
            <input type="hidden" name="paint_id" id="paintId" value="">
            <div class="form-grid">
              <div>
                <label for="p_brand">Brand</label>
                <input type="text" id="p_brand" name="brand" list="p_brandList" required placeholder="e.g. Citadel">
                <datalist id="p_brandList">
                  <option value="Citadel">
                  <option value="Pro Acryl">
                  <option value="Vallejo">
                  <option value="Army Painter">
                  <option value="Gamblin Artist Oils">
                  <option value="AK Interactive">
                  <option value="Scale75">
                  <option value="Two Thin Coats">
                </datalist>
              </div>
              <div>
                <label for="p_name">Paint Name</label>
                <input type="text" id="p_name" name="name" required placeholder="e.g. Mephiston Red">
              </div>
              <div>
                <label for="p_color">Colour Category</label>
                <input type="text" id="p_color" name="color" list="p_colorList" placeholder="e.g. Red">
                <datalist id="p_colorList">
                  <?php foreach (
                    [
                      'White',
                      'Grey',
                      'Black',
                      'Flesh',
                      'Red',
                      'Green',
                      'Blue',
                      'Yellow',
                      'Orange',
                      'Brown',
                      'Purple',
                      'Pink',
                      'Metallic',
                      'Shade',
                      'Wash',
                      'Contrast',
                      'Transparent',
                      'Fluorescent',
                      'Special',
                      'Ink',
                      'Medium',
                      'Effect',
                      'Texture',
                      'Pigment',
                      'Fluid',
                      'Primer',
                      'Utility'
                    ] as $cat
                  ): ?>
                    <option value="<?= e($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
              </div>
              <div>
                <label for="p_hue">Hue Description</label>
                <input type="text" id="p_hue" name="hue" placeholder="e.g. Dark Red">
              </div>
              <div class="form-full">
                <label for="p_layer">Layer / Type</label>
                <input type="text" id="p_layer" name="layer" list="p_layerList" placeholder="e.g. Base">
              </div>
              <div>
                <label for="p_hex">Swatch Hex (for Color Match)</label>
                <div style="display:flex;gap:6px;align-items:center">
                  <input type="color" id="p_hex_picker" style="width:42px;height:30px;padding:0;border:1px solid #2a2010;background:#130f08;cursor:pointer;border-radius:3px" oninput="document.getElementById('p_hex').value = this.value">
                  <input type="text" id="p_hex" name="hex" placeholder="#a02020" pattern="^#[0-9a-fA-F]{6}$" maxlength="7" style="flex:1" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('p_hex_picker').value = this.value.toLowerCase()">
                </div>
              </div>
              <div class="form-full">
                <label for="p_notes">Notes</label>
                <textarea id="p_notes" name="notes" rows="3" placeholder="e.g. thin 2:1, chalky, already thin out of pot, matte finish…" style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
              <div>
                <label>Quality Rating (optional)</label>
                <div class="brush-star-picker" id="paintStarPicker">
                  <span class="bsp-star" data-val="1">★</span>
                  <span class="bsp-star" data-val="2">★</span>
                  <span class="bsp-star" data-val="3">★</span>
                  <span class="bsp-star" data-val="4">★</span>
                  <span class="bsp-star" data-val="5">★</span>
                </div>
                <input type="hidden" id="p_stars" name="p_stars" value="">
              </div>
              <div class="form-full" style="display:none">
                <datalist id="p_layerList">
                  <?php foreach (
                    [
                      'Base',
                      'Contrast',
                      'Shade',
                      'Metallic',
                      'Transparent',
                      'Fluorescent',
                      'Special',
                      'Oil',
                      'Speedpaint',
                      'Technical',
                      'Varnish',
                      'Medium',
                      'Ink',
                      'Texture',
                      'Weathering',
                      'Airbrush',
                      'Air',
                      'Terrain',
                      'Tool',
                      'Effect',
                      'Primer'
                    ] as $lt
                  ): ?>
                    <option value="<?= e($lt) ?>">
                    <?php endforeach; ?>
                </datalist>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="paintSubmitBtn">Add Paint</button>
              <button type="button" class="btn btn-sm" id="paintCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Filter bar -->
        <div class="paint-toolbar">
          <input type="text" id="paintSearch" placeholder="Search name or hue…">
          <select id="paintBrandFilter">
            <option value="">All Brands</option>
            <?php foreach (array_unique(array_column($paints, 'brand')) as $b): ?>
              <option value="<?= e($b) ?>"><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="btn btn-sm" onclick="openPaintAdd()">+ Add Paint</button>
        </div>

        <!-- Paint table -->
        <div class="paint-table-wrap">
          <table class="paint-table" id="paintTable">
            <thead>
              <tr>
                <th>Brand</th>
                <th>Name</th>
                <th>Colour</th>
                <th>Hue</th>
                <th>Layer</th>
                <th colspan="3"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paints as $p):
                $pid = e($p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? '')); ?>
                <tr class="brand-<?= brandSlug($p['brand']) ?>"
                  data-brand="<?= e(strtolower($p['brand'])) ?>"
                  data-name="<?= e(strtolower($p['name'] . ' ' . $p['hue'])) ?>"
                  data-layer="<?= e(strtolower($p['layer'])) ?>">
                  <td style="font-size:11px;color:#7a6030"><?= e($p['brand']) ?></td>
                  <td><?= e($p['name']) ?><?php if (!empty($p['notes'])): ?> <span title="<?= e($p['notes']) ?>" style="color:#6a4f10;font-size:11px;cursor:default">✎</span><?php endif; ?><?php if (!empty($p['stars'])): ?> <span class="br-stars-cell"><?= str_repeat('★', (int)$p['stars']) ?></span><?php endif; ?></td>
                  <td>
                    <?php $sw = !empty($p['hex']) ? $p['hex'] : swatchColor($p['color']); ?>
                    <span class="paint-swatch" style="background:<?= e($sw) ?>" title="<?= e($sw) ?>"></span><?= e($p['color']) ?>
                  </td>
                  <td style="color:#7a6840;font-size:11px"><?= e($p['hue']) ?></td>
                  <td><?= layerBadge($p['layer']) ?></td>
                  <?php $stock = $p['stock'] ?? ''; ?>
                  <td>
                    <button type="button"
                      class="stock-btn<?= $stock ? ' stock-' . $stock : '' ?>"
                      data-pid="<?= $pid ?>"
                      data-stock="<?= e($stock) ?>"
                      onclick="toggleStock(this)"><?= $stock ? e($stock) : '&middot;' ?></button>
                  </td>
                  <td style="white-space:nowrap">
                    <button type="button" class="btn btn-sm"
                      data-pid="<?= $pid ?>"
                      data-brand="<?= e($p['brand']) ?>"
                      data-name="<?= e($p['name']) ?>"
                      data-color="<?= e($p['color']) ?>"
                      data-hue="<?= e($p['hue']) ?>"
                      data-layer="<?= e($p['layer']) ?>"
                      data-notes="<?= e($p['notes'] ?? '') ?>"
                      data-hex="<?= e($p['hex'] ?? '') ?>"
                      data-stars="<?= (int)($p['stars'] ?? 0) ?>"
                      onclick="openPaintEdit(this)">Edit</button>
                  </td>
                  <td>
                    <form method="post" onsubmit="return confirm('Delete this paint?')" style="margin:0">
                      <input type="hidden" name="action" value="delete_paint">
                      <input type="hidden" name="paint_id" value="<?= $pid ?>">
                      <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div id="paintVisibleCount" style="font-size:11px;color:#4a3a1a;margin-top:6px;font-family:'Cinzel',serif;letter-spacing:.04em"></div>
      <?php endif; ?>

      <h2 id="section-brushes" style="margin-top:40px">Brush Inventory
        <?php if ($hasBrushes): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($brushesData) ?> brush<?= count($brushesData) !== 1 ? 'es' : '' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasBrushes): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Start your brush inventory to track condition, use, and notes for each brush.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_brushes_file">
          <button type="submit" class="btn btn-sm">Start Brush Inventory</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openBrushAdd()">+ Add Brush</button>
        </div>

        <div class="paint-form-wrap" id="brushFormWrap" style="display:none">
          <div class="paint-form-title" id="brushFormTitle">Add Brush</div>
          <form method="post" id="brushForm">
            <input type="hidden" name="action" value="add_brush" id="brushAction">
            <input type="hidden" name="brush_id" id="brushId" value="">
            <div class="form-grid">
              <div>
                <label for="br_brand">Brand *</label>
                <input type="text" id="br_brand" name="br_brand" required placeholder="e.g. Artis Opus"
                  list="br_brandList">
                <datalist id="br_brandList">
                  <option value="Artis Opus">
                  <option value="Da Vinci">
                  <option value="Winsor &amp; Newton">
                  <option value="Army Painter">
                  <option value="Citadel">
                  <option value="Raphael">
                  <option value="Rosemary &amp; Co">
                  <option value="Princeton">
                </datalist>
              </div>
              <div>
                <label for="br_series">Series / Line</label>
                <input type="text" id="br_series" name="br_series" placeholder="e.g. S, Series 10">
              </div>
              <div>
                <label for="br_size">Size</label>
                <input type="text" id="br_size" name="br_size" placeholder="e.g. 1, 0, Small"
                  list="br_sizeList">
                <datalist id="br_sizeList">
                  <option value="000">
                  <option value="00">
                  <option value="0">
                  <option value="1">
                  <option value="2">
                  <option value="3">
                  <option value="Small">
                  <option value="Medium">
                  <option value="Large">
                  <option value="XL">
                </datalist>
              </div>
              <div>
                <label for="br_material">Material</label>
                <input type="text" id="br_material" name="br_material" placeholder="e.g. Sable"
                  list="br_materialList">
                <datalist id="br_materialList">
                  <option value="Sable">
                  <option value="Kolinsky Sable">
                  <option value="Synthetic">
                  <option value="Squirrel">
                  <option value="Taklon">
                  <option value="Hog">
                </datalist>
              </div>
              <div>
                <label for="br_use">Primary Use</label>
                <input type="text" id="br_use" name="br_use" placeholder="e.g. Detail / Layering"
                  list="br_useList">
                <datalist id="br_useList">
                  <option value="Detail / Layering">
                  <option value="Basecoating">
                  <option value="Drybrushing">
                  <option value="Washes / Glazes">
                  <option value="Metallics">
                  <option value="Basing / Texture">
                  <option value="Blending">
                  <option value="Stippling">
                  <option value="Terrain">
                </datalist>
              </div>
              <div>
                <label for="br_condition">Condition</label>
                <select id="br_condition" name="br_condition" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="prime">Prime</option>
                  <option value="workhorse">Workhorse</option>
                  <option value="retired">Retired</option>
                </select>
              </div>
              <div>
                <label>Quality Rating (optional)</label>
                <div class="brush-star-picker" id="brushStarPicker">
                  <span class="bsp-star" data-val="1">★</span>
                  <span class="bsp-star" data-val="2">★</span>
                  <span class="bsp-star" data-val="3">★</span>
                  <span class="bsp-star" data-val="4">★</span>
                  <span class="bsp-star" data-val="5">★</span>
                </div>
                <input type="hidden" id="br_stars" name="br_stars" value="">
              </div>
              <div>
                <label for="br_date_start">Date Started (optional - YYYY-MM)</label>
                <input type="text" id="br_date_start" name="br_date_start" placeholder="e.g. 2024-01" pattern="\d{4}-\d{2}" maxlength="7">
              </div>
              <div class="form-full">
                <label for="br_notes">Notes</label>
                <textarea id="br_notes" name="br_notes" rows="3"
                  placeholder="e.g. tip starting to splay, good for blending, keep for washes only…"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="brushSubmitBtn">Add Brush</button>
              <button type="button" class="btn btn-sm" id="brushCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($brushesData): ?>
          <div class="paint-table-wrap" style="max-height:min(80vh, 1200px)">
            <table class="paint-table" id="brushTable">
              <thead>
                <tr>
                  <th>Brand</th>
                  <th>Series</th>
                  <th>Size</th>
                  <th>Material</th>
                  <th>Primary Use</th>
                  <th>Condition</th>
                  <th>Started</th>
                  <th colspan="2"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($brushesData as $br):
                  $brCond     = $br['condition'] ?? 'prime';
                  $brCondLabel = ['prime' => 'Prime', 'workhorse' => 'Workhorse', 'retired' => 'Retired'][$brCond] ?? $brCond;
                  $brDate     = !empty($br['date_start']) ? date('M Y', strtotime($br['date_start'] . '-01')) : '';
                ?>
                  <tr>
                    <td style="font-family:'Cinzel',serif;font-size:11px;color:#c9a227">
                      <?= e($br['brand']) ?>
                      <?php if (!empty($br['notes'])): ?>
                        <span title="<?= e($br['notes']) ?>" style="color:#6a4f10;font-size:11px;cursor:default;margin-left:3px">✎</span>
                      <?php endif; ?>
                      <?php if (!empty($br['stars'])): ?>
                        <span class="br-stars-cell"><?= str_repeat('★', (int)$br['stars']) ?><span style="color:#1e1208"><?= str_repeat('★', 5 - (int)$br['stars']) ?></span></span>
                      <?php endif; ?>
                    </td>
                    <td style="color:#7a6840;font-size:11px"><?= e($br['series'] ?? '') ?></td>
                    <td><?= e($br['size'] ?? '') ?></td>
                    <td style="font-size:11px;color:#7a6840"><?= e($br['material'] ?? '') ?></td>
                    <td style="font-size:12px"><?= e($br['use'] ?? '') ?></td>
                    <td>
                      <button type="button"
                        class="brush-cond-btn cond-<?= e($brCond) ?>"
                        data-bid="<?= e($br['id']) ?>"
                        data-cond="<?= e($brCond) ?>"
                        onclick="toggleBrushCond(this)"><?= e($brCondLabel) ?></button>
                    </td>
                    <td style="font-size:11px;color:#4a3a1a;font-family:'Cinzel',serif;letter-spacing:.03em"><?= e($brDate) ?></td>
                    <td style="white-space:nowrap">
                      <button type="button" class="btn btn-sm"
                        data-id="<?= e($br['id']) ?>"
                        data-brand="<?= e($br['brand']) ?>"
                        data-series="<?= e($br['series'] ?? '') ?>"
                        data-size="<?= e($br['size'] ?? '') ?>"
                        data-material="<?= e($br['material'] ?? '') ?>"
                        data-use="<?= e($br['use'] ?? '') ?>"
                        data-condition="<?= e($brCond) ?>"
                        data-stars="<?= e($br['stars'] ?? '') ?>"
                        data-date_start="<?= e($br['date_start'] ?? '') ?>"
                        data-notes="<?= e($br['notes'] ?? '') ?>"
                        onclick="openBrushEdit(this)">Edit</button>
                    </td>
                    <td>
                      <form method="post" onsubmit="return confirm('Delete this brush?')" style="margin:0">
                        <input type="hidden" name="action" value="delete_brush">
                        <input type="hidden" name="brush_id" value="<?= e($br['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No brushes logged yet.</p>
        <?php endif; ?>
      <?php endif; ?>

      <h2 id="section-supplies" style="margin-top:40px">Supplies
        <?php if ($hasSupplies): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($suppliesData) ?> item<?= count($suppliesData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasSupplies): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Track your hobby supplies - palettes, mats, lamps, holders, and other tools of the craft.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_supplies_file">
          <button type="submit" class="btn btn-sm">Start Supplies Inventory</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openSupplyAdd()">+ Add Supply</button>
        </div>

        <div class="paint-form-wrap" id="supplyFormWrap" style="display:none">
          <div class="paint-form-title" id="supplyFormTitle">Add Supply</div>
          <form method="post" id="supplyForm">
            <input type="hidden" name="action" value="add_supply" id="supplyAction">
            <input type="hidden" name="supply_id" id="supplyId" value="">
            <div class="form-grid">
              <div>
                <label for="sp_name">Name *</label>
                <input type="text" id="sp_name" name="sp_name" required placeholder="e.g. Glass Palette">
              </div>
              <div>
                <label for="sp_brand">Brand</label>
                <input type="text" id="sp_brand" name="sp_brand" placeholder="e.g. Red Grass Games"
                  list="sp_brandList">
                <datalist id="sp_brandList">
                  <option value="Red Grass Games">
                  <option value="Squidmar">
                  <option value="Artis Opus">
                  <option value="Army Painter">
                  <option value="Citadel">
                  <option value="Vallejo">
                  <option value="Games Workshop">
                  <option value="Hobby Zone">
                </datalist>
              </div>
              <div>
                <label for="sp_type">Type</label>
                <input type="text" id="sp_type" name="sp_type" placeholder="e.g. palette"
                  list="sp_typeList">
                <datalist id="sp_typeList">
                  <option value="palette">
                  <option value="wet-palette">
                  <option value="dry-palette">
                  <option value="cutting-mat">
                  <option value="lamp">
                  <option value="holder">
                  <option value="storage">
                  <option value="tool">
                  <option value="other">
                </datalist>
              </div>
              <div>
                <label for="sp_condition">Condition</label>
                <select id="sp_condition" name="sp_condition" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="prime">Prime</option>
                  <option value="workhorse">Workhorse</option>
                  <option value="retired">Retired</option>
                </select>
              </div>
              <div>
                <label for="sp_acquired">Acquired (optional - YYYY-MM)</label>
                <input type="text" id="sp_acquired" name="sp_acquired" placeholder="e.g. 2024-01" pattern="\d{4}-\d{2}" maxlength="7">
              </div>
              <div class="form-full">
                <label for="sp_notes">Notes</label>
                <textarea id="sp_notes" name="sp_notes" rows="3"
                  placeholder="e.g. great for wet blending, paper starting to pill, keep away from oils&hellip;"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="supplySubmitBtn">Add Supply</button>
              <button type="button" class="btn btn-sm" id="supplyCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($suppliesData): ?>
          <div class="paint-table-wrap" style="max-height:min(80vh, 1200px)">
            <table class="paint-table" id="supplyTable">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Brand</th>
                  <th>Type</th>
                  <th>Condition</th>
                  <th>Acquired</th>
                  <th colspan="2"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($suppliesData as $sp):
                  $spCond      = $sp['condition'] ?? 'prime';
                  $spCondLabel = ['prime' => 'Prime', 'workhorse' => 'Workhorse', 'retired' => 'Retired'][$spCond] ?? $spCond;
                  $spDate      = !empty($sp['acquired']) ? date('M Y', strtotime($sp['acquired'] . '-01')) : '';
                ?>
                  <tr>
                    <td style="font-family:'Cinzel',serif;font-size:11px;color:#c9a227">
                      <?= e($sp['name']) ?>
                      <?php if (!empty($sp['notes'])): ?>
                        <span title="<?= e($sp['notes']) ?>" style="color:#6a4f10;font-size:11px;cursor:default;margin-left:3px">✎</span>
                      <?php endif; ?>
                    </td>
                    <td style="color:#7a6840;font-size:11px"><?= e($sp['brand'] ?? '') ?></td>
                    <td style="font-size:11px;color:#7a6840"><?= e($sp['type'] ?? '') ?></td>
                    <td>
                      <button type="button"
                        class="brush-cond-btn cond-<?= e($spCond) ?>"
                        data-bid="<?= e($sp['id']) ?>"
                        data-cond="<?= e($spCond) ?>"
                        onclick="toggleSupplyCond(this)"><?= e($spCondLabel) ?></button>
                    </td>
                    <td style="font-size:11px;color:#4a3a1a;font-family:'Cinzel',serif;letter-spacing:.03em"><?= e($spDate) ?></td>
                    <td style="white-space:nowrap">
                      <button type="button" class="btn btn-sm"
                        data-id="<?= e($sp['id']) ?>"
                        data-name="<?= e($sp['name']) ?>"
                        data-brand="<?= e($sp['brand'] ?? '') ?>"
                        data-type="<?= e($sp['type'] ?? '') ?>"
                        data-condition="<?= e($spCond) ?>"
                        data-acquired="<?= e($sp['acquired'] ?? '') ?>"
                        data-notes="<?= e($sp['notes'] ?? '') ?>"
                        onclick="openSupplyEdit(this)">Edit</button>
                    </td>
                    <td>
                      <form method="post" onsubmit="return confirm('Delete this supply?')" style="margin:0">
                        <input type="hidden" name="action" value="delete_supply">
                        <input type="hidden" name="supply_id" value="<?= e($sp['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No supplies logged yet.</p>
        <?php endif; ?>
      <?php endif; ?>

      <h2 id="section-checker" style="margin-top:40px">Paint Checker</h2>
      <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
        Paste a list of paint names (one per line) to see which you own, which are low or out, and which are missing entirely.
      </p>
      <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:center">
        <select id="checkerBrand" style="padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
          <option value="">- Select brand -</option>
          <?php
          $checkerBrands = array_unique(array_column($paints, 'brand'));
          sort($checkerBrands);
          foreach ($checkerBrands as $b) echo '<option value="' . e($b) . '">' . e($b) . '</option>';
          ?>
        </select>
        <button type="button" class="btn btn-sm" onclick="checkPaints()">Check List</button>
        <button type="button" class="btn btn-sm" style="color:#6a5a30;border-color:#2a2010" onclick="clearChecker()">Clear</button>
      </div>
      <textarea id="checkerInput" rows="7"
        placeholder="Mephiston Red&#10;Agrax Earthshade&#10;Nuln Oil&#10;Incubi Darkness&#10;…"
        style="width:100%;background:#0a0806;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;padding:10px;font-family:inherit;resize:vertical;outline:none"></textarea>
      <div id="checkerResults"></div>

      <h2 id="section-conversions" style="margin-top:40px">Conversion Chart
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($convRows) ?> rows</span>
      </h2>

      <?php
      $mDot = fn(string $q): string => match ($q) {
        'near identical' => '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#c9a227;margin-right:4px;vertical-align:middle"></span>',
        'usable'         => '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#6a4e14;margin-right:4px;vertical-align:middle"></span>',
        'avoid'          => '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#7a2020;margin-right:4px;vertical-align:middle"></span>',
        default          => '',
      };
      ?>

      <div class="paint-form-wrap" id="conv-form-wrap">
        <div class="paint-form-title" id="conv-form-title">Add Row</div>
        <form method="post" id="conv-form">
          <input type="hidden" name="action" value="add_conversion" id="conv-action">
          <input type="hidden" name="cv_orig" id="cv_orig" value="">
          <div class="form-grid">
            <div class="form-full">
              <label for="cv_citadel">Citadel Paint *</label>
              <input type="text" id="cv_citadel" name="cv_citadel" placeholder="e.g. Mephiston Red" required>
            </div>
            <div>
              <label for="cv_vallejo">Vallejo</label>
              <input type="text" id="cv_vallejo" name="cv_vallejo" placeholder="Paint name or leave blank">
            </div>
            <div>
              <label for="cv_val_q">Vallejo Match</label>
              <select id="cv_val_q" name="cv_val_q" class="form-select">
                <option value="">- no rating</option>
                <option value="near identical">Near Identical</option>
                <option value="usable">Usable</option>
                <option value="avoid">Avoid</option>
              </select>
            </div>
            <div>
              <label for="cv_pa">Pro Acryl</label>
              <input type="text" id="cv_pa" name="cv_pa" placeholder="Paint name or leave blank">
            </div>
            <div>
              <label for="cv_pa_q">Pro Acryl Match</label>
              <select id="cv_pa_q" name="cv_pa_q" class="form-select">
                <option value="">- no rating</option>
                <option value="near identical">Near Identical</option>
                <option value="usable">Usable</option>
                <option value="avoid">Avoid</option>
              </select>
            </div>
            <div>
              <label for="cv_ttc">Two Thin Coats</label>
              <input type="text" id="cv_ttc" name="cv_ttc" placeholder="Paint name or leave blank">
            </div>
            <div>
              <label for="cv_ttc_q">Two Thin Coats Match</label>
              <select id="cv_ttc_q" name="cv_ttc_q" class="form-select">
                <option value="">- no rating</option>
                <option value="near identical">Near Identical</option>
                <option value="usable">Usable</option>
                <option value="avoid">Avoid</option>
              </select>
            </div>
          </div>
          <div style="margin-top:14px;display:flex;gap:10px">
            <button type="submit" class="btn btn-sm" id="conv-save-btn">Add Row</button>
            <button type="button" class="btn btn-sm" id="conv-cancel-btn" style="display:none;color:#6a5a30;border-color:#2a2010" onclick="convCancelEdit()">Cancel</button>
          </div>
        </form>
      </div>

      <div class="paint-toolbar">
        <input type="text" id="conv-search" placeholder="Search Citadel, Vallejo, Pro Acryl, Two Thin Coats&hellip;" autocomplete="off">
        <span id="conv-count" style="font-size:12px;color:#4a3a1a;font-family:'Cinzel',serif;white-space:nowrap;letter-spacing:.04em"></span>
      </div>

      <div class="paint-table-wrap" style="max-height:560px">
        <table class="paint-table" id="conv-table">
          <thead>
            <tr>
              <th style="width:24%">Citadel</th>
              <th style="width:22%">Vallejo</th>
              <th style="width:22%">Pro Acryl</th>
              <th style="width:22%">Two Thin Coats</th>
              <th style="width:10%"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($convRows as $r):
              $showVal = ($r[1] !== '' && $r[1] !== '-');
              $showPa  = ($r[2] !== '' && $r[2] !== '-');
              $showTtc = ($r[3] !== '' && $r[3] !== '-');
            ?>
              <tr class="conv-row"
                data-cit="<?= e($r[0]) ?>"
                data-val="<?= e($r[1]) ?>"
                data-pa="<?= e($r[2]) ?>"
                data-ttc="<?= e($r[3]) ?>"
                data-val-q="<?= e($r[4]) ?>"
                data-pa-q="<?= e($r[5]) ?>"
                data-ttc-q="<?= e($r[6]) ?>">
                <td><?= e($r[0]) ?></td>
                <td><?= $showVal ? $mDot($r[4]) . e($r[1]) : '<span style="color:#2a2010">-</span>' ?></td>
                <td><?= $showPa  ? $mDot($r[5]) . e($r[2]) : '<span style="color:#2a2010">-</span>' ?></td>
                <td><?= $showTtc ? $mDot($r[6]) . e($r[3]) : '<span style="color:#2a2010">-</span>' ?></td>
                <td style="white-space:nowrap">
                  <button class="btn btn-sm" onclick="convEdit(this)" style="padding:3px 8px" title="Edit">✎</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this row?')">
                    <input type="hidden" name="action" value="delete_conversion">
                    <input type="hidden" name="cv_orig" value="<?= e($r[0]) ?>">
                    <button type="submit" class="btn btn-sm btn-danger" style="padding:3px 7px">&times;</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <h2 id="section-rescues" class="collapsible" style="margin-top:40px">Rescue Tracker
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($rescuesData) ?> rescue<?= count($rescuesData) !== 1 ? 's' : '' ?></span>
      </h2>
      <?php if (!$hasRescues): ?>
        <form method="post">
          <input type="hidden" name="action" value="create_rescues_file">
          <button type="submit" class="btn btn-sm">Start Rescue Tracker</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openRescueAdd()">+ Add Rescue</button>
        </div>

        <div class="paint-form-wrap" id="rescueFormWrap" style="display:none">
          <div class="paint-form-title" id="rescueFormTitle">Add Rescue</div>
          <form method="post" id="rescueForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_rescue" id="rescueAction">
            <input type="hidden" name="rc_id" id="rcId" value="">
            <div class="form-grid">
              <div>
                <label for="rsc_name">Name *</label>
                <input type="text" id="rsc_name" name="rc_name" required placeholder="e.g. Ork Boyz Job Lot">
              </div>
              <div>
                <label for="rsc_system">System</label>
                <select id="rsc_system" name="rc_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">- none -</option>
                  <option value="40k">Warhammer 40,000</option>
                  <option value="30k / HH">Horus Heresy / 30k</option>
                  <option value="AoS">Age of Sigmar</option>
                  <option value="Old World">The Old World</option>
                  <option value="Kill Team">Kill Team</option>
                  <option value="Blood Bowl">Blood Bowl</option>
                  <option value="Necromunda">Necromunda</option>
                  <option value="Epic">Epic Scale</option>
                  <option value="OPR">One Page Rules</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label for="rsc_faction">Faction</label>
                <input type="text" id="rsc_faction" name="rc_faction" placeholder="e.g. Orks">
              </div>
              <div>
                <label for="rsc_count">Model Count</label>
                <input type="number" id="rsc_count" name="rc_count" min="1" placeholder="e.g. 10">
              </div>
              <div>
                <label for="rsc_source">Source</label>
                <select id="rsc_source" name="rc_source" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">- none -</option>
                  <option value="eBay">eBay</option>
                  <option value="Trade">Trade</option>
                  <option value="LGS">LGS</option>
                  <option value="Gift">Gift</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label for="rsc_condition">Strip Difficulty</label>
                <select id="rsc_condition" name="rc_condition" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">- unknown -</option>
                  <option value="bare">Bare (no paint)</option>
                  <option value="primed_only">Primed Only</option>
                  <option value="light">Light</option>
                  <option value="medium">Medium</option>
                  <option value="heavy">Heavy</option>
                </select>
              </div>
              <div>
                <label for="rsc_stage">Stage</label>
                <select id="rsc_stage" name="rc_stage" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="bidding">Bidding</option>
                  <option value="in_transit">In Transit</option>
                  <option value="received" selected>Received</option>
                  <option value="stripping">Stripping</option>
                  <option value="prepped">Prepped</option>
                </select>
              </div>
              <div>
                <label for="rsc_acquired">Date Acquired (YYYY-MM)</label>
                <input type="text" id="rsc_acquired" name="rc_acquired" placeholder="e.g. 2026-05" maxlength="7">
              </div>
              <div class="form-full">
                <label for="rsc_notes">Notes</label>
                <textarea id="rsc_notes" name="rc_notes" rows="3" placeholder="e.g. Badly drybrushed, base colours intact" style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
              <div class="form-full">
                <label>Before Photos (up to 4 - condition on arrival)</label>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:4px" id="rcPhotoGrid">
                  <?php for ($slot = 1; $slot <= RESCUES_MAX_IMAGES; $slot++): ?>
                  <div style="border:1px solid #2a2010;border-radius:3px;padding:6px;text-align:center;background:#0a0806">
                    <div style="font-size:10px;color:#5a4a28;margin-bottom:4px">Slot <?= $slot ?></div>
                    <div id="rcPhotoPreview<?= $slot ?>" style="width:100%;aspect-ratio:1;background:#1a1810;border-radius:2px;margin-bottom:4px;background-size:cover;background-position:center"></div>
                    <input type="file" name="rc_image<?= $slot ?>" id="rcImage<?= $slot ?>" accept="image/*" style="font-size:10px;width:100%;color:#7a6a50" onchange="previewRcPhoto(this,<?= $slot ?>)">
                    <div style="margin-top:4px">
                      <label style="font-size:10px;color:#5a4a28"><input type="checkbox" name="delete_rc_img_<?= $slot ?>" value="1" id="rcDeleteImg<?= $slot ?>"> Delete</label>
                    </div>
                  </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="rescueSubmitBtn">Add Rescue</button>
              <button type="button" class="btn btn-sm" id="rescueCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($rescuesData): ?>
          <?php
          $rescueSysBg = ['40k' => ['#8a2020', '#f08080'], '30k / HH' => ['#4a3a10', '#d4a840'], 'AoS' => ['#1a2a5a', '#7090d8'], 'Kill Team' => ['#0a3a3a', '#70c8d8'], 'Blood Bowl' => ['#2a1a4a', '#9a70d8'], 'Necromunda' => ['#1a3a3a', '#70c8c8'], 'Epic' => ['#1a3a1a', '#70b870'], 'OPR' => ['#1a2a3a', '#708090'], 'Old World' => ['#3a2a0a', '#d4a040'], 'Other' => ['#2a2a2a', '#909090']];
          $rescueStageLabel = ['bidding' => 'Bidding', 'in_transit' => 'In Transit', 'received' => 'Received', 'stripping' => 'Stripping', 'prepped' => 'Prepped'];
          $rescueStageBg    = ['bidding' => ['#2a1a3a', '#c090e8'], 'in_transit' => ['#1a1a4a', '#8090e8'], 'received' => ['#0a3030', '#70c8c8'], 'stripping' => ['#3a1a08', '#e07030'], 'prepped' => ['#0a2a0a', '#70c870']];
          $rescueCondLabel  = ['bare' => 'Bare', 'primed_only' => 'Primed Only', 'light' => 'Light', 'medium' => 'Medium', 'heavy' => 'Heavy'];
          ?>
          <div class="model-list">
            <?php foreach ($rescuesData as $rc):
              $rcPromote = $rc['promoted_to'] ?? '';
              $rcStage   = $rc['stage'] ?? 'received';
              $sysBg     = $rescueSysBg[$rc['system'] ?? ''] ?? ['#2a2a2a', '#909090'];
              $stageBg   = $rescueStageBg[$rcStage] ?? ['#1a1a1a', '#7a7a7a'];
            ?>
              <div class="model-row" id="rescue-row-<?= e($rc['id']) ?>">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <?php if (!empty($rc['system'])): ?>
                      <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:<?= $sysBg[0] ?>;color:<?= $sysBg[1] ?>;font-family:'Cinzel',serif;letter-spacing:.05em"><?= e($rc['system']) ?></span>
                    <?php endif; ?>
                    <?= e($rc['name']) ?>
                    <button type="button" class="rescue-stage-btn stage-<?= e($rcStage) ?>" id="rcStageBtn-<?= e($rc['id']) ?>" onclick="advanceRescueStage('<?= e($rc['id']) ?>')"><?= e($rescueStageLabel[$rcStage] ?? $rcStage) ?></button>
                    <?php if ($rcPromote): ?>
                      <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:#1a3a1a;color:#7ad678;font-family:'Cinzel',serif">Promoted &rarr; <?= $rcPromote === 'bench' ? 'Bench' : 'Shame' ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="model-row-meta">
                    <?php if (!empty($rc['faction'])): ?><?= e($rc['faction']) ?> &middot; <?php endif; ?>
                    <?php if (!empty($rc['count'])): ?><?= (int)$rc['count'] ?> models &middot; <?php endif; ?>
                    <?php if (!empty($rc['source'])): ?><span style="font-size:10px;padding:1px 5px;background:#101018;color:#6a6a8a;border-radius:3px"><?= e($rc['source']) ?></span><?php endif; ?>
                    <?php if (!empty($rc['condition'])): ?><span style="font-size:10px;padding:1px 5px;background:#1a1a1a;color:#7a6a50;border-radius:3px"><?= e($rescueCondLabel[$rc['condition']] ?? $rc['condition']) ?></span><?php endif; ?>
                    <?php if (!empty($rc['acquired'])): ?> &middot; <span style="color:#c9a227;font-family:'Cinzel',serif;font-size:11px"><?= e($rc['acquired']) ?></span><?php endif; ?>
                  </div>
                  <?php if (!empty($rc['before_images'])): ?>
                    <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap">
                      <?php foreach (array_filter($rc['before_images']) as $rimg): ?>
                        <img src="<?= e($rimg) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:2px;border:1px solid #2a2010" alt="">
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($rc['notes'])): ?><div style="font-size:11px;color:#5a4a28;margin-top:3px"><?= e(mb_substr($rc['notes'], 0, 100)) ?><?= mb_strlen($rc['notes']) > 100 ? '&hellip;' : '' ?></div><?php endif; ?>
                </div>
                <div class="model-row-actions" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                  <?php if (!$rcPromote): ?>
                    <button type="button" class="btn btn-sm" onclick="promoteRescue('<?= e($rc['id']) ?>','bench')" style="font-size:10px">&rarr; Bench</button>
                    <button type="button" class="btn btn-sm" onclick="promoteRescue('<?= e($rc['id']) ?>','shame')" style="font-size:10px">&rarr; Shame</button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-sm" onclick="openRescueEdit(this)"
                    data-id="<?= e($rc['id']) ?>"
                    data-name="<?= e($rc['name']) ?>"
                    data-system="<?= e($rc['system'] ?? '') ?>"
                    data-faction="<?= e($rc['faction'] ?? '') ?>"
                    data-count="<?= (int)($rc['count'] ?? 0) ?>"
                    data-source="<?= e($rc['source'] ?? '') ?>"
                    data-condition="<?= e($rc['condition'] ?? '') ?>"
                    data-stage="<?= e($rcStage) ?>"
                    data-acquired="<?= e($rc['acquired'] ?? '') ?>"
                    data-notes="<?= e($rc['notes'] ?? '') ?>"
                    data-images="<?= e(json_encode($rc['before_images'] ?? [])) ?>">Edit</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Remove this rescue?')">
                    <input type="hidden" name="action" value="delete_rescue">
                    <input type="hidden" name="rc_id" value="<?= e($rc['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <script>
        function openRescueAdd() {
          if (window._adminShowSection) window._adminShowSection('section-rescues');
          document.getElementById('rescueFormTitle').textContent = 'Add Rescue';
          document.getElementById('rescueAction').value = 'add_rescue';
          document.getElementById('rcId').value = '';
          document.getElementById('rescueForm').reset();
          for (let s = 1; s <= <?= RESCUES_MAX_IMAGES ?>; s++) {
            document.getElementById('rcPhotoPreview' + s).style.backgroundImage = '';
            const cb = document.getElementById('rcDeleteImg' + s); if (cb) cb.checked = false;
          }
          document.getElementById('rescueSubmitBtn').textContent = 'Add Rescue';
          document.getElementById('rescueFormWrap').style.display = '';
        }
        function openRescueEdit(btn) {
          if (window._adminShowSection) window._adminShowSection('section-rescues');
          document.getElementById('rescueFormTitle').textContent = 'Edit Rescue';
          document.getElementById('rescueAction').value = 'edit_rescue';
          document.getElementById('rcId').value = btn.dataset.id;
          document.getElementById('rsc_name').value = btn.dataset.name;
          document.getElementById('rsc_system').value = btn.dataset.system || '';
          document.getElementById('rsc_faction').value = btn.dataset.faction;
          document.getElementById('rsc_count').value = btn.dataset.count > 0 ? btn.dataset.count : '';
          document.getElementById('rsc_source').value = btn.dataset.source || '';
          document.getElementById('rsc_condition').value = btn.dataset.condition || '';
          document.getElementById('rsc_stage').value = btn.dataset.stage;
          document.getElementById('rsc_acquired').value = btn.dataset.acquired;
          document.getElementById('rsc_notes').value = btn.dataset.notes;
          try {
            const imgs = JSON.parse(btn.dataset.images || '[]');
            for (let s = 1; s <= <?= RESCUES_MAX_IMAGES ?>; s++) {
              const prev = document.getElementById('rcPhotoPreview' + s);
              prev.style.backgroundImage = imgs[s-1] ? "url('" + imgs[s-1] + "')" : '';
              const cb = document.getElementById('rcDeleteImg' + s); if (cb) cb.checked = false;
            }
          } catch(e) {}
          document.getElementById('rescueSubmitBtn').textContent = 'Save Changes';
          document.getElementById('rescueFormWrap').style.display = '';
        }
        document.getElementById('rescueCancelBtn').addEventListener('click', () => {
          document.getElementById('rescueFormWrap').style.display = 'none';
        });
        function previewRcPhoto(inp, slot) {
          if (!inp.files[0]) return;
          const r = new FileReader();
          r.onload = e => { document.getElementById('rcPhotoPreview' + slot).style.backgroundImage = "url('" + e.target.result + "')"; };
          r.readAsDataURL(inp.files[0]);
        }
        function advanceRescueStage(id) {
          fetch('<?= ADMIN_FILENAME ?>', { method: 'POST', body: new URLSearchParams({ action: 'set_rescue_stage', rc_id: id }) })
            .then(r => r.json())
            .then(d => {
              if (!d.ok) return;
              const btn = document.getElementById('rcStageBtn-' + id);
              if (!btn) return;
              const stageLabels = { bidding: 'Bidding', in_transit: 'In Transit', received: 'Received', stripping: 'Stripping', prepped: 'Prepped' };
              btn.className = 'rescue-stage-btn stage-' + d.stage;
              btn.textContent = stageLabels[d.stage] || d.stage;
            });
        }
        function promoteRescue(id, dest) {
          if (!confirm('Promote this rescue to ' + (dest === 'bench' ? 'On the Bench' : 'Pile of Shame') + '?')) return;
          fetch('<?= ADMIN_FILENAME ?>', { method: 'POST', body: new URLSearchParams({ action: 'promote_rescue', rc_id: id, promote_to: dest }) })
            .then(r => r.json())
            .then(d => {
              if (!d.ok) return;
              const row = document.getElementById('rescue-row-' + id);
              if (row) {
                const acts = row.querySelector('.model-row-actions');
                if (acts) { const btns = acts.querySelectorAll('.btn-sm'); btns.forEach(b => { if (b.textContent.includes('Bench') || b.textContent.includes('Shame')) b.remove(); }); }
                const nameDiv = row.querySelector('.model-row-name');
                if (nameDiv) {
                  const badge = document.createElement('span');
                  badge.style.cssText = 'font-size:10px;padding:2px 7px;border-radius:3px;background:#1a3a1a;color:#7ad678;font-family:Cinzel,serif';
                  badge.textContent = 'Promoted → ' + (dest === 'bench' ? 'Bench' : 'Shame');
                  nameDiv.appendChild(badge);
                }
              }
            });
        }
        </script>
      <?php endif; ?>

      <h2 id="section-shame" class="collapsible" style="margin-top:40px">Pile of Shame
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($shameData) ?> box<?= count($shameData) !== 1 ? 'es' : '' ?></span>
      </h2>
      <?php if (!$hasShame): ?>
        <form method="post">
          <input type="hidden" name="action" value="create_shame_file">
          <button type="submit" class="btn btn-sm">Start Pile of Shame</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openShameAdd()">+ Add Box</button>
        </div>

        <div class="paint-form-wrap" id="shameFormWrap" style="display:none">
          <div class="paint-form-title" id="shameFormTitle">Add Box</div>
          <form method="post" id="shameForm">
            <input type="hidden" name="action" value="add_shame" id="shameAction">
            <input type="hidden" name="sh_id" id="shId" value="">
            <div class="form-grid">
              <div>
                <label for="sh_name">Box Name *</label>
                <input type="text" id="sh_name" name="sh_name" required placeholder="e.g. Death Guard Plague Marines">
              </div>
              <div>
                <label for="sh_system">System</label>
                <select id="sh_system" name="sh_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="40k">Warhammer 40,000</option>
                  <option value="30k / HH">Horus Heresy / 30k</option>
                  <option value="AoS">Age of Sigmar</option>
                  <option value="Old World">The Old World</option>
                  <option value="Kill Team">Kill Team</option>
                  <option value="Blood Bowl">Blood Bowl</option>
                  <option value="Necromunda">Necromunda</option>
                  <option value="Epic">Epic Scale</option>
                  <option value="OPR">One Page Rules</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label for="sh_faction">Faction</label>
                <input type="text" id="sh_faction" name="sh_faction" placeholder="e.g. Death Guard">
              </div>
              <div>
                <label for="sh_count">Model Count</label>
                <input type="number" id="sh_count" name="sh_count" min="1" placeholder="e.g. 10">
              </div>
              <div>
                <label for="sh_status">Status</label>
                <select id="sh_status" name="sh_status" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="sealed">Sealed</option>
                  <option value="opened">Opened</option>
                  <option value="partial">Partially Built</option>
                </select>
              </div>
              <div>
                <label for="sh_acquired">Date Acquired (YYYY-MM)</label>
                <input type="text" id="sh_acquired" name="sh_acquired" placeholder="e.g. 2024-03" maxlength="7">
              </div>
              <div class="form-full">
                <label for="sh_notes">Notes</label>
                <textarea id="sh_notes" name="sh_notes" rows="3" placeholder="e.g. Picked up at Adepticon" style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="shameSubmitBtn">Add Box</button>
              <button type="button" class="btn btn-sm" id="shameCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($shameData): ?>
          <div class="model-list">
            <?php
            $shameSystems = ['40k' => ['#8a2020', '#f08080'], '30k / HH' => ['#4a3a10', '#d4a840'], 'AoS' => ['#1a2a5a', '#7090d8'], 'Kill Team' => ['#0a3a3a', '#70c8d8'], 'Blood Bowl' => ['#2a1a4a', '#9a70d8'], 'Necromunda' => ['#1a3a3a', '#70c8c8'], 'Epic' => ['#1a3a1a', '#70b870'], 'OPR' => ['#1a2a3a', '#708090'], 'Old World' => ['#3a2a0a', '#d4a040'], 'Other' => ['#2a2a2a', '#909090']];
            foreach ($shameData as $sh):
              $shAcq     = $sh['acquired'] ?? '';
              $shPromote = $sh['promoted_to'] ?? '';
              $sysBg     = $shameSystems[$sh['system'] ?? ''] ?? ['#2a2a2a', '#909090'];
            ?>
              <div class="model-row" id="shame-row-<?= e($sh['id']) ?>">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:<?= $sysBg[0] ?>;color:<?= $sysBg[1] ?>;font-family:'Cinzel',serif;letter-spacing:.05em"><?= e($sh['system'] ?? 'Other') ?></span>
                    <?= e($sh['name']) ?>
                    <?php if ($shPromote): ?>
                      <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:#1a3a1a;color:#7ad678;font-family:'Cinzel',serif">Promoted &rarr; <?= $shPromote === 'planned' ? 'Planned' : 'Bench' ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="model-row-meta">
                    <?php if (!empty($sh['faction'])): ?><?= e($sh['faction']) ?> &middot; <?php endif; ?>
                  <?php
                  $statusLabel = ['sealed' => 'Sealed', 'opened' => 'Opened', 'partial' => 'Partial'][$sh['status'] ?? 'sealed'] ?? 'Sealed';
                  $statusBg    = ['sealed' => '#1a1a1a', 'opened' => '#2a1808', 'partial' => '#1e1808'][$sh['status'] ?? 'sealed'];
                  $statusFg    = ['sealed' => '#7a7a7a', 'opened' => '#c87a30', 'partial' => '#c9a227'][$sh['status'] ?? 'sealed'];
                  ?>
                  <span style="font-size:10px;padding:1px 6px;border-radius:3px;background:<?= $statusBg ?>;color:<?= $statusFg ?>"><?= $statusLabel ?></span>
                  <?php if (!empty($sh['count'])): ?> &middot; <?= (int)$sh['count'] ?> models<?php endif; ?>
                    <?php if ($shAcq): ?> &middot; <span style="color:#c9a227;font-family:'Cinzel',serif;font-size:11px"><?= e($shAcq) ?></span><?php endif; ?>
                  </div>
                  <?php if (!empty($sh['notes'])): ?><div style="font-size:11px;color:#5a4a28;margin-top:3px"><?= e(mb_substr($sh['notes'], 0, 100)) ?><?= mb_strlen($sh['notes']) > 100 ? '…' : '' ?></div><?php endif; ?>
                </div>
                <div class="model-row-actions" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                  <?php if (!$shPromote): ?>
                    <button type="button" class="btn btn-sm" onclick="promoteShame('<?= e($sh['id']) ?>','planned')" style="font-size:10px">&rarr; Planned</button>
                    <button type="button" class="btn btn-sm" onclick="promoteShame('<?= e($sh['id']) ?>','bench')" style="font-size:10px">&rarr; Bench</button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-sm" onclick="openShameEdit(this)"
                    data-id="<?= e($sh['id']) ?>"
                    data-name="<?= e($sh['name']) ?>"
                    data-system="<?= e($sh['system'] ?? '40k') ?>"
                    data-faction="<?= e($sh['faction'] ?? '') ?>"
                    data-count="<?= (int)($sh['count'] ?? 0) ?>"
                    data-status="<?= e($sh['status'] ?? 'sealed') ?>"
                    data-acquired="<?= e($shAcq) ?>"
                    data-notes="<?= e($sh['notes'] ?? '') ?>">Edit</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Remove this box from the pile?')">
                    <input type="hidden" name="action" value="delete_shame">
                    <input type="hidden" name="sh_id" value="<?= e($sh['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <h2 id="section-planned" style="margin-top:40px">Planned Schemes
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($planned) ?> scheme<?= count($planned) !== 1 ? 's' : '' ?></span>
      </h2>

      <div style="margin-bottom:14px">
        <button type="button" class="btn btn-sm" onclick="openPlannedAdd()">+ Add Planned Scheme</button>
      </div>

      <div class="paint-form-wrap" id="plannedFormWrap" style="display:none">
        <div class="paint-form-title" id="plannedFormTitle">Add Planned Scheme</div>
        <form method="post" id="plannedForm">
          <input type="hidden" name="action" value="add_planned" id="plannedAction">
          <input type="hidden" name="planned_id" id="plannedId" value="">
          <div class="form-grid">
            <div>
              <label for="pl_name">Scheme Name *</label>
              <input type="text" id="pl_name" name="pl_name" required placeholder="e.g. Blood Angels Infantry">
            </div>
            <div>
              <label for="pl_model">Kit / Model</label>
              <input type="text" id="pl_model" name="pl_model" placeholder="e.g. Space Marines Intercessors">
            </div>
            <div>
              <label for="pl_faction">Faction</label>
              <input type="text" id="pl_faction" name="pl_faction" placeholder="e.g. Blood Angels">
            </div>
            <div>
              <label for="pl_system">Game System</label>
              <select id="pl_system" name="pl_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                <option value="">- none -</option>
                <option value="40k">Warhammer 40,000</option>
                <option value="30k / HH">Horus Heresy / 30k</option>
                <option value="AoS">Age of Sigmar</option>
                <option value="Old World">The Old World</option>
                <option value="Kill Team">Kill Team</option>
                <option value="Blood Bowl">Blood Bowl</option>
                <option value="Necromunda">Necromunda</option>
                <option value="Epic">Epic Scale</option>
                <option value="OPR">One Page Rules</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div>
              <label for="pl_codex_source">Codex Reference</label>
              <select id="pl_codex_source" name="pl_codex_source">
                <option value="">- none -</option>
                <?php foreach ($codexOptions as $opt): ?>
                  <option value="<?= e($opt['value']) ?>"><?= e($opt['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-full">
              <label for="pl_description">Notes</label>
              <textarea id="pl_description" name="pl_description" rows="2" placeholder="Colour ideas, inspiration…"></textarea>
            </div>
            <div class="form-full">
              <label>Colours</label>
              <input type="text" class="color-search" id="colorSearchPl" placeholder="Filter paints…" autocomplete="off">
              <div class="color-list" id="colorListPl"></div>
              <div class="selected-colors" id="selectedCountPl">0 colours selected</div>
              <div id="colorInputsPl"></div>
            </div>
            <?php if ($hasRecipes && $recipesData): ?>
              <div class="form-full">
                <label>Recipes (optional)</label>
                <div class="rc-pill-picker" id="plannedRecipePicker" data-form="planned">
                  <?php foreach ($recipesData as $rc): ?>
                    <span class="rc-pill" data-id="<?= e($rc['id']) ?>">
                      <?= e($rc['name']) ?><?php if (!empty($rc['category'])): ?> <small>(<?= e($rc['category']) ?>)</small><?php endif; ?>
                    </span>
                  <?php endforeach; ?>
                </div>
                <div id="plannedRecipeInputs"></div>
              </div>
            <?php endif; ?>
          </div>
          <div style="margin-top:16px;display:flex;gap:10px">
            <button type="submit" class="btn" id="plannedSubmitBtn">Add Scheme</button>
            <button type="button" class="btn btn-sm" id="plannedCancelBtn">Cancel</button>
          </div>
        </form>
      </div>

      <?php if ($planned): ?>
        <div class="model-list">
          <?php foreach ($planned as $pl): ?>
            <div class="model-row">
              <div class="model-row-info">
                <div class="model-row-name"><?= e($pl['name']) ?></div>
                <div class="model-row-meta">
                  <?php if (!empty($pl['model'])): ?>Kit: <?= e($pl['model']) ?>&nbsp;&nbsp;<?php endif; ?>
                  <?= e($pl['faction'] ?? '') ?>
                  &nbsp;&nbsp;<?= count($pl['colors'] ?? []) ?> colour<?= count($pl['colors'] ?? []) !== 1 ? 's' : '' ?>
                </div>
              </div>
              <?php if (empty($pl['promoted_to'])): ?>
                <button type="button" class="btn btn-sm" onclick="promotePlanned('<?= e($pl['id']) ?>')" style="font-size:10px">&rarr; Bench</button>
              <?php else: ?>
                <span style="font-size:10px;color:#c9a227;font-family:'Cinzel',serif">Promoted &rarr; <?= ucfirst(e($pl['promoted_to'])) ?></span>
              <?php endif; ?>
              <button class="btn btn-sm"
                data-id="<?= e($pl['id']) ?>"
                data-name="<?= e($pl['name']) ?>"
                data-model="<?= e($pl['model'] ?? '') ?>"
                data-faction="<?= e($pl['faction'] ?? '') ?>"
                data-description="<?= e($pl['description'] ?? '') ?>"
                data-colors="<?= e(json_encode($pl['colors'] ?? [])) ?>"
                data-recipes="<?= e(json_encode($pl['recipes'] ?? [])) ?>"
                data-system="<?= e($pl['system'] ?? '') ?>"
                data-codex_source="<?= e($pl['codex_source'] ?? '') ?>"
                onclick="openPlannedEdit(this)">Edit</button>
              <form method="post" onsubmit="return confirm('Delete this planned scheme?')" style="margin:0">
                <input type="hidden" name="action" value="delete_planned">
                <input type="hidden" name="planned_id" value="<?= e($pl['id']) ?>">
                <button type="submit" class="btn btn-sm btn-danger">&times;</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No planned schemes yet.</p>
      <?php endif; ?>

      <h2 id="section-bench" style="margin-top:40px">On the Bench
        <?php if ($hasBench): ?>
          <?php $activeBench = count(array_filter($benchData, fn($b) => ($b['stage'] ?? 'built') !== 'done')); ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($benchData) ?> entr<?= count($benchData) !== 1 ? 'ies' : 'y' ?> &middot; <?= $activeBench ?> active</span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasBench): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Track active painting projects through their stages - built, primed, basecoated, washed, highlighted, based, varnished, done. Add WIP photos and a paint queue as you go.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_bench_file">
          <button type="submit" class="btn btn-sm">Start Workbench</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openBenchAdd()">+ Add Bench Entry</button>
        </div>

        <div class="paint-form-wrap" id="benchFormWrap" style="display:none">
          <div class="paint-form-title" id="benchFormTitle">Add Bench Entry</div>
          <form method="post" id="benchForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_bench" id="benchAction">
            <input type="hidden" name="bench_id" id="benchId" value="">
            <div class="form-grid">
              <div>
                <label for="bn_name">Project Name *</label>
                <input type="text" id="bn_name" name="bn_name" required placeholder="e.g. Death Guard Plague Marines">
              </div>
              <div>
                <label for="bn_faction">Faction</label>
                <input type="text" id="bn_faction" name="bn_faction" placeholder="e.g. Death Guard">
              </div>
              <div>
                <label for="bn_system">Game System</label>
                <select id="bn_system" name="bn_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">- none -</option>
                  <option value="40k">Warhammer 40,000</option>
                  <option value="30k / HH">Horus Heresy / 30k</option>
                  <option value="AoS">Age of Sigmar</option>
                  <option value="Old World">The Old World</option>
                  <option value="Kill Team">Kill Team</option>
                  <option value="Blood Bowl">Blood Bowl</option>
                  <option value="Necromunda">Necromunda</option>
                  <option value="Epic">Epic Scale</option>
                  <option value="OPR">One Page Rules</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label for="bn_stage">Stage</label>
                <select id="bn_stage" name="bn_stage" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="built">Built</option>
                  <option value="primed">Primed</option>
                  <option value="basecoated">Basecoated</option>
                  <option value="washed">Washed</option>
                  <option value="highlighted">Highlighted</option>
                  <option value="based">Based</option>
                  <option value="varnished">Varnished</option>
                  <option value="done">Done</option>
                </select>
              </div>
              <div>
                <label for="bn_date_start">Date Started (optional - YYYY-MM-DD)</label>
                <input type="text" id="bn_date_start" name="bn_date_start" placeholder="e.g. 2026-04-12" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
              </div>
              <div class="form-full">
                <label for="bn_notes">Notes</label>
                <textarea id="bn_notes" name="bn_notes" rows="3"
                  placeholder="Techniques you're trying, lessons learned, things to remember next time…"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
              <div>
                <label for="bn_codex_source">Codex Reference</label>
                <select id="bn_codex_source" name="bn_codex_source">
                  <option value="">- none -</option>
                  <?php foreach ($codexOptions as $opt): ?>
                    <option value="<?= e($opt['value']) ?>"><?= e($opt['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-full">
                <label>Paint Queue</label>
                <input type="text" class="color-search" id="colorSearchBn" placeholder="Filter paints…" autocomplete="off">
                <div class="color-list" id="colorListBn"></div>
                <div class="selected-colors" id="selectedCountBn">0 colours selected</div>
                <div id="colorInputsBn"></div>
              </div>
              <?php if ($hasBrushes && $brushesData): ?>
                <div class="form-full">
                  <label>Brushes</label>
                  <div class="bench-brush-picker" id="benchBrushPicker">
                    <?php foreach ($brushesData as $br):
                      if (($br['condition'] ?? 'prime') === 'retired') continue;
                      $blabel = trim(($br['brand'] ?? '') . ' ' . ($br['series'] ?? '') . ' ' . ($br['size'] ?? ''));
                    ?>
                      <span class="bbp-item" data-id="<?= e($br['id']) ?>"><?= e($blabel) ?></span>
                    <?php endforeach; ?>
                  </div>
                  <div id="brushInputsBn"></div>
                </div>
              <?php endif; ?>
              <?php if ($hasRecipes && $recipesData): ?>
                <div class="form-full">
                  <label>Recipes (optional)</label>
                  <div class="rc-pill-picker" id="benchRecipePicker" data-form="bench">
                    <?php foreach ($recipesData as $rc): ?>
                      <span class="rc-pill" data-id="<?= e($rc['id']) ?>">
                        <?= e($rc['name']) ?><?php if (!empty($rc['category'])): ?> <small>(<?= e($rc['category']) ?>)</small><?php endif; ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                  <div id="benchRecipeInputs"></div>
                </div>
              <?php endif; ?>
              <div class="form-full">
                <label>WIP Photos (up to <?= BENCH_MAX_IMAGES ?>)</label>
                <div class="bench-image-grid" id="benchImageGrid">
                  <?php for ($i = 1; $i <= BENCH_MAX_IMAGES; $i++): ?>
                    <div class="bench-img-slot" data-slot="<?= $i ?>">
                      <div class="bench-img-thumb" id="bn_img_thumb_<?= $i ?>"></div>
                      <input type="file" name="bn_image<?= $i ?>" id="bn_image_input_<?= $i ?>" accept="image/*" style="font-size:11px;width:100%">
                      <label class="bench-img-delete" id="bn_img_del_label_<?= $i ?>" style="display:none">
                        <input type="checkbox" name="delete_bn_img_<?= $i ?>" value="1"> Delete
                      </label>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="benchSubmitBtn">Add Entry</button>
              <button type="button" class="btn btn-sm" id="benchCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($benchData): ?>
          <?php
          $stageLabel = ['built' => 'Built', 'primed' => 'Primed', 'basecoated' => 'Basecoated', 'washed' => 'Washed', 'highlighted' => 'Highlighted', 'based' => 'Based', 'varnished' => 'Varnished', 'done' => 'Done'];
          ?>
          <div class="model-list">
            <?php foreach ($benchData as $bn):
              $st = $bn['stage'] ?? 'built';
              $lt = !empty($bn['last_touched']) ? date('M j', strtotime($bn['last_touched'])) : '';
              $imgCount = count($bn['wip_images'] ?? []);
              $colorCount = count($bn['colors'] ?? []);
            ?>
              <div class="model-row">
                <div class="model-row-info">
                  <div class="model-row-name"><?= e($bn['name']) ?></div>
                  <div class="model-row-meta">
                    <?= e($bn['faction'] ?? '') ?>
                    <?php if ($colorCount): ?>&nbsp;&middot;&nbsp;<?= $colorCount ?> paint<?= $colorCount !== 1 ? 's' : '' ?><?php endif; ?>
                    <?php if ($imgCount): ?>&nbsp;&middot;&nbsp;<?= $imgCount ?> photo<?= $imgCount !== 1 ? 's' : '' ?><?php endif; ?>
                    <?php if ($lt): ?>&nbsp;&middot;&nbsp;touched <?= e($lt) ?><?php endif; ?>
                  </div>
                  <?php if (!empty($bn['history'])): ?>
                    <details class="bench-hist-details">
                      <summary><?= count($bn['history']) ?> stage transition<?= count($bn['history']) !== 1 ? 's' : '' ?></summary>
                      <?php foreach (array_reverse($bn['history']) as $h): ?>
                        <div class="bench-hist-adm-row">
                          <span><?= e($stageLabel[$h['from']] ?? $h['from']) ?></span>
                          <span class="bench-hist-arrow">→</span>
                          <span><?= e($stageLabel[$h['to']] ?? $h['to']) ?></span>
                          <span class="bench-hist-adm-date"><?= !empty($h['date']) ? e(date('M j, Y', strtotime($h['date']))) : '' ?></span>
                        </div>
                      <?php endforeach; ?>
                    </details>
                  <?php endif; ?>
                </div>
                <button type="button"
                  class="bench-stage-btn stage-<?= e($st) ?>"
                  data-bid="<?= e($bn['id']) ?>"
                  data-stage="<?= e($st) ?>"
                  onclick="cycleBenchStage(this)"><?= e($stageLabel[$st] ?? $st) ?></button>
                <button type="button" class="btn btn-sm"
                  data-bid="<?= e($bn['id']) ?>"
                  data-bname="<?= e($bn['name']) ?>"
                  onclick="openSessionModal(this)">+ Session</button>
                <button class="btn btn-sm"
                  data-id="<?= e($bn['id']) ?>"
                  data-name="<?= e($bn['name']) ?>"
                  data-faction="<?= e($bn['faction'] ?? '') ?>"
                  data-system="<?= e($bn['system'] ?? '') ?>"
                  data-stage="<?= e($st) ?>"
                  data-date_start="<?= e($bn['date_start'] ?? '') ?>"
                  data-notes="<?= e($bn['notes'] ?? '') ?>"
                  data-codex_source="<?= e($bn['codex_source'] ?? '') ?>"
                  data-colors="<?= e(json_encode($bn['colors'] ?? [])) ?>"
                  data-brushes="<?= e(json_encode($bn['brushes'] ?? [])) ?>"
                  data-recipes="<?= e(json_encode($bn['recipes'] ?? [])) ?>"
                  data-images="<?= e(json_encode($bn['wip_images'] ?? [])) ?>"
                  onclick="openBenchEdit(this)">Edit</button>
                <?php if (empty($bn['promoted_to'])): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Archive to Gallery? A draft gallery entry will be created - you can add images and description in the edit form.')">
                    <input type="hidden" name="action" value="promote_bench">
                    <input type="hidden" name="bench_id" value="<?= e($bn['id']) ?>">
                    <button type="submit" class="btn btn-sm" style="font-size:10px">&rarr; Gallery</button>
                  </form>
                <?php else: ?>
                  <span style="font-size:10px;color:#c9a227;font-family:'Cinzel',serif">Promoted &rarr; <?= ucfirst(e($bn['promoted_to'])) ?></span>
                <?php endif; ?>
                <form method="post" onsubmit="return confirm('Delete this bench entry and its photos?')" style="margin:0">
                  <input type="hidden" name="action" value="delete_bench">
                  <input type="hidden" name="bench_id" value="<?= e($bn['id']) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">Nothing on the bench yet.</p>
        <?php endif; ?>
      <?php endif; ?>

      <h2 id="section-forces" class="collapsible" style="margin-top:40px">Forces &amp; Rosters
        <?php if ($hasForces): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($forcesData) ?> force<?= count($forcesData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>
      <div>
        <?php if (!$hasForces): ?>
          <form method="post" style="margin-top:12px">
            <input type="hidden" name="action" value="create_forces_file">
            <button class="btn">Start Forces &amp; Rosters</button>
          </form>
          <p style="color:#5a4a28;font-size:13px;margin-top:8px">Group your painted schemes into named rosters for Kill Team, OPR, Blood Bowl, Necromunda, and other game systems.</p>
        <?php else: ?>

          <!-- Add / Edit form -->
          <div style="margin-bottom:24px">
            <h3 style="font-family:'Cinzel',serif;font-size:14px;color:#9a8a6a;margin:0 0 12px"><?= $editForce ? 'Edit Force' : 'Add Force' ?></h3>
            <form method="post" action="<?= ADMIN_FILENAME ?>">
              <input type="hidden" name="action" value="<?= $editForce ? 'edit_force' : 'add_force' ?>">
              <?php if ($editForce): ?><input type="hidden" name="force_id" value="<?= e($editForce['id']) ?>"><?php endif; ?>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                  <label for="fo_name">Name *</label>
                  <input type="text" id="fo_name" name="fo_name" required placeholder="e.g. Contagion Protocol Kill Team"
                    value="<?= e($editForce['name'] ?? '') ?>">
                </div>
                <div>
                  <label for="fo_system">Game System</label>
                  <select id="fo_system" name="fo_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                    <option value="">- none -</option>
                    <?php foreach (['40k' => 'Warhammer 40,000', '30k / HH' => 'Horus Heresy / 30k', 'AoS' => 'Age of Sigmar', 'Old World' => 'The Old World', 'Kill Team' => 'Kill Team', 'Blood Bowl' => 'Blood Bowl', 'Necromunda' => 'Necromunda', 'Epic' => 'Epic Scale', 'OPR' => 'One Page Rules', 'Other' => 'Other'] as $sv => $sl): ?>
                      <option value="<?= e($sv) ?>" <?= ($editForce['system'] ?? '') === $sv ? ' selected' : '' ?>><?= e($sl) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="fo_faction">Faction</label>
                  <input type="text" id="fo_faction" name="fo_faction" placeholder="e.g. Death Guard"
                    value="<?= e($editForce['faction'] ?? '') ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                  <div>
                    <label for="fo_target_count">Target Models</label>
                    <input type="number" id="fo_target_count" name="fo_target_count" min="0" placeholder="e.g. 10"
                      value="<?= e($editForce['target_count'] ?? '') ?>" style="width:100%">
                  </div>
                  <div>
                    <label for="fo_target_points">Target Pts</label>
                    <input type="number" id="fo_target_points" name="fo_target_points" min="0" placeholder="e.g. 250"
                      value="<?= e($editForce['target_points'] ?? '') ?>" style="width:100%">
                  </div>
                </div>
              </div>
              <div style="margin-bottom:10px">
                <label for="fo_roster_url">Roster URL</label>
                <input type="url" id="fo_roster_url" name="fo_roster_url" placeholder="https://ktdash.app/rosters/… or Google Drive PDF link"
                  value="<?= e($editForce['roster_url'] ?? '') ?>">
              </div>
              <div style="margin-bottom:10px">
                <label for="fo_notes">Notes</label>
                <textarea id="fo_notes" name="fo_notes" rows="2" placeholder="Roster notes, list version, campaign context…" style="width:100%;resize:vertical"><?= e($editForce['notes'] ?? '') ?></textarea>
              </div>
              <?php if (!empty($models)): ?>
                <div style="margin-bottom:14px">
                  <label>Schemes in this Force</label>
                  <div style="max-height:220px;overflow-y:auto;border:1px solid #2a2010;background:#0e0d09;padding:8px 12px;border-radius:3px">
                    <?php foreach ($models as $m): ?>
                      <label style="display:flex;align-items:center;gap:8px;padding:3px 0;cursor:pointer;font-size:12px;color:#9a8a6a">
                        <input type="checkbox" name="force_models[]" value="<?= e($m['id']) ?>"
                          <?= in_array($m['id'], $editForce['models'] ?? []) ? 'checked' : '' ?>>
                        <span><?= e($m['name']) ?><?= !empty($m['faction']) ? '<span style="color:#5a4a28"> &mdash; ' . e($m['faction']) . '</span>' : '' ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
              <div style="display:flex;gap:8px;align-items:center">
                <button class="btn" type="submit"><?= $editForce ? 'Save Changes' : 'Add Force' ?></button>
                <?php if ($editForce): ?><a href="<?= ADMIN_FILENAME ?>#section-forces" style="color:#5a4a28;font-size:12px">Cancel</a><?php endif; ?>
              </div>
            </form>
          </div>

          <!-- Forces list -->
          <?php if (empty($forcesData)): ?>
            <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No forces yet. Add one above.</p>
          <?php else:
            $foModelById = array_column($models, null, 'id');
          ?>
            <div class="model-list" style="max-height:min(80vh,1200px);overflow-y:auto">
              <?php foreach ($forcesData as $fo): ?>
                <?php
                $foSchemes = count($fo['models'] ?? []);
                $foPainted = array_sum(array_map(fn($mid) => max(1, (int)(($foModelById[$mid] ?? [])['count'] ?? 1)), $fo['models'] ?? []));
                $foTarget  = $fo['target_count'] ?? 0;
                $foSystem  = $fo['system'] ?? '';
                ?>
                <div class="model-list-item" style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #1a1408">
                  <div style="flex:1">
                    <div style="font-family:'Cinzel',serif;font-size:13px;color:#c9a227"><?= e($fo['name']) ?></div>
                    <div style="font-size:11px;color:#5a4a28;margin-top:3px">
                      <?= $foSystem ? '<span style="margin-right:8px">' . e($foSystem) . '</span>' : '' ?>
                      <?= !empty($fo['faction']) ? e($fo['faction']) . ' &middot; ' : '' ?>
                      <?= $foSchemes ?> scheme<?= $foSchemes !== 1 ? 's' : '' ?><?= $foPainted !== $foSchemes ? ' &middot; ' . $foPainted . ' model' . ($foPainted !== 1 ? 's' : '') : '' ?> painted
                      <?= $foTarget ? ' / ' . $foTarget . ' target' : '' ?>
                      <?= !empty($fo['target_points']) ? ' &middot; ' . e($fo['target_points']) . ' pts' : '' ?>
                    </div>
                    <?php if (!empty($fo['notes'])): ?>
                      <div style="font-size:11px;color:#3a2a10;margin-top:2px"><?= e(mb_strimwidth($fo['notes'], 0, 80, '…')) ?></div>
                    <?php endif; ?>
                  </div>
                  <div style="display:flex;gap:6px;align-items:center;flex-shrink:0">
                    <button type="button" class="btn btn-sm fo-pin-btn<?= !empty($fo['pinned']) ? ' fo-pin-active' : '' ?>" data-id="<?= e($fo['id']) ?>" title="<?= !empty($fo['pinned']) ? 'Unpin' : 'Pin to top' ?>">★</button>
                    <a href="<?= ADMIN_FILENAME ?>?edit_force=<?= e($fo['id']) ?>#section-forces" class="btn btn-sm">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this force?')" style="margin:0">
                      <input type="hidden" name="action" value="delete_force">
                      <input type="hidden" name="force_id" value="<?= e($fo['id']) ?>">
                      <button class="btn btn-sm btn-danger" type="submit">&times;</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>

      <h2 id="section-recipes" style="margin-top:40px">Recipe Library
        <?php if ($hasRecipes): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($recipesData) ?> recipe<?= count($recipesData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasRecipes): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Author reusable technique recipes - "How I Paint Ork Flesh," "NMM Gold," "Blood Angels Red" - with ordered steps. Gallery, planned, and bench entries can reference them so you never re-describe the same technique twice.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_recipes_file">
          <button type="submit" class="btn btn-sm">Start Recipe Library</button>
        </form>
      <?php else: ?>
        <?php
        // Non-retired brushes for step picker (brushes with layout: brand · series · size)
        $brushOptions = [];
        if ($hasBrushes) {
          foreach ($brushesData as $br) {
            if (($br['condition'] ?? 'prime') === 'retired') continue;
            $brushOptions[] = ['id' => $br['id'], 'label' => trim(($br['brand'] ?? '') . ' ' . ($br['series'] ?? '') . ' ' . ($br['size'] ?? ''))];
          }
        }
        ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openRecipeAdd()">+ Add Recipe</button>
        </div>

        <datalist id="rc_paintList">
          <?php foreach ($paints as $p):
            $k = $p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? '');
          ?>
            <option value="<?= e($k) ?>"><?= e($p['brand'] . ' - ' . $p['name'] . ' (' . ($p['layer'] ?? '') . ')') ?></option>
          <?php endforeach; ?>
        </datalist>
        <datalist id="rc_brushList">
          <?php foreach ($brushOptions as $bo): ?>
            <option value="<?= e($bo['id']) ?>"><?= e($bo['label']) ?></option>
          <?php endforeach; ?>
        </datalist>

        <div class="paint-form-wrap" id="recipeFormWrap" style="display:none">
          <div class="paint-form-title" id="recipeFormTitle">Add Recipe</div>
          <form method="post" id="recipeForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_recipe" id="recipeAction">
            <input type="hidden" name="recipe_id" id="recipeId" value="">
            <div class="form-grid">
              <div>
                <label for="rc_name">Recipe Name *</label>
                <input type="text" id="rc_name" name="rc_name" required placeholder="e.g. Ork Skin Recipe">
              </div>
              <div>
                <label for="rc_category">Category</label>
                <input type="text" id="rc_category" name="rc_category" list="rc_catList" placeholder="e.g. Flesh">
                <datalist id="rc_catList">
                  <?php foreach (['Flesh', 'Metal', 'Cloth', 'Armour', 'Base', 'Leather', 'Bone', 'NMM', 'Skin', 'Cloak', 'Fur', 'Weapon', 'Eye', 'Wood', 'Stone', 'Gem', 'Fire', 'Glow'] as $c): ?>
                    <option value="<?= e($c) ?>">
                    <?php endforeach; ?>
                </datalist>
              </div>
              <div>
                <label for="rc_faction">Faction</label>
                <input type="text" id="rc_faction" name="rc_faction" placeholder="e.g. Orks (optional)">
              </div>
              <div class="form-full">
                <label for="rc_description">Description</label>
                <input type="text" id="rc_description" name="rc_description" placeholder="Short summary of what this recipe does">
              </div>
              <div class="form-full">
                <label>Steps</label>
                <div id="rc_steps"></div>
                <button type="button" class="btn btn-sm" onclick="addRecipeStep()" style="margin-top:6px">+ Add Step</button>
              </div>
              <div class="form-full">
                <label for="rc_notes">Notes</label>
                <textarea id="rc_notes" name="rc_notes" rows="2" placeholder="End-of-recipe freeform notes" style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
              <div class="form-full">
                <label>Reference Photo <span style="font-size:10px;color:#4a3a1a">(optional - finished result)</span></label>
                <div id="rc_image_preview" style="display:none;margin-bottom:6px">
                  <img id="rc_image_thumb" src="" alt="" style="height:80px;width:80px;object-fit:cover;border-radius:3px;border:1px solid #2a2010;display:block;margin-bottom:4px">
                  <label style="font-size:11px;color:#4a3a1a;display:flex;align-items:center;gap:5px;cursor:pointer">
                    <input type="checkbox" name="delete_rc_image" id="delete_rc_image" value="1" onchange="if(this.checked){document.getElementById('rc_image_preview').style.display='none'}"> Remove photo
                  </label>
                </div>
                <input type="file" name="rc_image" id="rc_image_file" accept="image/*">
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="recipeSubmitBtn">Add Recipe</button>
              <button type="button" class="btn btn-sm" id="recipeCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($recipesData): ?>
          <div class="model-list">
            <?php foreach ($recipesData as $rc):
              $stepCount = count($rc['steps'] ?? []);
            ?>
              <div class="model-row">
                <div class="model-row-info">
                  <div class="model-row-name"><?= e($rc['name']) ?></div>
                  <div class="model-row-meta">
                    <?php if (!empty($rc['category'])): ?><?= e($rc['category']) ?>&nbsp;&middot;&nbsp;<?php endif; ?>
                    <?php if (!empty($rc['faction'])): ?><?= e($rc['faction']) ?>&nbsp;&middot;&nbsp;<?php endif; ?>
                    <?= $stepCount ?> step<?= $stepCount !== 1 ? 's' : '' ?>
                  </div>
                </div>
                <button class="btn btn-sm"
                  data-id="<?= e($rc['id']) ?>"
                  data-name="<?= e($rc['name']) ?>"
                  data-category="<?= e($rc['category'] ?? '') ?>"
                  data-faction="<?= e($rc['faction'] ?? '') ?>"
                  data-description="<?= e($rc['description'] ?? '') ?>"
                  data-steps="<?= e(json_encode($rc['steps'] ?? [])) ?>"
                  data-notes="<?= e($rc['notes'] ?? '') ?>"
                  data-image="<?= e($rc['image'] ?? '') ?>"
                  onclick="openRecipeEdit(this)">Edit</button>
                <form method="post" onsubmit="return confirm('Delete this recipe? Any schemes referencing it will silently drop it.')" style="margin:0">
                  <input type="hidden" name="action" value="delete_recipe">
                  <input type="hidden" name="recipe_id" value="<?= e($rc['id']) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No recipes yet.</p>
        <?php endif; ?>
      <?php endif; ?>

      <h2 id="section-battles" class="collapsible" style="margin-top:40px">Battle Honours
        <?php if ($hasBattles && count($battlesData)): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($battlesData) ?> battle<?= count($battlesData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>
      <div>
        <?php if (!$hasBattles): ?>
          <form method="post" style="margin-top:12px">
            <input type="hidden" name="action" value="create_battles_file">
            <button class="btn">Start Battle Honours</button>
          </form>
          <p style="color:#5a4a28;font-size:13px;margin-top:8px">Track your games - results, opponents, armies, missions, and notes. Links to your Forces & Rosters for W/L/D records.</p>
        <?php else: ?>

          <!-- Add / Edit form -->
          <div style="margin-bottom:24px">
            <h3 id="bh-form-heading" style="font-family:'Cinzel',serif;font-size:14px;color:#9a8a6a;margin:0 0 12px">Log a Battle</h3>
            <form method="post" action="<?= ADMIN_FILENAME ?>" id="bh-form">
              <input type="hidden" name="action" id="bh_action" value="add_battle">
              <input type="hidden" name="bh_id" id="bh_id" value="">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                  <label for="bh_date">Date *</label>
                  <input type="date" id="bh_date" name="bh_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                  <label for="bh_result">Result *</label>
                  <select id="bh_result" name="bh_result" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                    <option value="win">Win</option>
                    <option value="loss">Loss</option>
                    <option value="draw">Draw</option>
                  </select>
                </div>
                <div>
                  <label for="bh_my_army">My Army</label>
                  <input type="text" id="bh_my_army" name="bh_my_army" placeholder="e.g. Death Guard">
                </div>
                <?php if ($hasForces && !empty($forcesData)): ?>
                <div>
                  <label for="bh_force_id">Linked Force</label>
                  <select id="bh_force_id" name="bh_force_id" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                    <option value="">- none -</option>
                    <?php foreach ($forcesData as $fo): ?>
                      <option value="<?= e($fo['id']) ?>"><?= e($fo['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php endif; ?>
                <div>
                  <label for="bh_system">Game System</label>
                  <select id="bh_system" name="bh_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                    <option value="">- none -</option>
                    <?php foreach (['40k' => 'Warhammer 40,000', '30k / HH' => 'Horus Heresy / 30k', 'AoS' => 'Age of Sigmar', 'Old World' => 'The Old World', 'Kill Team' => 'Kill Team', 'Blood Bowl' => 'Blood Bowl', 'Necromunda' => 'Necromunda', 'Epic' => 'Epic Scale', 'OPR' => 'One Page Rules', 'Other' => 'Other'] as $sv => $sl): ?>
                      <option value="<?= e($sv) ?>"><?= e($sl) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="bh_points">Points</label>
                  <input type="number" id="bh_points" name="bh_points" min="0" placeholder="e.g. 2000" style="width:100%">
                </div>
                <div>
                  <label for="bh_opponent">Opponent</label>
                  <input type="text" id="bh_opponent" name="bh_opponent" placeholder="e.g. Dave">
                </div>
                <div>
                  <label for="bh_opponent_army">Opponent's Army</label>
                  <input type="text" id="bh_opponent_army" name="bh_opponent_army" placeholder="e.g. Tyranids">
                </div>
                <div>
                  <label for="bh_mission">Mission</label>
                  <input type="text" id="bh_mission" name="bh_mission" placeholder="e.g. Sweep and Clear">
                </div>
              </div>
              <div style="margin-bottom:10px">
                <label for="bh_notes">Notes</label>
                <textarea id="bh_notes" name="bh_notes" rows="2" placeholder="How did it go?" style="width:100%;resize:vertical"></textarea>
              </div>
              <div style="display:flex;gap:8px;align-items:center">
                <button class="btn" type="submit" id="bh-submit-btn">Log Battle</button>
                <button type="button" class="btn" id="bh-cancel-btn" style="display:none" onclick="bhCancelEdit()">Cancel</button>
              </div>
            </form>
          </div>

          <!-- Battle list -->
          <?php if (empty($battlesData)): ?>
            <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No battles logged yet.</p>
          <?php else:
            $bhForceById = $hasForces ? array_column($forcesData, null, 'id') : [];
          ?>
            <div class="model-list" style="max-height:min(80vh,1200px);overflow-y:auto">
              <?php foreach ($battlesData as $bh): ?>
                <?php
                  $bhResult  = $bh['result'] ?? 'draw';
                  $bhForce   = !empty($bh['force_id']) && isset($bhForceById[$bh['force_id']]) ? $bhForceById[$bh['force_id']]['name'] : '';
                ?>
                <div class="model-list-item" style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #1a1408">
                  <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px">
                      <span style="font-family:'Cinzel',serif;font-size:12px;color:#9a8a6a"><?= e($bh['date'] ?? '') ?></span>
                      <span class="bh-result-badge bh-result-<?= e($bhResult) ?>"><?= ucfirst($bhResult) ?></span>
                      <?php if (!empty($bh['system'])): ?><span style="font-size:11px;color:#4a3a1a"><?= e($bh['system']) ?></span><?php endif; ?>
                      <?php if (!empty($bh['points'])): ?><span style="font-size:11px;color:#4a3a1a"><?= e($bh['points']) ?>pts</span><?php endif; ?>
                    </div>
                    <div style="font-size:12px;color:#c4b49a;margin-top:3px">
                      <?= e($bh['my_army'] ?? ($bhForce ?: '—')) ?>
                      <?php if ($bhForce && !empty($bh['my_army'])): ?><span style="color:#4a3a1a;font-size:11px"> (<?= e($bhForce) ?>)</span><?php endif; ?>
                      <span style="color:#5a4a28"> vs </span>
                      <?php if (!empty($bh['opponent'])): ?><span style="color:#9a8a6a"><?= e($bh['opponent']) ?></span><?php endif; ?>
                      <?php if (!empty($bh['opponent_army'])): ?><span style="color:#7a6a4a"><?= !empty($bh['opponent']) ? ' &mdash; ' : '' ?><?= e($bh['opponent_army']) ?></span><?php endif; ?>
                    </div>
                    <?php if (!empty($bh['mission'])): ?>
                      <div style="font-size:11px;color:#5a4a28;margin-top:2px"><?= e($bh['mission']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($bh['notes'])): ?>
                      <div style="font-size:11px;color:#3a2a10;margin-top:2px"><?= e(mb_strimwidth($bh['notes'], 0, 100, '…')) ?></div>
                    <?php endif; ?>
                  </div>
                  <div style="display:flex;gap:6px;align-items:center;flex-shrink:0">
                    <button type="button" class="btn btn-sm bh-edit-btn"
                      data-id="<?= e($bh['id']) ?>"
                      data-date="<?= e($bh['date'] ?? '') ?>"
                      data-result="<?= e($bh['result'] ?? 'draw') ?>"
                      data-my-army="<?= e($bh['my_army'] ?? '') ?>"
                      data-force-id="<?= e($bh['force_id'] ?? '') ?>"
                      data-system="<?= e($bh['system'] ?? '') ?>"
                      data-points="<?= e($bh['points'] ?? '') ?>"
                      data-opponent="<?= e($bh['opponent'] ?? '') ?>"
                      data-opponent-army="<?= e($bh['opponent_army'] ?? '') ?>"
                      data-mission="<?= e($bh['mission'] ?? '') ?>"
                      data-notes="<?= e($bh['notes'] ?? '') ?>">Edit</button>
                    <form method="post" onsubmit="return confirm('Delete this battle?')" style="margin:0">
                      <input type="hidden" name="action" value="delete_battle">
                      <input type="hidden" name="bh_id" value="<?= e($bh['id']) ?>">
                      <button class="btn btn-sm btn-danger" type="submit">&times;</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>

      <h2 id="section-wishlist" class="collapsible" style="margin-top:40px">Hobby Wishlist
        <?php if ($hasWishlist): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($wishlistData) ?> item<?= count($wishlistData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>
      <?php if (!$hasWishlist): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">Track everything you want to acquire — paints, kits, brushes, codices, back issues. Start the wishlist to enable it on the main site.</p>
        <form method="post">
          <input type="hidden" name="action" value="create_wishlist_file">
          <button type="submit" class="btn btn-sm">Start Wishlist</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <button type="button" class="btn btn-sm" onclick="openWishlistAdd()">+ Add Item</button>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="seed_wishlist_from_planned">
            <button type="submit" class="btn btn-sm" title="Adds missing/unowned paints from all Planned schemes">Seed from Planned</button>
          </form>
        </div>
        <div class="paint-form-wrap" id="wishlistFormWrap" style="display:none">
          <div class="paint-form-title" id="wishlistFormTitle">Add Item</div>
          <form method="post" id="wishlistForm">
            <input type="hidden" name="action" id="wishlistAction" value="add_wishlist_item">
            <input type="hidden" name="wl_id" id="wlId" value="">
            <div class="form-grid">
              <div>
                <label for="wl_type">Type *</label>
                <select id="wl_type" name="wl_type" onchange="wishlistTypeChange()" style="width:160px">
                  <option value="paint">Paint</option>
                  <option value="model">Model / Kit</option>
                  <option value="brush">Brush</option>
                  <option value="codex">Codex / Book</option>
                </select>
              </div>
              <div>
                <label for="wl_priority">Priority</label>
                <select id="wl_priority" name="wl_priority" style="width:120px">
                  <option value="high">High</option>
                  <option value="medium" selected>Medium</option>
                  <option value="low">Low</option>
                </select>
              </div>
              <div class="form-full">
                <label for="wl_name" id="wl_name_label">Paint Name *</label>
                <input type="text" id="wl_name" name="wl_name" required placeholder="e.g. Ironjawz Yellow" style="width:100%;max-width:360px">
              </div>
              <div id="wl_brand_row">
                <label for="wl_brand">Brand</label>
                <input type="text" id="wl_brand" name="wl_brand" list="wl_brandList" placeholder="e.g. Citadel" style="width:180px">
                <datalist id="wl_brandList">
                  <option value="Citadel">
                  <option value="Pro Acryl">
                  <option value="Vallejo">
                  <option value="Army Painter">
                  <option value="Gamblin Artist Oils">
                  <option value="AK Interactive">
                  <option value="Scale75">
                  <option value="Two Thin Coats">
                  <option value="Artis Opus">
                  <option value="Rosemary &amp; Co">
                </datalist>
              </div>
              <div id="wl_faction_row" style="display:none">
                <label for="wl_faction">Faction / Army</label>
                <input type="text" id="wl_faction" name="wl_faction" placeholder="e.g. Death Guard" style="width:180px">
              </div>
              <div id="wl_system_row" style="display:none">
                <label for="wl_system">Game System</label>
                <select id="wl_system" name="wl_system" style="width:160px">
                  <option value="">— none —</option>
                  <option value="40k">Warhammer 40,000</option>
                  <option value="30k / HH">Horus Heresy</option>
                  <option value="AoS">Age of Sigmar</option>
                  <option value="Old World">The Old World</option>
                  <option value="Kill Team">Kill Team</option>
                  <option value="Blood Bowl">Blood Bowl</option>
                  <option value="Necromunda">Necromunda</option>
                  <option value="Epic">Epic Scale</option>
                  <option value="OPR">One Page Rules</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="form-full">
                <label for="wl_url">Product URL <span style="font-weight:normal;opacity:.6">(optional)</span></label>
                <input type="url" id="wl_url" name="wl_url" placeholder="https://www.games-workshop.com/..." style="width:100%;max-width:480px">
              </div>
              <div class="form-full">
                <label for="wl_notes">Notes <span style="font-weight:normal;opacity:.6">(optional)</span></label>
                <input type="text" id="wl_notes" name="wl_notes" placeholder="e.g. for Ork boyz skin" style="width:100%;max-width:480px">
              </div>
              <div class="form-full">
                <label for="wl_ordered_date">Order Date <span style="font-weight:normal;opacity:.6">(optional - set when you place the order)</span></label>
                <input type="date" id="wl_ordered_date" name="wl_ordered_date" max="<?= date('Y-m-d') ?>" style="width:180px">
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="wishlistSubmitBtn">Add Item</button>
              <button type="button" class="btn btn-sm" id="wishlistCancelBtn" onclick="cancelWishlistEdit()">Cancel</button>
            </div>
          </form>
        </div>
        <?php if ($wishlistData): ?>
          <?php
          $wTypeLabels = ['paint' => 'Paint', 'model' => 'Model', 'brush' => 'Brush', 'codex' => 'Codex', 'wd' => 'WD'];
          $wTypeColors = ['paint' => '#1a4a4a', 'model' => '#1a3a1a', 'brush' => '#3a1a10', 'codex' => '#2a1a4a', 'wd' => '#3a2a08'];
          $wPriColors  = ['high' => ['bg' => 'rgba(239,68,68,.18)', 'txt' => '#ef4444'], 'medium' => ['bg' => 'rgba(249,115,22,.14)', 'txt' => '#f97316'], 'low' => ['bg' => 'rgba(80,80,80,.2)', 'txt' => '#7a7a7a']];
          ?>
          <div class="model-list">
            <?php foreach ($wishlistData as $w): ?>
              <?php
              $wtype     = $w['type'] ?? 'paint';
              $wpri      = $w['priority'] ?? 'medium';
              $typeColor = $wTypeColors[$wtype] ?? '#1a4a4a';
              $typeLabel = $wTypeLabels[$wtype]  ?? 'Item';
              $priC      = $wPriColors[$wpri] ?? $wPriColors['medium'];
              ?>
              <div class="model-row" id="wish-row-<?= e($w['id']) ?>" style="border-left:3px solid <?= $typeColor ?>">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:<?= $typeColor ?>;color:#c9a227;font-family:'Cinzel',serif;letter-spacing:.04em"><?= $typeLabel ?></span>
                    <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:<?= $priC['bg'] ?>;color:<?= $priC['txt'] ?>;font-family:'Cinzel',serif;letter-spacing:.04em"><?= ucfirst($wpri) ?></span>
                    <strong><?= e($w['name']) ?></strong>
                  </div>
                  <div class="model-row-meta">
                    <?php $meta = array_filter([e($w['brand'] ?? ''), e($w['faction'] ?? ''), e($w['system'] ?? '')]);
                    echo implode(' &middot; ', $meta); ?>
                    <?php if (!empty($w['added'])): ?><span style="margin-left:8px;opacity:.5;font-size:10px">added <?= e($w['added']) ?></span><?php endif; ?>
                  </div>
                  <?php if (!empty($w['notes'])): ?><div style="font-size:11px;color:#5a4a28;margin-top:2px"><?= e(mb_substr($w['notes'], 0, 120)) ?></div><?php endif; ?>
                  <?php if (!empty($w['url'])): ?><div style="margin-top:3px"><a href="<?= e($w['url']) ?>" target="_blank" rel="noopener" style="font-size:11px;color:#6a8a6a;text-decoration:none" title="<?= e($w['url']) ?>">&#128279; Link</a></div><?php endif; ?>
                </div>
                <div class="model-row-actions">
                  <?php if (empty($w['ordered_date'])): ?>
                    <button type="button" class="btn btn-sm btn-ordered" onclick="markOrdered('<?= e($w['id']) ?>')" style="font-size:10px">Mark Ordered</button>
                  <?php else: ?>
                    <span class="wish-ordered-badge" style="display:inline-block">Ordered <?= e($w['ordered_date']) ?></span>
                    <button type="button" class="btn-ordered-clear" onclick="clearOrdered('<?= e($w['id']) ?>')">Clear</button>
                  <?php endif; ?>
                  <?php if ($wtype === 'model' && empty($w['promoted_to'])): ?>
                    <button type="button" class="btn btn-sm" onclick="promoteWishlist('<?= e($w['id']) ?>')" style="font-size:10px">&rarr; Shame</button>
                  <?php elseif (!empty($w['promoted_to'])): ?>
                    <span style="font-size:10px;color:#c9a227;font-family:'Cinzel',serif">Promoted &rarr; <?= ucfirst(e($w['promoted_to'])) ?></span>
                  <?php endif; ?>
                  <button type="button" class="btn btn-sm" onclick="openWishlistEdit(this)"
                    data-id="<?= e($w['id']) ?>"
                    data-type="<?= e($wtype) ?>"
                    data-name="<?= e($w['name']) ?>"
                    data-brand="<?= e($w['brand'] ?? '') ?>"
                    data-faction="<?= e($w['faction'] ?? '') ?>"
                    data-system="<?= e($w['system'] ?? '') ?>"
                    data-priority="<?= e($wpri) ?>"
                    data-notes="<?= e($w['notes'] ?? '') ?>"
                    data-url="<?= e($w['url'] ?? '') ?>"
                    data-ordered-date="<?= e($w['ordered_date'] ?? '') ?>">&#10000; Edit</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Remove from wishlist?')">
                    <input type="hidden" name="action" value="delete_wishlist_item">
                    <input type="hidden" name="wl_id" value="<?= e($w['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger" title="Remove">&times;</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No items yet. Add anything you want to acquire.</p>
        <?php endif; ?>
      <?php endif; ?>

      <h2 id="section-books" style="margin-top:40px">Codex Library
        <?php if ($hasBooks): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($booksData) ?> cod<?= count($booksData) !== 1 ? 'ices' : 'ex' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasBooks): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Your Codex Library is not active yet. Click below to start - it won't appear on the main site until enabled here.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_books_file">
          <button type="submit" class="btn btn-sm">Start Codex Library</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openBookAdd()">+ Add Codex</button>
        </div>

        <div class="paint-form-wrap" id="bookFormWrap" style="display:none">
          <div class="paint-form-title" id="bookFormTitle">Add Codex</div>
          <form method="post" id="bookForm">
            <input type="hidden" name="action" value="add_book" id="bookAction">
            <input type="hidden" name="bk_id" id="bkId" value="">
            <div class="form-grid">
              <div>
                <label for="bk_type">Type</label>
                <select id="bk_type" name="bk_type" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="codex">Codex / Army Book</option>
                  <option value="supplement">Supplement / Campaign</option>
                </select>
              </div>
              <div>
                <label for="bk_faction">Faction / Legion</label>
                <input type="text" id="bk_faction" name="bk_faction" placeholder="e.g. Death Guard">
              </div>
              <div class="form-full">
                <label for="bk_title">Title *</label>
                <input type="text" id="bk_title" name="bk_title" required placeholder="e.g. Codex: Death Guard">
              </div>
              <div>
                <label for="bk_author">Publisher / Credit</label>
                <input type="text" id="bk_author" name="bk_author" placeholder="e.g. Games Workshop">
              </div>
              <div>
                <label for="bk_series">Edition</label>
                <input type="text" id="bk_series" name="bk_series" placeholder="e.g. 10th Edition">
              </div>
              <div class="form-full">
                <label for="bk_notes">Notes</label>
                <textarea id="bk_notes" name="bk_notes" rows="4"
                  placeholder="Paint schemes, lore notes, page references…"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="bookSubmitBtn">Add Codex</button>
              <button type="button" class="btn btn-sm" id="bookCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($booksData): ?>
          <div class="model-list">
            <?php foreach ($booksData as $bk): ?>
              <?php
              $bkType = $bk['type'] ?? 'codex';
              $bkPrev = !empty($bk['notes']) ? mb_substr($bk['notes'], 0, 80) . (mb_strlen($bk['notes']) > 80 ? '…' : '') : '';
              ?>
              <div class="model-row">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <?= e($bk['title']) ?>
                    <span style="font-size:9px;background:#0e1a1a;color:#70c8c8;border:1px solid #1a3a3a;border-radius:2px;padding:1px 5px;font-family:'Cinzel',serif;letter-spacing:.06em;text-transform:uppercase"><?= $bkType === 'supplement' ? 'Supplement' : 'Codex' ?></span>
                    <?php if (!empty($bk['faction'])): ?>
                      <span style="font-size:9px;background:#1a0a06;color:#8a6a3a;border:1px solid #3a2010;border-radius:2px;padding:1px 5px;font-family:'Cinzel',serif;letter-spacing:.05em"><?= e($bk['faction']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="model-row-meta">
                    <?php if (!empty($bk['series'])): ?><em><?= e($bk['series']) ?></em><?php endif; ?>
                    <?php if (!empty($bk['series']) && !empty($bk['author'])): ?> &middot; <?php endif; ?>
                    <?php if (!empty($bk['author'])): ?><?= e($bk['author']) ?><?php endif; ?>
                  </div>
                  <?php if ($bkPrev): ?>
                    <div class="wd-notes-preview"><?= e($bkPrev) ?></div>
                  <?php endif; ?>
                </div>
                <button class="btn btn-sm"
                  data-id="<?= e($bk['id']) ?>"
                  data-type="<?= e($bkType) ?>"
                  data-title="<?= e($bk['title']) ?>"
                  data-author="<?= e($bk['author'] ?? '') ?>"
                  data-series="<?= e($bk['series'] ?? '') ?>"
                  data-faction="<?= e($bk['faction'] ?? '') ?>"
                  data-notes="<?= e($bk['notes'] ?? '') ?>"
                  onclick="openBookEdit(this)">Edit</button>
                <form method="post" onsubmit="return confirm('Delete &quot;<?= e($bk['title']) ?>&quot;?')" style="margin:0">
                  <input type="hidden" name="action" value="delete_book">
                  <input type="hidden" name="bk_id" value="<?= e($bk['id']) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No codices logged yet.</p>
        <?php endif; ?>
      <?php endif; ?>

      <h2 id="section-journal" class="collapsible" style="margin-top:40px">Scrap Notes
        <?php if ($hasJournal): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($journalData) ?> entr<?= count($journalData) !== 1 ? 'ies' : 'y' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasJournal): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Scrap Notes not active yet. Click below to start one - log sessions, discoveries, and hobby moments.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_journal_file">
          <button type="submit" class="btn btn-sm">Start Scrap Notes</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openJournalAdd()">+ Add Entry</button>
        </div>

        <div class="paint-form-wrap" id="journalFormWrap" style="display:none">
          <div class="paint-form-title" id="journalFormTitle">Add Journal Entry</div>
          <form method="post" id="journalForm">
            <input type="hidden" name="action" value="add_journal" id="journalAction">
            <input type="hidden" name="jn_id" id="jnId" value="">
            <div class="form-grid">
              <div>
                <label for="jn_date">Date *</label>
                <input type="date" id="jn_date" name="jn_date" required value="<?= date('Y-m-d') ?>">
              </div>
              <div>
                <label for="jn_mood">Mood</label>
                <select id="jn_mood" name="jn_mood" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">-</option>
                  <option value="great">Great</option>
                  <option value="good">Good</option>
                  <option value="okay">Okay</option>
                  <option value="rough">Rough</option>
                </select>
              </div>
              <div class="form-full">
                <label for="jn_title">Title (optional)</label>
                <input type="text" id="jn_title" name="jn_title" placeholder="e.g. Found a better wet-blending ratio">
              </div>
              <div class="form-full" style="position:relative">
                <label for="jn_body">Entry * <span style="color:#5a4a28;font-size:11px;font-family:inherit">- type @ to tag a scheme, recipe, or WD issue</span></label>
                <textarea id="jn_body" name="jn_body" rows="8" required
                  placeholder="What did you paint, discover, or think about today?"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
                <div id="jnMentionPicker" style="display:none;position:absolute;z-index:200;background:#0e0d09;border:1px solid #c9a227;border-radius:4px;min-width:260px;max-width:340px;box-shadow:0 4px 18px rgba(0,0,0,.7);overflow:hidden">
                  <div id="jnMentionList" style="max-height:220px;overflow-y:auto"></div>
                </div>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="journalSubmitBtn">Add Entry</button>
              <button type="button" class="btn btn-sm" id="journalCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($journalData): ?>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap">
            <input type="search" id="jn-admin-filter" placeholder="Filter by date or text&hellip;" autocomplete="off" oninput="filterJournalList(this.value)" style="background:#130f08;border:1px solid #2a1e08;color:#c4b49a;border-radius:4px;padding:5px 10px;font-size:12px;flex:1 1 200px;min-width:0">
            <span id="jn-admin-count" style="font-family:'Cinzel',serif;font-size:10px;color:#6a5a30;white-space:nowrap"><?= count($journalData) ?> entries</span>
          </div>
          <div class="model-list" id="jn-admin-list">
            <?php foreach ($journalData as $jn): ?>
              <?php
              $jnPrev    = !empty($jn['body']) ? mb_substr($jn['body'], 0, 100) . (mb_strlen($jn['body']) > 100 ? '…' : '') : '';
              $jnMood    = $jn['mood'] ?? '';
              $jnDateFmt = !empty($jn['date']) ? date('M j, Y', strtotime($jn['date'])) : '';
              $moodMap   = ['great' => ['#1c3a1c', '#7ad678'], 'good' => ['#1c2a1a', '#a0c878'], 'okay' => ['#3a2d10', '#e8b060'], 'rough' => ['#3a1c1c', '#e88080']];
              [$jnMoodBg, $jnMoodFg] = $moodMap[$jnMood] ?? ['#1c2a3a', '#7ab0e8'];
              $jnSearch  = ($jn['date'] ?? '') . ' ' . ($jn['title'] ?? '') . ' ' . ($jn['body'] ?? '');
              ?>
              <div class="model-row" data-jnsearch="<?= e($jnSearch) ?>">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <?php if ($jnDateFmt): ?>
                      <span style="font-family:'Cinzel',serif;font-size:11px;color:#c9a227;letter-spacing:.05em"><?= e($jnDateFmt) ?></span>
                    <?php endif; ?>
                    <?php if ($jnMood): ?>
                      <span style="font-size:9px;font-weight:700;letter-spacing:.1em;padding:2px 6px;border-radius:2px;background:<?= $jnMoodBg ?>;color:<?= $jnMoodFg ?>;font-family:'Cinzel',serif;text-transform:uppercase"><?= ucfirst($jnMood) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($jn['title'])): ?>
                      <span style="font-size:12px;color:#a89868"><?= e($jn['title']) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if ($jnPrev): ?>
                    <div class="wd-notes-preview"><?= e($jnPrev) ?></div>
                  <?php endif; ?>
                </div>
                <button class="btn btn-sm"
                  data-id="<?= e($jn['id']) ?>"
                  data-date="<?= e($jn['date'] ?? '') ?>"
                  data-title="<?= e($jn['title'] ?? '') ?>"
                  data-mood="<?= e($jnMood) ?>"
                  data-body="<?= e($jn['body'] ?? '') ?>"
                  onclick="openJournalEdit(this)">Edit</button>
                <form method="post" onsubmit="return confirm('Delete this journal entry?')" style="margin:0">
                  <input type="hidden" name="action" value="delete_journal">
                  <input type="hidden" name="jn_id" value="<?= e($jn['id']) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No journal entries yet.</p>
        <?php endif; ?>
      <?php endif; ?>

    <?php endif; ?>
  </div><!-- /admin-wrap -->
  <?php if ($authed): ?></div><!-- /admin-main --><?php endif; ?>

  <?php if ($authed): ?>
    <?php
    $jnMentionables = [];
    foreach ($models as $m) {
        $jnMentionables[] = ['type' => 'scheme', 'id' => $m['id'], 'label' => $m['name']];
    }
    if ($hasRecipes) {
        foreach ($recipesData as $r) {
            $jnMentionables[] = ['type' => 'recipe', 'id' => $r['id'], 'label' => $r['name']];
        }
    }
    if ($hasBench) {
        foreach ($benchData as $b) {
            $jnMentionables[] = ['type' => 'bench', 'id' => $b['id'], 'label' => $b['name']];
        }
    }
    ?>
    <script>
      const ADMIN_PHP = '<?= ADMIN_FILENAME ?>';
      const TODAY_DATE = '<?= date('Y-m-d') ?>';
      const BENCH_MAX_IMAGES_JS = <?= BENCH_MAX_IMAGES ?>;
      const JOURNAL_COUNT = <?= count($journalData ?? []) ?>;
      const ALL_PAINTS = <?= json_encode(array_map(fn($p) => $p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? ''), $paints)) ?>;
      const INVENTORY_DATA = <?= json_encode(array_map(fn($p) => [$p['brand'], $p['name'], $p['color'] ?? '', $p['layer'] ?? '', $p['stock'] ?? ''], $paints), JSON_UNESCAPED_UNICODE) ?>;
      const MASTER_PAINTS = <?= $masterPaintsJson ?>;
      const CONVERSIONS = <?= $conversionsJson ?>;
      const PLANNED_DATA = <?= json_encode(array_map(fn($p) => ['name' => $p['name'], 'colors' => $p['colors'] ?? []], $planned), JSON_UNESCAPED_UNICODE) ?>;
      const PRE_SELECTED = <?= json_encode($editModel ? ($editModel['colors'] ?? []) : []) ?>;
      const JN_MENTIONABLES_DATA = <?= json_encode($jnMentionables) ?>;
    </script>
    <script src="js/admin.js?v=5"></script>
  <?php endif; ?>

  <button id="back-to-top" title="Back to top">↑</button>

  <?php if ($authed && $hasBench): ?>
    <div class="adm-shop-overlay" id="sess-modal-overlay" onclick="if(event.target===this)closeSessionModal()">
      <div class="adm-shop-sheet">
        <div class="adm-shop-title">Log Session</div>
        <div class="adm-shop-subtitle" id="sess-modal-project"></div>
        <div style="display:flex;flex-direction:column;gap:10px;margin:14px 0 4px">
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Date *
            <input type="date" id="sess-date" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px">
          </label>
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Duration (minutes, optional)
            <input type="number" id="sess-duration" min="1" placeholder="e.g. 90" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px">
          </label>
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Notes (optional)
            <textarea id="sess-note" rows="3" placeholder="What did you work on?" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px;resize:vertical"></textarea>
          </label>
        </div>
        <div class="adm-shop-actions">
          <button class="btn btn-sm" onclick="submitSessionLog()">Log Session</button>
          <button class="btn btn-sm" onclick="closeSessionModal()" style="background:#1a1a1a">Cancel</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($authed): ?>
    <div class="adm-shop-overlay" id="gallery-sess-overlay" onclick="if(event.target===this)closeGallerySessionModal()">
      <div class="adm-shop-sheet">
        <div class="adm-shop-title" id="gallery-sess-title">Log Painted Models</div>
        <div class="adm-shop-subtitle" id="gallery-sess-project"></div>
        <input type="hidden" id="gallery-sess-idx" value="-1">
        <div style="display:flex;flex-direction:column;gap:10px;margin:14px 0 4px">
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Date *
            <input type="date" id="gallery-sess-date" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px">
          </label>
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Models painted *
            <input type="number" id="gallery-sess-count" min="1" placeholder="e.g. 3" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px">
          </label>
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Notes (optional)
            <textarea id="gallery-sess-note" rows="3" placeholder="What did you finish?" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px;resize:vertical"></textarea>
          </label>
        </div>
        <div class="adm-shop-actions">
          <button class="btn btn-sm" onclick="submitGallerySessionLog()">Log</button>
          <button class="btn btn-sm" onclick="closeGallerySessionModal()" style="background:#1a1a1a">Cancel</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($authed && ($missingPlanned ?? 0) > 0): ?>
    <div class="adm-shop-overlay" id="adm-shop-overlay" onclick="if(event.target===this)closeAdmShopModal()">
      <div class="adm-shop-sheet">
        <div class="adm-shop-title">Missing Paints - Planned Schemes</div>
        <div class="adm-shop-subtitle" id="adm-shop-subtitle"></div>
        <div id="adm-shop-content"></div>
        <div class="adm-shop-actions">
          <button class="btn btn-sm" onclick="closeAdmShopModal()">Close</button>
        </div>
      </div>
    </div>
  <?php endif; ?>
  </body>

</html>