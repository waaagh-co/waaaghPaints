<?php
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
