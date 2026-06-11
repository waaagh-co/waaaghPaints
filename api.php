<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$key = trim($_POST['key'] ?? $_GET['key'] ?? '');
if (!defined('ADMIN_PASSWORD') || $key !== ADMIN_PASSWORD) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

// ── get_bench ─────────────────────────────────────────────────────────────────
if ($action === 'get_bench') {
    $bench = file_exists(__DIR__ . '/data/bench.json')
        ? json_decode(file_get_contents(__DIR__ . '/data/bench.json'), true) ?? []
        : [];
    $active = array_values(array_filter($bench, fn($b) => ($b['stage'] ?? '') !== 'done' && empty($b['promoted_to'])));
    usort($active, fn($a, $b) => strcmp($b['last_touched'] ?? '', $a['last_touched'] ?? ''));
    echo json_encode(['ok' => true, 'projects' => array_map(fn($b) => [
        'id'    => $b['id'],
        'name'  => $b['name'],
        'stage' => ucfirst($b['stage'] ?? ''),
        'image' => !empty($b['wip_images']) ? $b['wip_images'][0] : null,
    ], $active)]);
    exit;
}

// ── log_session ───────────────────────────────────────────────────────────────
if ($action === 'log_session') {
    $projectId = trim($_POST['project_id'] ?? '');
    $duration  = (int)($_POST['duration'] ?? 0);
    $note      = trim($_POST['note'] ?? '');

    if (!$projectId || $duration <= 0) {
        echo json_encode(['ok' => false, 'error' => 'missing fields']);
        exit;
    }

    $file = __DIR__ . '/data/bench.json';
    if (!file_exists($file)) {
        echo json_encode(['ok' => false, 'error' => 'no bench data']);
        exit;
    }

    $bench = json_decode(file_get_contents($file), true) ?? [];
    $found = false;
    foreach ($bench as &$b) {
        if ($b['id'] === $projectId) {
            $session = ['date' => date('Y-m-d'), 'duration' => $duration];
            if ($note !== '') $session['note'] = $note;
            if (!isset($b['sessions'])) $b['sessions'] = [];
            $b['sessions'][] = $session;
            $b['last_touched'] = date('Y-m-d');
            $found = true;
            break;
        }
    }
    unset($b);

    if (!$found) {
        echo json_encode(['ok' => false, 'error' => 'project not found']);
        exit;
    }

    file_put_contents($file, json_encode($bench, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true]);
    exit;
}

// ── get_shopping_list ─────────────────────────────────────────────────────────
if ($action === 'get_shopping_list') {
    $paints = file_exists(__DIR__ . '/data/paints.json')
        ? json_decode(file_get_contents(__DIR__ . '/data/paints.json'), true) ?? []
        : [];
    $order = ['out' => 0, 'low' => 1, 'wanted' => 2];
    $list = array_values(array_filter($paints, fn($p) => isset($order[$p['stock'] ?? ''])));
    usort($list, function($a, $b) use ($order) {
        $ao = $order[$a['stock']];
        $bo = $order[$b['stock']];
        if ($ao !== $bo) return $ao - $bo;
        $bc = strcmp($a['brand'] ?? '', $b['brand'] ?? '');
        return $bc !== 0 ? $bc : strcmp($a['name'] ?? '', $b['name'] ?? '');
    });
    echo json_encode(['ok' => true, 'paints' => array_map(fn($p) => [
        'brand' => $p['brand'] ?? '',
        'name'  => $p['name']  ?? '',
        'layer' => $p['layer'] ?? '',
        'hex'   => $p['hex']   ?? '',
        'stock' => $p['stock'],
    ], $list)]);
    exit;
}

