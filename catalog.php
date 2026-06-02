<?php
@ini_set('display_errors', '0');
require_once __DIR__ . '/config.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function loadCatalog(): array {
  $file = __DIR__ . '/inventory/paint_catalog.json';
  if (!file_exists($file)) return [];
  return json_decode(file_get_contents($file), true) ?? [];
}

session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','use_strict_mode'=>true]);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

$authError = '';
if (isset($_POST['password'])) {
  if ($_POST['password'] === ADMIN_PASSWORD) {
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
  } else { sleep(1); $authError = 'Incorrect password.'; }
}
if (isset($_POST['logout'])) { session_destroy(); header('Location: catalog.php'); exit; }
$authed = !empty($_SESSION['admin']);

$flash = '';

if ($authed && isset($_POST['action']) && $_POST['action'] === 'import') {
  $keys = $_POST['keys'] ?? [];
  if ($keys) {
    $paintsFile = __DIR__ . '/data/paints.json';
    $existing = file_exists($paintsFile) ? (json_decode(file_get_contents($paintsFile), true) ?? []) : [];
    $existingKeys = [];
    foreach ($existing as $p) { $existingKeys[$p['brand'].'|'.$p['name'].'|'.($p['layer']??'')] = true; }

    $catalogByKey = [];
    foreach (loadCatalog() as $p) { $catalogByKey[$p['brand'].'|'.$p['name'].'|'.$p['layer']] = $p; }

    $added = 0;
    foreach ($keys as $k) {
      $k = trim($k);
      if (!isset($catalogByKey[$k]) || isset($existingKeys[$k])) continue;
      $p = $catalogByKey[$k];
      $entry = ['brand'=>$p['brand'],'name'=>$p['name'],'color'=>$p['color'],'hue'=>$p['hue'],'layer'=>$p['layer']];
      if (!empty($p['hex'])) $entry['hex'] = $p['hex'];
      $existing[] = $entry;
      $added++;
    }
    if ($added) {
      usort($existing, fn($a,$b) => ($a['brand'].$a['name']) <=> ($b['brand'].$b['name']));
      if (!is_dir(__DIR__.'/data')) mkdir(__DIR__.'/data', 0755, true);
      file_put_contents($paintsFile, json_encode($existing, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
    $flash = $added ? "Imported $added paint".($added!==1?'s':'').' into your inventory.' : 'No new paints added (already owned or not found).';
  }
}

if (!$authed) { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Paint Catalog</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0a0906;color:#c4b49a;font-family:Georgia,serif;display:flex;align-items:center;justify-content:center;min-height:100vh}
.login{background:#130f08;border:1px solid #2a2010;padding:40px;max-width:360px;width:100%;text-align:center}
h1{font-family:Cinzel,serif;color:#c9a227;font-size:1.4rem;margin-bottom:24px}
input[type=password]{width:100%;background:#0e0d0a;border:1px solid #3a2a10;color:#c4b49a;padding:10px 14px;font-size:1rem;margin-bottom:16px}
button{background:#c9a227;color:#0e0d0a;border:none;padding:10px 28px;font-family:Cinzel,serif;font-size:.9rem;cursor:pointer;width:100%}
.err{color:#c05050;margin-top:12px;font-size:.85rem}
</style></head><body>
<div class="login">
  <h1>Paint Catalog</h1>
  <form method="post">
    <input type="password" name="password" placeholder="Admin password" autofocus>
    <button type="submit">Enter</button>
    <?php if($authError): ?><p class="err"><?=e($authError)?></p><?php endif; ?>
  </form>
</div>
</body></html>
<?php exit; }

$catalog    = loadCatalog();
$hexRef   = file_exists(__DIR__.'/inventory/paint_hex.json')
  ? (json_decode(file_get_contents(__DIR__.'/inventory/paint_hex.json'), true) ?? [])
  : [];
$paintsFile = __DIR__ . '/data/paints.json';
$owned = [];
if (file_exists($paintsFile)) {
  foreach (json_decode(file_get_contents($paintsFile), true) ?? [] as $p) {
    $owned[$p['brand'].'|'.$p['name'].'|'.($p['layer']??'')] = true;
  }
}

$brands  = array_unique(array_column($catalog, 'brand'));
$colors  = array_unique(array_column($catalog, 'color'));
$layers  = array_unique(array_column($catalog, 'layer'));
sort($brands); sort($colors); sort($layers);


function swatchFallback(string $color): string {
  $map = ['Red'=>'#8a2020','Orange'=>'#a04010','Yellow'=>'#a08010','Green'=>'#1a5a20','Blue'=>'#1a2a6a','Purple'=>'#4a1a6a','Pink'=>'#8a2060','Brown'=>'#5a3010','White'=>'#e8e6e0','Grey'=>'#6a6a6a','Black'=>'#202020','Metallic'=>'#7a7870','Wash'=>'#2a2018','Shade'=>'#2a2018','Contrast'=>'#4a3828','Ink'=>'#2a2848','Primer'=>'#8a8888','Texture'=>'#6a5a40'];
  return $map[$color] ?? '#4a4030';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Paint Catalog — Waaagh! Paint</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0a0906;color:#c4b49a;font-family:Georgia,serif;font-size:.9rem}
a{color:#c9a227;text-decoration:none}

/* top bar */
.topbar{position:sticky;top:0;z-index:100;background:#0e0d0a;border-bottom:1px solid #2a2010;padding:0 20px;display:flex;align-items:center;gap:16px;height:52px}
.topbar-title{font-family:Cinzel,serif;color:#c9a227;font-size:1rem;font-weight:600;white-space:nowrap}
.topbar-back{font-size:.8rem;color:#7a6a4a}
.topbar-back:hover{color:#c9a227}
.topbar-spacer{flex:1}
.sel-count{font-family:Cinzel,serif;font-size:.8rem;color:#c9a227}
.btn-import{background:#c9a227;color:#0e0d0a;border:none;padding:7px 18px;font-family:Cinzel,serif;font-size:.8rem;cursor:pointer;font-weight:600}
.btn-import:disabled{background:#3a2a10;color:#5a4a28;cursor:default}
.btn-logout{background:none;border:1px solid #3a2a10;color:#7a6a4a;padding:5px 12px;font-size:.75rem;cursor:pointer}
.btn-logout:hover{border-color:#c9a227;color:#c9a227}

/* flash */
.flash{background:#1a3a18;border:1px solid #2a5a20;color:#80c878;padding:10px 20px;font-size:.85rem}

/* filter bar */
.filters{background:#100e08;border-bottom:1px solid #1e1a10;padding:12px 20px;display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.filter-lbl{font-family:Cinzel,serif;font-size:.7rem;color:#5a4a28;letter-spacing:.05em;margin-right:2px}
select{background:#0a0906;border:1px solid #2a2010;color:#c4b49a;padding:4px 8px;font-size:.8rem}
.search-inp{background:#0a0906;border:1px solid #2a2010;color:#c4b49a;padding:4px 10px;font-size:.8rem;width:200px}
.search-inp::placeholder{color:#3a2a10}
.filter-sep{color:#2a2010}
.owned-toggle{display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.8rem;color:#7a6a4a;user-select:none}
.owned-toggle input{accent-color:#c9a227}
.btn-selall,.btn-selnone{background:none;border:1px solid #2a2010;color:#7a6a4a;padding:3px 10px;font-size:.75rem;cursor:pointer}
.btn-selall:hover,.btn-selnone:hover{border-color:#c9a227;color:#c9a227}

/* table */
.table-wrap{overflow-x:auto;padding:0 20px 80px}
table{width:100%;border-collapse:collapse;min-width:600px}
th{font-family:Cinzel,serif;font-size:.72rem;color:#5a4a28;letter-spacing:.06em;padding:8px 10px;border-bottom:1px solid #1e1a10;text-align:left;background:#0a0906}
th.sortable{cursor:pointer;user-select:none}
th.sortable:hover{color:#c9a227}
td{padding:6px 10px;border-bottom:1px solid #150f06;vertical-align:middle}
tr.owned td{opacity:.45}
tr.hidden-row{display:none}
tr:hover td{background:#120e08}
td.cb-cell{width:30px;padding:6px 6px 6px 10px}
input[type=checkbox]{accent-color:#c9a227;width:15px;height:15px;cursor:pointer}
tr.owned input[type=checkbox]{cursor:default}

.swatch{display:inline-block;width:18px;height:18px;border-radius:50%;border:1px solid rgba(255,255,255,.1);vertical-align:middle;flex-shrink:0}
.paint-name{font-weight:bold;color:#c4b49a}
.brand-badge{font-size:.7rem;background:#1a1408;border:1px solid #2a2010;color:#7a6a4a;padding:1px 6px;border-radius:2px;white-space:nowrap}
.layer-badge{font-size:.7rem;background:#1a1408;border:1px solid #2a2010;color:#7a6a4a;padding:1px 6px;border-radius:2px;white-space:nowrap}
.owned-badge{font-size:.7rem;background:#1a3010;border:1px solid #2a4818;color:#60a848;padding:1px 6px;border-radius:2px}
.hex-val{font-size:.75rem;font-family:monospace;color:#5a4a28}
.no-hex{font-size:.75rem;color:#2a2010;font-style:italic}

.count-line{padding:6px 20px 2px;font-size:.75rem;color:#5a4a28}
</style>
</head>
<body>

<div class="topbar">
  <span class="topbar-title">Paint Catalog</span>
  <a class="topbar-back" href="admin.php">← Admin</a>
  <span class="topbar-spacer"></span>
  <span class="sel-count" id="sel-count">0 selected</span>
  <form method="post" id="import-form" style="display:inline">
    <input type="hidden" name="action" value="import">
    <div id="key-inputs"></div>
    <button class="btn-import" id="btn-import" type="submit" disabled>Import Selected</button>
  </form>
  <form method="post" style="display:inline">
    <button class="btn-logout" name="logout" value="1">Log out</button>
  </form>
</div>

<?php if ($flash): ?>
<div class="flash"><?=e($flash)?></div>
<?php endif; ?>

<div class="filters">
  <span class="filter-lbl">Brand</span>
  <select id="f-brand">
    <option value="">All Brands</option>
    <?php foreach ($brands as $b): ?><option value="<?=e($b)?>"><?=e($b)?></option><?php endforeach; ?>
  </select>
  <span class="filter-sep">·</span>
  <span class="filter-lbl">Colour</span>
  <select id="f-color">
    <option value="">All Colours</option>
    <?php foreach ($colors as $c): ?><option value="<?=e($c)?>"><?=e($c)?></option><?php endforeach; ?>
  </select>
  <span class="filter-sep">·</span>
  <span class="filter-lbl">Layer</span>
  <select id="f-layer">
    <option value="">All Layers</option>
    <?php foreach ($layers as $l): ?><option value="<?=e($l)?>"><?=e($l)?></option><?php endforeach; ?>
  </select>
  <span class="filter-sep">·</span>
  <input class="search-inp" id="f-search" type="search" placeholder="Search name or hue…">
  <span class="filter-sep">·</span>
  <label class="owned-toggle"><input type="checkbox" id="f-hide-owned"> Hide owned</label>
  <span class="filter-sep">·</span>
  <button class="btn-selall" id="btn-selall">Select visible</button>
  <button class="btn-selnone" id="btn-selnone">Deselect all</button>
</div>

<div class="count-line" id="count-line"></div>

<?php if (empty($catalog)): ?>
<div style="padding:40px 20px;text-align:center;color:#5a4a28">
  <p style="font-family:Cinzel,serif;color:#c9a227;margin-bottom:12px">Catalog not found</p>
  <p><code>inventory/paint_catalog.json</code> is missing &mdash; make sure it was deployed with the app.</p>
</div>
<?php endif; ?>

<div class="table-wrap">
<table id="cat-table">
<thead>
<tr>
  <th style="width:30px"></th>
  <th>Swatch</th>
  <th class="sortable" data-col="brand">Brand</th>
  <th class="sortable" data-col="name">Name</th>
  <th class="sortable" data-col="color">Colour</th>
  <th>Hue</th>
  <th class="sortable" data-col="layer">Layer</th>
  <th>Hex</th>
  <th>Status</th>
</tr>
</thead>
<tbody id="cat-body">
<?php foreach ($catalog as $p):
  $key   = $p['brand'].'|'.$p['name'].'|'.$p['layer'];
  $hex   = $hexRef[$key] ?? null;
  $swatch = $hex ?: swatchFallback($p['color']);
  $isOwned = isset($owned[$key]);
?>
<tr class="<?=$isOwned?'owned':''?>" data-key="<?=e($key)?>" data-brand="<?=e($p['brand'])?>" data-color="<?=e($p['color'])?>" data-layer="<?=e($p['layer'])?>" data-search="<?=e(strtolower($p['name'].' '.$p['hue']))?>">
  <td class="cb-cell"><input type="checkbox" class="row-cb" <?=$isOwned?'disabled checked':''?> data-key="<?=e($key)?>"></td>
  <td><span class="swatch" style="background:<?=e($swatch)?>"></span></td>
  <td><span class="brand-badge"><?=e($p['brand'])?></span></td>
  <td class="paint-name"><?=e($p['name'])?></td>
  <td><?=e($p['color'])?></td>
  <td style="color:#7a6a4a;font-size:.8rem"><?=e($p['hue'])?></td>
  <td><span class="layer-badge"><?=e($p['layer'])?></span></td>
  <td><?php if($hex): ?><span class="hex-val"><?=e($hex)?></span><?php else: ?><span class="no-hex">—</span><?php endif; ?></td>
  <td><?=$isOwned?'<span class="owned-badge">Owned</span>':''?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<script>
(function() {
  const rows    = Array.from(document.querySelectorAll('#cat-body tr'));
  const fBrand  = document.getElementById('f-brand');
  const fColor  = document.getElementById('f-color');
  const fLayer  = document.getElementById('f-layer');
  const fSearch = document.getElementById('f-search');
  const fHide   = document.getElementById('f-hide-owned');
  const countEl = document.getElementById('count-line');
  const selEl   = document.getElementById('sel-count');
  const btnImp  = document.getElementById('btn-import');
  const keyDiv  = document.getElementById('key-inputs');

  function applyFilters() {
    const br = fBrand.value.toLowerCase();
    const co = fColor.value.toLowerCase();
    const la = fLayer.value.toLowerCase();
    const q  = fSearch.value.toLowerCase().trim();
    const hideOwned = fHide.checked;
    let vis = 0, tot = rows.length;
    rows.forEach(tr => {
      const owned = tr.classList.contains('owned');
      const show = (!br || tr.dataset.brand.toLowerCase() === br)
                && (!co || tr.dataset.color.toLowerCase() === co)
                && (!la || tr.dataset.layer.toLowerCase() === la)
                && (!q  || tr.dataset.search.includes(q))
                && !(hideOwned && owned);
      tr.classList.toggle('hidden-row', !show);
      if (show) vis++;
    });
    countEl.textContent = vis + ' of ' + tot + ' paints';
    updateSel();
  }

  function updateSel() {
    const checked = document.querySelectorAll('.row-cb:not(:disabled):checked');
    const n = checked.length;
    selEl.textContent = n + ' selected';
    btnImp.disabled = n === 0;
    keyDiv.innerHTML = '';
    checked.forEach(cb => {
      const inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = 'keys[]'; inp.value = cb.dataset.key;
      keyDiv.appendChild(inp);
    });
  }

  document.getElementById('btn-selall').addEventListener('click', () => {
    rows.forEach(tr => {
      if (!tr.classList.contains('hidden-row')) {
        const cb = tr.querySelector('.row-cb:not(:disabled)');
        if (cb) cb.checked = true;
      }
    });
    updateSel();
  });
  document.getElementById('btn-selnone').addEventListener('click', () => {
    document.querySelectorAll('.row-cb:not(:disabled)').forEach(cb => cb.checked = false);
    updateSel();
  });

  document.querySelectorAll('.row-cb').forEach(cb => cb.addEventListener('change', updateSel));
  [fBrand, fColor, fLayer, fHide].forEach(el => el.addEventListener('change', applyFilters));
  fSearch.addEventListener('input', applyFilters);

  // Column sort
  let sortCol = '', sortDir = 1;
  document.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (sortCol === col) sortDir *= -1; else { sortCol = col; sortDir = 1; }
      document.querySelectorAll('th.sortable').forEach(t => t.textContent = t.textContent.replace(/ [▲▼]$/,''));
      th.textContent += sortDir === 1 ? ' ▲' : ' ▼';
      const tbody = document.getElementById('cat-body');
      rows.sort((a, b) => {
        const va = a.dataset[col] || a.querySelector('td:nth-child(' + (col==='brand'?3:col==='name'?4:col==='color'?5:7) + ')')?.textContent || '';
        const vb = b.dataset[col] || b.querySelector('td:nth-child(' + (col==='brand'?3:col==='name'?4:col==='color'?5:7) + ')')?.textContent || '';
        return va.localeCompare(vb) * sortDir;
      });
      rows.forEach(r => tbody.appendChild(r));
    });
  });

  applyFilters();
})();
</script>
</body>
</html>
