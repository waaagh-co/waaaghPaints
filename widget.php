<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$bench = file_exists(__DIR__.'/data/bench.json')
  ? json_decode(file_get_contents(__DIR__.'/data/bench.json'), true) ?? []
  : [];
$wkAgo = date('Y-m-d', strtotime('-6 days'));
$mins  = 0;
foreach ($bench as $b) {
  foreach ($b['sessions'] ?? [] as $s) {
    if (($s['date'] ?? '') >= $wkAgo && isset($s['duration'])) {
      $mins += (int)$s['duration'];
    }
  }
}
echo json_encode(['minutes' => $mins, 'updated' => date('H:i')]);
