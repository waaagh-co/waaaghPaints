<?php
if ($authed && ($_POST['action'] ?? '') === 'add_planned') {
  $name        = trim($_POST['pl_name']        ?? '');
  $model        = trim($_POST['pl_model']        ?? '');
  $faction      = trim($_POST['pl_faction']      ?? '');
  $sub_faction  = trim($_POST['pl_sub_faction']  ?? '');
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
    if ($sub_faction)  $entry['sub_faction']  = $sub_faction;
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
  $sub_faction  = trim($_POST['pl_sub_faction']  ?? '');
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
        if ($sub_faction)  $p['sub_faction']  = $sub_faction;
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
