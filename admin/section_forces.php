      <h2 id="section-forces" class="collapsible" style="margin-top:40px">Forces &amp; Rosters
        <?php if ($hasForces): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($forcesData) ?> force<?= count($forcesData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>
      <div>
        <?php if (!$hasForces): ?>
          <form method="post" style="margin-top:12px">
            <input type="hidden" name="action" value="create_forces_file">
            <button class="btn">Start Forces &amp; Rosters</button>
          </form>
          <p style="color:#5a4a28;font-size:13px;margin-top:8px">Group your painted schemes into named rosters for Kill Team, OPR, Blood Bowl, Necromunda, and other game systems.</p>
        <?php else: ?>

          <!-- Add / Edit form -->
          <div style="margin-bottom:24px">
            <h3 style="font-family:'Cinzel',serif;font-size:14px;color:#9a8a6a;margin:0 0 12px"><?= $editForce ? 'Edit Force' : 'Add Force' ?></h3>
            <form method="post" action="<?= ADMIN_FILENAME ?>">
              <input type="hidden" name="action" value="<?= $editForce ? 'edit_force' : 'add_force' ?>">
              <?php if ($editForce): ?><input type="hidden" name="force_id" value="<?= e($editForce['id']) ?>"><?php endif; ?>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                  <label for="fo_name">Name *</label>
                  <input type="text" id="fo_name" name="fo_name" required placeholder="e.g. Contagion Protocol Kill Team"
                    value="<?= e($editForce['name'] ?? '') ?>">
                </div>
                <div>
                  <label for="fo_system">Game System</label>
                  <select id="fo_system" name="fo_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                    <option value="">- none -</option>
                    <?php foreach (['40k' => 'Warhammer 40,000', '30k / HH' => 'Horus Heresy / 30k', 'AoS' => 'Age of Sigmar', 'Old World' => 'The Old World', 'Kill Team' => 'Kill Team', 'Blood Bowl' => 'Blood Bowl', 'Necromunda' => 'Necromunda', 'Epic' => 'Epic Scale', 'OPR' => 'One Page Rules', 'Other' => 'Other'] as $sv => $sl): ?>
                      <option value="<?= e($sv) ?>" <?= ($editForce['system'] ?? '') === $sv ? ' selected' : '' ?>><?= e($sl) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="fo_faction">Faction</label>
                  <input type="text" id="fo_faction" name="fo_faction" placeholder="e.g. Death Guard"
                    value="<?= e($editForce['faction'] ?? '') ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                  <div>
                    <label for="fo_target_count">Target Models</label>
                    <input type="number" id="fo_target_count" name="fo_target_count" min="0" placeholder="e.g. 10"
                      value="<?= e($editForce['target_count'] ?? '') ?>" style="width:100%">
                  </div>
                  <div>
                    <label for="fo_target_points">Target Pts</label>
                    <input type="number" id="fo_target_points" name="fo_target_points" min="0" placeholder="e.g. 250"
                      value="<?= e($editForce['target_points'] ?? '') ?>" style="width:100%">
                  </div>
                </div>
              </div>
              <div style="margin-bottom:10px">
                <label for="fo_roster_url">Roster URL</label>
                <input type="url" id="fo_roster_url" name="fo_roster_url" placeholder="https://ktdash.app/rosters/… or Google Drive PDF link"
                  value="<?= e($editForce['roster_url'] ?? '') ?>">
              </div>
              <div style="margin-bottom:10px">
                <label for="fo_notes">Notes</label>
                <textarea id="fo_notes" name="fo_notes" rows="2" placeholder="Roster notes, list version, campaign context…" style="width:100%;resize:vertical"><?= e($editForce['notes'] ?? '') ?></textarea>
              </div>
              <?php if (!empty($models)): ?>
                <div style="margin-bottom:14px">
                  <label>Schemes in this Force</label>
                  <div style="max-height:220px;overflow-y:auto;border:1px solid #2a2010;background:#0e0d09;padding:8px 12px;border-radius:3px">
                    <?php foreach ($models as $m): ?>
                      <label style="display:flex;align-items:center;gap:8px;padding:3px 0;cursor:pointer;font-size:12px;color:#9a8a6a">
                        <input type="checkbox" name="force_models[]" value="<?= e($m['id']) ?>"
                          <?= in_array($m['id'], $editForce['models'] ?? []) ? 'checked' : '' ?>>
                        <span><?= e($m['name']) ?><?= !empty($m['faction']) ? '<span style="color:#5a4a28"> &mdash; ' . e($m['faction']) . '</span>' : '' ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
              <div style="display:flex;gap:8px;align-items:center">
                <button class="btn" type="submit"><?= $editForce ? 'Save Changes' : 'Add Force' ?></button>
                <?php if ($editForce): ?><a href="<?= ADMIN_FILENAME ?>#section-forces" style="color:#5a4a28;font-size:12px">Cancel</a><?php endif; ?>
              </div>
            </form>
          </div>

          <!-- Forces list -->
          <?php if (empty($forcesData)): ?>
            <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No forces yet. Add one above.</p>
          <?php else:
            $foModelById = array_column($models, null, 'id');
          ?>
            <div class="model-list" style="max-height:min(80vh,1200px);overflow-y:auto">
              <?php foreach ($forcesData as $fo): ?>
                <?php
                $foSchemes = count($fo['models'] ?? []);
                $foPainted = array_sum(array_map(fn($mid) => max(1, (int)(($foModelById[$mid] ?? [])['count'] ?? 1)), $fo['models'] ?? []));
                $foTarget  = $fo['target_count'] ?? 0;
                $foSystem  = $fo['system'] ?? '';
                ?>
                <div class="model-list-item" style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #1a1408">
                  <div style="flex:1">
                    <div style="font-family:'Cinzel',serif;font-size:13px;color:#c9a227"><?= e($fo['name']) ?></div>
                    <div style="font-size:11px;color:#5a4a28;margin-top:3px">
                      <?= $foSystem ? '<span style="margin-right:8px">' . e($foSystem) . '</span>' : '' ?>
                      <?= !empty($fo['faction']) ? e($fo['faction']) . ' &middot; ' : '' ?>
                      <?= $foSchemes ?> scheme<?= $foSchemes !== 1 ? 's' : '' ?><?= $foPainted !== $foSchemes ? ' &middot; ' . $foPainted . ' model' . ($foPainted !== 1 ? 's' : '') : '' ?> painted
                      <?= $foTarget ? ' / ' . $foTarget . ' target' : '' ?>
                      <?= !empty($fo['target_points']) ? ' &middot; ' . e($fo['target_points']) . ' pts' : '' ?>
                    </div>
                    <?php if (!empty($fo['notes'])): ?>
                      <div style="font-size:11px;color:#3a2a10;margin-top:2px"><?= e(mb_strimwidth($fo['notes'], 0, 80, '…')) ?></div>
                    <?php endif; ?>
                  </div>
                  <div style="display:flex;gap:6px;align-items:center;flex-shrink:0">
                    <button type="button" class="btn btn-sm fo-pin-btn<?= !empty($fo['pinned']) ? ' fo-pin-active' : '' ?>" data-id="<?= e($fo['id']) ?>" title="<?= !empty($fo['pinned']) ? 'Unpin' : 'Pin to top' ?>">★</button>
                    <a href="<?= ADMIN_FILENAME ?>?edit_force=<?= e($fo['id']) ?>#section-forces" class="btn btn-sm">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this force?')" style="margin:0">
                      <input type="hidden" name="action" value="delete_force">
                      <input type="hidden" name="force_id" value="<?= e($fo['id']) ?>">
                      <button class="btn btn-sm btn-danger" type="submit">&times;</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>
