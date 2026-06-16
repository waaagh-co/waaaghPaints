      <h2 id="section-wishlist" class="collapsible" style="margin-top:40px">Hobby Wishlist
        <?php if ($hasWishlist): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($wishlistData) ?> item<?= count($wishlistData) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </h2>
      <?php if (!$hasWishlist): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">Track everything you want to acquire — paints, kits, brushes, codices, back issues. Start the wishlist to enable it on the main site.</p>
        <form method="post">
          <input type="hidden" name="action" value="create_wishlist_file">
          <button type="submit" class="btn btn-sm">Start Wishlist</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <button type="button" class="btn btn-sm" onclick="openWishlistAdd()">+ Add Item</button>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="seed_wishlist_from_planned">
            <button type="submit" class="btn btn-sm" title="Adds missing/unowned paints from all Planned schemes">Seed from Planned</button>
          </form>
        </div>
        <div class="paint-form-wrap" id="wishlistFormWrap" style="display:none">
          <div class="paint-form-title" id="wishlistFormTitle">Add Item</div>
          <form method="post" id="wishlistForm">
            <input type="hidden" name="action" id="wishlistAction" value="add_wishlist_item">
            <input type="hidden" name="wl_id" id="wlId" value="">
            <div class="form-grid">
              <div>
                <label for="wl_type">Type *</label>
                <select id="wl_type" name="wl_type" onchange="wishlistTypeChange()" style="width:160px">
                  <option value="paint">Paint</option>
                  <option value="model">Model / Kit</option>
                  <option value="brush">Brush</option>
                  <option value="codex">Codex / Book</option>
                </select>
              </div>
              <div>
                <label for="wl_priority">Priority</label>
                <select id="wl_priority" name="wl_priority" style="width:120px">
                  <option value="high">High</option>
                  <option value="medium" selected>Medium</option>
                  <option value="low">Low</option>
                </select>
              </div>
              <div class="form-full">
                <label for="wl_name" id="wl_name_label">Paint Name *</label>
                <input type="text" id="wl_name" name="wl_name" required placeholder="e.g. Ironjawz Yellow" style="width:100%;max-width:360px">
              </div>
              <div id="wl_brand_row">
                <label for="wl_brand">Brand</label>
                <input type="text" id="wl_brand" name="wl_brand" list="wl_brandList" placeholder="e.g. Citadel" style="width:180px">
                <datalist id="wl_brandList">
                  <option value="Citadel">
                  <option value="Pro Acryl">
                  <option value="Vallejo">
                  <option value="Army Painter">
                  <option value="Gamblin Artist Oils">
                  <option value="AK Interactive">
                  <option value="Scale75">
                  <option value="Two Thin Coats">
                  <option value="Artis Opus">
                  <option value="Rosemary &amp; Co">
                </datalist>
              </div>
              <div id="wl_faction_row" style="display:none">
                <label for="wl_faction">Faction / Army</label>
                <input type="text" id="wl_faction" name="wl_faction" placeholder="e.g. Death Guard" style="width:180px">
              </div>
              <div id="wl_system_row" style="display:none">
                <label for="wl_system">Game System</label>
                <select id="wl_system" name="wl_system" style="width:160px">
                  <option value="">— none —</option>
                  <option value="40k">Warhammer 40,000</option>
                  <option value="30k / HH">Horus Heresy</option>
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
              <div class="form-full">
                <label for="wl_url">Product URL <span style="font-weight:normal;opacity:.6">(optional)</span></label>
                <input type="url" id="wl_url" name="wl_url" placeholder="https://www.games-workshop.com/..." style="width:100%;max-width:480px">
              </div>
              <div class="form-full">
                <label for="wl_notes">Notes <span style="font-weight:normal;opacity:.6">(optional)</span></label>
                <input type="text" id="wl_notes" name="wl_notes" placeholder="e.g. for Ork boyz skin" style="width:100%;max-width:480px">
              </div>
              <div class="form-full">
                <label for="wl_ordered_date">Order Date <span style="font-weight:normal;opacity:.6">(optional - set when you place the order)</span></label>
                <input type="date" id="wl_ordered_date" name="wl_ordered_date" max="<?= date('Y-m-d') ?>" style="width:180px">
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="wishlistSubmitBtn">Add Item</button>
              <button type="button" class="btn btn-sm" id="wishlistCancelBtn" onclick="cancelWishlistEdit()">Cancel</button>
            </div>
          </form>
        </div>
        <?php if ($wishlistData): ?>
          <?php
          $wTypeLabels = ['paint' => 'Paint', 'model' => 'Model', 'brush' => 'Brush', 'codex' => 'Codex', 'wd' => 'WD'];
          $wTypeColors = ['paint' => '#1a4a4a', 'model' => '#1a3a1a', 'brush' => '#3a1a10', 'codex' => '#2a1a4a', 'wd' => '#3a2a08'];
          $wPriColors  = ['high' => ['bg' => 'rgba(239,68,68,.18)', 'txt' => '#ef4444'], 'medium' => ['bg' => 'rgba(249,115,22,.14)', 'txt' => '#f97316'], 'low' => ['bg' => 'rgba(80,80,80,.2)', 'txt' => '#7a7a7a']];
          ?>
          <div class="model-list">
            <?php foreach ($wishlistData as $w): ?>
              <?php
              $wtype     = $w['type'] ?? 'paint';
              $wpri      = $w['priority'] ?? 'medium';
              $typeColor = $wTypeColors[$wtype] ?? '#1a4a4a';
              $typeLabel = $wTypeLabels[$wtype]  ?? 'Item';
              $priC      = $wPriColors[$wpri] ?? $wPriColors['medium'];
              ?>
              <div class="model-row" id="wish-row-<?= e($w['id']) ?>" style="border-left:3px solid <?= $typeColor ?>">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:<?= $typeColor ?>;color:#c9a227;font-family:'Cinzel',serif;letter-spacing:.04em"><?= $typeLabel ?></span>
                    <span style="font-size:10px;padding:2px 7px;border-radius:3px;background:<?= $priC['bg'] ?>;color:<?= $priC['txt'] ?>;font-family:'Cinzel',serif;letter-spacing:.04em"><?= ucfirst($wpri) ?></span>
                    <strong><?= e($w['name']) ?></strong>
                  </div>
                  <div class="model-row-meta">
                    <?php $meta = array_filter([e($w['brand'] ?? ''), e($w['faction'] ?? ''), e($w['system'] ?? '')]);
                    echo implode(' &middot; ', $meta); ?>
                    <?php if (!empty($w['added'])): ?><span style="margin-left:8px;opacity:.5;font-size:10px">added <?= e($w['added']) ?></span><?php endif; ?>
                  </div>
                  <?php if (!empty($w['notes'])): ?><div style="font-size:11px;color:#5a4a28;margin-top:2px"><?= e(mb_substr($w['notes'], 0, 120)) ?></div><?php endif; ?>
                  <?php if (!empty($w['url'])): ?><div style="margin-top:3px"><a href="<?= e($w['url']) ?>" target="_blank" rel="noopener" style="font-size:11px;color:#6a8a6a;text-decoration:none" title="<?= e($w['url']) ?>">&#128279; Link</a></div><?php endif; ?>
                </div>
                <div class="model-row-actions">
                  <?php if (empty($w['ordered_date'])): ?>
                    <button type="button" class="btn btn-sm btn-ordered" onclick="markOrdered('<?= e($w['id']) ?>')" style="font-size:10px">Mark Ordered</button>
                  <?php else: ?>
                    <span class="wish-ordered-badge" style="display:inline-block">Ordered <?= e($w['ordered_date']) ?></span>
                    <button type="button" class="btn-ordered-clear" onclick="clearOrdered('<?= e($w['id']) ?>')">Clear</button>
                  <?php endif; ?>
                  <?php if ($wtype === 'model' && empty($w['promoted_to'])): ?>
                    <button type="button" class="btn btn-sm" onclick="promoteWishlist('<?= e($w['id']) ?>')" style="font-size:10px">&rarr; Shame</button>
                  <?php elseif (!empty($w['promoted_to'])): ?>
                    <span style="font-size:10px;color:#c9a227;font-family:'Cinzel',serif">Promoted &rarr; <?= ucfirst(e($w['promoted_to'])) ?></span>
                  <?php endif; ?>
                  <button type="button" class="btn btn-sm" onclick="openWishlistEdit(this)"
                    data-id="<?= e($w['id']) ?>"
                    data-type="<?= e($wtype) ?>"
                    data-name="<?= e($w['name']) ?>"
                    data-brand="<?= e($w['brand'] ?? '') ?>"
                    data-faction="<?= e($w['faction'] ?? '') ?>"
                    data-system="<?= e($w['system'] ?? '') ?>"
                    data-priority="<?= e($wpri) ?>"
                    data-notes="<?= e($w['notes'] ?? '') ?>"
                    data-url="<?= e($w['url'] ?? '') ?>"
                    data-ordered-date="<?= e($w['ordered_date'] ?? '') ?>">&#10000; Edit</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Remove from wishlist?')">
                    <input type="hidden" name="action" value="delete_wishlist_item">
                    <input type="hidden" name="wl_id" value="<?= e($w['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger" title="Remove">&times;</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No items yet. Add anything you want to acquire.</p>
        <?php endif; ?>
      <?php endif; ?>
