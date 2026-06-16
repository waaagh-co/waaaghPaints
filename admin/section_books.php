      <h2 id="section-books" style="margin-top:40px">Codex Library
        <?php if ($hasBooks): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($booksData) ?> cod<?= count($booksData) !== 1 ? 'ices' : 'ex' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasBooks): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Your Codex Library is not active yet. Click below to start - it won't appear on the main site until enabled here.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_books_file">
          <button type="submit" class="btn btn-sm">Start Codex Library</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openBookAdd()">+ Add Codex</button>
        </div>

        <div class="paint-form-wrap" id="bookFormWrap" style="display:none">
          <div class="paint-form-title" id="bookFormTitle">Add Codex</div>
          <form method="post" id="bookForm">
            <input type="hidden" name="action" value="add_book" id="bookAction">
            <input type="hidden" name="bk_id" id="bkId" value="">
            <div class="form-grid">
              <div>
                <label for="bk_type">Type</label>
                <select id="bk_type" name="bk_type" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="codex">Codex / Army Book</option>
                  <option value="supplement">Supplement / Campaign</option>
                </select>
              </div>
              <div>
                <label for="bk_faction">Faction / Legion</label>
                <input type="text" id="bk_faction" name="bk_faction" placeholder="e.g. Death Guard">
              </div>
              <div class="form-full">
                <label for="bk_title">Title *</label>
                <input type="text" id="bk_title" name="bk_title" required placeholder="e.g. Codex: Death Guard">
              </div>
              <div>
                <label for="bk_author">Publisher / Credit</label>
                <input type="text" id="bk_author" name="bk_author" placeholder="e.g. Games Workshop">
              </div>
              <div>
                <label for="bk_series">Edition</label>
                <input type="text" id="bk_series" name="bk_series" placeholder="e.g. 10th Edition">
              </div>
              <div class="form-full">
                <label for="bk_notes">Notes</label>
                <textarea id="bk_notes" name="bk_notes" rows="4"
                  placeholder="Paint schemes, lore notes, page references…"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="bookSubmitBtn">Add Codex</button>
              <button type="button" class="btn btn-sm" id="bookCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($booksData): ?>
          <div class="model-list">
            <?php foreach ($booksData as $bk): ?>
              <?php
              $bkType = $bk['type'] ?? 'codex';
              $bkPrev = !empty($bk['notes']) ? mb_substr($bk['notes'], 0, 80) . (mb_strlen($bk['notes']) > 80 ? '…' : '') : '';
              ?>
              <div class="model-row">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <?= e($bk['title']) ?>
                    <span style="font-size:9px;background:#0e1a1a;color:#70c8c8;border:1px solid #1a3a3a;border-radius:2px;padding:1px 5px;font-family:'Cinzel',serif;letter-spacing:.06em;text-transform:uppercase"><?= $bkType === 'supplement' ? 'Supplement' : 'Codex' ?></span>
                    <?php if (!empty($bk['faction'])): ?>
                      <span style="font-size:9px;background:#1a0a06;color:#8a6a3a;border:1px solid #3a2010;border-radius:2px;padding:1px 5px;font-family:'Cinzel',serif;letter-spacing:.05em"><?= e($bk['faction']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="model-row-meta">
                    <?php if (!empty($bk['series'])): ?><em><?= e($bk['series']) ?></em><?php endif; ?>
                    <?php if (!empty($bk['series']) && !empty($bk['author'])): ?> &middot; <?php endif; ?>
                    <?php if (!empty($bk['author'])): ?><?= e($bk['author']) ?><?php endif; ?>
                  </div>
                  <?php if ($bkPrev): ?>
                    <div class="wd-notes-preview"><?= e($bkPrev) ?></div>
                  <?php endif; ?>
                </div>
                <button class="btn btn-sm"
                  data-id="<?= e($bk['id']) ?>"
                  data-type="<?= e($bkType) ?>"
                  data-title="<?= e($bk['title']) ?>"
                  data-author="<?= e($bk['author'] ?? '') ?>"
                  data-series="<?= e($bk['series'] ?? '') ?>"
                  data-faction="<?= e($bk['faction'] ?? '') ?>"
                  data-notes="<?= e($bk['notes'] ?? '') ?>"
                  onclick="openBookEdit(this)">Edit</button>
                <form method="post" onsubmit="return confirm('Delete &quot;<?= e($bk['title']) ?>&quot;?')" style="margin:0">
                  <input type="hidden" name="action" value="delete_book">
                  <input type="hidden" name="bk_id" value="<?= e($bk['id']) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No codices logged yet.</p>
        <?php endif; ?>
      <?php endif; ?>
