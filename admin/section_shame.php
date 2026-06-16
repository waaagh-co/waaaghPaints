      <h2 id="section-shame" class="collapsible" style="margin-top:40px">Pile of Shame
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($shameData) ?> box<?= count($shameData) !== 1 ? 'es' : '' ?></span>
      </h2>
      <?php if (!$hasShame): ?>
        <form method="post">
          <input type="hidden" name="action" value="create_shame_file">
          <button type="submit" class="btn btn-sm">Start Pile of Shame</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openShameAdd()">+ Add Box</button>
        </div>

        <div class="paint-form-wrap" id="shameFormWrap" style="display:none">
          <div class="paint-form-title" id="shameFormTitle">Add Box</div>
          <form method="post" id="shameForm">
            <input type="hidden" name="action" value="add_shame" id="shameAction">
            <input type="hidden" name="sh_id" id="shId" value="">
            <div class="form-grid">
              <div>
                <label for="sh_name">Box Name *</label>
                <input type="text" id="sh_name" name="sh_name" required placeholder="e.g. Death Guard Plague Marines">
              </div>
              <div>
                <label for="sh_system">System</label>
                <select id="sh_system" name="sh_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="40k">Warhammer 40,000</option>
                  <option value="30k / HH">Horus Heresy / 30k</option>
                  <option value="AoS">Age of Sigmar</option>
                  <option value="Old World">The Old World</option>
                  <option value="Kill Team">Kill Team</option>
                  <option value="Blood Bowl">Blood Bowl</option>
                  <option value="Necromunda">Necromunda</option>
                  <option value="Epic">Epic Scale</option>
                  <option value="OPR">One Page Rules</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label for="sh_faction">Faction</label>
                <input type="text" id="sh_faction" name="sh_faction" placeholder="e.g. Death Guard">
              </div>
              <div>
                <label for="sh_count">Model Count</label>
                <input type="number" id="sh_count" name="sh_count" min="1" placeholder="e.g. 10">
              </div>
              <div>
                <label for="sh_status">Status</label>
                <select id="sh_status" name="sh_status" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="sealed">Sealed</option>
                  <option value="opened">Opened</option>
                  <option value="partial">Partially Built</option>
                </select>
              </div>
              <div>
                <label for="sh_acquired">Date Acquired (YYYY-MM)</label>
                <input type="text" id="sh_acquired" name="sh_acquired" placeholder="e.g. 2024-03" maxlength="7">
              </div>
              <div class="form-full">
                <label for="sh_notes">Notes</label>
                <textarea id="sh_notes" name="sh_notes" rows="3" placeholder="e.g. Picked up at Adepticon" style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="shameSubmitBtn">Add Box</button>
              <button type="button" class="btn btn-sm" id="shameCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($shameData): ?>
          <div class="model-list">
            <?php
            $shameSystems = ['40k' => ['#8a2020', '#f08080'], '30k / HH' => ['#4a3a10', '#d4a840'], 'AoS' => ['#1a2a5a', '#7090d8'], 'Kill Team' => ['#0a3a3a', '#70c8d8'], 'Blood Bowl' => ['#2a1a4a', '#9a70d8'], 'Necromunda' => ['#1a3a3a', '#70c8c8'], 'Epic' => ['#1a3a1a', '#70b870'], 'OPR' => ['#1a2a3a', '#708090'], 'Old World' => ['#3a2a0a', '#d4a040'], 'Other' => ['#2a2a2a', '#909090']];
            $displayShame = $shameData; usort($displayShame, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? '')); foreach ($displayShame as $sh):
              $shAcq     = $sh['acquired'] ?? '';
              $shPromote = $sh['promoted_to'] ?? '';
              $sysBg     = $shameSystems[$sh['system'] ?? ''] ?? ['#2a2a2a', '#909090'];
            ?>
              <div class="model-row" id="shame-row-<?= e($sh['id']) ?>">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:<?= $sysBg[0] ?>;color:<?= $sysBg[1] ?>;font-family:'Cinzel',serif;letter-spacing:.05em"><?= e($sh['system'] ?? 'Other') ?></span>
                    <?= e($sh['name']) ?>
                    <?php if ($shPromote): ?>
                      <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:#1a3a1a;color:#7ad678;font-family:'Cinzel',serif">Promoted &rarr; <?= $shPromote === 'planned' ? 'Planned' : 'Bench' ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="model-row-meta">
                    <?php if (!empty($sh['faction'])): ?><?= e($sh['faction']) ?> &middot; <?php endif; ?>
                  <?php
                  $statusLabel = ['sealed' => 'Sealed', 'opened' => 'Opened', 'partial' => 'Partial'][$sh['status'] ?? 'sealed'] ?? 'Sealed';
                  $statusBg    = ['sealed' => '#1a1a1a', 'opened' => '#2a1808', 'partial' => '#1e1808'][$sh['status'] ?? 'sealed'];
                  $statusFg    = ['sealed' => '#7a7a7a', 'opened' => '#c87a30', 'partial' => '#c9a227'][$sh['status'] ?? 'sealed'];
                  ?>
                  <span style="font-size:10px;padding:1px 6px;border-radius:3px;background:<?= $statusBg ?>;color:<?= $statusFg ?>"><?= $statusLabel ?></span>
                  <?php if (!empty($sh['count'])): ?> &middot; <?= (int)$sh['count'] ?> models<?php endif; ?>
                    <?php if ($shAcq): ?> &middot; <span style="color:#c9a227;font-family:'Cinzel',serif;font-size:11px"><?= e($shAcq) ?></span><?php endif; ?>
                  </div>
                  <?php if (!empty($sh['notes'])): ?><div style="font-size:11px;color:#5a4a28;margin-top:3px"><?= e(mb_substr($sh['notes'], 0, 100)) ?><?= mb_strlen($sh['notes']) > 100 ? '…' : '' ?></div><?php endif; ?>
                </div>
                <div class="model-row-actions" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                  <?php if (!$shPromote): ?>
                    <button type="button" class="btn btn-sm" onclick="promoteShame('<?= e($sh['id']) ?>','planned')" style="font-size:10px">&rarr; Planned</button>
                    <button type="button" class="btn btn-sm" onclick="promoteShame('<?= e($sh['id']) ?>','bench')" style="font-size:10px">&rarr; Bench</button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-sm" onclick="openShameEdit(this)"
                    data-id="<?= e($sh['id']) ?>"
                    data-name="<?= e($sh['name']) ?>"
                    data-system="<?= e($sh['system'] ?? '40k') ?>"
                    data-faction="<?= e($sh['faction'] ?? '') ?>"
                    data-count="<?= (int)($sh['count'] ?? 0) ?>"
                    data-status="<?= e($sh['status'] ?? 'sealed') ?>"
                    data-acquired="<?= e($shAcq) ?>"
                    data-notes="<?= e($sh['notes'] ?? '') ?>">Edit</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Remove this box from the pile?')">
                    <input type="hidden" name="action" value="delete_shame">
                    <input type="hidden" name="sh_id" value="<?= e($sh['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
