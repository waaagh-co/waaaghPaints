      <h2 id="section-bench" style="margin-top:40px">On the Bench
        <?php if ($hasBench): ?>
          <?php $activeBench = count(array_filter($benchData, fn($b) => ($b['stage'] ?? 'built') !== 'done')); ?>
          <span style="color:#4a3a1a;font-size:.75em;font-weight:400;letter-spacing:.04em">&nbsp;<?= count($benchData) ?> entr<?= count($benchData) !== 1 ? 'ies' : 'y' ?> &middot; <?= $activeBench ?> active</span>
        <?php endif; ?>
      </h2>

      <?php if (!$hasBench): ?>
        <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
          Track active painting projects through their stages - built, primed, basecoated, washed, highlighted, based, varnished, done. Add WIP photos and a paint queue as you go.
        </p>
        <form method="post">
          <input type="hidden" name="action" value="create_bench_file">
          <button type="submit" class="btn btn-sm">Start Workbench</button>
        </form>
      <?php else: ?>
        <div style="margin-bottom:14px">
          <button type="button" class="btn btn-sm" onclick="openBenchAdd()">+ Add Bench Entry</button>
        </div>

        <div class="paint-form-wrap" id="benchFormWrap" style="display:none">
          <div class="paint-form-title" id="benchFormTitle">Add Bench Entry</div>
          <form method="post" id="benchForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_bench" id="benchAction">
            <input type="hidden" name="bench_id" id="benchId" value="">
            <div class="form-grid">
              <div>
                <label for="bn_name">Project Name *</label>
                <input type="text" id="bn_name" name="bn_name" required placeholder="e.g. Death Guard Plague Marines">
              </div>
              <div>
                <label for="bn_faction">Faction</label>
                <input type="text" id="bn_faction" name="bn_faction" placeholder="e.g. Death Guard">
              </div>
              <div>
                <label for="bn_sub_faction">Unit / Sub-faction</label>
                <input type="text" id="bn_sub_faction" name="bn_sub_faction" placeholder="e.g. Blightlord Terminators">
              </div>
              <div>
                <label for="bn_system">Game System</label>
                <select id="bn_system" name="bn_system" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="">- none -</option>
                  <option value="40k">Warhammer 40,000</option>
                  <option value="30k / HH">Horus Heresy / 30k</option>
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
              <div>
                <label for="bn_stage">Stage</label>
                <select id="bn_stage" name="bn_stage" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
                  <option value="built">Built</option>
                  <option value="primed">Primed</option>
                  <option value="basecoated">Basecoated</option>
                  <option value="washed">Washed</option>
                  <option value="highlighted">Highlighted</option>
                  <option value="based">Based</option>
                  <option value="varnished">Varnished</option>
                  <option value="done">Done</option>
                </select>
              </div>
              <div>
                <label for="bn_date_start">Date Started (optional - YYYY-MM-DD)</label>
                <input type="text" id="bn_date_start" name="bn_date_start" placeholder="e.g. 2026-04-12" pattern="\d{4}-\d{2}-\d{2}" maxlength="10">
              </div>
              <div>
                <label for="bn_count">Models in Box (optional)</label>
                <input type="number" id="bn_count" name="bn_count" min="1" placeholder="e.g. 10" style="width:100%;padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
              </div>
              <div class="form-full">
                <label for="bn_notes">Notes</label>
                <textarea id="bn_notes" name="bn_notes" rows="3"
                  placeholder="Techniques you're trying, lessons learned, things to remember next time…"
                  style="width:100%;resize:vertical;font-size:13px;background:#130f08;color:#c4b49a;border:1px solid #2a2010;border-radius:4px;padding:6px 10px;font-family:inherit"></textarea>
              </div>
              <div>
                <label for="bn_codex_source">Codex Reference</label>
                <select id="bn_codex_source" name="bn_codex_source">
                  <option value="">- none -</option>
                  <?php foreach ($codexOptions as $opt): ?>
                    <option value="<?= e($opt['value']) ?>"><?= e($opt['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-full">
                <label>Paint Queue</label>
                <input type="text" class="color-search" id="colorSearchBn" placeholder="Filter paints…" autocomplete="off">
                <div class="color-list" id="colorListBn"></div>
                <div class="selected-colors" id="selectedCountBn">0 colours selected</div>
                <div id="colorInputsBn"></div>
              </div>
              <?php if ($hasBrushes && $brushesData): ?>
                <div class="form-full">
                  <label>Brushes</label>
                  <div class="bench-brush-picker" id="benchBrushPicker">
                    <?php foreach ($brushesData as $br):
                      if (($br['condition'] ?? 'prime') === 'retired') continue;
                      $blabel = trim(($br['brand'] ?? '') . ' ' . ($br['series'] ?? '') . ' ' . ($br['size'] ?? ''));
                    ?>
                      <span class="bbp-item" data-id="<?= e($br['id']) ?>"><?= e($blabel) ?></span>
                    <?php endforeach; ?>
                  </div>
                  <div id="brushInputsBn"></div>
                </div>
              <?php endif; ?>
              <?php if ($hasRecipes && $recipesData): ?>
                <div class="form-full">
                  <label>Recipes (optional)</label>
                  <div class="rc-pill-picker" id="benchRecipePicker" data-form="bench">
                    <?php foreach ($recipesData as $rc): ?>
                      <span class="rc-pill" data-id="<?= e($rc['id']) ?>">
                        <?= e($rc['name']) ?><?php if (!empty($rc['category'])): ?> <small>(<?= e($rc['category']) ?>)</small><?php endif; ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                  <div id="benchRecipeInputs"></div>
                </div>
              <?php endif; ?>
              <div class="form-full">
                <label>WIP Photos (up to <?= BENCH_MAX_IMAGES ?>)</label>
                <div class="bench-image-grid" id="benchImageGrid">
                  <?php for ($i = 1; $i <= BENCH_MAX_IMAGES; $i++): ?>
                    <div class="bench-img-slot" data-slot="<?= $i ?>">
                      <div class="bench-img-thumb" id="bn_img_thumb_<?= $i ?>"></div>
                      <input type="file" name="bn_image<?= $i ?>" id="bn_image_input_<?= $i ?>" accept="image/*" style="font-size:11px;width:100%">
                      <label class="bench-img-delete" id="bn_img_del_label_<?= $i ?>" style="display:none">
                        <input type="checkbox" name="delete_bn_img_<?= $i ?>" value="1"> Delete
                      </label>
                    </div>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
            <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
              <button type="submit" class="btn" id="benchSubmitBtn">Add Entry</button>
              <button type="button" class="btn btn-sm" id="benchCancelBtn">Cancel</button>
            </div>
          </form>
        </div>

        <?php if ($benchData): ?>
          <?php
          $stageLabel = ['built' => 'Built', 'primed' => 'Primed', 'basecoated' => 'Basecoated', 'washed' => 'Washed', 'highlighted' => 'Highlighted', 'based' => 'Based', 'varnished' => 'Varnished', 'done' => 'Done'];
          ?>
          <div class="model-list">
            <?php foreach ($benchData as $bn):
              $st = $bn['stage'] ?? 'built';
              $lt = !empty($bn['last_touched']) ? date('M j', strtotime($bn['last_touched'])) : '';
              $imgCount = count($bn['wip_images'] ?? []);
              $colorCount = count($bn['colors'] ?? []);
              $bnCount = max(1, (int)($bn['count'] ?? 0));
              $bnDone  = (int)($bn['models_done'] ?? 0);
              $bnHasCount = !empty($bn['count']) && $bnCount > 1;
              $bnAllDone  = $bnHasCount && $bnDone >= $bnCount;
              $bnStepsDone = $bn['recipe_steps_done'] ?? [];
            ?>
              <div class="model-row">
                <div class="model-row-info">
                  <div class="model-row-name"><?= e($bn['name']) ?></div>
                  <div class="model-row-meta">
                    <?= e($bn['faction'] ?? '') ?>
                    <?php if ($colorCount): ?>&nbsp;&middot;&nbsp;<?= $colorCount ?> paint<?= $colorCount !== 1 ? 's' : '' ?><?php endif; ?>
                    <?php if ($imgCount): ?>&nbsp;&middot;&nbsp;<?= $imgCount ?> photo<?= $imgCount !== 1 ? 's' : '' ?><?php endif; ?>
                    <?php if ($lt): ?>&nbsp;&middot;&nbsp;touched <?= e($lt) ?><?php endif; ?>
                  </div>
                  <?php if (!empty($bn['history'])): ?>
                    <details class="bench-hist-details">
                      <summary><?= count($bn['history']) ?> stage transition<?= count($bn['history']) !== 1 ? 's' : '' ?></summary>
                      <?php foreach (array_reverse($bn['history']) as $h): ?>
                        <div class="bench-hist-adm-row">
                          <span><?= e($stageLabel[$h['from']] ?? $h['from']) ?></span>
                          <span class="bench-hist-arrow">→</span>
                          <span><?= e($stageLabel[$h['to']] ?? $h['to']) ?></span>
                          <span class="bench-hist-adm-date"><?= !empty($h['date']) ? e(date('M j, Y', strtotime($h['date']))) : '' ?></span>
                        </div>
                      <?php endforeach; ?>
                    </details>
                  <?php endif; ?>
                </div>
                <button type="button"
                  class="bench-stage-btn stage-<?= e($st) ?>"
                  data-bid="<?= e($bn['id']) ?>"
                  data-stage="<?= e($st) ?>"
                  onclick="cycleBenchStage(this)"><?= e($stageLabel[$st] ?? $st) ?></button>
                <button type="button" class="btn btn-sm"
                  data-bid="<?= e($bn['id']) ?>"
                  data-bname="<?= e($bn['name']) ?>"
                  onclick="openSessionModal(this)">+ Session</button>
                <button class="btn btn-sm"
                  data-id="<?= e($bn['id']) ?>"
                  data-name="<?= e($bn['name']) ?>"
                  data-faction="<?= e($bn['faction'] ?? '') ?>"
                  data-sub_faction="<?= e($bn['sub_faction'] ?? '') ?>"
                  data-system="<?= e($bn['system'] ?? '') ?>"
                  data-stage="<?= e($st) ?>"
                  data-date_start="<?= e($bn['date_start'] ?? '') ?>"
                  data-notes="<?= e($bn['notes'] ?? '') ?>"
                  data-codex_source="<?= e($bn['codex_source'] ?? '') ?>"
                  data-colors="<?= e(json_encode($bn['colors'] ?? [])) ?>"
                  data-brushes="<?= e(json_encode($bn['brushes'] ?? [])) ?>"
                  data-recipes="<?= e(json_encode($bn['recipes'] ?? [])) ?>"
                  data-images="<?= e(json_encode($bn['wip_images'] ?? [])) ?>"
                  data-count="<?= $bnHasCount ? $bnCount : '' ?>"
                  onclick="openBenchEdit(this)">Edit</button>
                <?php if (empty($bn['promoted_to'])): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Archive to Gallery? A draft gallery entry will be created - you can add images and description in the edit form.')">
                    <input type="hidden" name="action" value="promote_bench">
                    <input type="hidden" name="bench_id" value="<?= e($bn['id']) ?>">
                    <button type="submit" class="btn btn-sm" style="font-size:10px">&rarr; Gallery</button>
                  </form>
                <?php else: ?>
                  <span style="font-size:10px;color:#c9a227;font-family:'Cinzel',serif">Promoted &rarr; <?= ucfirst(e($bn['promoted_to'])) ?></span>
                <?php endif; ?>
                <?php if ($bnHasCount && !$bnAllDone): ?>
                  <button type="button" class="btn btn-sm bench-model-done-btn"
                    data-bid="<?= e($bn['id']) ?>"
                    data-done="<?= $bnDone ?>"
                    data-count="<?= $bnCount ?>"
                    onclick="benchModelDone(this)"
                    title="Mark one model as done, increment gallery count">+1 Done</button>
                <?php elseif ($bnAllDone): ?>
                  <span style="font-size:10px;color:#60c060;font-family:'Cinzel',serif">All <?= $bnCount ?> done &#10003;</span>
                <?php endif; ?>
                <form method="post" onsubmit="return confirm('Delete this bench entry and its photos?')" style="margin:0">
                  <input type="hidden" name="action" value="delete_bench">
                  <input type="hidden" name="bench_id" value="<?= e($bn['id']) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                </form>
              </div>
              <?php if ($bnHasCount): ?>
                <div class="bench-model-progress-wrap">
                  <div class="bench-model-progress-bar" style="width:<?= min(100, $bnCount > 0 ? round($bnDone / $bnCount * 100) : 0) ?>%"></div>
                </div>
                <div class="bench-model-count-label" id="bmc-label-<?= e($bn['id']) ?>"><?= $bnDone ?> / <?= $bnCount ?> done</div>
              <?php endif; ?>
              <?php if (!empty($bn['recipes']) && $hasRecipes && $recipesData): ?>
                <div class="bench-step-toggle-wrap">
                  <button type="button" class="btn btn-sm bench-step-toggle-btn"
                    onclick="this.closest('.model-row').querySelector('.bench-step-list').classList.toggle('open');this.textContent=this.closest('.model-row').querySelector('.bench-step-list').classList.contains('open')?'Steps ↑':'Steps ↓'">Steps ↓</button>
                </div>
                <div class="bench-step-list">
                  <?php foreach ($bn['recipes'] as $rid):
                    $rc = null;
                    foreach ($recipesData as $r) { if ($r['id'] === $rid) { $rc = $r; break; } }
                    if (!$rc) continue;
                    $rcStepsDone = $bnStepsDone[$rid] ?? [];
                  ?>
                    <div class="bench-step-recipe-name"><?= e($rc['name']) ?></div>
                    <?php foreach (($rc['steps'] ?? []) as $si => $step):
                      $isDone = in_array($si, $rcStepsDone, true);
                      $parts = explode('|', $step['paint'] ?? '');
                      $paintName = $parts[1] ?? ($step['paint'] ?? '');
                    ?>
                      <label class="bench-step-row<?= $isDone ? ' step-done' : '' ?>">
                        <input type="checkbox" class="bench-step-check"
                          <?= $isDone ? 'checked' : '' ?>
                          onchange="setRecipeStepDone('<?= e($bn['id']) ?>','<?= e($rid) ?>',<?= $si ?>,this.checked,this)">
                        <span class="pb-<?= e(str_replace(' ', '', $step['technique'] ?? '')) ?> recipe-technique-badge"><?= e($step['technique'] ?? '') ?></span>
                        <?= e($paintName) ?>
                        <?php if (!empty($step['note'])): ?><span class="bench-step-note"><?= e($step['note']) ?></span><?php endif; ?>
                      </label>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="font-size:12px;color:#3a2a10;font-family:'Cinzel',serif;letter-spacing:.05em;padding:12px 0">Nothing on the bench yet.</p>
        <?php endif; ?>
      <?php endif; ?>
