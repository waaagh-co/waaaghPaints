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

echo json_encode(['ok' => false, 'error' => 'unknown action']);
