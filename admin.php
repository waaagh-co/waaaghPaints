<?php
require_once __DIR__ . '/admin/bootstrap.php';

require_once __DIR__ . '/admin/actions_paints.php';
require_once __DIR__ . '/admin/actions_library.php';
require_once __DIR__ . '/admin/actions_collections.php';
require_once __DIR__ . '/admin/actions_pipeline.php';
require_once __DIR__ . '/admin/actions_gallery.php';
require_once __DIR__ . '/admin/actions_game.php';
require_once __DIR__ . '/admin/actions_wishlist.php';

$editModel = null;
if ($authed && isset($_GET['edit'])) {
  $reqEditId = $_GET['edit'];
  foreach ($models as $m) {
    if (($m['id'] ?? '') === $reqEditId) {
      $editModel = $m;
      break;
    }
  }
}

$editForce = null;
if ($authed && isset($_GET['edit_force'])) {
  $reqFid = $_GET['edit_force'];
  foreach ($forcesData as $f) {
    if (($f['id'] ?? '') === $reqFid) {
      $editForce = $f;
      break;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
<?php if (defined('GA4_ID') && GA4_ID !== ''): ?>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars(GA4_ID) ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= htmlspecialchars(GA4_ID) ?>');
  </script>
<?php endif; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Waaagh! Paint</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css?v=13">
</head>

<body<?php if ($authed && $editModel): ?> data-open-section="section-gallery" <?php elseif ($authed && $editForce): ?> data-open-section="section-forces" <?php endif; ?>>

  <?php if (!$authed): ?>
  <header>
    <a href="index.php"><img src="img/logo_sm.png" alt="Waaagh! Paint" class="logo"></a>
    <p>Gallery Admin</p>
  </header>
  <?php endif; ?>

  <?php if ($authed): ?>
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="as-header">
      <a href="index.php"><img src="img/logo_sm.png" alt="Waaagh! Paint" class="as-logo"></a>
      <div class="as-badge">Admin Panel</div>
      <button class="as-collapse-btn" id="as-collapse-btn" aria-label="Collapse sidebar">&#8249;</button>
    </div>
    <nav class="as-nav">
      <div class="as-group">
        <div class="as-group-label">The Work</div>
        <a href="#section-bench" class="as-link">On the Bench</a>
        <a href="#section-gallery" class="as-link">Add Scheme</a>
        <a href="#section-entries" class="as-link">Edit Schemes</a>
        <a href="#section-planned" class="as-link">Planned</a>
        <?php if ($hasShame): ?><a href="#section-shame" class="as-link">Pile of Shame</a><?php endif; ?>
        <?php if ($hasRescues): ?><a href="#section-rescues" class="as-link">Rescue Tracker</a><?php endif; ?>
        <?php if ($hasForces): ?><a href="#section-forces" class="as-link">Forces</a><?php endif; ?>
        <?php if ($hasBattles): ?><a href="#section-battles" class="as-link">Battle Honours</a><?php endif; ?>
        <?php if ($hasRecipes): ?><a href="#section-recipes" class="as-link">Recipes</a><?php endif; ?>
        <?php if ($hasJournal): ?><a href="#section-journal" class="as-link">Scrap Notes</a><?php endif; ?>
      </div>
      <div class="as-group">
        <div class="as-group-label">Collection</div>
        <a href="#section-inventory" class="as-link">Paint Inventory</a>
        <?php if ($hasBrushes): ?><a href="#section-brushes" class="as-link">Brushes</a><?php endif; ?>
        <?php if ($hasSupplies): ?><a href="#section-supplies" class="as-link">Supplies</a><?php endif; ?>
        <?php if ($hasWishlist): ?><a href="#section-wishlist" class="as-link">Wishlist</a><?php endif; ?>
        <?php if ($hasBooks): ?><a href="#section-books" class="as-link">Codices</a><?php endif; ?>
      </div>
      <div class="as-group">
        <div class="as-group-label">Tools</div>
        <a href="#section-stats" class="as-link">Hobby Stats</a>
        <a href="#section-checker" class="as-link">Paint Checker</a>
        <a href="#section-conversions" class="as-link">Equivalency</a>
        <a href="catalog.php" target="_blank" class="as-link">Paint Catalog &#8599;</a>
        <a href="guide.php" target="_blank" class="as-link">User Guide &#8599;</a>
      </div>
      <div class="asnav-fade" id="asnav-fade"></div>
    </nav>
    <div class="as-footer">
      <a href="index.php" class="as-back-link">&#8592; Back to site</a>
      <form method="post" style="margin:0"><input type="hidden" name="action" value="export_backup"><button type="submit" class="btn btn-sm" title="Download a JSON backup of every data file">Export Backup</button></form>
      <button class="btn btn-sm" onclick="document.getElementById('import-backup-panel').style.display=document.getElementById('import-backup-panel').style.display==='none'?'flex':'none'" title="Restore from a previously exported backup JSON">Import Backup</button>
      <form method="post" style="margin:0"><button name="logout" value="1" class="btn btn-sm">Log out</button></form>
    </div>
  <div id="import-backup-panel" style="display:none;flex-direction:column;gap:6px;padding:10px 12px;border-top:1px solid #2a1e08;background:#0a0806;">
    <div style="font-family:var(--font-heading);font-size:.58rem;letter-spacing:.12em;color:#8a3020;text-transform:uppercase;">Restore from Backup</div>
    <div style="font-size:.62rem;color:#5a4828;">This overwrites existing data files. Images are not included in backups.</div>
    <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:6px;">
      <input type="hidden" name="action" value="import_backup">
      <input type="file" name="backup_file" accept=".json" required style="font-size:.65rem;color:#c4b49a;">
      <button type="submit" class="btn btn-sm" style="border-color:#8a2020;color:#c47a6a;" onclick="return confirm('This will overwrite your current data with the backup. Continue?')">Restore</button>
    </form>
  </div>
  </aside>
  <button class="admin-sidebar-toggle" id="admin-sidebar-toggle" aria-label="Toggle navigation">&#9776;</button>
  <div class="admin-main">
  <?php endif; ?>

  <div class="admin-wrap">

    <?php if (!$authed): ?>
      <div class="auth-box">
        <h2>Admin Access</h2>
        <?php if ($authError): ?><div class="alert alert-error"><?= e($authError) ?></div><?php endif; ?>
        <form method="post">
          <label for="pw">Password</label>
          <input type="password" id="pw" name="password" autofocus style="margin-bottom:14px">
          <button type="submit" class="btn" style="width:100%">Enter</button>
        </form>
      </div>

    <?php else: ?>
      <?php if ($successMsg): ?><div class="alert alert-success"><?= e($successMsg) ?></div><?php endif; ?>
      <?php if ($formError):   ?><div class="alert alert-error"><?= e($formError) ?></div><?php endif; ?>

      <?php require_once __DIR__ . '/admin/section_stats.php'; ?>
      <?php require_once __DIR__ . '/admin/section_gallery.php'; ?>
      <?php require_once __DIR__ . '/admin/section_inventory.php'; ?>
      <?php require_once __DIR__ . '/admin/section_brushes.php'; ?>
      <?php require_once __DIR__ . '/admin/section_supplies.php'; ?>
      <?php require_once __DIR__ . '/admin/section_checker.php'; ?>
      <?php require_once __DIR__ . '/admin/section_conversions.php'; ?>
      <?php require_once __DIR__ . '/admin/section_rescues.php'; ?>
      <?php require_once __DIR__ . '/admin/section_shame.php'; ?>
      <?php require_once __DIR__ . '/admin/section_planned.php'; ?>
      <?php require_once __DIR__ . '/admin/section_bench.php'; ?>
      <?php require_once __DIR__ . '/admin/section_forces.php'; ?>
      <?php require_once __DIR__ . '/admin/section_recipes.php'; ?>
      <?php require_once __DIR__ . '/admin/section_battles.php'; ?>
      <?php require_once __DIR__ . '/admin/section_wishlist.php'; ?>
      <?php require_once __DIR__ . '/admin/section_books.php'; ?>
      <?php require_once __DIR__ . '/admin/section_journal.php'; ?>

    <?php endif; ?>
  </div><!-- /admin-wrap -->
  <?php if ($authed): ?></div><!-- /admin-main --><?php endif; ?>

  <?php if ($authed): ?>
    <?php
    $jnMentionables = [];
    foreach ($models as $m) {
        $jnMentionables[] = ['type' => 'scheme', 'id' => $m['id'], 'label' => $m['name']];
    }
    if ($hasRecipes) {
        foreach ($recipesData as $r) {
            $jnMentionables[] = ['type' => 'recipe', 'id' => $r['id'], 'label' => $r['name']];
        }
    }
    if ($hasBench) {
        foreach ($benchData as $b) {
            $jnMentionables[] = ['type' => 'bench', 'id' => $b['id'], 'label' => $b['name']];
        }
    }
    ?>
    <script>
      const ADMIN_PHP = '<?= ADMIN_FILENAME ?>';
      const TODAY_DATE = '<?= date('Y-m-d') ?>';
      const BENCH_MAX_IMAGES_JS = <?= BENCH_MAX_IMAGES ?>;
      const JOURNAL_COUNT = <?= count($journalData ?? []) ?>;
      const ALL_PAINTS = <?= json_encode(array_map(fn($p) => $p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? ''), $paints)) ?>;
      const INVENTORY_DATA = <?= json_encode(array_map(fn($p) => [$p['brand'], $p['name'], $p['color'] ?? '', $p['layer'] ?? '', $p['stock'] ?? ''], $paints), JSON_UNESCAPED_UNICODE) ?>;
      const MASTER_PAINTS = <?= $masterPaintsJson ?>;
      const CONVERSIONS = <?= $conversionsJson ?>;
      const PLANNED_DATA = <?= json_encode(array_map(fn($p) => ['name' => $p['name'], 'colors' => $p['colors'] ?? []], $planned), JSON_UNESCAPED_UNICODE) ?>;
      const PRE_SELECTED = <?= json_encode($editModel ? ($editModel['colors'] ?? []) : []) ?>;
      const JN_MENTIONABLES_DATA = <?= json_encode($jnMentionables) ?>;
    </script>
    <script src="js/admin.js?v=6"></script>
  <?php endif; ?>

  <button id="back-to-top" title="Back to top">↑</button>

  <?php if ($authed && $hasBench): ?>
    <div class="adm-shop-overlay" id="sess-modal-overlay" onclick="if(event.target===this)closeSessionModal()">
      <div class="adm-shop-sheet">
        <div class="adm-shop-title">Log Session</div>
        <div class="adm-shop-subtitle" id="sess-modal-project"></div>
        <div style="display:flex;flex-direction:column;gap:10px;margin:14px 0 4px">
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Date *
            <input type="date" id="sess-date" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px">
          </label>
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Duration (minutes, optional)
            <input type="number" id="sess-duration" min="1" placeholder="e.g. 90" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px">
          </label>
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Notes (optional)
            <textarea id="sess-note" rows="3" placeholder="What did you work on?" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px;resize:vertical"></textarea>
          </label>
        </div>
        <div class="adm-shop-actions">
          <button class="btn btn-sm" onclick="submitSessionLog()">Log Session</button>
          <button class="btn btn-sm" onclick="closeSessionModal()" style="background:#1a1a1a">Cancel</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($authed): ?>
    <div class="adm-shop-overlay" id="gallery-sess-overlay" onclick="if(event.target===this)closeGallerySessionModal()">
      <div class="adm-shop-sheet">
        <div class="adm-shop-title" id="gallery-sess-title">Log Painted Models</div>
        <div class="adm-shop-subtitle" id="gallery-sess-project"></div>
        <input type="hidden" id="gallery-sess-idx" value="-1">
        <div style="display:flex;flex-direction:column;gap:10px;margin:14px 0 4px">
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Date *
            <input type="date" id="gallery-sess-date" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px">
          </label>
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Models painted *
            <input type="number" id="gallery-sess-count" min="1" placeholder="e.g. 3" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px">
          </label>
          <label style="font-family:'Cinzel',serif;font-size:11px;color:#8a7a5a;letter-spacing:.05em">
            Notes (optional)
            <textarea id="gallery-sess-note" rows="3" placeholder="What did you finish?" style="display:block;margin-top:4px;width:100%;background:#1a1508;border:1px solid #3a2a10;color:#c4b49a;padding:6px 8px;border-radius:3px;font-size:13px;resize:vertical"></textarea>
          </label>
        </div>
        <div class="adm-shop-actions">
          <button class="btn btn-sm" onclick="submitGallerySessionLog()">Log</button>
          <button class="btn btn-sm" onclick="closeGallerySessionModal()" style="background:#1a1a1a">Cancel</button>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($authed && ($missingPlanned ?? 0) > 0): ?>
    <div class="adm-shop-overlay" id="adm-shop-overlay" onclick="if(event.target===this)closeAdmShopModal()">
      <div class="adm-shop-sheet">
        <div class="adm-shop-title">Missing Paints - Planned Schemes</div>
        <div class="adm-shop-subtitle" id="adm-shop-subtitle"></div>
        <div id="adm-shop-content"></div>
        <div class="adm-shop-actions">
          <button class="btn btn-sm" onclick="closeAdmShopModal()">Close</button>
        </div>
      </div>
    </div>
  <?php endif; ?>
  </body>

</html>
