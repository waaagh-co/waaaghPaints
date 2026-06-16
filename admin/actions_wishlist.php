<?php

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
