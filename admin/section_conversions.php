      <h2 id="section-conversions" style="margin-top:40px">Conversion Chart
        <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($convRows) ?> rows</span>
      </h2>

      <?php
      $mDot = fn(string $q): string => match ($q) {
        'near identical' => '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#c9a227;margin-right:4px;vertical-align:middle"></span>',
        'usable'         => '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#6a4e14;margin-right:4px;vertical-align:middle"></span>',
        'avoid'          => '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#7a2020;margin-right:4px;vertical-align:middle"></span>',
        default          => '',
      };
      ?>

      <div class="paint-form-wrap" id="conv-form-wrap">
        <div class="paint-form-title" id="conv-form-title">Add Row</div>
        <form method="post" id="conv-form">
          <input type="hidden" name="action" value="add_conversion" id="conv-action">
          <input type="hidden" name="cv_orig" id="cv_orig" value="">
          <div class="form-grid">
            <div class="form-full">
              <label for="cv_citadel">Citadel Paint *</label>
              <input type="text" id="cv_citadel" name="cv_citadel" placeholder="e.g. Mephiston Red" required>
            </div>
            <div>
              <label for="cv_vallejo">Vallejo</label>
              <input type="text" id="cv_vallejo" name="cv_vallejo" placeholder="Paint name or leave blank">
            </div>
            <div>
              <label for="cv_val_q">Vallejo Match</label>
              <select id="cv_val_q" name="cv_val_q" class="form-select">
                <option value="">- no rating</option>
                <option value="near identical">Near Identical</option>
                <option value="usable">Usable</option>
                <option value="avoid">Avoid</option>
              </select>
            </div>
            <div>
              <label for="cv_pa">Pro Acryl</label>
              <input type="text" id="cv_pa" name="cv_pa" placeholder="Paint name or leave blank">
            </div>
            <div>
              <label for="cv_pa_q">Pro Acryl Match</label>
              <select id="cv_pa_q" name="cv_pa_q" class="form-select">
                <option value="">- no rating</option>
                <option value="near identical">Near Identical</option>
                <option value="usable">Usable</option>
                <option value="avoid">Avoid</option>
              </select>
            </div>
            <div>
              <label for="cv_ttc">Two Thin Coats</label>
              <input type="text" id="cv_ttc" name="cv_ttc" placeholder="Paint name or leave blank">
            </div>
            <div>
              <label for="cv_ttc_q">Two Thin Coats Match</label>
              <select id="cv_ttc_q" name="cv_ttc_q" class="form-select">
                <option value="">- no rating</option>
                <option value="near identical">Near Identical</option>
                <option value="usable">Usable</option>
                <option value="avoid">Avoid</option>
              </select>
            </div>
          </div>
          <div style="margin-top:14px;display:flex;gap:10px">
            <button type="submit" class="btn btn-sm" id="conv-save-btn">Add Row</button>
            <button type="button" class="btn btn-sm" id="conv-cancel-btn" style="display:none;color:#6a5a30;border-color:#2a2010" onclick="convCancelEdit()">Cancel</button>
          </div>
        </form>
      </div>

      <div class="paint-toolbar">
        <input type="text" id="conv-search" placeholder="Search Citadel, Vallejo, Pro Acryl, Two Thin Coats&hellip;" autocomplete="off">
        <span id="conv-count" style="font-size:12px;color:#4a3a1a;font-family:'Cinzel',serif;white-space:nowrap;letter-spacing:.04em"></span>
      </div>

      <div class="paint-table-wrap" style="max-height:560px">
        <table class="paint-table" id="conv-table">
          <thead>
            <tr>
              <th style="width:24%">Citadel</th>
              <th style="width:22%">Vallejo</th>
              <th style="width:22%">Pro Acryl</th>
              <th style="width:22%">Two Thin Coats</th>
              <th style="width:10%"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($convRows as $r):
              $showVal = ($r[1] !== '' && $r[1] !== '-');
              $showPa  = ($r[2] !== '' && $r[2] !== '-');
              $showTtc = ($r[3] !== '' && $r[3] !== '-');
            ?>
              <tr class="conv-row"
                data-cit="<?= e($r[0]) ?>"
                data-val="<?= e($r[1]) ?>"
                data-pa="<?= e($r[2]) ?>"
                data-ttc="<?= e($r[3]) ?>"
                data-val-q="<?= e($r[4]) ?>"
                data-pa-q="<?= e($r[5]) ?>"
                data-ttc-q="<?= e($r[6]) ?>">
                <td><?= e($r[0]) ?></td>
                <td><?= $showVal ? $mDot($r[4]) . e($r[1]) : '<span style="color:#2a2010">-</span>' ?></td>
                <td><?= $showPa  ? $mDot($r[5]) . e($r[2]) : '<span style="color:#2a2010">-</span>' ?></td>
                <td><?= $showTtc ? $mDot($r[6]) . e($r[3]) : '<span style="color:#2a2010">-</span>' ?></td>
                <td style="white-space:nowrap">
                  <button class="btn btn-sm" onclick="convEdit(this)" style="padding:3px 8px" title="Edit">✎</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this row?')">
                    <input type="hidden" name="action" value="delete_conversion">
                    <input type="hidden" name="cv_orig" value="<?= e($r[0]) ?>">
                    <button type="submit" class="btn btn-sm btn-danger" style="padding:3px 7px">&times;</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
