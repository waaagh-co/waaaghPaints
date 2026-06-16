<?php

if ($authed && isset($_POST['action']) && $_POST['action'] === 'add_model') {
  $name         = trim($_POST['model_name']    ?? '');
  $faction      = trim($_POST['faction']       ?? '');
  $sub_faction  = trim($_POST['sub_faction']   ?? '');
  $system       = trim($_POST['system']        ?? '');
  $date         = trim($_POST['date']          ?? '');
  $description  = trim($_POST['description']   ?? '');
  $codex_source = trim($_POST['codex_source']  ?? '');
  $count        = max(1, (int)($_POST['model_count'] ?? 1));
  $colors       = dedupeColors($_POST['colors'] ?? []);
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
        'sub_faction'  => $sub_faction,
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
  $sub_faction  = trim($_POST['sub_faction']   ?? '');
  $system       = trim($_POST['system']        ?? '');
  $date         = trim($_POST['date']          ?? '');
  $description  = trim($_POST['description']   ?? '');
  $codex_source = trim($_POST['codex_source']  ?? '');
  $count        = max(1, (int)($_POST['model_count'] ?? 1));
  $colors       = dedupeColors($_POST['colors'] ?? []);
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
          $filePath = __DIR__ . '/../' . $slotImages[$slot - 1];
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
        if ($sub_faction !== '')  $entry['sub_faction']  = $sub_faction;
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
