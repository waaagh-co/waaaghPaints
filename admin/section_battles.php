      <h2 id="section-battles" class="collapsible" style="margin-top:40px">Battle Honours
        <?php if ($hasBattles && count($battlesData)): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($battlesData) ?> battle<?= count($battlesData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>
      <div>
        <?php if (!$hasBattles): ?>
          <form method="post" style="margin-top:12px">
            <input type="hidden" name="action" value="create_battles_file">
            <button class="btn">Start Battle Honours</button>
          </form>
          <p style="color:#5a4a28;font-size:13px;margin-top:8px">Track your games - results, opponents, armies, missions, and notes. Links to your Forces & Rosters for W/L/D records.</p>
        <?php else: ?>

          <!-- Add / Edit form -->
          <div style="margin-bottom:24px">
            <h3 id="bh-form-heading" style="font-family:'Cinzel',serif;font-size:14px;color:#9a8a6a;margin:0 0 12px">Log a Battle</h3>
            <form method="post" action="<?= ADMIN_FILENAME ?>" id="bh-form">
              <input type="hidden" name="action" id="bh_action" value="add_battle">
              <input type="hidden" name="bh_id" id="bh_id" value="">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                <div>
                  <label for="bh_date">Date *</label>
                  <input type="date" id="bh_date" name="bh_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                  <label for="bh_result">Result *</label>
                  <select id="bh_result" name="bh_result" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                    <option value="win">Win</option>
                    <option value="loss">Loss</option>
                    <option value="draw">Draw</option>
                  </select>
                </div>
                <div>
                  <label for="bh_my_army">My Army</label>
                  <input type="text" id="bh_my_army" name="bh_my_army" placeholder="e.g. Death Guard">
                </div>
                <?php if ($hasForces && !empty($forcesData)): ?>
                <div>
                  <label for="bh_force_id">Linked Force</label>
                  <select id="bh_force_id" name="bh_force_id" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                    <option value="">- none -</option>
                    <?php foreach ($forcesData as $fo): ?>
                      <option value="<?= e($fo['id']) ?>"><?= e($fo['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php endif; ?>
                <div>
                  <label for="bh_system">Game System</label>
                  <select id="bh_system" name="bh_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                    <option value="">- none -</option>
                    <?php foreach (['40k' => 'Warhammer 40,000', '30k / HH' => 'Horus Heresy / 30k', 'AoS' => 'Age of Sigmar', 'Old World' => 'The Old World', 'Kill Team' => 'Kill Team', 'Blood Bowl' => 'Blood Bowl', 'Necromunda' => 'Necromunda', 'Epic' => 'Epic Scale', 'OPR' => 'One Page Rules', 'Other' => 'Other'] as $sv => $sl): ?>
                      <option value="<?= e($sv) ?>"><?= e($sl) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="bh_points">Points</label>
                  <input type="number" id="bh_points" name="bh_points" min="0" placeholder="e.g. 2000" style="width:100%">
                </div>
                <div>
                  <label for="bh_opponent">Opponent</label>
                  <input type="text" id="bh_opponent" name="bh_opponent" placeholder="e.g. Dave">
                </div>
                <div>
                  <label for="bh_opponent_army">Opponent's Army</label>
                  <input type="text" id="bh_opponent_army" name="bh_opponent_army" placeholder="e.g. Tyranids">
                </div>
                <div>
                  <label for="bh_mission">Mission</label>
                  <input type="text" id="bh_mission" name="bh_mission" placeholder="e.g. Sweep and Clear">
                </div>
              </div>
              <div style="margin-bottom:10px">
                <label for="bh_notes">Notes</label>
                <textarea id="bh_notes" name="bh_notes" rows="2" placeholder="How did it go?" style="width:100%;resize:vertical"></textarea>
              </div>
              <div style="display:flex;gap:8px;align-items:center">
                <button class="btn" type="submit" id="bh-submit-btn">Log Battle</button>
                <button type="button" class="btn" id="bh-cancel-btn" style="display:none" onclick="bhCancelEdit()">Cancel</button>
              </div>
            </form>
          </div>

          <!-- Battle list -->
          <?php if (empty($battlesData)): ?>
            <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No battles logged yet.</p>
          <?php else:
            $bhForceById = $hasForces ? array_column($forcesData, null, 'id') : [];
          ?>
            <div class="model-list" style="max-height:min(80vh,1200px);overflow-y:auto">
              <?php foreach ($battlesData as $bh): ?>
                <?php
                  $bhResult  = $bh['result'] ?? 'draw';
                  $bhForce   = !empty($bh['force_id']) && isset($bhForceById[$bh['force_id']]) ? $bhForceById[$bh['force_id']]['name'] : '';
                ?>
                <div class="model-list-item" style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #1a1408">
                  <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px">
                      <span style="font-family:'Cinzel',serif;font-size:12px;color:#9a8a6a"><?= e($bh['date'] ?? '') ?></span>
                      <span class="bh-result-badge bh-result-<?= e($bhResult) ?>"><?= ucfirst($bhResult) ?></span>
                      <?php if (!empty($bh['system'])): ?><span style="font-size:11px;color:#4a3a1a"><?= e($bh['system']) ?></span><?php endif; ?>
                      <?php if (!empty($bh['points'])): ?><span style="font-size:11px;color:#4a3a1a"><?= e($bh['points']) ?>pts</span><?php endif; ?>
                    </div>
                    <div style="font-size:12px;color:#c4b49a;margin-top:3px">
                      <?= e($bh['my_army'] ?? ($bhForce ?: '—')) ?>
                      <?php if ($bhForce && !empty($bh['my_army'])): ?><span style="color:#4a3a1a;font-size:11px"> (<?= e($bhForce) ?>)</span><?php endif; ?>
                      <span style="color:#5a4a28"> vs </span>
                      <?php if (!empty($bh['opponent'])): ?><span style="color:#9a8a6a"><?= e($bh['opponent']) ?></span><?php endif; ?>
                      <?php if (!empty($bh['opponent_army'])): ?><span style="color:#7a6a4a"><?= !empty($bh['opponent']) ? ' &mdash; ' : '' ?><?= e($bh['opponent_army']) ?></span><?php endif; ?>
                    </div>
                    <?php if (!empty($bh['mission'])): ?>
                      <div style="font-size:11px;color:#5a4a28;margin-top:2px"><?= e($bh['mission']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($bh['notes'])): ?>
                      <div style="font-size:11px;color:#3a2a10;margin-top:2px"><?= e(mb_strimwidth($bh['notes'], 0, 100, '…')) ?></div>
                    <?php endif; ?>
                  </div>
                  <div style="display:flex;gap:6px;align-items:center;flex-shrink:0">
                    <button type="button" class="btn btn-sm bh-edit-btn"
                      data-id="<?= e($bh['id']) ?>"
                      data-date="<?= e($bh['date'] ?? '') ?>"
                      data-result="<?= e($bh['result'] ?? 'draw') ?>"
                      data-my-army="<?= e($bh['my_army'] ?? '') ?>"
                      data-force-id="<?= e($bh['force_id'] ?? '') ?>"
                      data-system="<?= e($bh['system'] ?? '') ?>"
                      data-points="<?= e($bh['points'] ?? '') ?>"
                      data-opponent="<?= e($bh['opponent'] ?? '') ?>"
                      data-opponent-army="<?= e($bh['opponent_army'] ?? '') ?>"
                      data-mission="<?= e($bh['mission'] ?? '') ?>"
                      data-notes="<?= e($bh['notes'] ?? '') ?>">Edit</button>
                    <form method="post" onsubmit="return confirm('Delete this battle?')" style="margin:0">
                      <input type="hidden" name="action" value="delete_battle">
                      <input type="hidden" name="bh_id" value="<?= e($bh['id']) ?>">
                      <button class="btn btn-sm btn-danger" type="submit">&times;</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>
