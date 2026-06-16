      <?php if ($editModel): ?>
        <h2 id="section-gallery">Edit: <?= e($editModel['name']) ?></h2>
      <?php else: ?>
        <h2 id="section-gallery" style="margin-top:40px">Add a Scheme</h2>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <?php if ($editModel): ?>
          <input type="hidden" name="action" value="edit_model">
          <input type="hidden" name="model_id" value="<?= e($editModel['id']) ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="add_model">
        <?php endif; ?>

        <div class="form-grid">
          <div>
            <label for="model_name">Model Name *</label>
            <input type="text" id="model_name" name="model_name"
              value="<?= $editModel ? e($editModel['name']) : '' ?>"
              placeholder="e.g. Ultramarines Sergeant" required>
          </div>
          <div>
            <label for="faction">Faction / Army</label>
            <input type="text" id="faction" name="faction"
              value="<?= $editModel ? e($editModel['faction'] ?? '') : '' ?>"
              placeholder="e.g. Blood Angels">
          </div>
          <div>
            <label for="sub_faction">Unit / Sub-faction</label>
            <input type="text" id="sub_faction" name="sub_faction"
              value="<?= $editModel ? e($editModel['sub_faction'] ?? '') : '' ?>"
              placeholder="e.g. Death Company, Shoota Boyz">
          </div>
          <div>
            <label for="sys_game">Game System</label>
            <select id="sys_game" name="system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
              <option value="">- none -</option>
              <?php foreach (['40k' => 'Warhammer 40,000', '30k / HH' => 'Horus Heresy / 30k', 'AoS' => 'Age of Sigmar', 'Old World' => 'The Old World', 'Kill Team' => 'Kill Team', 'Blood Bowl' => 'Blood Bowl', 'Necromunda' => 'Necromunda', 'Epic' => 'Epic Scale', 'OPR' => 'One Page Rules', 'Other' => 'Other'] as $sv => $sl): ?>
                <option value="<?= e($sv) ?>" <?= ($editModel['system'] ?? '') === $sv ? ' selected' : '' ?>><?= e($sl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="date">Date Completed</label>
            <input type="date" id="date" name="date"
              value="<?= e($editModel ? ($editModel['date'] ?? '') : date('Y-m-d')) ?>">
          </div>
          <div>
            <label for="model_count">Models Painted</label>
            <input type="number" id="model_count" name="model_count" min="1" step="1"
              value="<?= e($editModel ? (int)($editModel['count'] ?? 1) : 1) ?>"
              title="How many miniatures were painted under this scheme (e.g. 20 for a full Boyz mob)">
          </div>
          <div class="form-full">
            <label for="description">Notes / Description</label>
            <textarea id="description" name="description"
              placeholder="Techniques used, basing, conversions…"><?= $editModel ? e($editModel['description'] ?? '') : '' ?></textarea>
            <small style="display:block;margin-top:5px;color:#4a3a1a;font-size:10px;line-height:1.6">
              <strong style="color:#6a5020;letter-spacing:.04em">Format:</strong>
              ALL CAPS line = section header &nbsp;&middot;&nbsp;
              <code style="background:#1a1408;padding:1px 4px;border-radius:2px">- Label: value</code> = step row &nbsp;&middot;&nbsp;
              <code style="background:#1a1408;padding:1px 4px;border-radius:2px">&nbsp;&nbsp;- item</code> = sub-bullet (2 spaces)<br>
              <span style="color:#3a2e10">e.g. &nbsp;BASE / FLESH / ARMOUR (Red) / OSL (Glow) &nbsp;&rarr;&nbsp; - Prime: Black &nbsp;&rarr;&nbsp; - Base: Mephiston Red</span>
            </small>
          </div>
          <div>
            <label for="summary_finish">Finish</label>
            <input type="text" id="summary_finish" name="summary_finish"
              placeholder="e.g. Worn, field-used"
              value="<?= e($editModel ? ($editModel['summary']['finish'] ?? '') : '') ?>">
          </div>
          <div>
            <label for="summary_primary">Primary</label>
            <input type="text" id="summary_primary" name="summary_primary"
              placeholder="e.g. Muted green over dark base"
              value="<?= e($editModel ? ($editModel['summary']['primary'] ?? '') : '') ?>">
          </div>
          <div>
            <label for="summary_contrast">Contrast</label>
            <input type="text" id="summary_contrast" name="summary_contrast"
              placeholder="e.g. Grey/red camo, dark tracks"
              value="<?= e($editModel ? ($editModel['summary']['contrast'] ?? '') : '') ?>">
          </div>
          <div>
            <label for="summary_technique">Technique Bias</label>
            <input type="text" id="summary_technique" name="summary_technique"
              placeholder="e.g. Sponge texture, oil wash, pigments"
              value="<?= e($editModel ? ($editModel['summary']['technique'] ?? '') : '') ?>">
          </div>
          <div>
            <label for="theme_hex">Card Stripe Colour</label>
            <div style="display:flex;gap:6px;align-items:center">
              <input type="color" id="theme_hex_picker" style="width:42px;height:30px;padding:0;border:1px solid #2a2010;background:#130f08;cursor:pointer;border-radius:3px"
                value="<?= e($editModel ? ($editModel['theme_hex'] ?? '#000000') : '#000000') ?>"
                oninput="document.getElementById('theme_hex').value = this.value">
              <input type="text" id="theme_hex" name="theme_hex"
                placeholder="e.g. #a02020 (leave blank to hide stripe)"
                pattern="^#[0-9a-fA-F]{6}$" maxlength="7" style="flex:1"
                value="<?= e($editModel ? ($editModel['theme_hex'] ?? '') : '') ?>"
                oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('theme_hex_picker').value = this.value.toLowerCase()">
            </div>
          </div>
          <div>
            <label for="codex_source">Codex Reference</label>
            <select id="codex_source" name="codex_source">
              <option value="">- none -</option>
              <?php foreach ($codexOptions as $opt): $sel = ($editModel && ($editModel['codex_source'] ?? '') === $opt['value']) ? ' selected' : ''; ?>
                <option value="<?= e($opt['value']) ?>" <?= $sel ?>><?= e($opt['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Images -->
          <div class="form-full">
            <label>Photos (up to 4)</label>
            <div class="image-slots">
              <?php for ($i = 1; $i <= 4; $i++):
                $slotImg = $editModel ? ($editModel['images'][$i - 1] ?? null) : null; ?>
                <div class="image-slot">
                  <label>Image <?= $i ?></label>
                  <?php if ($slotImg): ?>
                    <div class="slot-preview" id="preview_<?= $i ?>">
                      <img src="<?= e($slotImg) ?>" alt="">
                      <button type="button" class="slot-delete-btn" onclick="clearSlot(<?= $i ?>)">&times;</button>
                    </div>
                    <div class="slot-cleared" id="cleared_<?= $i ?>" style="display:none">Will be removed</div>
                    <input type="hidden" name="delete_img_<?= $i ?>" id="delete_img_<?= $i ?>" value="0">
                  <?php endif; ?>
                  <input type="file" name="image<?= $i ?>" accept="image/*" id="file_<?= $i ?>" <?= $slotImg ? ' style="display:none"' : '' ?>>
                  <?php if ($slotImg): ?>
                    <div style="font-size:10px;color:#4a3a1a;margin-top:2px" id="keep_hint_<?= $i ?>">Leave blank to keep</div>
                  <?php endif; ?>
                </div>
              <?php endfor; ?>
            </div>
          </div>

          <!-- Colors -->
          <div class="form-full">
            <label>Colours Used</label>
            <input type="text" class="color-search" id="colorSearch" placeholder="Filter paints…" autocomplete="off">
            <div class="color-list" id="colorList"></div>
            <div class="selected-colors" id="selectedCount">0 colours selected</div>
            <div id="colorInputs"></div>
          </div>

          <?php if ($hasRecipes && $recipesData): ?>
            <div class="form-full">
              <label>Recipes (optional)</label>
              <?php $editRecipes = $editModel['recipes'] ?? []; ?>
              <div class="rc-pill-picker" id="galleryRecipePicker" data-form="gallery">
                <?php foreach ($recipesData as $rc): ?>
                  <span class="rc-pill<?= in_array($rc['id'], $editRecipes, true) ? ' selected' : '' ?>" data-id="<?= e($rc['id']) ?>">
                    <?= e($rc['name']) ?><?php if (!empty($rc['category'])): ?> <small>(<?= e($rc['category']) ?>)</small><?php endif; ?>
                  </span>
                <?php endforeach; ?>
              </div>
              <div id="galleryRecipeInputs">
                <?php foreach ($editRecipes as $rid): ?>
                  <input type="hidden" name="gallery_recipes[]" value="<?= e($rid) ?>">
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div style="margin-top:20px;display:flex;gap:10px;align-items:center">
          <button type="submit" class="btn"><?= $editModel ? 'Save Changes' : 'Add to Gallery' ?></button>
          <?php if ($editModel): ?>
            <a href="<?= ADMIN_FILENAME ?>" class="btn btn-sm" style="text-decoration:none">Cancel</a>
          <?php endif; ?>
        </div>
      </form>

      <?php if ($models):
        $featuredCount  = count(array_filter($models, fn($m) => !empty($m['featured'])));
        $showcaseImgCount = array_sum(array_map(fn($m) => is_array($m['featured'] ?? null) ? count($m['featured']) : (!empty($m['featured']) ? 1 : 0), $models)); ?>
        <h2 id="section-entries" style="margin-top:40px">Edit Scheme
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($models) ?> entr<?= count($models) !== 1 ? 'ies' : 'y' ?></span>
          <?php if ($featuredCount > 0): ?>
            <span style="color:#6a4f10;font-size:.7em;font-weight:400;letter-spacing:.04em">&nbsp;·&nbsp;★ <?= $showcaseImgCount ?> photo<?= $showcaseImgCount !== 1 ? 's' : '' ?> across <?= $featuredCount ?> scheme<?= $featuredCount !== 1 ? 's' : '' ?></span>
          <?php endif; ?>
          <?php if (defined('SHOWCASE_PUBLIC') && SHOWCASE_PUBLIC): ?>
            <a href="showcase.php" target="_blank" style="font-size:.65em;font-weight:400;color:#6a4f10;text-decoration:none;letter-spacing:.04em;margin-left:10px">View Showcase →</a>
          <?php endif; ?>
        </h2>
        <?php if ($hasRecipes): ?>
          <form method="post" style="margin-bottom:14px" onsubmit="return confirm('Remove paints from scheme color lists that are already covered by a linked recipe? This cannot be undone.')">
            <input type="hidden" name="action" value="cleanup_scheme_colors">
            <button type="submit" class="btn btn-sm">Clean up duplicate recipe colors</button>
            <span style="font-size:11px;color:#5a4a28;margin-left:8px">Removes paints from scheme color lists that are already in a linked recipe</span>
          </form>
        <?php endif; ?>
        <div class="model-list">
          <?php $displayModels = $models; usort($displayModels, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? '')); foreach ($displayModels as $m): ?>
            <div class="model-row">
              <?php if (!empty($m['images'][0])): ?>
                <img class="model-row-thumb" src="<?= e($m['images'][0]) ?>" alt="">
              <?php else: ?>
                <div class="model-row-thumb" style="display:flex;align-items:center;justify-content:center;color:#2a2010;font-size:10px">-</div>
              <?php endif; ?>
              <div class="model-row-info">
                <div class="model-row-name">
                  <?= e($m['name']) ?>
                  <?php $cnt = max(1, (int)($m['count'] ?? 1));
                  if ($cnt > 1): ?>
                    <span class="model-count-badge" title="<?= $cnt ?> miniatures painted under this scheme">&times;<?= $cnt ?></span>
                  <?php endif; ?>
                </div>
                <div class="model-row-meta">
                  <?= e($m['faction'] ?? '') ?>
                  <?php if (!empty($m['faction']) && !empty($m['date'])): ?> - <?php endif; ?>
                  <?= e($m['date'] ?? '') ?>
                  &nbsp;&nbsp;<?= count($m['colors'] ?? []) ?> colour<?= count($m['colors'] ?? []) !== 1 ? 's' : '' ?>
                  &nbsp;&nbsp;<?= count($m['images'] ?? []) ?> image<?= count($m['images'] ?? []) !== 1 ? 's' : '' ?>
                </div>
                <?php if (!empty($m['sessions'])): ?>
                  <div class="model-sessions">
                    <?php foreach ($m['sessions'] as $si => $s): ?>
                      <span class="ms-row"><span class="ms-date"><?= e($s['date']) ?></span><span class="ms-count">&times;<?= (int)$s['count'] ?></span><?php if (!empty($s['note'])): ?><span class="ms-note"><?= e($s['note']) ?></span><?php endif; ?><button type="button" class="ms-btn" data-mid="<?= e($m['id']) ?>" data-mname="<?= e($m['name']) ?>" data-idx="<?= $si ?>" data-date="<?= e($s['date']) ?>" data-count="<?= (int)$s['count'] ?>" data-note="<?= e($s['note'] ?? '') ?>" onclick="openGallerySessionEdit(this)">edit</button><button type="button" class="ms-btn ms-del" data-mid="<?= e($m['id']) ?>" data-idx="<?= $si ?>" onclick="deleteGallerySession(this)">del</button></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
              <button type="button" class="btn btn-sm"
                data-mid="<?= e($m['id'] ?? '') ?>"
                data-mname="<?= e($m['name'] ?? '') ?>"
                onclick="openGallerySessionModal(this)">+ Log</button>
              <?php
                $featuredArr = is_array($m['featured'] ?? null) ? $m['featured'] : (!empty($m['featured']) ? [0] : []);
                $isFeatured  = !empty($featuredArr);
                $imgList     = array_values(array_filter($m['images'] ?? []));
              ?>
              <button type="button" class="btn btn-sm mo-feature-btn<?= $isFeatured ? ' mo-feature-active' : '' ?>"
                data-id="<?= e($m['id'] ?? '') ?>"
                title="<?= $isFeatured ? 'Remove from showcase' : 'Add to showcase' ?>">★</button>
              <?php if (count($imgList) > 1): ?>
                <span class="sc-img-picks<?= $isFeatured ? '' : ' sc-img-picks-hidden' ?>"
                      data-mid="<?= e($m['id'] ?? '') ?>">
                  <?php foreach ($imgList as $imgIdx => $imgSrc): ?>
                    <button type="button"
                      class="sc-img-pick<?= in_array($imgIdx, $featuredArr, true) ? ' sc-img-pick-active' : '' ?>"
                      data-id="<?= e($m['id'] ?? '') ?>"
                      data-idx="<?= $imgIdx ?>"
                      title="<?= in_array($imgIdx, $featuredArr, true) ? 'Remove photo ' . ($imgIdx+1) . ' from showcase' : 'Add photo ' . ($imgIdx+1) . ' to showcase' ?>">
                      <img src="<?= e($imgSrc) ?>" alt="<?= $imgIdx + 1 ?>">
                    </button>
                  <?php endforeach; ?>
                </span>
              <?php endif; ?>
              <a href="<?= ADMIN_FILENAME ?>?edit=<?= e($m['id'] ?? '') ?>" class="btn btn-sm" style="text-decoration:none;<?= ($editModel && ($editModel['id'] ?? '') === ($m['id'] ?? '')) ? 'border-color:#c9a227;' : '' ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this entry?')">
                <input type="hidden" name="action" value="delete_model">
                <input type="hidden" name="model_id" value="<?= e($m['id'] ?? '') ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
