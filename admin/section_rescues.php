      <h2 id="section-rescues" class="collapsible" style="margin-top:40px">Rescue Tracker
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($rescuesData) ?> rescue<?= count($rescuesData) !== 1 ? 's' : '' ?></span>
      </h2>
      <?php if (!$hasRescues): ?>
        <form method="post">
          <input type="hidden" name="action" value="create_rescues_file">
          <button type="submit" class="btn btn-sm">Start Rescue Tracker</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openRescueAdd()">+ Add Rescue</button>
        </div>

        <div class="paint-form-wrap" id="rescueFormWrap" style="display:none">
          <div class="paint-form-title" id="rescueFormTitle">Add Rescue</div>
          <form method="post" id="rescueForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_rescue" id="rescueAction">
            <input type="hidden" name="rc_id" id="rcId" value="">
            <div class="form-grid">
              <div>
                <label for="rsc_name">Name *</label>
                <input type="text" id="rsc_name" name="rc_name" required placeholder="e.g. Ork Boyz Job Lot">
              </div>
              <div>
                <label for="rsc_system">System</label>
                <select id="rsc_system" name="rc_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">- none -</option>
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
                <label for="rsc_faction">Faction</label>
                <input type="text" id="rsc_faction" name="rc_faction" placeholder="e.g. Orks">
              </div>
              <div>
                <label for="rsc_count">Model Count</label>
                <input type="number" id="rsc_count" name="rc_count" min="1" placeholder="e.g. 10">
              </div>
              <div>
                <label for="rsc_source">Source</label>
                <select id="rsc_source" name="rc_source" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">- none -</option>
                  <option value="eBay">eBay</option>
                  <option value="Trade">Trade</option>
                  <option value="LGS">LGS</option>
                  <option value="Gift">Gift</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label for="rsc_condition">Strip Difficulty</label>
                <select id="rsc_condition" name="rc_condition" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">- unknown -</option>
                  <option value="bare">Bare (no paint)</option>
                  <option value="primed_only">Primed Only</option>
                  <option value="light">Light</option>
                  <option value="medium">Medium</option>
                  <option value="heavy">Heavy</option>
                </select>
              </div>
              <div>
                <label for="rsc_stage">Stage</label>
                <select id="rsc_stage" name="rc_stage" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="bidding">Bidding</option>
                  <option value="in_transit">In Transit</option>
                  <option value="received" selected>Received</option>
                  <option value="stripping">Stripping</option>
                  <option value="prepped">Prepped</option>
                </select>
              </div>
              <div>
                <label for="rsc_acquired">Date Acquired (YYYY-MM)</label>
                <input type="text" id="rsc_acquired" name="rc_acquired" placeholder="e.g. 2026-05" maxlength="7">
              </div>
              <div class="form-full">
                <label for="rsc_notes">Notes</label>
                <textarea id="rsc_notes" name="rc_notes" rows="3" placeholder="e.g. Badly drybrushed, base colours intact" style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
              <div class="form-full">
                <label>Before Photos (up to 4 - condition on arrival)</label>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:4px" id="rcPhotoGrid">
                  <?php for ($slot = 1; $slot <= RESCUES_MAX_IMAGES; $slot++): ?>
                  <div style="border:1px solid #2a2010;border-radius:3px;padding:6px;text-align:center;background:#0a0806">
                    <div style="font-size:10px;color:#5a4a28;margin-bottom:4px">Slot <?= $slot ?></div>
                    <div id="rcPhotoPreview<?= $slot ?>" style="width:100%;aspect-ratio:1;background:#1a1810;border-radius:2px;margin-bottom:4px;background-size:cover;background-position:center"></div>
                    <input type="file" name="rc_image<?= $slot ?>" id="rcImage<?= $slot ?>" accept="image/*" style="font-size:10px;width:100%;color:#7a6a50" onchange="previewRcPhoto(this,<?= $slot ?>)">
                    <div style="margin-top:4px">
                      <label style="font-size:10px;color:#5a4a28"><input type="checkbox" name="delete_rc_img_<?= $slot ?>" value="1" id="rcDeleteImg<?= $slot ?>"> Delete</label>
                    </div>
                  </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="rescueSubmitBtn">Add Rescue</button>
              <button type="button" class="btn btn-sm" id="rescueCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($rescuesData): ?>
          <?php
          $rescueSysBg = ['40k' => ['#8a2020', '#f08080'], '30k / HH' => ['#4a3a10', '#d4a840'], 'AoS' => ['#1a2a5a', '#7090d8'], 'Kill Team' => ['#0a3a3a', '#70c8d8'], 'Blood Bowl' => ['#2a1a4a', '#9a70d8'], 'Necromunda' => ['#1a3a3a', '#70c8c8'], 'Epic' => ['#1a3a1a', '#70b870'], 'OPR' => ['#1a2a3a', '#708090'], 'Old World' => ['#3a2a0a', '#d4a040'], 'Other' => ['#2a2a2a', '#909090']];
          $rescueStageLabel = ['bidding' => 'Bidding', 'in_transit' => 'In Transit', 'received' => 'Received', 'stripping' => 'Stripping', 'prepped' => 'Prepped'];
          $rescueStageBg    = ['bidding' => ['#2a1a3a', '#c090e8'], 'in_transit' => ['#1a1a4a', '#8090e8'], 'received' => ['#0a3030', '#70c8c8'], 'stripping' => ['#3a1a08', '#e07030'], 'prepped' => ['#0a2a0a', '#70c870']];
          $rescueCondLabel  = ['bare' => 'Bare', 'primed_only' => 'Primed Only', 'light' => 'Light', 'medium' => 'Medium', 'heavy' => 'Heavy'];
          ?>
          <div class="model-list">
            <?php foreach ($rescuesData as $rc):
              $rcPromote = $rc['promoted_to'] ?? '';
              $rcStage   = $rc['stage'] ?? 'received';
              $sysBg     = $rescueSysBg[$rc['system'] ?? ''] ?? ['#2a2a2a', '#909090'];
              $stageBg   = $rescueStageBg[$rcStage] ?? ['#1a1a1a', '#7a7a7a'];
            ?>
              <div class="model-row" id="rescue-row-<?= e($rc['id']) ?>">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <?php if (!empty($rc['system'])): ?>
                      <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:<?= $sysBg[0] ?>;color:<?= $sysBg[1] ?>;font-family:'Cinzel',serif;letter-spacing:.05em"><?= e($rc['system']) ?></span>
                    <?php endif; ?>
                    <?= e($rc['name']) ?>
                    <button type="button" class="rescue-stage-btn stage-<?= e($rcStage) ?>" id="rcStageBtn-<?= e($rc['id']) ?>" onclick="advanceRescueStage('<?= e($rc['id']) ?>')"><?= e($rescueStageLabel[$rcStage] ?? $rcStage) ?></button>
                    <?php if ($rcPromote): ?>
                      <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:#1a3a1a;color:#7ad678;font-family:'Cinzel',serif">Promoted &rarr; <?= $rcPromote === 'bench' ? 'Bench' : 'Shame' ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="model-row-meta">
                    <?php if (!empty($rc['faction'])): ?><?= e($rc['faction']) ?> &middot; <?php endif; ?>
                    <?php if (!empty($rc['count'])): ?><?= (int)$rc['count'] ?> models &middot; <?php endif; ?>
                    <?php if (!empty($rc['source'])): ?><span style="font-size:10px;padding:1px 5px;background:#101018;color:#6a6a8a;border-radius:3px"><?= e($rc['source']) ?></span><?php endif; ?>
                    <?php if (!empty($rc['condition'])): ?><span style="font-size:10px;padding:1px 5px;background:#1a1a1a;color:#7a6a50;border-radius:3px"><?= e($rescueCondLabel[$rc['condition']] ?? $rc['condition']) ?></span><?php endif; ?>
                    <?php if (!empty($rc['acquired'])): ?> &middot; <span style="color:#c9a227;font-family:'Cinzel',serif;font-size:11px"><?= e($rc['acquired']) ?></span><?php endif; ?>
                  </div>
                  <?php if (!empty($rc['before_images'])): ?>
                    <div style="display:flex;gap:4px;margin-top:4px;flex-wrap:wrap">
                      <?php foreach (array_filter($rc['before_images']) as $rimg): ?>
                        <img src="<?= e($rimg) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:2px;border:1px solid #2a2010" alt="">
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($rc['notes'])): ?><div style="font-size:11px;color:#5a4a28;margin-top:3px"><?= e(mb_substr($rc['notes'], 0, 100)) ?><?= mb_strlen($rc['notes']) > 100 ? '&hellip;' : '' ?></div><?php endif; ?>
                </div>
                <div class="model-row-actions" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                  <?php if (!$rcPromote): ?>
                    <button type="button" class="btn btn-sm" onclick="promoteRescue('<?= e($rc['id']) ?>','bench')" style="font-size:10px">&rarr; Bench</button>
                    <button type="button" class="btn btn-sm" onclick="promoteRescue('<?= e($rc['id']) ?>','shame')" style="font-size:10px">&rarr; Shame</button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-sm" onclick="openRescueEdit(this)"
                    data-id="<?= e($rc['id']) ?>"
                    data-name="<?= e($rc['name']) ?>"
                    data-system="<?= e($rc['system'] ?? '') ?>"
                    data-faction="<?= e($rc['faction'] ?? '') ?>"
                    data-count="<?= (int)($rc['count'] ?? 0) ?>"
                    data-source="<?= e($rc['source'] ?? '') ?>"
                    data-condition="<?= e($rc['condition'] ?? '') ?>"
                    data-stage="<?= e($rcStage) ?>"
                    data-acquired="<?= e($rc['acquired'] ?? '') ?>"
                    data-notes="<?= e($rc['notes'] ?? '') ?>"
                    data-images="<?= e(json_encode($rc['before_images'] ?? [])) ?>">Edit</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Remove this rescue?')">
                    <input type="hidden" name="action" value="delete_rescue">
                    <input type="hidden" name="rc_id" value="<?= e($rc['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <script>
        function openRescueAdd() {
          if (window._adminShowSection) window._adminShowSection('section-rescues');
          document.getElementById('rescueFormTitle').textContent = 'Add Rescue';
          document.getElementById('rescueAction').value = 'add_rescue';
          document.getElementById('rcId').value = '';
          document.getElementById('rescueForm').reset();
          for (let s = 1; s <= <?= RESCUES_MAX_IMAGES ?>; s++) {
            document.getElementById('rcPhotoPreview' + s).style.backgroundImage = '';
            const cb = document.getElementById('rcDeleteImg' + s); if (cb) cb.checked = false;
          }
          document.getElementById('rescueSubmitBtn').textContent = 'Add Rescue';
          document.getElementById('rescueFormWrap').style.display = '';
        }
        function openRescueEdit(btn) {
          if (window._adminShowSection) window._adminShowSection('section-rescues');
          document.getElementById('rescueFormTitle').textContent = 'Edit Rescue';
          document.getElementById('rescueAction').value = 'edit_rescue';
          document.getElementById('rcId').value = btn.dataset.id;
          document.getElementById('rsc_name').value = btn.dataset.name;
          document.getElementById('rsc_system').value = btn.dataset.system || '';
          document.getElementById('rsc_faction').value = btn.dataset.faction;
          document.getElementById('rsc_count').value = btn.dataset.count > 0 ? btn.dataset.count : '';
          document.getElementById('rsc_source').value = btn.dataset.source || '';
          document.getElementById('rsc_condition').value = btn.dataset.condition || '';
          document.getElementById('rsc_stage').value = btn.dataset.stage;
          document.getElementById('rsc_acquired').value = btn.dataset.acquired;
          document.getElementById('rsc_notes').value = btn.dataset.notes;
          try {
            const imgs = JSON.parse(btn.dataset.images || '[]');
            for (let s = 1; s <= <?= RESCUES_MAX_IMAGES ?>; s++) {
              const prev = document.getElementById('rcPhotoPreview' + s);
              prev.style.backgroundImage = imgs[s-1] ? "url('" + imgs[s-1] + "')" : '';
              const cb = document.getElementById('rcDeleteImg' + s); if (cb) cb.checked = false;
            }
          } catch(e) {}
          document.getElementById('rescueSubmitBtn').textContent = 'Save Changes';
          document.getElementById('rescueFormWrap').style.display = '';
        }
        document.getElementById('rescueCancelBtn').addEventListener('click', () => {
          document.getElementById('rescueFormWrap').style.display = 'none';
        });
        function previewRcPhoto(inp, slot) {
          if (!inp.files[0]) return;
          const r = new FileReader();
          r.onload = e => { document.getElementById('rcPhotoPreview' + slot).style.backgroundImage = "url('" + e.target.result + "')"; };
          r.readAsDataURL(inp.files[0]);
        }
        function advanceRescueStage(id) {
          fetch('<?= ADMIN_FILENAME ?>', { method: 'POST', body: new URLSearchParams({ action: 'set_rescue_stage', rc_id: id }) })
            .then(r => r.json())
            .then(d => {
              if (!d.ok) return;
              const btn = document.getElementById('rcStageBtn-' + id);
              if (!btn) return;
              const stageLabels = { bidding: 'Bidding', in_transit: 'In Transit', received: 'Received', stripping: 'Stripping', prepped: 'Prepped' };
              btn.className = 'rescue-stage-btn stage-' + d.stage;
              btn.textContent = stageLabels[d.stage] || d.stage;
            });
        }
        function promoteRescue(id, dest) {
          if (!confirm('Promote this rescue to ' + (dest === 'bench' ? 'On the Bench' : 'Pile of Shame') + '?')) return;
          fetch('<?= ADMIN_FILENAME ?>', { method: 'POST', body: new URLSearchParams({ action: 'promote_rescue', rc_id: id, promote_to: dest }) })
            .then(r => r.json())
            .then(d => {
              if (!d.ok) return;
              const row = document.getElementById('rescue-row-' + id);
              if (row) {
                const acts = row.querySelector('.model-row-actions');
                if (acts) { const btns = acts.querySelectorAll('.btn-sm'); btns.forEach(b => { if (b.textContent.includes('Bench') || b.textContent.includes('Shame')) b.remove(); }); }
                const nameDiv = row.querySelector('.model-row-name');
                if (nameDiv) {
                  const badge = document.createElement('span');
                  badge.style.cssText = 'font-size:10px;padding:2px 7px;border-radius:3px;background:#1a3a1a;color:#7ad678;font-family:Cinzel,serif';
                  badge.textContent = 'Promoted → ' + (dest === 'bench' ? 'Bench' : 'Shame');
                  nameDiv.appendChild(badge);
                }
              }
            });
        }
        </script>
      <?php endif; ?>
