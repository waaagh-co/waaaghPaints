      <h2 id="section-inventory" style="margin-top:40px">Paint Inventory
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($paints) ?> paints</span>
      </h2>

      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap">
        <?php if (!file_exists(PAINTS_FILE)): ?>
          <form method="post" style="margin:0">
            <input type="hidden" name="action" value="import_paints">
            <button type="submit" class="btn btn-sm">Import <?= count($paints) ?> Paints from CSVs</button>
          </form>
        <?php endif; ?>
        <?php if (!file_exists(PAINTS_FILE)): ?>
          <span style="font-size:11px;color:#4a3a1a">Import first to enable add / edit / delete.</span>
        <?php endif; ?>
        <?php
        $hexCovered = count(array_filter($paints, fn($p) => !empty($p['hex'])));
        $seedExists = file_exists(HEX_SEED_FILE);
        ?>
        <?php if ($seedExists && file_exists(PAINTS_FILE)): ?>
          <form method="post" style="margin:0" onsubmit="return confirm('Seed hex values from data/paint_hex_seed.json into paints.json? This only fills paints that don\'t already have a hex value.');">
            <input type="hidden" name="action" value="apply_hex_seed">
            <button type="submit" class="btn btn-sm">Apply Hex Seed</button>
          </form>
        <?php endif; ?>
        <?php if (file_exists(PAINTS_FILE)): ?>
          <span style="font-size:11px;color:#4a3a1a"><?= $hexCovered ?> of <?= count($paints) ?> paints have hex values</span>
        <?php endif; ?>
      </div>

      <?php if (file_exists(PAINTS_FILE)): ?>
        <!-- Add / Edit form -->
        <div class="paint-form-wrap" id="paintFormWrap" style="display:none">
          <div class="paint-form-title" id="paintFormTitle">Add Paint</div>
          <form method="post" id="paintForm">
            <input type="hidden" name="action" value="add_paint" id="paintAction">
            <input type="hidden" name="paint_id" id="paintId" value="">
            <div class="form-grid">
              <div>
                <label for="p_brand">Brand</label>
                <input type="text" id="p_brand" name="brand" list="p_brandList" required placeholder="e.g. Citadel">
                <datalist id="p_brandList">
                  <option value="Citadel">
                  <option value="Pro Acryl">
                  <option value="Vallejo">
                  <option value="Army Painter">
                  <option value="Gamblin Artist Oils">
                  <option value="AK Interactive">
                  <option value="Scale75">
                  <option value="Two Thin Coats">
                </datalist>
              </div>
              <div>
                <label for="p_name">Paint Name</label>
                <input type="text" id="p_name" name="name" required placeholder="e.g. Mephiston Red">
              </div>
              <div>
                <label for="p_color">Colour Category</label>
                <input type="text" id="p_color" name="color" list="p_colorList" placeholder="e.g. Red">
                <datalist id="p_colorList">
                  <?php foreach (
                    [
                      'White',
                      'Grey',
                      'Black',
                      'Flesh',
                      'Red',
                      'Green',
                      'Blue',
                      'Yellow',
                      'Orange',
                      'Brown',
                      'Purple',
                      'Pink',
                      'Metallic',
                      'Shade',
                      'Wash',
                      'Contrast',
                      'Transparent',
                      'Fluorescent',
                      'Special',
                      'Ink',
                      'Medium',
                      'Effect',
                      'Texture',
                      'Pigment',
                      'Fluid',
                      'Primer',
                      'Utility'
                    ] as $cat
                  ): ?>
                    <option value="<?= e($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
              </div>
              <div>
                <label for="p_hue">Hue Description</label>
                <input type="text" id="p_hue" name="hue" placeholder="e.g. Dark Red">
              </div>
              <div class="form-full">
                <label for="p_layer">Layer / Type</label>
                <input type="text" id="p_layer" name="layer" list="p_layerList" placeholder="e.g. Base">
              </div>
              <div>
                <label for="p_hex">Swatch Hex (for Color Match)</label>
                <div style="display:flex;gap:6px;align-items:center">
                  <input type="color" id="p_hex_picker" style="width:42px;height:30px;padding:0;border:1px solid #2a2010;background:#130f08;cursor:pointer;border-radius:3px" oninput="document.getElementById('p_hex').value = this.value">
                  <input type="text" id="p_hex" name="hex" placeholder="#a02020" pattern="^#[0-9a-fA-F]{6}$" maxlength="7" style="flex:1" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('p_hex_picker').value = this.value.toLowerCase()">
                </div>
              </div>
              <div class="form-full">
                <label for="p_notes">Notes</label>
                <textarea id="p_notes" name="notes" rows="3" placeholder="e.g. thin 2:1, chalky, already thin out of pot, matte finish…" style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
              <div>
                <label>Quality Rating (optional)</label>
                <div class="brush-star-picker" id="paintStarPicker">
                  <span class="bsp-star" data-val="1">★</span>
                  <span class="bsp-star" data-val="2">★</span>
                  <span class="bsp-star" data-val="3">★</span>
                  <span class="bsp-star" data-val="4">★</span>
                  <span class="bsp-star" data-val="5">★</span>
                </div>
                <input type="hidden" id="p_stars" name="p_stars" value="">
              </div>
              <div class="form-full" style="display:none">
                <datalist id="p_layerList">
                  <?php foreach (
                    [
                      'Base',
                      'Contrast',
                      'Shade',
                      'Metallic',
                      'Transparent',
                      'Fluorescent',
                      'Special',
                      'Oil',
                      'Speedpaint',
                      'Technical',
                      'Varnish',
                      'Medium',
                      'Ink',
                      'Texture',
                      'Weathering',
                      'Airbrush',
                      'Air',
                      'Terrain',
                      'Tool',
                      'Effect',
                      'Primer'
                    ] as $lt
                  ): ?>
                    <option value="<?= e($lt) ?>">
                    <?php endforeach; ?>
                </datalist>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="paintSubmitBtn">Add Paint</button>
              <button type="button" class="btn btn-sm" id="paintCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Filter bar -->
        <div class="paint-toolbar">
          <input type="text" id="paintSearch" placeholder="Search name or hue…">
          <select id="paintBrandFilter">
            <option value="">All Brands</option>
            <?php foreach (array_unique(array_column($paints, 'brand')) as $b): ?>
              <option value="<?= e($b) ?>"><?= e($b) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="btn btn-sm" onclick="openPaintAdd()">+ Add Paint</button>
        </div>

        <!-- Paint table -->
        <div class="paint-table-wrap">
          <table class="paint-table" id="paintTable">
            <thead>
              <tr>
                <th>Brand</th>
                <th>Name</th>
                <th>Colour</th>
                <th>Hue</th>
                <th>Layer</th>
                <th colspan="3"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paints as $p):
                $pid = e($p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? '')); ?>
                <tr class="brand-<?= brandSlug($p['brand']) ?>"
                  data-brand="<?= e(strtolower($p['brand'])) ?>"
                  data-name="<?= e(strtolower($p['name'] . ' ' . $p['hue'])) ?>"
                  data-layer="<?= e(strtolower($p['layer'])) ?>">
                  <td style="font-size:11px;color:#7a6030"><?= e($p['brand']) ?></td>
                  <td><?= e($p['name']) ?><?php if (!empty($p['notes'])): ?> <span title="<?= e($p['notes']) ?>" style="color:#6a4f10;font-size:11px;cursor:default">✎</span><?php endif; ?><?php if (!empty($p['stars'])): ?> <span class="br-stars-cell"><?= str_repeat('★', (int)$p['stars']) ?></span><?php endif; ?></td>
                  <td>
                    <?php $sw = !empty($p['hex']) ? $p['hex'] : swatchColor($p['color']); ?>
                    <span class="paint-swatch" style="background:<?= e($sw) ?>" title="<?= e($sw) ?>"></span><?= e($p['color']) ?>
                  </td>
                  <td style="color:#7a6840;font-size:11px"><?= e($p['hue']) ?></td>
                  <td><?= layerBadge($p['layer']) ?></td>
                  <?php $stock = $p['stock'] ?? ''; ?>
                  <td>
                    <button type="button"
                      class="stock-btn<?= $stock ? ' stock-' . $stock : '' ?>"
                      data-pid="<?= $pid ?>"
                      data-stock="<?= e($stock) ?>"
                      onclick="toggleStock(this)"><?= $stock ? e($stock) : '&middot;' ?></button>
                  </td>
                  <td style="white-space:nowrap">
                    <button type="button" class="btn btn-sm"
                      data-pid="<?= $pid ?>"
                      data-brand="<?= e($p['brand']) ?>"
                      data-name="<?= e($p['name']) ?>"
                      data-color="<?= e($p['color']) ?>"
                      data-hue="<?= e($p['hue']) ?>"
                      data-layer="<?= e($p['layer']) ?>"
                      data-notes="<?= e($p['notes'] ?? '') ?>"
                      data-hex="<?= e($p['hex'] ?? '') ?>"
                      data-stars="<?= (int)($p['stars'] ?? 0) ?>"
                      onclick="openPaintEdit(this)">Edit</button>
                  </td>
                  <td>
                    <form method="post" onsubmit="return confirm('Delete this paint?')" style="margin:0">
                      <input type="hidden" name="action" value="delete_paint">
                      <input type="hidden" name="paint_id" value="<?= $pid ?>">
                      <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div id="paintVisibleCount" style="font-size:11px;color:#4a3a1a;margin-top:6px;font-family:'Cinzel',serif;letter-spacing:.04em"></div>
      <?php endif; ?>
