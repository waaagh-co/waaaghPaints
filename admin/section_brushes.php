      <h2 id="section-brushes" style="margin-top:40px">Brush Inventory
        <?php if ($hasBrushes): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($brushesData) ?> brush<?= count($brushesData) !== 1 ? 'es' : '' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasBrushes): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Start your brush inventory to track condition, use, and notes for each brush.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_brushes_file">
          <button type="submit" class="btn btn-sm">Start Brush Inventory</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openBrushAdd()">+ Add Brush</button>
        </div>

        <div class="paint-form-wrap" id="brushFormWrap" style="display:none">
          <div class="paint-form-title" id="brushFormTitle">Add Brush</div>
          <form method="post" id="brushForm">
            <input type="hidden" name="action" value="add_brush" id="brushAction">
            <input type="hidden" name="brush_id" id="brushId" value="">
            <div class="form-grid">
              <div>
                <label for="br_brand">Brand *</label>
                <input type="text" id="br_brand" name="br_brand" required placeholder="e.g. Artis Opus"
                  list="br_brandList">
                <datalist id="br_brandList">
                  <option value="Artis Opus">
                  <option value="Da Vinci">
                  <option value="Winsor &amp; Newton">
                  <option value="Army Painter">
                  <option value="Citadel">
                  <option value="Raphael">
                  <option value="Rosemary &amp; Co">
                  <option value="Princeton">
                </datalist>
              </div>
              <div>
                <label for="br_series">Series / Line</label>
                <input type="text" id="br_series" name="br_series" placeholder="e.g. S, Series 10">
              </div>
              <div>
                <label for="br_size">Size</label>
                <input type="text" id="br_size" name="br_size" placeholder="e.g. 1, 0, Small"
                  list="br_sizeList">
                <datalist id="br_sizeList">
                  <option value="000">
                  <option value="00">
                  <option value="0">
                  <option value="1">
                  <option value="2">
                  <option value="3">
                  <option value="Small">
                  <option value="Medium">
                  <option value="Large">
                  <option value="XL">
                </datalist>
              </div>
              <div>
                <label for="br_material">Material</label>
                <input type="text" id="br_material" name="br_material" placeholder="e.g. Sable"
                  list="br_materialList">
                <datalist id="br_materialList">
                  <option value="Sable">
                  <option value="Kolinsky Sable">
                  <option value="Synthetic">
                  <option value="Squirrel">
                  <option value="Taklon">
                  <option value="Hog">
                </datalist>
              </div>
              <div>
                <label for="br_use">Primary Use</label>
                <input type="text" id="br_use" name="br_use" placeholder="e.g. Detail / Layering"
                  list="br_useList">
                <datalist id="br_useList">
                  <option value="Detail / Layering">
                  <option value="Basecoating">
                  <option value="Drybrushing">
                  <option value="Washes / Glazes">
                  <option value="Metallics">
                  <option value="Basing / Texture">
                  <option value="Blending">
                  <option value="Stippling">
                  <option value="Terrain">
                </datalist>
              </div>
              <div>
                <label for="br_condition">Condition</label>
                <select id="br_condition" name="br_condition" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="prime">Prime</option>
                  <option value="workhorse">Workhorse</option>
                  <option value="retired">Retired</option>
                </select>
              </div>
              <div>
                <label>Quality Rating (optional)</label>
                <div class="brush-star-picker" id="brushStarPicker">
                  <span class="bsp-star" data-val="1">★</span>
                  <span class="bsp-star" data-val="2">★</span>
                  <span class="bsp-star" data-val="3">★</span>
                  <span class="bsp-star" data-val="4">★</span>
                  <span class="bsp-star" data-val="5">★</span>
                </div>
                <input type="hidden" id="br_stars" name="br_stars" value="">
              </div>
              <div>
                <label for="br_date_start">Date Started (optional - YYYY-MM)</label>
                <input type="text" id="br_date_start" name="br_date_start" placeholder="e.g. 2024-01" pattern="\d{4}-\d{2}" maxlength="7">
              </div>
              <div class="form-full">
                <label for="br_notes">Notes</label>
                <textarea id="br_notes" name="br_notes" rows="3"
                  placeholder="e.g. tip starting to splay, good for blending, keep for washes only…"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="brushSubmitBtn">Add Brush</button>
              <button type="button" class="btn btn-sm" id="brushCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($brushesData): ?>
          <div class="paint-table-wrap" style="max-height:min(80vh, 1200px)">
            <table class="paint-table" id="brushTable">
              <thead>
                <tr>
                  <th>Brand</th>
                  <th>Series</th>
                  <th>Size</th>
                  <th>Material</th>
                  <th>Primary Use</th>
                  <th>Condition</th>
                  <th>Started</th>
                  <th colspan="2"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($brushesData as $br):
                  $brCond     = $br['condition'] ?? 'prime';
                  $brCondLabel = ['prime' => 'Prime', 'workhorse' => 'Workhorse', 'retired' => 'Retired'][$brCond] ?? $brCond;
                  $brDate     = !empty($br['date_start']) ? date('M Y', strtotime($br['date_start'] . '-01')) : '';
                ?>
                  <tr>
                    <td style="font-family:'Cinzel',serif;font-size:11px;color:#c9a227">
                      <?= e($br['brand']) ?>
                      <?php if (!empty($br['notes'])): ?>
                        <span title="<?= e($br['notes']) ?>" style="color:#6a4f10;font-size:11px;cursor:default;margin-left:3px">✎</span>
                      <?php endif; ?>
                      <?php if (!empty($br['stars'])): ?>
                        <span class="br-stars-cell"><?= str_repeat('★', (int)$br['stars']) ?><span style="color:#1e1208"><?= str_repeat('★', 5 - (int)$br['stars']) ?></span></span>
                      <?php endif; ?>
                    </td>
                    <td style="color:#7a6840;font-size:11px"><?= e($br['series'] ?? '') ?></td>
                    <td><?= e($br['size'] ?? '') ?></td>
                    <td style="font-size:11px;color:#7a6840"><?= e($br['material'] ?? '') ?></td>
                    <td style="font-size:12px"><?= e($br['use'] ?? '') ?></td>
                    <td>
                      <button type="button"
                        class="brush-cond-btn cond-<?= e($brCond) ?>"
                        data-bid="<?= e($br['id']) ?>"
                        data-cond="<?= e($brCond) ?>"
                        onclick="toggleBrushCond(this)"><?= e($brCondLabel) ?></button>
                    </td>
                    <td style="font-size:11px;color:#4a3a1a;font-family:'Cinzel',serif;letter-spacing:.03em"><?= e($brDate) ?></td>
                    <td style="white-space:nowrap">
                      <button type="button" class="btn btn-sm"
                        data-id="<?= e($br['id']) ?>"
                        data-brand="<?= e($br['brand']) ?>"
                        data-series="<?= e($br['series'] ?? '') ?>"
                        data-size="<?= e($br['size'] ?? '') ?>"
                        data-material="<?= e($br['material'] ?? '') ?>"
                        data-use="<?= e($br['use'] ?? '') ?>"
                        data-condition="<?= e($brCond) ?>"
                        data-stars="<?= e($br['stars'] ?? '') ?>"
                        data-date_start="<?= e($br['date_start'] ?? '') ?>"
                        data-notes="<?= e($br['notes'] ?? '') ?>"
                        onclick="openBrushEdit(this)">Edit</button>
                    </td>
                    <td>
                      <form method="post" onsubmit="return confirm('Delete this brush?')" style="margin:0">
                        <input type="hidden" name="action" value="delete_brush">
                        <input type="hidden" name="brush_id" value="<?= e($br['id']) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No brushes logged yet.</p>
        <?php endif; ?>
      <?php endif; ?>
