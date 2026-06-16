<?php
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
      $fp = __DIR__ . '/../' . $slotImages[$slot - 1];
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
          if ($img) { $fp = __DIR__ . '/../' . $img; if (file_exists($fp)) @unlink($fp); }
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
