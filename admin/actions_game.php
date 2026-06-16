<?php
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

if ($authed && ($_POST['action'] ?? '') === 'toggle_model_feature') {
  header('Content-Type: application/json');
  $mid = trim($_POST['model_id'] ?? '');
  if (!$mid || !file_exists(MODELS_FILE)) { echo json_encode(['ok' => false]); exit; }
  $all = json_decode(file_get_contents(MODELS_FILE), true) ?? [];
  $targetIdx = -1;
  foreach ($all as $i => $m) {
    if ($m['id'] === $mid) { $targetIdx = $i; break; }
  }
  if ($targetIdx === -1) { echo json_encode(['ok' => false]); exit; }
  $nowFeatured = empty($all[$targetIdx]['featured']);
  if ($nowFeatured) {
    $imgCount = count($all[$targetIdx]['images'] ?? []);
    $all[$targetIdx]['featured'] = $imgCount > 0 ? range(0, $imgCount - 1) : [0];
  } else {
    unset($all[$targetIdx]['featured']);
  }
  file_put_contents(MODELS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
  echo json_encode(['ok' => true, 'featured' => $nowFeatured ? ($all[$targetIdx]['featured'] ?? []) : false]);
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'toggle_model_showcase_image') {
  header('Content-Type: application/json');
  $mid = trim($_POST['model_id'] ?? '');
  $idx = (int)($_POST['image_idx'] ?? -1);
  if (!$mid || $idx < 0 || !file_exists(MODELS_FILE)) { echo json_encode(['ok' => false]); exit; }
  $all = json_decode(file_get_contents(MODELS_FILE), true) ?? [];
  $targetIdx = -1;
  foreach ($all as $i => $m) {
    if ($m['id'] === $mid) { $targetIdx = $i; break; }
  }
  if ($targetIdx === -1) { echo json_encode(['ok' => false]); exit; }
  $current = is_array($all[$targetIdx]['featured'] ?? null) ? $all[$targetIdx]['featured'] : [];
  if (in_array($idx, $current, true)) {
    $current = array_values(array_filter($current, fn($v) => $v !== $idx));
  } else {
    $current[] = $idx;
    sort($current);
  }
  if (empty($current)) {
    unset($all[$targetIdx]['featured']);
    $result = false;
  } else {
    $all[$targetIdx]['featured'] = $current;
    $result = $current;
  }
  file_put_contents(MODELS_FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
  echo json_encode(['ok' => true, 'active' => in_array($idx, $current, true), 'featured' => $result]);
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