// ── mark_bought ───────────────────────────────────────────────────────────────
if ($action === 'mark_bought') {
    $brand = trim($_POST['brand'] ?? '');
    $name  = trim($_POST['name']  ?? '');
    $layer = trim($_POST['layer'] ?? '');
    if (!$brand || !$name) {
        echo json_encode(['ok' => false, 'error' => 'missing fields']);
        exit;
    }
    $file = __DIR__ . '/data/paints.json';
    $paints = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
    $found = false;
    foreach ($paints as &$p) {
        if ($p['brand'] === $brand && $p['name'] === $name && ($layer === '' || ($p['layer'] ?? '') === $layer)) {
            unset($p['stock']);
            $found = true;
            break;
        }
    }
    unset($p);
    if ($found) {
        file_put_contents($file, json_encode($paints, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'paint not found']);
    }
    exit;
}

// ── get_schemes ───────────────────────────────────────────────────────────────
if ($action === 'get_schemes') {
    $file   = __DIR__ . '/data/models.json';
    $models = file_exists($file) ? json_decode(file_get_contents($file), true) ?? [] : [];
    usort($models, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    echo json_encode(['ok' => true, 'schemes' => array_map(fn($m) => [
        'id'      => $m['id'],
        'name'    => $m['name'],
        'faction' => $m['faction'] ?? '',
        'count'   => max(1, (int)($m['count'] ?? 1)),
        'image'   => !empty($m['images']) ? $m['images'][0] : null,
    ], $models)]);
    exit;
}

// ── log_model_count ───────────────────────────────────────────────────────────
if ($action === 'log_model_count') {
    $sid  = trim($_POST['scheme_id'] ?? '');
    $cnt  = max(1, (int)($_POST['count'] ?? 1));
    $note = trim($_POST['note'] ?? '');
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'] ?? '') ? $_POST['date'] : date('Y-m-d');

    if (!$sid) {
        echo json_encode(['ok' => false, 'error' => 'missing scheme_id']);
        exit;
    }

    $file = __DIR__ . '/data/models.json';
    if (!file_exists($file)) {
        echo json_encode(['ok' => false, 'error' => 'no models data']);
        exit;
    }

    $models = json_decode(file_get_contents($file), true) ?? [];
    $found    = false;
    $newCount = 1;
    foreach ($models as &$m) {
        if ($m['id'] === $sid) {
            $sess = ['date' => $date, 'count' => $cnt];
            if ($note !== '') $sess['note'] = $note;
            if (!isset($m['sessions'])) $m['sessions'] = [];
            $m['sessions'][] = $sess;
            $existing = max(1, (int)($m['count'] ?? 1));
            $newCount = $existing + $cnt;
            if ($newCount > 1) $m['count'] = $newCount;
            $found = true;
            break;
        }
    }
    unset($m);

    if (!$found) {
        echo json_encode(['ok' => false, 'error' => 'scheme not found']);
        exit;
    }

    file_put_contents($file, json_encode($models, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true, 'new_count' => $newCount]);
    exit;
}

// ── add_shame ─────────────────────────────────────────────────────────────────
if ($action === 'add_shame') {
    $name    = trim($_POST['name']    ?? '');
    $faction = trim($_POST['faction'] ?? '');
    $system  = trim($_POST['system']  ?? 'Other');
    $count   = (int)($_POST['count']  ?? 0);
    $status  = trim($_POST['status']  ?? 'sealed');

    if (!$name) {
        echo json_encode(['ok' => false, 'error' => 'missing name']);
        exit;
    }

    if (!in_array($system, ['40k', '30k / HH', 'AoS', 'Old World', 'Epic', 'Blood Bowl', 'Necromunda', 'Kill Team', 'OPR', 'Other'], true)) {
        $system = 'Other';
    }
    if (!in_array($status, ['sealed', 'opened', 'partial'], true)) {
        $status = 'sealed';
    }

    $file = __DIR__ . '/data/shame.json';
    $shame = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];

    $entry = ['id' => (string)time(), 'name' => $name, 'system' => $system, 'status' => $status];
    if ($faction) $entry['faction'] = $faction;
    if ($count > 0) $entry['count'] = $count;
    $entry['acquired'] = date('Y-m');

    $shame[] = $entry;
    file_put_contents($file, json_encode($shame, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'unknown action']);
