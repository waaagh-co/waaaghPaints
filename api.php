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

echo json_encode(['ok' => false, 'error' => 'unknown action']);
