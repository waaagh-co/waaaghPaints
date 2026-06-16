      <h2 id="section-planned" style="margin-top:40px">Planned Schemes
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($planned) ?> scheme<?= count($planned) !== 1 ? 's' : '' ?></span>
      </h2>

      <div style="margin-bottom:14px">
        <button type="button" class="btn btn-sm" onclick="openPlannedAdd()">+ Add Planned Scheme</button>
      </div>

      <div class="paint-form-wrap" id="plannedFormWrap" style="display:none">
        <div class="paint-form-title" id="plannedFormTitle">Add Planned Scheme</div>
        <form method="post" id="plannedForm">
          <input type="hidden" name="action" value="add_planned" id="plannedAction">
          <input type="hidden" name="planned_id" id="plannedId" value="">
          <div class="form-grid">
            <div>
              <label for="pl_name">Scheme Name *</label>
              <input type="text" id="pl_name" name="pl_name" required placeholder="e.g. Blood Angels Infantry">
            </div>
            <div>
              <label for="pl_model">Kit / Model</label>
              <input type="text" id="pl_model" name="pl_model" placeholder="e.g. Space Marines Intercessors">
            </div>
            <div>
              <label for="pl_faction">Faction</label>
              <input type="text" id="pl_faction" name="pl_faction" placeholder="e.g. Blood Angels">
            </div>
            <div>
              <label for="pl_sub_faction">Unit / Sub-faction</label>
              <input type="text" id="pl_sub_faction" name="pl_sub_faction" placeholder="e.g. Death Company, Shoota Boyz">
            </div>
            <div>
              <label for="pl_system">Game System</label>
              <select id="pl_system" name="pl_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
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
              <label for="pl_codex_source">Codex Reference</label>
              <select id="pl_codex_source" name="pl_codex_source">
                <option value="">- none -</option>
                <?php foreach ($codexOptions as $opt): ?>
                  <option value="<?= e($opt['value']) ?>"><?= e($opt['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-full">
              <label for="pl_description">Notes</label>
              <textarea id="pl_description" name="pl_description" rows="2" placeholder="Colour ideas, inspiration…"></textarea>
            </div>
            <div class="form-full">
              <label>Colours</label>
              <input type="text" class="color-search" id="colorSearchPl" placeholder="Filter paints…" autocomplete="off">
              <div class="color-list" id="colorListPl"></div>
              <div class="selected-colors" id="selectedCountPl">0 colours selected</div>
              <div id="colorInputsPl"></div>
            </div>
            <?php if ($hasRecipes && $recipesData): ?>
              <div class="form-full">
                <label>Recipes (optional)</label>
                <div class="rc-pill-picker" id="plannedRecipePicker" data-form="planned">
                  <?php foreach ($recipesData as $rc): ?>
                    <span class="rc-pill" data-id="<?= e($rc['id']) ?>">
                      <?= e($rc['name']) ?><?php if (!empty($rc['category'])): ?> <small>(<?= e($rc['category']) ?>)</small><?php endif; ?>
                    </span>
                  <?php endforeach; ?>
                </div>
                <div id="plannedRecipeInputs"></div>
              </div>
            <?php endif; ?>
          </div>
          <div style="margin-top:16px;display:flex;gap:10px">
            <button type="submit" class="btn" id="plannedSubmitBtn">Add Scheme</button>
            <button type="button" class="btn btn-sm" id="plannedCancelBtn">Cancel</button>
          </div>
        </form>
      </div>

      <?php if ($planned): ?>
        <div class="model-list">
          <?php foreach ($planned as $pl): ?>
            <div class="model-row">
              <div class="model-row-info">
                <div class="model-row-name"><?= e($pl['name']) ?></div>
                <div class="model-row-meta">
                  <?php if (!empty($pl['model'])): ?>Kit: <?= e($pl['model']) ?>&nbsp;&nbsp;<?php endif; ?>
                  <?= e($pl['faction'] ?? '') ?>
                  &nbsp;&nbsp;<?= count($pl['colors'] ?? []) ?> colour<?= count($pl['colors'] ?? []) !== 1 ? 's' : '' ?>
                </div>
              </div>
              <?php if (empty($pl['promoted_to'])): ?>
                <button type="button" class="btn btn-sm" onclick="promotePlanned('<?= e($pl['id']) ?>')" style="font-size:10px">&rarr; Bench</button>
              <?php else: ?>
                <span style="font-size:10px;color:#c9a227;font-family:'Cinzel',serif">Promoted &rarr; <?= ucfirst(e($pl['promoted_to'])) ?></span>
              <?php endif; ?>
              <button class="btn btn-sm"
                data-id="<?= e($pl['id']) ?>"
                data-name="<?= e($pl['name']) ?>"
                data-model="<?= e($pl['model'] ?? '') ?>"
                data-faction="<?= e($pl['faction'] ?? '') ?>"
                data-sub_faction="<?= e($pl['sub_faction'] ?? '') ?>"
                data-description="<?= e($pl['description'] ?? '') ?>"
                data-colors="<?= e(json_encode($pl['colors'] ?? [])) ?>"
                data-recipes="<?= e(json_encode($pl['recipes'] ?? [])) ?>"
                data-system="<?= e($pl['system'] ?? '') ?>"
                data-codex_source="<?= e($pl['codex_source'] ?? '') ?>"
                onclick="openPlannedEdit(this)">Edit</button>
              <form method="post" onsubmit="return confirm('Delete this planned scheme?')" style="margin:0">
                <input type="hidden" name="action" value="delete_planned">
                <input type="hidden" name="planned_id" value="<?= e($pl['id']) ?>">
                <button type="submit" class="btn btn-sm btn-danger">&times;</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No planned schemes yet.</p>
      <?php endif; ?>
