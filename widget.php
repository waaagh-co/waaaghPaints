<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$bench  = file_exists(__DIR__.'/data/bench.json')  ? json_decode(file_get_contents(__DIR__.'/data/bench.json'),  true) ?? [] : [];
$models = file_exists(__DIR__.'/data/models.json') ? json_decode(file_get_contents(__DIR__.'/data/models.json'), true) ?? [] : [];
$shame  = file_exists(__DIR__.'/data/shame.json')  ? json_decode(file_get_contents(__DIR__.'/data/shame.json'),  true) ?? [] : [];
$goals  = file_exists(__DIR__.'/data/goals.json')  ? json_decode(file_get_contents(__DIR__.'/data/goals.json'),  true) ?? [] : [];

// Minutes this week (rolling 7 days)
$wkAgo = date('Y-m-d', strtotime('-6 days'));
$mins  = 0;
foreach ($bench as $b) {
  foreach ($b['sessions'] ?? [] as $s) {
    if (($s['date'] ?? '') >= $wkAgo && isset($s['duration'])) $mins += (int)$s['duration'];
  }
}

// Hobby streak - consecutive days with bench or model sessions
$activityDates = [];
foreach ($bench as $b) {
  foreach ($b['sessions'] ?? [] as $s) { if (!empty($s['date'])) $activityDates[$s['date']] = true; }
  if (!empty($b['last_touched'])) $activityDates[$b['last_touched']] = true;
}
foreach ($models as $m) {
  foreach ($m['sessions'] ?? [] as $s) { if (!empty($s['date'])) $activityDates[$s['date']] = true; }
  if (!empty($m['date'])) $activityDates[$m['date']] = true;
}
$streak = 0;
$checkDate = date('Y-m-d');
while (isset($activityDates[$checkDate])) {
  $streak++;
  $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
}

// Minis painted this year (always computed, regardless of goal)
$curYear     = (int)date('Y');
$minisPainted = 0;
foreach ($models as $m) {
  foreach ($m['sessions'] ?? [] as $s) {
    if (substr($s['date'] ?? '', 0, 4) === (string)$curYear) $minisPainted += max(1, (int)($s['count'] ?? 1));
  }
}
$yearGoal = $goals[(string)$curYear] ?? null;
if ($yearGoal) $minisPainted += is_array($yearGoal) ? (int)($yearGoal['seed'] ?? 0) : 0;

// Annual goal progress (for any other consumers)
$goalTarget  = 0;
$goalCurrent = $minisPainted;
$goalPct     = 0;
if ($yearGoal) {
  $goalTarget = is_array($yearGoal) ? (int)($yearGoal['target'] ?? 0) : (int)$yearGoal;
  $goalPct    = $goalTarget > 0 ? min(100, (int)round($minisPainted * 100 / $goalTarget)) : 0;
}

// Bench snapshot
$activeBench = array_values(array_filter($bench, fn($b) => ($b['stage'] ?? '') !== 'done' && empty($b['promoted_to'])));
$benchCount  = count($activeBench);
usort($activeBench, fn($a, $b) => strcmp($b['last_touched'] ?? '', $a['last_touched'] ?? ''));
$latest     = $activeBench[0] ?? null;
$benchName  = $latest ? (string)($latest['name']  ?? '') : '';
$benchStage = $latest ? (string)($latest['stage'] ?? '') : '';

// Pile of Shame
$activeShame = array_filter($shame, fn($s) => empty($s['promoted_to']));
$shameBoxes  = count($activeShame);
$shameUnits  = array_sum(array_map(fn($s) => max(1, (int)($s['count'] ?? 1)), $activeShame));

echo json_encode([
  'minutes'       => $mins,
  'updated'       => date('H:i'),
  'streak'        => $streak,
  'minis_year'    => $minisPainted,
  'goal_current'  => $goalCurrent,
  'goal_target'   => $goalTarget,
  'goal_pct'      => $goalPct,
  'bench_count'   => $benchCount,
  'bench_name'    => $benchName,
  'bench_stage'   => ucfirst($benchStage),
  'shame_boxes'   => $shameBoxes,
  'shame_units'   => $shameUnits,
]);
