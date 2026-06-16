      <h2 id="section-recipes" style="margin-top:40px">Recipe Library
        <?php if ($hasRecipes): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($recipesData) ?> recipe<?= count($recipesData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasRecipes): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Author reusable technique recipes - "How I Paint Ork Flesh," "NMM Gold," "Blood Angels Red" - with ordered steps. Gallery, planned, and bench entries can reference them so you never re-describe the same technique twice.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_recipes_file">
          <button type="submit" class="btn btn-sm">Start Recipe Library</button>
        </form>
      <?php else: ?>
        <?php
        // Non-retired brushes for step picker (brushes with layout: brand · series · size)
        $brushOptions = [];
        if ($hasBrushes) {
          foreach ($brushesData as $br) {
            if (($br['condition'] ?? 'prime') === 'retired') continue;
            $brushOptions[] = ['id' => $br['id'], 'label' => trim(($br['brand'] ?? '') . ' ' . ($br['series'] ?? '') . ' ' . ($br['size'] ?? ''))];
          }
        }
        ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openRecipeAdd()">+ Add Recipe</button>
        </div>

        <datalist id="rc_paintList">
          <?php foreach ($paints as $p):
            $k = $p['brand'] . '|' . $p['name'] . '|' . ($p['layer'] ?? '');
          ?>
            <option value="<?= e($k) ?>"><?= e($p['brand'] . ' - ' . $p['name'] . ' (' . ($p['layer'] ?? '') . ')') ?></option>
          <?php endforeach; ?>
        </datalist>
        <datalist id="rc_brushList">
          <?php foreach ($brushOptions as $bo): ?>
            <option value="<?= e($bo['id']) ?>"><?= e($bo['label']) ?></option>
          <?php endforeach; ?>
        </datalist>

        <div class="paint-form-wrap" id="recipeFormWrap" style="display:none">
          <div class="paint-form-title" id="recipeFormTitle">Add Recipe</div>
          <form method="post" id="recipeForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_recipe" id="recipeAction">
            <input type="hidden" name="recipe_id" id="recipeId" value="">
            <div class="form-grid">
              <div>
                <label for="rc_name">Recipe Name *</label>
                <input type="text" id="rc_name" name="rc_name" required placeholder="e.g. Ork Skin Recipe">
              </div>
              <div>
                <label for="rc_category">Category</label>
                <input type="text" id="rc_category" name="rc_category" list="rc_catList" placeholder="e.g. Flesh">
                <datalist id="rc_catList">
                  <?php foreach (['Flesh', 'Metal', 'Cloth', 'Armour', 'Base', 'Leather', 'Bone', 'NMM', 'Skin', 'Cloak', 'Fur', 'Weapon', 'Eye', 'Wood', 'Stone', 'Gem', 'Fire', 'Glow'] as $c): ?>
                    <option value="<?= e($c) ?>">
                    <?php endforeach; ?>
                </datalist>
              </div>
              <div>
                <label for="rc_faction">Faction</label>
                <input type="text" id="rc_faction" name="rc_faction" placeholder="e.g. Orks (optional)">
              </div>
              <div class="form-full">
                <label for="rc_description">Description</label>
                <input type="text" id="rc_description" name="rc_description" placeholder="Short summary of what this recipe does">
              </div>
              <div class="form-full">
                <label>Steps</label>
                <div id="rc_steps"></div>
                <button type="button" class="btn btn-sm" onclick="addRecipeStep()" style="margin-top:6px">+ Add Step</button>
              </div>
              <div class="form-full">
                <label for="rc_notes">Notes</label>
                <textarea id="rc_notes" name="rc_notes" rows="2" placeholder="End-of-recipe freeform notes" style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
              <div class="form-full">
                <label>Reference Photo <span style="font-size:10px;color:#4a3a1a">(optional - finished result)</span></label>
                <div id="rc_image_preview" style="display:none;margin-bottom:6px">
                  <img id="rc_image_thumb" src="" alt="" style="height:80px;width:80px;object-fit:cover;border-radius:3px;border:1px solid #2a2010;display:block;margin-bottom:4px">
                  <label style="font-size:11px;color:#4a3a1a;display:flex;align-items:center;gap:5px;cursor:pointer">
                    <input type="checkbox" name="delete_rc_image" id="delete_rc_image" value="1" onchange="if(this.checked){document.getElementById('rc_image_preview').style.display='none'}"> Remove photo
                  </label>
                </div>
                <input type="file" name="rc_image" id="rc_image_file" accept="image/*">
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="recipeSubmitBtn">Add Recipe</button>
              <button type="button" class="btn btn-sm" id="recipeCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($recipesData): ?>
          <div class="model-list">
            <?php foreach ($recipesData as $rc):
              $stepCount = count($rc['steps'] ?? []);
            ?>
              <div class="model-row">
                <div class="model-row-info">
                  <div class="model-row-name"><?= e($rc['name']) ?></div>
                  <div class="model-row-meta">
                    <?php if (!empty($rc['category'])): ?><?= e($rc['category']) ?>&nbsp;&middot;&nbsp;<?php endif; ?>
                    <?php if (!empty($rc['faction'])): ?><?= e($rc['faction']) ?>&nbsp;&middot;&nbsp;<?php endif; ?>
                    <?= $stepCount ?> step<?= $stepCount !== 1 ? 's' : '' ?>
                  </div>
                </div>
                <button class="btn btn-sm"
                  data-id="<?= e($rc['id']) ?>"
                  data-name="<?= e($rc['name']) ?>"
                  data-category="<?= e($rc['category'] ?? '') ?>"
                  data-faction="<?= e($rc['faction'] ?? '') ?>"
                  data-description="<?= e($rc['description'] ?? '') ?>"
                  data-steps="<?= e(json_encode($rc['steps'] ?? [])) ?>"
                  data-notes="<?= e($rc['notes'] ?? '') ?>"
                  data-image="<?= e($rc['image'] ?? '') ?>"
                  onclick="openRecipeEdit(this)">Edit</button>
                <form method="post" onsubmit="return confirm('Delete this recipe? Any schemes referencing it will silently drop it.')" style="margin:0">
                  <input type="hidden" name="action" value="delete_recipe">
                  <input type="hidden" name="recipe_id" value="<?= e($rc['id']) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No recipes yet.</p>
        <?php endif; ?>
      <?php endif; ?>
