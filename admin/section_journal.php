      <h2 id="section-journal" class="collapsible" style="margin-top:40px">Scrap Notes
        <?php if ($hasJournal): ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($journalData) ?> entr<?= count($journalData) !== 1 ? 'ies' : 'y' ?></span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasJournal): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Scrap Notes not active yet. Click below to start one - log sessions, discoveries, and hobby moments.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_journal_file">
          <button type="submit" class="btn btn-sm">Start Scrap Notes</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openJournalAdd()">+ Add Entry</button>
        </div>

        <div class="paint-form-wrap" id="journalFormWrap" style="display:none">
          <div class="paint-form-title" id="journalFormTitle">Add Journal Entry</div>
          <form method="post" id="journalForm">
            <input type="hidden" name="action" value="add_journal" id="journalAction">
            <input type="hidden" name="jn_id" id="jnId" value="">
            <div class="form-grid">
              <div>
                <label for="jn_date">Date *</label>
                <input type="date" id="jn_date" name="jn_date" required value="<?= date('Y-m-d') ?>">
              </div>
              <div>
                <label for="jn_mood">Mood</label>
                <select id="jn_mood" name="jn_mood" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">-</option>
                  <option value="great">Great</option>
                  <option value="good">Good</option>
                  <option value="okay">Okay</option>
                  <option value="rough">Rough</option>
                </select>
              </div>
              <div class="form-full">
                <label for="jn_title">Title (optional)</label>
                <input type="text" id="jn_title" name="jn_title" placeholder="e.g. Found a better wet-blending ratio">
              </div>
              <div class="form-full" style="position:relative">
                <label for="jn_body">Entry * <span style="color:#5a4a28;font-size:11px;font-family:inherit">- type @ to tag a scheme, recipe, or WD issue</span></label>
                <textarea id="jn_body" name="jn_body" rows="8" required
                  placeholder="What did you paint, discover, or think about today?"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
                <div id="jnMentionPicker" style="display:none;position:absolute;z-index:200;background:#0e0d09;border:1px solid #c9a227;border-radius:4px;min-width:260px;max-width:340px;box-shadow:0 4px 18px rgba(0,0,0,.7);overflow:hidden">
                  <div id="jnMentionList" style="max-height:220px;overflow-y:auto"></div>
                </div>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="journalSubmitBtn">Add Entry</button>
              <button type="button" class="btn btn-sm" id="journalCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($journalData): ?>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap">
            <input type="search" id="jn-admin-filter" placeholder="Filter by date or text&hellip;" autocomplete="off" oninput="filterJournalList(this.value)" style="background:#130f08;border:1px solid #2a1e08;color:#c4b49a;border-radius:4px;padding:5px 10px;font-size:12px;flex:1 1 200px;min-width:0">
            <span id="jn-admin-count" style="font-family:'Cinzel',serif;font-size:10px;color:#6a5a30;white-space:nowrap"><?= count($journalData) ?> entries</span>
          </div>
          <div class="model-list" id="jn-admin-list">
            <?php foreach ($journalData as $jn): ?>
              <?php
              $jnPrev    = !empty($jn['body']) ? mb_substr($jn['body'], 0, 100) . (mb_strlen($jn['body']) > 100 ? '…' : '') : '';
              $jnMood    = $jn['mood'] ?? '';
              $jnDateFmt = !empty($jn['date']) ? date('M j, Y', strtotime($jn['date'])) : '';
              $moodMap   = ['great' => ['#1c3a1c', '#7ad678'], 'good' => ['#1c2a1a', '#a0c878'], 'okay' => ['#3a2d10', '#e8b060'], 'rough' => ['#3a1c1c', '#e88080']];
              [$jnMoodBg, $jnMoodFg] = $moodMap[$jnMood] ?? ['#1c2a3a', '#7ab0e8'];
              $jnSearch  = ($jn['date'] ?? '') . ' ' . ($jn['title'] ?? '') . ' ' . ($jn['body'] ?? '');
              ?>
              <div class="model-row" data-jnsearch="<?= e($jnSearch) ?>">
                <div class="model-row-info">
                  <div class="model-row-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <?php if ($jnDateFmt): ?>
                      <span style="font-family:'Cinzel',serif;font-size:11px;color:#c9a227;letter-spacing:.05em"><?= e($jnDateFmt) ?></span>
                    <?php endif; ?>
                    <?php if ($jnMood): ?>
                      <span style="font-size:9px;font-weight:700;letter-spacing:.1em;padding:2px 6px;border-radius:2px;background:<?= $jnMoodBg ?>;color:<?= $jnMoodFg ?>;font-family:'Cinzel',serif;text-transform:uppercase"><?= ucfirst($jnMood) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($jn['title'])): ?>
                      <span style="font-size:12px;color:#a89868"><?= e($jn['title']) ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if ($jnPrev): ?>
                    <div class="wd-notes-preview"><?= e($jnPrev) ?></div>
                  <?php endif; ?>
                </div>
                <button class="btn btn-sm"
                  data-id="<?= e($jn['id']) ?>"
                  data-date="<?= e($jn['date'] ?? '') ?>"
                  data-title="<?= e($jn['title'] ?? '') ?>"
                  data-mood="<?= e($jnMood) ?>"
                  data-body="<?= e($jn['body'] ?? '') ?>"
                  onclick="openJournalEdit(this)">Edit</button>
                <form method="post" onsubmit="return confirm('Delete this journal entry?')" style="margin:0">
                  <input type="hidden" name="action" value="delete_journal">
                  <input type="hidden" name="jn_id" value="<?= e($jn['id']) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">No journal entries yet.</p>
        <?php endif; ?>
      <?php endif; ?>
