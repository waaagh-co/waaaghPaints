<?php
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
  $sub_faction  = trim($_POST['bn_sub_faction'] ?? '');
  $system       = trim($_POST['bn_system']      ?? '');
  $stage        = trim($_POST['bn_stage']       ?? 'built');
  $date_start   = trim($_POST['bn_date_start']  ?? '');
  $notes        = trim($_POST['bn_notes']       ?? '');
  $codex_source = trim($_POST['bn_codex_source'] ?? '');
  $colors       = array_values(array_filter($_POST['bench_colors'] ?? []));
  $brushes      = array_values(array_filter($_POST['bench_brushes'] ?? []));
  $recipes      = array_values(array_filter($_POST['bench_recipes'] ?? []));
  $count        = max(0, (int)($_POST['bn_count'] ?? 0));
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
        $fp = __DIR__ . '/../' . $slotImages[$slot - 1];
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
    if ($sub_faction)  $entry['sub_faction']  = $sub_faction;
    if ($system)       $entry['system']       = $system;
    if ($date_start)   $entry['date_start']   = $date_start;
    if ($notes)        $entry['notes']        = $notes;
    if ($codex_source) $entry['codex_source'] = $codex_source;
    if ($colors)       $entry['colors']       = $colors;
    if ($brushes)    $entry['brushes']    = $brushes;
    if ($recipes)    $entry['recipes']    = $recipes;
    if ($images)     $entry['wip_images'] = $images;
    if ($count > 0)                              $entry['count']             = $count;
    if (!empty($existing['models_done']))        $entry['models_done']       = (int)$existing['models_done'];
    if (!empty($existing['recipe_steps_done']))  $entry['recipe_steps_done'] = $existing['recipe_steps_done'];
    if (!empty($existing['promoted_to']))        $entry['promoted_to']       = $existing['promoted_to'];
    if (!empty($existing['promoted_id']))        $entry['promoted_id']       = $existing['promoted_id'];
    if (!empty($existing['history']))            $entry['history']           = $existing['history'];
    if (!empty($existing['sessions']))           $entry['sessions']          = $existing['sessions'];

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
          $fp = __DIR__ . '/../' . $img;
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

if ($authed && ($_POST['action'] ?? '') === 'bench_model_done') {
  header('Content-Type: application/json');
  $bid = trim($_POST['bench_id'] ?? '');
  if ($bid !== '') {
    $bench = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
    $result = ['ok' => false];
    $promotedId = '';
    foreach ($bench as &$b) {
      if ($b['id'] === $bid) {
        $count = max(1, (int)($b['count'] ?? 1));
        $done  = (int)($b['models_done'] ?? 0) + 1;
        $b['models_done'] = $done;
        unset($b['recipe_steps_done']);
        $b['last_touched'] = date('Y-m-d');
        $promotedId = $b['promoted_id'] ?? '';
        $result = ['ok' => true, 'models_done' => $done, 'count' => $count, 'all_done' => $done >= $count];
        break;
      }
    }
    unset($b);
    benchSort($bench);
    file_put_contents(BENCH_FILE, json_encode($bench, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($promotedId !== '' && file_exists(MODELS_FILE)) {
      $models = json_decode(file_get_contents(MODELS_FILE), true) ?? [];
      foreach ($models as &$m) {
        if ($m['id'] === $promotedId) {
          $m['count'] = max(1, (int)($m['count'] ?? 1)) + 1;
          break;
        }
      }
      unset($m);
      file_put_contents(MODELS_FILE, json_encode($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    echo json_encode($result);
  } else {
    echo json_encode(['ok' => false]);
  }
  exit;
}

if ($authed && ($_POST['action'] ?? '') === 'set_recipe_step_done') {
  header('Content-Type: application/json');
  $bid  = trim($_POST['bench_id']    ?? '');
  $rid  = trim($_POST['recipe_id']   ?? '');
  $step = (int)($_POST['step_index'] ?? -1);
  $done = ($_POST['done'] ?? '0') === '1';
  if ($bid !== '' && $rid !== '' && $step >= 0) {
    $bench = file_exists(BENCH_FILE) ? (json_decode(file_get_contents(BENCH_FILE), true) ?? []) : [];
    foreach ($bench as &$b) {
      if ($b['id'] === $bid) {
        $stepsDone   = $b['recipe_steps_done'] ?? [];
        $recipeSteps = $stepsDone[$rid] ?? [];
        if ($done) {
          if (!in_array($step, $recipeSteps, true)) $recipeSteps[] = $step;
        } else {
          $recipeSteps = array_values(array_filter($recipeSteps, fn($s) => $s !== $step));
        }
        sort($recipeSteps);
        if ($recipeSteps) $stepsDone[$rid] = $recipeSteps; else unset($stepsDone[$rid]);
        if ($stepsDone) $b['recipe_steps_done'] = $stepsDone; else unset($b['recipe_steps_done']);
        break;
      }
    }
    unset($b);
    file_put_contents(BENCH_FILE, json_encode($bench, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true]);
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
      $fp = __DIR__ . '/../' . $existingImage;
      if (file_exists($fp)) @unlink($fp);
      $image = '';
    } elseif (!empty($_FILES['rc_image']['name']) && $_FILES['rc_image']['error'] === UPLOAD_ERR_OK) {
      $file = $_FILES['rc_image'];
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
      if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']) && $file['size'] <= MAX_FILE_BYTES) {
        $filename = $id . '_1.' . imageExt($mime);
        if (saveModelImage($file['tmp_name'], RECIPE_IMG_DIR . $filename, $mime)) {
          if ($existingImage !== '' && $existingImage !== RECIPE_IMG_WEB . $filename) {
            $fp = __DIR__ . '/../' . $existingImage;
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
        $fp = __DIR__ . '/../' . $r['image'];
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
    'paints'    => __DIR__ . '/../data/paints.json',
    'models'    => __DIR__ . '/../data/models.json',
    'planned'   => __DIR__ . '/../data/planned.json',
    'brushes'   => __DIR__ . '/../data/brushes.json',
    'bench'     => __DIR__ . '/../data/bench.json',
    'recipes'   => __DIR__ . '/../data/recipes.json',
    'books'     => __DIR__ . '/../data/books.json',
    'journal'   => __DIR__ . '/../data/journal.json',
    'shame'     => __DIR__ . '/../data/shame.json',
    'wishlist'  => __DIR__ . '/../data/wishlist.json',
    'forces'    => __DIR__ . '/../data/forces.json',
    'battles'   => __DIR__ . '/../data/battles.json',
    'supplies'  => __DIR__ . '/../data/supplies.json',
    'rescues'   => __DIR__ . '/../data/rescues.json',
    'goals'     => __DIR__ . '/../data/goals.json',
    'tab_stats' => __DIR__ . '/../data/tab_stats.json',
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
        $path = __DIR__ . '/../data/' . $key . '.json';
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
  header('Location: admin.php#section-bench');
  exit;
}
