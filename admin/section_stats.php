      <h2 id="section-stats">Hobby Stats</h2>
      <p style="font-size:.72rem;color:#5a4828;margin:0 0 12px;font-style:italic;">Full intelligence dossier &rarr; <a href="index.php?tab=commissar" target="_blank" style="color:#7a6a3a;">The Commissar tab</a></p>
      <?php
      $ownedPaints = array_values(array_filter($paints, fn($p) => ($p['stock'] ?? '') !== 'wanted'));
      $wantedCount = count($paints) - count($ownedPaints);
      $lowOutCount = count(array_filter($paints, fn($p) => in_array($p['stock'] ?? '', ['low', 'out'], true)));
      $ownedKeys = array_flip(array_map(fn($p) => strtolower($p['brand'] . '|' . $p['name']), $ownedPaints));
      $missingPlanned = 0;
      $seenMissing = [];
      foreach ($planned as $scheme) {
        foreach ($scheme['colors'] ?? [] as $key) {
          $lc = strtolower($key);
          if (!isset($ownedKeys[$lc]) && !isset($seenMissing[$lc])) {
            $seenMissing[$lc] = true;
            $missingPlanned++;
          }
        }
      }

      $byBrand = [];
      foreach ($ownedPaints as $p) {
        $byBrand[$p['brand']] = ($byBrand[$p['brand']] ?? 0) + 1;
      }
      arsort($byBrand);
      $maxBrand = $byBrand ? max(array_values($byBrand)) : 1;

      $usageCount = [];
      foreach ($models as $m) {
        $mc = max(1, (int)($m['count'] ?? 1));
        foreach ($m['colors'] ?? [] as $c) {
          $usageCount[$c] = ($usageCount[$c] ?? 0) + $mc;
        }
      }
      arsort($usageCount);
      $topPaints = array_slice($usageCount, 0, 8, true);

      $byFaction = [];
      foreach ($models as $m) {
        if (!empty($m['faction'])) {
          $byFaction[$m['faction']] = ($byFaction[$m['faction']] ?? 0) + max(1, (int)($m['count'] ?? 1));
        }
      }
      arsort($byFaction);
      $noFaction = count(array_filter($models, fn($m) => empty($m['faction'])));

      $byYear = [];
      foreach ($models as $m) {
        $yr = !empty($m['date']) ? substr($m['date'], 0, 4) : 'Undated';
        $byYear[$yr] = ($byYear[$yr] ?? 0) + max(1, (int)($m['count'] ?? 1));
      }
      krsort($byYear);

      $sessionsByYear = [];
      foreach ($models as $m) {
        foreach ($m['sessions'] ?? [] as $s) {
          $yr = !empty($s['date']) ? substr($s['date'], 0, 4) : null;
          if ($yr) $sessionsByYear[$yr] = ($sessionsByYear[$yr] ?? 0) + max(1, (int)($s['count'] ?? 1));
        }
      }

      $totalSessions = 0; $totalMinutes = 0;
      foreach ($benchData as $b) {
        foreach ($b['sessions'] ?? [] as $s) {
          $totalSessions++;
          $totalMinutes += (int)($s['duration'] ?? 0);
        }
      }

      // Waaagh! Meter computation (same rolling 7-day logic as index.php)
      $_mWkAgo = date('Y-m-d', strtotime('-6 days'));
      $_mScore = 0;
      $_mModels = 0;
      foreach ($models as $_mm) {
        foreach ($_mm['sessions'] ?? [] as $_ms) {
          if (($_ms['date'] ?? '') >= $_mWkAgo) $_mModels += max(1, (int)($_ms['count'] ?? 1));
        }
      }
      if ($_mModels > 0) { $_mScore += 3; if ($_mModels >= 3) $_mScore += 1; }
      $_mBench = 0;
      if ($hasBench) {
        foreach ($benchData as $_mb) {
          foreach ($_mb['sessions'] ?? [] as $_bs) {
            if (($_bs['date'] ?? '') >= $_mWkAgo) $_mBench++;
          }
        }
      }
      if ($_mBench > 0) { $_mScore += 2; if ($_mBench >= 3) $_mScore += 1; }
      if ($hasJournal) {
        foreach ($journalData as $_mj) {
          if (($_mj['date'] ?? '') >= $_mWkAgo) { $_mScore += 1; break; }
        }
      }
      if ($hasBench && !empty($benchData)) {
        $_mActive = array_values(array_filter($benchData, fn($b) => ($b['stage'] ?? '') !== 'done'));
        if (!empty($_mActive)) {
          usort($_mActive, fn($a, $b) => strcmp($b['last_touched'] ?? '', $a['last_touched'] ?? ''));
          $_mTs = strtotime($_mActive[0]['last_touched'] ?? '');
          if ($_mTs) {
            $_mDays = floor((time() - $_mTs) / 86400);
            if      ($_mDays <= 1) $_mScore += 2;
            elseif  ($_mDays <= 3) $_mScore += 1;
            elseif  ($_mDays > 7)  $_mScore -= 1;
          }
        }
      }
      $adMeterScore = max(0, min(10, $_mScore));
      if      ($adMeterScore <= 1) { $adMeterState = "DOZIN'";         $adMeterClass = 'meter-s0'; }
      elseif  ($adMeterScore <= 3) { $adMeterState = "STIRRIN'";       $adMeterClass = 'meter-s1'; }
      elseif  ($adMeterScore <= 5) { $adMeterState = "ON DA WARPATH";  $adMeterClass = 'meter-s2'; }
      elseif  ($adMeterScore <= 7) { $adMeterState = "WAAAGH!";        $adMeterClass = 'meter-s3'; }
      else                          { $adMeterState = "FULL WAAAGH!!"; $adMeterClass = 'meter-s4'; }
      $_mNotes = 0; $_mBattles = 0;
      if ($hasJournal)  foreach ($journalData as $_mj2) { if (($_mj2['date']  ?? '') >= $_mWkAgo) $_mNotes++; }
      if ($hasBattles)  foreach ($battlesData as $_mbh) { if (($_mbh['date']  ?? '') >= $_mWkAgo) $_mBattles++; }
      $adWkParts = [];
      if ($_mModels  > 0) $adWkParts[] = $_mModels  . ' model'         . ($_mModels  !== 1 ? 's' : '') . ' painted';
      if ($_mBench   > 0) $adWkParts[] = $_mBench   . ' bench session' . ($_mBench   !== 1 ? 's' : '');
      if ($_mBattles > 0) $adWkParts[] = $_mBattles . ' battle'        . ($_mBattles !== 1 ? 's' : '') . ' fought';
      if ($_mNotes   > 0) $adWkParts[] = $_mNotes   . ' note'          . ($_mNotes   !== 1 ? 's' : '') . ' scribbled';
      $adWkActive = !empty($adWkParts);
      unset($_mWkAgo, $_mScore, $_mModels, $_mBench, $_mm, $_ms, $_mb, $_bs, $_mj, $_mActive, $_mTs, $_mDays, $_mNotes, $_mBattles, $_mj2, $_mbh);
      ?>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-num"><?= count($ownedPaints) ?></div>
          <div class="stat-label">Paints Owned</div>
        </div>
        <div class="stat-card">
          <div class="stat-num"><?= count($models) ?></div>
          <div class="stat-label">Recorded Schemes</div>
        </div>
        <?php
        $totalPainted = array_sum(array_map(fn($m) => max(1, (int)($m['count'] ?? 1)), $models));
        ?>
        <?php if ($totalPainted > count($models)): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $totalPainted ?></div>
            <div class="stat-label">Models Painted</div>
          </div>
        <?php endif; ?>
        <div class="stat-card">
          <div class="stat-num"><?= count($planned) ?></div>
          <div class="stat-label">Planned</div>
        </div>
        <?php if ($hasBooks): ?>
          <div class="stat-card">
            <div class="stat-num"><?= count($booksData) ?></div>
            <div class="stat-label">Codex Library</div>
          </div>
        <?php endif; ?>
        <?php if ($hasBrushes): ?>
          <div class="stat-card">
            <div class="stat-num"><?= count(array_filter($brushesData, fn($b) => ($b['condition'] ?? 'prime') !== 'retired')) ?></div>
            <div class="stat-label">Active Brushes</div>
          </div>
        <?php endif; ?>
        <?php if ($hasRecipes): ?>
          <div class="stat-card">
            <div class="stat-num"><?= count($recipesData) ?></div>
            <div class="stat-label">Recipes</div>
          </div>
        <?php endif; ?>
        <?php if ($totalSessions > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $totalSessions ?></div>
            <div class="stat-label">Sessions</div>
          </div>
        <?php endif; ?>
        <?php if ($totalMinutes > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= floor($totalMinutes / 60) ?>h <?= $totalMinutes % 60 ?>m</div>
            <div class="stat-label">Hobby Hours</div>
          </div>
        <?php endif; ?>
        <?php if ($wantedCount > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $wantedCount ?></div>
            <div class="stat-label">Wanted</div>
          </div>
        <?php endif; ?>
        <?php if ($lowOutCount > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $lowOutCount ?></div>
            <div class="stat-label">Low / Out</div>
          </div>
        <?php endif; ?>
        <?php if ($missingPlanned > 0): ?>
          <div class="stat-card stat-card-clickable" onclick="openPlannedShopModal()">
            <div class="stat-num"><?= $missingPlanned ?></div>
            <div class="stat-label">Missing (Planned)</div>
          </div>
        <?php endif; ?>
        <?php if ($hasBattles && count($battlesData)): ?>
          <?php
            $bw = count(array_filter($battlesData, fn($b) => ($b['result'] ?? '') === 'win'));
            $bl = count(array_filter($battlesData, fn($b) => ($b['result'] ?? '') === 'loss'));
            $bd = count(array_filter($battlesData, fn($b) => ($b['result'] ?? '') === 'draw'));
          ?>
          <div class="stat-card">
            <div class="stat-num"><?= count($battlesData) ?></div>
            <div class="stat-label">Battles (<?= $bw ?>W <?= $bl ?>L <?= $bd ?>D)</div>
          </div>
        <?php endif; ?>
        <?php if ($hasRescues):
          $cntRescuesActive = count(array_filter($rescuesData, fn($r) => empty($r['promoted_to'])));
          $cntRescueUnits   = array_sum(array_map(fn($r) => max(1, (int)($r['count'] ?? 1)), array_filter($rescuesData, fn($r) => empty($r['promoted_to']))));
        ?>
        <?php if ($cntRescuesActive > 0): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $cntRescuesActive ?></div>
            <div class="stat-label">Active Rescues</div>
          </div>
          <?php if ($cntRescueUnits > $cntRescuesActive): ?>
          <div class="stat-card">
            <div class="stat-num"><?= $cntRescueUnits ?></div>
            <div class="stat-label">Rescue Units</div>
          </div>
          <?php endif; ?>
        <?php endif; ?>
        <?php
          $cntShameActive = array_sum(array_map(fn($s) => max(1, (int)($s['count'] ?? 1)), array_filter($shameData, fn($s) => empty($s['promoted_to']))));
          $cntBenchActive = array_sum(array_map(fn($b) => max(1, (int)($b['count'] ?? 1)), array_filter($benchData, fn($b) => empty($b['promoted_to']))));
          $_totalUnits = $totalPainted + $cntBenchActive + $cntShameActive + $cntRescueUnits;
          if ($_totalUnits > $totalPainted):
        ?>
          <div class="stat-card">
            <div class="stat-num"><?= $_totalUnits ?></div>
            <div class="stat-label">Total Units Tracked</div>
          </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="stats-two-col">
        <div>
          <div class="stats-sub-heading">Collection by Brand</div>
          <?php foreach ($byBrand as $brand => $cnt): ?>
            <div class="stats-bar-row">
              <span class="stats-bar-label-text"><?= e($brand) ?></span>
              <div class="stats-bar-track">
                <div class="stats-bar-fill" style="width:<?= round($cnt / $maxBrand * 100) ?>%"></div>
              </div>
              <span class="stats-bar-count"><?= $cnt ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div>
          <?php if ($models): ?>
            <div class="stats-sub-heading">Gallery by Faction</div>
            <?php foreach ($byFaction as $faction => $cnt): ?>
              <div class="stats-faction-row">
                <span class="stats-faction-name"><?= e($faction) ?></span>
                <span class="stats-faction-count"><?= $cnt ?></span>
              </div>
            <?php endforeach; ?>
            <?php if ($noFaction): ?>
              <div class="stats-faction-row">
                <span class="stats-faction-name" style="color:#3a2a10;font-style:italic">Untagged</span>
                <span class="stats-faction-count"><?= $noFaction ?></span>
              </div>
            <?php endif; ?>
            <?php
              $curYr = date('Y');
              $displayYears = $byYear;
              if (!array_key_exists($curYr, $displayYears)) $displayYears[$curYr] = 0;
              krsort($displayYears);
            ?>
            <?php if (count($displayYears) > 1 || (count($displayYears) === 1 && !array_key_exists('Undated', $displayYears))): ?>
              <div class="stats-sub-heading" style="margin-top:18px">By Year</div>
              <?php foreach ($displayYears as $year => $cnt):
                if ($year === 'Undated') {
                  ?>
                  <div class="stats-faction-row">
                    <span class="stats-faction-name" style="color:#3a2a10;font-style:italic">Undated</span>
                    <span class="stats-faction-count"><?= $cnt ?></span>
                  </div>
                  <?php continue;
                }
                $rawGoal = $goalsData[$year] ?? null;
                $target  = is_array($rawGoal) ? (int)($rawGoal['target'] ?? 0) : (int)($rawGoal ?? 0);
                $seed    = is_array($rawGoal) ? (int)($rawGoal['seed']   ?? 0) : 0;
                $sessCount = $seed + ($sessionsByYear[$year] ?? 0);
                $displayCount = $target > 0 ? $sessCount : $cnt;
                $pct    = $target > 0 ? min(100, (int)round($sessCount / $target * 100)) : 0;
              ?>
                <div class="stats-year-row" id="year-row-<?= e($year) ?>">
                  <div class="stats-year-main">
                    <span class="stats-year-label"><?= e($year) ?></span>
                    <span class="stats-year-count"><?= $displayCount ?><?= $target > 0 ? ' / ' . $target : '' ?></span>
                    <button class="stats-goal-btn" onclick="toggleGoalForm('<?= e($year) ?>')"><?= $target > 0 ? '&#9998;' : '+ Goal' ?></button>
                    <?php if ($target > 0): ?>
                      <button class="stats-goal-del" onclick="deleteGoal('<?= e($year) ?>')" title="Remove goal">&times;</button>
                    <?php endif; ?>
                  </div>
                  <?php if ($target > 0): ?>
                    <div class="stats-goal-bar-wrap"><div class="stats-goal-bar-fill" style="width:<?= $pct ?>%"></div></div>
                    <?php if ($pct >= 100): ?><div class="stats-goal-complete">Goal reached!</div><?php endif; ?>
                  <?php endif; ?>
                  <div class="stats-goal-form" id="goal-form-<?= e($year) ?>" style="display:none">
                    <input type="number" class="stats-goal-input" id="goal-input-<?= e($year) ?>" min="1" placeholder="target" value="<?= $target ?: '' ?>">
                    <input type="number" class="stats-goal-input" id="goal-seed-<?= e($year) ?>" min="0" placeholder="baseline (already painted)" value="<?= $seed ?: '' ?>" style="margin-left:6px">
                    <button onclick="saveGoal('<?= e($year) ?>')" class="btn btn-sm">Save</button>
                    <button onclick="toggleGoalForm('<?= e($year) ?>')" class="btn btn-sm" style="background:#1a1a1a">Cancel</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          <?php else: ?>
            <div style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em">No recorded schemes yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <?php /* Gauge widget — hidden for now, logic preserved
      <div class="admin-wm-widget <?= $adMeterClass ?>">
        <img src="img/waaagh.png" class="wm-title-img" alt="Waaagh! Index">
        <div class="wm-gauge">
          <div class="wm-fill"></div>
          <img src="img/gauge.png" class="wm-gauge-img" alt="">
          <div class="wm-needle"></div>
          <div class="wm-pivot"></div>
        </div>
        <div class="wm-state"><?= htmlspecialchars($adMeterState) ?></div>
        <div class="wm-week"><?php if ($adWkActive): ?><?= implode(' &middot; ', $adWkParts) ?><?php else: ?>Nowt done yet&hellip;<?php endif; ?></div>
      </div>
      */ ?>

      <?php if ($topPaints): ?>
        <div class="stats-sub-heading" style="margin-top:20px">Most Used Paints</div>
        <div class="stats-top-paints">
          <?php $rank = 1;
          foreach ($topPaints as $key => $cnt): ?>
            <?php $parts = explode('|', $key);
            $pName = $parts[1] ?? $key;
            $pBrand = $parts[0] ?? ''; ?>
            <div class="stats-top-row">
              <span class="stats-top-rank"><?= $rank++ ?></span>
              <span class="stats-top-name"><?= e($pName) ?></span>
              <span class="stats-top-brand"><?= e($pBrand) ?></span>
              <span class="stats-top-count"><?= $cnt ?>×</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($tabStats):
        $tabLabels = [
          'inventory' => 'Paint Inventory',
          'gallery'   => 'Paint Schemes',
          'equiv'     => 'Equivalency',
          'planned'   => 'Planned',
          'brushes'   => 'Brushes',
          'books'     => 'Codices',
        ];
        arsort($tabStats);
        $maxTabCount = max(array_values($tabStats));
      ?>
        <div class="stats-sub-heading" style="margin-top:20px">Tab Visits</div>
        <?php foreach ($tabStats as $tabKey => $tabCnt): ?>
          <div class="stats-bar-row">
            <span class="stats-bar-label-text"><?= e($tabLabels[$tabKey] ?? $tabKey) ?></span>
            <div class="stats-bar-track">
              <div class="stats-bar-fill" style="width:<?= round($tabCnt / $maxTabCount * 100) ?>%"></div>
            </div>
            <span class="stats-bar-count"><?= $tabCnt ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
