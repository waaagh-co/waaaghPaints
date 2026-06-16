      <h2 id="section-supplies" style="margin-top:40px">Supplies
        <?php if ($hasSupplies): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($suppliesData) ?> item<?= count($suppliesData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasSupplies): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Track your hobby supplies - palettes, mats, lamps, holders, and other tools of the craft.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_supplies_file">
          <button type="submit" class="btn btn-sm">Start Supplies Inventory</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openSupplyAdd()">+ Add Supply</button>
        </div>

        <div class="paint-form-wrap" id="supplyFormWrap" style="display:none">
          <div class="paint-form-title" id="supplyFormTitle">Add Supply</div>
          <form method="post" id="supplyForm">
            <input type="hidden" name="action" value="add_supply" id="supplyAction">
            <input type="hidden" name="supply_id" id="supplyId" value="">
            <div class="form-grid">
              <div>
                <label for="sp_name">Name *</label>
                <input type="text" id="sp_name" name="sp_name" required placeholder="e.g. Glass Palette">
              </div>
              <div>
                <label for="sp_brand">Brand</label>
                <input type="text" id="sp_brand" name="sp_brand" placeholder="e.g. Red Grass Games"
                  list="sp_brandList">
                <datalist id="sp_brandList">
                  <option value="Red Grass Games">
                  <option value="Squidmar">
                  <option value="Artis Opus">
                  <option value="Army Painter">
                  <option value="Citadel">
                  <option value="Vallejo">
                  <option value="Games Workshop">
                  <option value="Hobby Zone">
                </datalist>
              </div>
              <div>
                <label for="sp_type">Type</label>
                <input type="text" id="sp_type" name="sp_type" placeholder="e.g. palette"
                  list="sp_typeList">
                <datalist id="sp_typeList">
                  <option value="palette">
                  <option value="wet-palette">
                  <option value="dry-palette">
                  <option value="cutting-mat">
                  <option value="lamp">
                  <option value="holder">
                  <option value="storage">
                  <option value="tool">
                  <option value="other">
                </datalist>
              </div>
              <div>
                <label for="sp_condition">Condition</label>
                <select id="sp_condition" name="sp_condition" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="prime">Prime</option>
                  <option value="workhorse">Workhorse</option>
                  <option value="retired">Retired</option>
                </select>
              </div>
              <div>
                <label for="sp_acquired">Acquired (optional - YYYY-MM)</label>
                <input type="text" id="sp_acquired" name="sp_acquired" placeholder="e.g. 2024-01" pattern="\d{4}-\d{2}" maxlength="7">
              </div>
              <div class="form-full">
                <label for="sp_notes">Notes</label>
                <textarea id="sp_notes" name="sp_notes" rows="3"
                  placeholder="e.g. great for wet blending, paper starting to pill, keep away from oils&hellip;"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="supplySubmitBtn">Add Supply</button>
              <button type="button" class="btn btn-sm" id="supplyCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($suppliesData): ?>
          <div class="paint-table-wrap" style="max-height:min(80vh, 1200px)">
            <table class="paint-table" id="supplyTable">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Brand</th>
                  <th>Type</th>
                  <th>Condition</th>
                  <th>Acquired</th>
                  <th colspan="2"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($suppliesData as $sp):
                  $spCond      = $sp['condition'] ?? 'prime';
                  $spCondLabel = ['prime' => 'Prime', 'workhorse' => 'Workhorse', 'retired' => 'Retired'][$spCond] ?? $spCond;
                  $spDate      = !empty($sp['acquired']) ? date('M Y', strtotime($sp['acquired'] . '-01')) : '';
                ?>
                  <tr>
                    <td style="font-family:'Cinzel',serif;font-size:11px;color:#c9a227">
                      <?= e($sp['name']) ?>
                      <?php if (!empty($sp['notes'])): ?>
                        <span title="<?= e($sp['notes']) ?>" style="color:#6a4f10;font-size:11px;cursor:default;margin-left:3px">✎</span>
                      <?php endif; ?>
                    </td>
                    <td style="color:#7a6840;font-size:11px"><?= e($sp['brand'] ?? '') ?></td>
                    <td style="font-size:11px;color:#7a6840"><?= e($sp['type'] ?? '') ?></td>
                    <td>
                      <button type="button"
                        class="brush-cond-btn cond-<?= e($spCond) ?>"
                        data-bid="<?= e($sp['id']) ?>"
                        data-cond="<?= e($spCond) ?>"
                        onclick="toggleSupplyCond(this)"><?= e($spCondLabel) ?></button>
                    </td>
                    <td style="font-size:11px;color:#4a3a1a;font-family:'Cinzel',serif;letter-spacing:.03em"><?= e($spDate) ?></td>
                    <td style="white-space:nowrap">
                      <button type="button" class="btn btn-sm"
                        data-id="<?= e($sp['id']) ?>"
                        data-name="<?= e($sp['name']) ?>"
                        data-brand="<?= e($sp['brand'] ?? '') ?>"
                        data-type="<?= e($sp['type'] ?? '') ?>"
                        data-condition="<?= e($spCond) ?>"
                        data-acquired="<?= e($sp['acquired'] ?? '') ?>"
                        data-notes="<?= e($sp['notes'] ?? '') ?>"
                        onclick="openSupplyEdit(this)">Edit</button>
                    </td>
                    <td>
                      <form method="post" onsubmit="return confirm('Delete this supply?')" style="margin:0">
                        <input type="hidden" name="action" value="delete_supply">
                        <input type="hidden" name="supply_id" value="<?= e($sp['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No supplies logged yet.</p>
        <?php endif; ?>
      <?php endif; ?>
