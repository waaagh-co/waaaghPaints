      // key: "brand|name" (lowercase) → {brand, name, color, layer, stock}
      const inventoryMap = new Map(INVENTORY_DATA.map(([b, n, c, l, s]) => [(b + '|' + n).toLowerCase(), {
        brand: b,
        name: n,
        color: c,
        layer: l,
        stock: s
      }]));
      const paintOwned = new Set(INVENTORY_DATA.filter(([, , , , s]) => s !== 'wanted').map(([b, n]) => (b + '|' + n).toLowerCase()));
      const paintStock = new Map(INVENTORY_DATA.filter(([, , , , s]) => s !== '').map(([b, n, , , s]) => [(b + '|' + n).toLowerCase(), s]));
      let selected = new Set(PRE_SELECTED);

      function buildList(filter) {
        const q = filter.toLowerCase();
        const list = document.getElementById('colorList');
        list.innerHTML = '';
        ALL_PAINTS.forEach(key => {
          const parts = key.split('|');
          const label = parts[1] + ' (' + parts[0] + (parts[2] ? ' — ' + parts[2] : '') + ')';
          if (q && !label.toLowerCase().includes(q)) return;
          const el = document.createElement('span');
          el.className = 'cp-item' + (selected.has(key) ? ' selected' : '');
          el.textContent = label;
          el.dataset.key = key;
          el.addEventListener('click', () => {
            if (selected.has(key)) {
              selected.delete(key);
              el.classList.remove('selected');
            } else {
              selected.add(key);
              el.classList.add('selected');
            }
            updateHidden();
          });
          list.appendChild(el);
        });
      }

      function updateHidden() {
        document.getElementById('selectedCount').textContent =
          selected.size + ' colour' + (selected.size !== 1 ? 's' : '') + ' selected';
        const wrap = document.getElementById('colorInputs');
        wrap.innerHTML = '';
        selected.forEach(key => {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'colors[]';
          inp.value = key;
          wrap.appendChild(inp);
        });
      }

      document.getElementById('colorSearch').addEventListener('input', function() {
        buildList(this.value);
      });

      buildList('');
      updateHidden(); // sync pre-selected colors to hidden inputs immediately (edit mode)

      function openPaintAdd() {
        if (window._adminShowSection) window._adminShowSection('section-inventory');
        document.getElementById('paintFormTitle').textContent = 'Add Paint';
        document.getElementById('paintAction').value = 'add_paint';
        document.getElementById('paintId').value = '';
        document.getElementById('p_brand').value = '';
        document.getElementById('p_name').value = '';
        document.getElementById('p_color').value = '';
        document.getElementById('p_hue').value = '';
        document.getElementById('p_layer').value = '';
        document.getElementById('p_notes').value = '';
        document.getElementById('p_hex').value = '';
        document.getElementById('p_hex_picker').value = '#888888';
        paintStarSet(0);
        document.getElementById('paintSubmitBtn').textContent = 'Add Paint';
        const wrap = document.getElementById('paintFormWrap');
        wrap.style.display = 'block';
        document.getElementById('p_brand').focus();
        wrap.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest'
        });
      }

      function openPaintEdit(btn) {
        if (window._adminShowSection) window._adminShowSection('section-inventory');
        document.getElementById('paintFormTitle').textContent = 'Edit Paint';
        document.getElementById('paintAction').value = 'edit_paint';
        document.getElementById('paintId').value = btn.dataset.pid;
        document.getElementById('p_brand').value = btn.dataset.brand;
        document.getElementById('p_name').value = btn.dataset.name;
        document.getElementById('p_color').value = btn.dataset.color;
        document.getElementById('p_hue').value = btn.dataset.hue;
        document.getElementById('p_layer').value = btn.dataset.layer;
        document.getElementById('p_notes').value = btn.dataset.notes || '';
        const hex = (btn.dataset.hex || '').toLowerCase();
        document.getElementById('p_hex').value = hex;
        document.getElementById('p_hex_picker').value = /^#[0-9a-f]{6}$/.test(hex) ? hex : '#888888';
        paintStarSet(parseInt(btn.dataset.stars) || 0);
        document.getElementById('paintSubmitBtn').textContent = 'Save Changes';
        const wrap = document.getElementById('paintFormWrap');
        wrap.style.display = 'block';
        wrap.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest'
        });
      }

      document.getElementById('paintCancelBtn')?.addEventListener('click', () => {
        document.getElementById('paintFormWrap').style.display = 'none';
      });

      function clearSlot(n) {
        document.getElementById('delete_img_' + n).value = '1';
        document.getElementById('preview_' + n).style.display = 'none';
        document.getElementById('cleared_' + n).style.display = 'block';
        document.getElementById('file_' + n).style.display = '';
        const hint = document.getElementById('keep_hint_' + n);
        if (hint) hint.style.display = 'none';
      }

      async function toggleStock(btn) {
        const cycle = {
          '': 'low',
          'low': 'out',
          'out': 'wanted',
          'wanted': 'retired',
          'retired': ''
        };
        const pid = btn.dataset.pid;
        const cur = btn.dataset.stock || '';
        const next = cycle[cur] ?? '';
        const fd = new FormData();
        fd.append('action', 'set_stock');
        fd.append('paint_id', pid);
        fd.append('stock', next);
        try {
          const res = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
          const data = await res.json();
          if (data.ok) {
            btn.dataset.stock = next;
            btn.textContent = next || '·';
            btn.className = next ? `stock-btn stock-${next}` : 'stock-btn';
          }
        } catch (e) {
          console.warn('toggleStock failed:', e);
        }
      }

      function filterPaints() {
        const searchEl = document.getElementById('paintSearch');
        const brandEl  = document.getElementById('paintBrandFilter');
        if (!searchEl || !brandEl) return;
        const q = searchEl.value.toLowerCase();
        const brand = brandEl.value.toLowerCase();
        let visible = 0;
        document.querySelectorAll('#paintTable tbody tr').forEach(row => {
          const matchName = !q || row.dataset.name.includes(q);
          const matchBrand = !brand || row.dataset.brand === brand;
          const show = matchName && matchBrand;
          row.style.display = show ? '' : 'none';
          if (show) visible++;
        });
        const el = document.getElementById('paintVisibleCount');
        if (el) el.textContent = visible + ' paint' + (visible !== 1 ? 's' : '') + ' shown';
      }

      document.getElementById('paintSearch')?.addEventListener('input', filterPaints);
      document.getElementById('paintBrandFilter')?.addEventListener('change', filterPaints);
      filterPaints();

      let selectedPl = new Set();

      function buildListPl(filter) {
        const q = filter.toLowerCase();
        const list = document.getElementById('colorListPl');
        list.innerHTML = '';
        ALL_PAINTS.forEach(key => {
          const parts = key.split('|');
          const label = parts[1] + ' (' + parts[0] + (parts[2] ? ' — ' + parts[2] : '') + ')';
          if (q && !label.toLowerCase().includes(q)) return;
          const el = document.createElement('span');
          el.className = 'cp-item' + (selectedPl.has(key) ? ' selected' : '');
          el.textContent = label;
          el.dataset.key = key;
          el.addEventListener('click', () => {
            if (selectedPl.has(key)) {
              selectedPl.delete(key);
              el.classList.remove('selected');
            } else {
              selectedPl.add(key);
              el.classList.add('selected');
            }
            updateHiddenPl();
          });
          list.appendChild(el);
        });
      }

      function updateHiddenPl() {
        document.getElementById('selectedCountPl').textContent =
          selectedPl.size + ' color' + (selectedPl.size !== 1 ? 's' : '') + ' selected';
        const wrap = document.getElementById('colorInputsPl');
        wrap.innerHTML = '';
        selectedPl.forEach(key => {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'planned_colors[]';
          inp.value = key;
          wrap.appendChild(inp);
        });
      }

      document.getElementById('colorSearchPl').addEventListener('input', function() {
        buildListPl(this.value);
      });

      function openPlannedAdd() {
        if (window._adminShowSection) window._adminShowSection('section-planned');
        selectedPl = new Set();
        document.getElementById('plannedFormTitle').textContent = 'Add Planned Scheme';
        document.getElementById('plannedAction').value = 'add_planned';
        document.getElementById('plannedId').value = '';
        document.getElementById('pl_name').value = '';
        document.getElementById('pl_model').value = '';
        document.getElementById('pl_faction').value = '';
        document.getElementById('pl_system').value = '';
        document.getElementById('pl_description').value = '';
        document.getElementById('pl_codex_source').value = '';
        document.getElementById('plannedSubmitBtn').textContent = 'Add Scheme';
        buildListPl('');
        updateHiddenPl();
        if (typeof setRecipePickerSelection === 'function') setRecipePickerSelection('plannedRecipePicker', 'plannedRecipeInputs', 'planned_recipes[]', []);
        const wrap = document.getElementById('plannedFormWrap');
        wrap.style.display = 'block';
        document.getElementById('pl_name').focus();
        wrap.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest'
        });
      }

      function openPlannedEdit(btn) {
        if (window._adminShowSection) window._adminShowSection('section-planned');
        const colors = (() => { try { return JSON.parse(btn.dataset.colors || '[]'); } catch { return []; } })();
        selectedPl = new Set(colors);
        document.getElementById('plannedFormTitle').textContent = 'Edit Planned Scheme';
        document.getElementById('plannedAction').value = 'edit_planned';
        document.getElementById('plannedId').value = btn.dataset.id;
        document.getElementById('pl_name').value = btn.dataset.name;
        document.getElementById('pl_model').value = btn.dataset.model;
        document.getElementById('pl_faction').value = btn.dataset.faction;
        document.getElementById('pl_sub_faction').value = btn.dataset.sub_faction || '';
        document.getElementById('pl_system').value = btn.dataset.system || '';
        document.getElementById('pl_description').value = btn.dataset.description;
        document.getElementById('pl_codex_source').value = btn.dataset.codex_source || '';
        document.getElementById('plannedSubmitBtn').textContent = 'Save Changes';
        buildListPl('');
        updateHiddenPl();
        if (typeof setRecipePickerSelection === 'function') {
          const recipes = (() => { try { return JSON.parse(btn.dataset.recipes || '[]'); } catch { return []; } })();
          setRecipePickerSelection('plannedRecipePicker', 'plannedRecipeInputs', 'planned_recipes[]', recipes);
        }
        const wrap = document.getElementById('plannedFormWrap');
        wrap.style.display = 'block';
        wrap.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest'
        });
      }

      document.getElementById('plannedCancelBtn')?.addEventListener('click', () => {
        document.getElementById('plannedFormWrap').style.display = 'none';
      });

      function escA(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      }

      function findSubstitute(brand, name) {
        const convKey = (brand + '|' + name).toLowerCase();
        const convList = CONVERSIONS[convKey];

        if (convList && convList.length) {
          // 1a. Prefer a known conversion that's actually in inventory + usable stock
          for (const conv of convList) {
            const entry = inventoryMap.get((conv.brand + '|' + conv.name).toLowerCase());
            if (entry && entry.stock !== 'out' && entry.stock !== 'wanted') {
              return {
                brand: conv.brand,
                name: conv.name,
                layer: entry.layer,
                quality: conv.q,
                exact: true
              };
            }
          }
          // 1b. Known conversion exists but not owned - still worth surfacing
          const best = convList[0];
          return {
            brand: best.brand,
            name: best.name,
            quality: best.q,
            exact: true,
            notOwned: true
          };
        }

        // 2. Fall back: algorithmic color+layer match from inventory
        const master = MASTER_PAINTS[convKey];
        if (!master || !master.c) return null;

        const candidates = INVENTORY_DATA.filter(([b, , c, , s]) =>
          c === master.c && b !== brand && s !== 'out' && s !== 'wanted'
        );
        if (!candidates.length) return null;

        const sameLayer = candidates.filter(([, , , l]) => l === master.l);
        const pool = sameLayer.length ? sameLayer : candidates;
        pool.sort((a, b) => a[1].localeCompare(b[1]));

        const [sb, sn, , sl] = pool[0];
        return {
          brand: sb,
          name: sn,
          layer: sl,
          exact: false
        };
      }

      function checkPaints() {
        const brand = document.getElementById('checkerBrand').value;
        if (!brand) {
          alert('Select a brand first.');
          return;
        }
        const raw = document.getElementById('checkerInput').value;
        const names = raw.split('\n').map(s => s.trim()).filter(Boolean);
        if (!names.length) return;

        const STATUS = {
          owned: {
            icon: '✓',
            label: 'owned',
            color: '#5a8a5a'
          },
          low: {
            icon: '▲',
            label: 'low stock',
            color: '#c97a20'
          },
          out: {
            icon: '✗',
            label: 'out of stock',
            color: '#c94040'
          },
          wanted: {
            icon: '◇',
            label: 'wanted (not owned)',
            color: '#60a5fa'
          },
          missing: {
            icon: '✗',
            label: 'not in inventory',
            color: '#6a3a3a'
          },
        };

        const results = names.map(name => {
          const entry = inventoryMap.get((brand + '|' + name).toLowerCase());
          const stock = entry?.stock;
          let status;
          if (!entry) status = 'missing';
          else if (stock === 'wanted') status = 'wanted';
          else if (stock === 'out') status = 'out';
          else if (stock === 'low') status = 'low';
          else status = 'owned';
          const sub = (status === 'missing' || status === 'wanted' || status === 'out') ?
            findSubstitute(brand, name) : null;
          return {
            name,
            status,
            sub
          };
        });

        const counts = {};
        results.forEach(r => counts[r.status] = (counts[r.status] || 0) + 1);
        const summaryParts = [
          counts.owned ? `${counts.owned} owned` : '',
          counts.low ? `${counts.low} low` : '',
          counts.out ? `${counts.out} out` : '',
          counts.wanted ? `${counts.wanted} wanted` : '',
          counts.missing ? `${counts.missing} not in inventory` : '',
        ].filter(Boolean).join(' &nbsp;&middot;&nbsp; ');

        let html = `<div style="margin-top:14px;border:1px solid #2a2010;border-radius:3px;overflow:hidden"><div style="background:#0a0806;padding:8px 12px;font-family:'Cinzel',serif;font-size:10px;color:#6a5a30;letter-spacing:.06em;border-bottom:1px solid #2a2010">${names.length} paint${names.length !== 1 ? 's' : ''} checked &nbsp;-&nbsp; ${summaryParts}</div>`;

        html += results.map(r => {
          const s = STATUS[r.status];
          let subHtml = '';
          if (r.sub) {
            if (r.sub.exact && !r.sub.notOwned) {
              const q = r.sub.quality === 'near identical' ? 'near identical' : 'usable';
              subHtml = `<div style="font-size:10px;color:#3a6a3a;padding:0 12px 5px;font-style:italic">&nbsp;&nbsp;&#8594; ${escA(r.sub.name)} - ${escA(r.sub.brand)} &middot; ${q}</div>`;
            } else if (r.sub.exact && r.sub.notOwned) {
              subHtml = `<div style="font-size:10px;color:#3a3a2a;padding:0 12px 5px;font-style:italic">&nbsp;&nbsp;&#8594; ${escA(r.sub.name)} - ${escA(r.sub.brand)} &middot; ${r.sub.quality} &middot; not in your inventory</div>`;
            } else {
              subHtml = `<div style="font-size:10px;color:#2a3a2a;padding:0 12px 5px;font-style:italic">&nbsp;&nbsp;&#8594; ${escA(r.sub.name)} - ${escA(r.sub.brand)} &middot; same colour category</div>`;
            }
          }
          return `<div style="border-bottom:1px solid #151008"><div style="display:flex;justify-content:space-between;align-items:center;padding:6px 12px;font-size:13px"><span style="color:#c4b49a">${escA(r.name)}</span><span style="color:${s.color};font-size:10px;font-family:'Cinzel',serif;letter-spacing:.06em;white-space:nowrap;margin-left:16px">${s.icon} ${s.label}</span></div>${subHtml}</div>`;
        }).join('');

        html += '</div>';
        document.getElementById('checkerResults').innerHTML = html;
      }

      function clearChecker() {
        document.getElementById('checkerInput').value = '';
        document.getElementById('checkerResults').innerHTML = '';
      }

        function openBookAdd() {
          if (window._adminShowSection) window._adminShowSection('section-books');
          document.getElementById('bookFormTitle').textContent = 'Add Codex';
          document.getElementById('bookAction').value = 'add_book';
          document.getElementById('bkId').value = '';
          document.getElementById('bk_type').value = 'codex';
          document.getElementById('bk_title').value = '';
          document.getElementById('bk_author').value = '';
          document.getElementById('bk_series').value = '';
          document.getElementById('bk_faction').value = '';
          document.getElementById('bk_notes').value = '';
          document.getElementById('bookSubmitBtn').textContent = 'Add Codex';
          const wrap = document.getElementById('bookFormWrap');
          wrap.style.display = 'block';
          document.getElementById('bk_title').focus();
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        function openBookEdit(btn) {
          if (window._adminShowSection) window._adminShowSection('section-books');
          document.getElementById('bookFormTitle').textContent = 'Edit Codex';
          document.getElementById('bookAction').value = 'edit_book';
          document.getElementById('bkId').value = btn.dataset.id;
          document.getElementById('bk_type').value = btn.dataset.type || 'codex';
          document.getElementById('bk_title').value = btn.dataset.title;
          document.getElementById('bk_author').value = btn.dataset.author || '';
          document.getElementById('bk_series').value = btn.dataset.series || '';
          document.getElementById('bk_faction').value = btn.dataset.faction || '';
          document.getElementById('bk_notes').value = btn.dataset.notes || '';
          document.getElementById('bookSubmitBtn').textContent = 'Save Changes';
          const wrap = document.getElementById('bookFormWrap');
          wrap.style.display = 'block';
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        document.getElementById('bookCancelBtn')?.addEventListener('click', () => {
          document.getElementById('bookFormWrap').style.display = 'none';
        });

        function openJournalAdd() {
          if (window._adminShowSection) window._adminShowSection('section-journal');
          document.getElementById('journalFormTitle').textContent = 'Add Journal Entry';
          document.getElementById('journalAction').value = 'add_journal';
          document.getElementById('jnId').value = '';
          document.getElementById('jn_date').value = TODAY_DATE;
          document.getElementById('jn_title').value = '';
          document.getElementById('jn_mood').value = '';
          document.getElementById('jn_body').value = '';
          document.getElementById('journalSubmitBtn').textContent = 'Add Entry';
          const wrap = document.getElementById('journalFormWrap');
          wrap.style.display = 'block';
          document.getElementById('jn_body').focus();
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        function openJournalEdit(btn) {
          if (window._adminShowSection) window._adminShowSection('section-journal');
          document.getElementById('journalFormTitle').textContent = 'Edit Journal Entry';
          document.getElementById('journalAction').value = 'edit_journal';
          document.getElementById('jnId').value = btn.dataset.id;
          document.getElementById('jn_date').value = btn.dataset.date || '';
          document.getElementById('jn_title').value = btn.dataset.title || '';
          document.getElementById('jn_mood').value = btn.dataset.mood || '';
          document.getElementById('jn_body').value = btn.dataset.body || '';
          document.getElementById('journalSubmitBtn').textContent = 'Save Changes';
          const wrap = document.getElementById('journalFormWrap');
          wrap.style.display = 'block';
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        document.getElementById('journalCancelBtn')?.addEventListener('click', () => {
          document.getElementById('journalFormWrap').style.display = 'none';
        });

        function filterJournalList(q) {
          const rows = document.querySelectorAll('#jn-admin-list .model-row');
          const term = q.trim().toLowerCase();
          let shown = 0;
          rows.forEach(r => {
            const match = !term || (r.dataset.jnsearch || '').toLowerCase().includes(term);
            r.style.display = match ? '' : 'none';
            if (match) shown++;
          });
          const countEl = document.getElementById('jn-admin-count');
          if (countEl) countEl.textContent = term ? shown + ' of ' + JOURNAL_COUNT + ' entries' : JOURNAL_COUNT + ' entries';
        }

          function openShameAdd() {
            if (window._adminShowSection) window._adminShowSection('section-shame');
            document.getElementById('shameFormTitle').textContent = 'Add Box';
            document.getElementById('shameAction').value = 'add_shame';
            document.getElementById('shId').value = '';
            document.getElementById('sh_name').value = '';
            document.getElementById('sh_system').value = '40k';
            document.getElementById('sh_faction').value = '';
            document.getElementById('sh_count').value = '';
            document.getElementById('sh_status').value = 'sealed';
            document.getElementById('sh_acquired').value = '';
            document.getElementById('sh_notes').value = '';
            document.getElementById('shameSubmitBtn').textContent = 'Add Box';
            const wrap = document.getElementById('shameFormWrap');
            wrap.style.display = 'block';
            document.getElementById('sh_name').focus();
            wrap.scrollIntoView({
              behavior: 'smooth',
              block: 'nearest'
            });
          }

          function openShameEdit(btn) {
            if (window._adminShowSection) window._adminShowSection('section-shame');
            document.getElementById('shameFormTitle').textContent = 'Edit Box';
            document.getElementById('shameAction').value = 'edit_shame';
            document.getElementById('shId').value = btn.dataset.id;
            document.getElementById('sh_name').value = btn.dataset.name || '';
            document.getElementById('sh_system').value = btn.dataset.system || '40k';
            document.getElementById('sh_faction').value = btn.dataset.faction || '';
            document.getElementById('sh_count').value = btn.dataset.count > 0 ? btn.dataset.count : '';
            document.getElementById('sh_status').value = btn.dataset.status || 'sealed';
            document.getElementById('sh_acquired').value = btn.dataset.acquired || '';
            document.getElementById('sh_notes').value = btn.dataset.notes || '';
            document.getElementById('shameSubmitBtn').textContent = 'Save Changes';
            const wrap = document.getElementById('shameFormWrap');
            wrap.style.display = 'block';
            wrap.scrollIntoView({
              behavior: 'smooth',
              block: 'nearest'
            });
          }

          document.getElementById('shameCancelBtn')?.addEventListener('click', () => {
            document.getElementById('shameFormWrap').style.display = 'none';
          });

          async function promoteShame(id, dest) {
            const label = dest === 'planned' ? 'Planned' : 'On the Bench';
            if (!confirm('Promote this box to ' + label + '? A new entry will be created there.')) return;
            const fd = new FormData();
            fd.append('action', 'promote_shame');
            fd.append('sh_id', id);
            fd.append('promote_to', dest);
            try {
              const r = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
              const j = await r.json();
              if (j.ok) {
                location.href = ADMIN_PHP + '#section-' + dest;
              } else {
                alert('Promote failed: ' + (j.error || 'unknown error'));
              }
            } catch (e) {
              alert('Promote failed (bad response): ' + e.message);
            }
          }

          async function promoteWishlist(id) {
            if (!confirm('Mark as purchased and add to Pile of Shame?')) return;
            const fd = new FormData();
            fd.append('action', 'promote_wishlist');
            fd.append('wl_id', id);
            try {
              const r = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
              const j = await r.json();
              if (j.ok) {
                const row = document.getElementById('wish-row-' + id);
                const btn = row?.querySelector('button[onclick*="promoteWishlist"]');
                if (btn) btn.outerHTML = '<span style="font-size:10px;color:#c9a227;font-family:\'Cinzel\',serif">Promoted &rarr; Shame</span>';
              } else {
                alert('Promote failed: ' + (j.error || 'unknown error'));
              }
            } catch (e) {
              alert('Promote failed: ' + e.message);
            }
          }

          async function markOrdered(id) {
            const fd = new FormData();
            fd.append('action', 'set_wishlist_ordered');
            fd.append('wl_id', id);
            fd.append('wl_ordered_date', new Date().toISOString().slice(0, 10));
            try {
              const r = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
              const j = await r.json();
              if (j.ok) location.reload();
              else alert('Failed: ' + (j.error || 'unknown error'));
            } catch (e) { alert('Failed: ' + e.message); }
          }

          async function clearOrdered(id) {
            const fd = new FormData();
            fd.append('action', 'set_wishlist_ordered');
            fd.append('wl_id', id);
            fd.append('wl_ordered_date', '');
            try {
              const r = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
              const j = await r.json();
              if (j.ok) location.reload();
              else alert('Failed: ' + (j.error || 'unknown error'));
            } catch (e) { alert('Failed: ' + e.message); }
          }

          async function promotePlanned(id) {
            if (!confirm('Start painting this scheme? A new Bench entry will be created at stage: Built.')) return;
            const fd = new FormData();
            fd.append('action', 'promote_planned');
            fd.append('planned_id', id);
            try {
              const r = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
              const j = await r.json();
              if (j.ok) {
                location.href = ADMIN_PHP + '#section-bench';
              } else {
                alert('Promote failed: ' + (j.error || 'unknown error'));
              }
            } catch (e) {
              alert('Promote failed: ' + e.message);
            }
          }

          // @mention picker for jn_body
          (function() {
            if (!document.getElementById('jn_body')) return;
            const JN_MENTIONABLES = JN_MENTIONABLES_DATA;

            const TYPE_LABEL = {
              scheme: 'Scheme',
              recipe: 'Recipe',
              wd: 'WD',
              bench: 'Bench'
            };
            const TYPE_COLOR = {
              scheme: '#3a6080',
              recipe: '#4a2a6a',
              wd: '#7a5a10',
              bench: '#2a5a3a'
            };

            const textarea = document.getElementById('jn_body');
            const picker = document.getElementById('jnMentionPicker');
            const list = document.getElementById('jnMentionList');
            let atPos = -1;

            function openPicker(q) {
              const lower = q.toLowerCase();
              const hits = JN_MENTIONABLES.filter(m => m.label.toLowerCase().includes(lower) || m.type.includes(lower)).slice(0, 12);
              if (!hits.length) {
                closePicker();
                return;
              }
              list.innerHTML = hits.map((m, i) => `<div class="jnmp-row" data-idx="${i}" style="padding:7px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;border-bottom:1px solid #1e1a10"><span style="font-size:10px;padding:2px 6px;border-radius:3px;background:${TYPE_COLOR[m.type]};color:#e8d8a0;font-family:'Cinzel',serif;letter-spacing:.04em">${TYPE_LABEL[m.type]}</span><span style="font-size:12px;color:#c4b49a">${m.label}</span></div>`).join('');
              list.querySelectorAll('.jnmp-row').forEach((row, i) => {
                row.addEventListener('mouseenter', () => list.querySelectorAll('.jnmp-row').forEach((r, j) => r.style.background = j === i ? '#1c1608' : ''));
                row.addEventListener('mouseleave', () => row.style.background = '');
                row.addEventListener('mousedown', ev => {
                  ev.preventDefault();
                  insertMention(hits[i]);
                });
              });
              const rect = textarea.getBoundingClientRect();
              const wrap = textarea.closest('.form-full');
              picker.style.top = (textarea.offsetTop + textarea.offsetHeight + 4) + 'px';
              picker.style.left = '0';
              picker.style.display = 'block';
            }

            function closePicker() {
              picker.style.display = 'none';
              atPos = -1;
            }

            function insertMention(m) {
              const val = textarea.value;
              const token = `@[${m.type}:${m.id}|${m.label}]`;
              textarea.value = val.slice(0, atPos) + token + val.slice(textarea.selectionEnd);
              const cur = atPos + token.length;
              textarea.setSelectionRange(cur, cur);
              textarea.focus();
              closePicker();
            }

            textarea.addEventListener('keyup', ev => {
              const pos = textarea.selectionStart;
              const text = textarea.value.slice(0, pos);
              const at = text.lastIndexOf('@');
              if (at === -1 || text.slice(at).includes(' ') || text.slice(at).includes('\n')) {
                closePicker();
                return;
              }
              atPos = at;
              openPicker(text.slice(at + 1));
            });

            textarea.addEventListener('keydown', ev => {
              if (ev.key === 'Escape') closePicker();
            });
            document.addEventListener('click', ev => {
              if (!picker.contains(ev.target) && ev.target !== textarea) closePicker();
            });
          })();

        const COND_CYCLE = {
          prime: 'workhorse',
          workhorse: 'retired',
          retired: 'prime'
        };
        const COND_LABEL = {
          prime: 'Prime',
          workhorse: 'Workhorse',
          retired: 'Retired'
        };

        function paintStarSet(n) {
          document.getElementById('p_stars').value = n || '';
          document.querySelectorAll('#paintStarPicker .bsp-star').forEach(s => {
            s.classList.toggle('on', parseInt(s.dataset.val) <= n);
            s.classList.remove('hover');
          });
        }

        (function() {
          document.querySelectorAll('#paintStarPicker .bsp-star').forEach(star => {
            const val = parseInt(star.dataset.val);
            star.addEventListener('mouseenter', () => {
              document.querySelectorAll('#paintStarPicker .bsp-star').forEach(s => {
                s.classList.remove('on');
                s.classList.toggle('hover', parseInt(s.dataset.val) <= val);
              });
            });
            star.addEventListener('mouseleave', () => {
              document.querySelectorAll('#paintStarPicker .bsp-star').forEach(s => s.classList.remove('hover'));
              paintStarSet(parseInt(document.getElementById('p_stars').value) || 0);
            });
            star.addEventListener('click', () => {
              const cur = parseInt(document.getElementById('p_stars').value) || 0;
              paintStarSet(val === cur ? 0 : val);
            });
          });
        })();

        function brushStarSet(n) {
          document.getElementById('br_stars').value = n || '';
          document.querySelectorAll('#brushStarPicker .bsp-star').forEach(s => {
            s.classList.toggle('on', parseInt(s.dataset.val) <= n);
            s.classList.remove('hover');
          });
        }

        (function() {
          document.querySelectorAll('#brushStarPicker .bsp-star').forEach(star => {
            const val = parseInt(star.dataset.val);
            star.addEventListener('mouseenter', () => {
              document.querySelectorAll('#brushStarPicker .bsp-star').forEach(s => {
                s.classList.remove('on');
                s.classList.toggle('hover', parseInt(s.dataset.val) <= val);
              });
            });
            star.addEventListener('mouseleave', () => {
              document.querySelectorAll('#brushStarPicker .bsp-star').forEach(s => s.classList.remove('hover'));
              brushStarSet(parseInt(document.getElementById('br_stars').value) || 0);
            });
            star.addEventListener('click', () => {
              const cur = parseInt(document.getElementById('br_stars').value) || 0;
              brushStarSet(val === cur ? 0 : val);
            });
          });
        })();

        function openBrushAdd() {
          if (window._adminShowSection) window._adminShowSection('section-brushes');
          document.getElementById('brushFormTitle').textContent = 'Add Brush';
          document.getElementById('brushAction').value = 'add_brush';
          document.getElementById('brushId').value = '';
          document.getElementById('br_brand').value = '';
          document.getElementById('br_series').value = '';
          document.getElementById('br_size').value = '';
          document.getElementById('br_material').value = '';
          document.getElementById('br_use').value = '';
          document.getElementById('br_condition').value = 'prime';
          brushStarSet(0);
          document.getElementById('br_date_start').value = '';
          document.getElementById('br_notes').value = '';
          document.getElementById('brushSubmitBtn').textContent = 'Add Brush';
          const wrap = document.getElementById('brushFormWrap');
          wrap.style.display = 'block';
          document.getElementById('br_brand').focus();
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        function openBrushEdit(btn) {
          if (window._adminShowSection) window._adminShowSection('section-brushes');
          document.getElementById('brushFormTitle').textContent = 'Edit Brush';
          document.getElementById('brushAction').value = 'edit_brush';
          document.getElementById('brushId').value = btn.dataset.id;
          document.getElementById('br_brand').value = btn.dataset.brand;
          document.getElementById('br_series').value = btn.dataset.series || '';
          document.getElementById('br_size').value = btn.dataset.size || '';
          document.getElementById('br_material').value = btn.dataset.material || '';
          document.getElementById('br_use').value = btn.dataset.use || '';
          document.getElementById('br_condition').value = btn.dataset.condition || 'prime';
          brushStarSet(parseInt(btn.dataset.stars) || 0);
          document.getElementById('br_date_start').value = btn.dataset.date_start || '';
          document.getElementById('br_notes').value = btn.dataset.notes || '';
          document.getElementById('brushSubmitBtn').textContent = 'Save Changes';
          const wrap = document.getElementById('brushFormWrap');
          wrap.style.display = 'block';
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        document.getElementById('brushCancelBtn')?.addEventListener('click', () => {
          document.getElementById('brushFormWrap').style.display = 'none';
        });

        document.getElementById('supplyCancelBtn')?.addEventListener('click', () => {
          document.getElementById('supplyFormWrap').style.display = 'none';
        });

        function openSupplyAdd() {
          if (window._adminShowSection) window._adminShowSection('section-supplies');
          document.getElementById('supplyFormTitle').textContent = 'Add Supply';
          document.getElementById('supplyAction').value = 'add_supply';
          document.getElementById('supplyId').value = '';
          document.getElementById('sp_name').value = '';
          document.getElementById('sp_brand').value = '';
          document.getElementById('sp_type').value = '';
          document.getElementById('sp_condition').value = 'prime';
          document.getElementById('sp_acquired').value = '';
          document.getElementById('sp_notes').value = '';
          document.getElementById('supplySubmitBtn').textContent = 'Add Supply';
          const wrap = document.getElementById('supplyFormWrap');
          wrap.style.display = 'block';
          document.getElementById('sp_name').focus();
          wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function openSupplyEdit(btn) {
          if (window._adminShowSection) window._adminShowSection('section-supplies');
          document.getElementById('supplyFormTitle').textContent = 'Edit Supply';
          document.getElementById('supplyAction').value = 'edit_supply';
          document.getElementById('supplyId').value = btn.dataset.id;
          document.getElementById('sp_name').value = btn.dataset.name;
          document.getElementById('sp_brand').value = btn.dataset.brand || '';
          document.getElementById('sp_type').value = btn.dataset.type || '';
          document.getElementById('sp_condition').value = btn.dataset.condition || 'prime';
          document.getElementById('sp_acquired').value = btn.dataset.acquired || '';
          document.getElementById('sp_notes').value = btn.dataset.notes || '';
          document.getElementById('supplySubmitBtn').textContent = 'Save Changes';
          const wrap = document.getElementById('supplyFormWrap');
          wrap.style.display = 'block';
          wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        async function toggleSupplyCond(btn) {
          const sid = btn.dataset.bid;
          const cur = btn.dataset.cond;
          const next = COND_CYCLE[cur] ?? 'prime';
          const fd = new FormData();
          fd.append('action', 'set_supply_condition');
          fd.append('supply_id', sid);
          fd.append('condition', next);
          const res = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
          const data = await res.json();
          if (data.ok) {
            btn.dataset.cond = next;
            btn.textContent = COND_LABEL[next];
            btn.className = 'brush-cond-btn cond-' + next;
          }
        }

        async function toggleBrushCond(btn) {
          const bid = btn.dataset.bid;
          const cur = btn.dataset.cond;
          const next = COND_CYCLE[cur] ?? 'prime';
          const fd = new FormData();
          fd.append('action', 'set_brush_condition');
          fd.append('brush_id', bid);
          fd.append('condition', next);
          const res = await fetch(ADMIN_PHP, {
            method: 'POST',
            body: fd
          });
          const data = await res.json();
          if (data.ok) {
            btn.dataset.cond = next;
            btn.textContent = COND_LABEL[next];
            btn.className = 'brush-cond-btn cond-' + next;
          }
        }

        document.querySelectorAll('.mo-feature-btn').forEach(btn => {
          btn.addEventListener('click', async function() {
            const fd = new FormData();
            fd.append('action', 'toggle_model_feature');
            fd.append('model_id', this.dataset.id);
            const data = await fetch(ADMIN_PHP, { method: 'POST', body: fd }).then(r => r.json());
            if (!data.ok) return;
            const nowOn = !!data.featured;
            this.classList.toggle('mo-feature-active', nowOn);
            this.title = nowOn ? 'Remove from showcase' : 'Add to showcase';
            // Show or hide the image pick strip for multi-image schemes
            const picks = this.parentElement.querySelector('.sc-img-picks');
            if (picks) {
              picks.classList.toggle('sc-img-picks-hidden', !nowOn);
              if (nowOn && Array.isArray(data.featured)) {
                picks.querySelectorAll('.sc-img-pick').forEach(p => {
                  const active = data.featured.includes(parseInt(p.dataset.idx));
                  p.classList.toggle('sc-img-pick-active', active);
                  p.title = active ? 'Remove photo ' + (parseInt(p.dataset.idx)+1) + ' from showcase' : 'Add photo ' + (parseInt(p.dataset.idx)+1) + ' to showcase';
                });
              }
            }
          });
        });

        document.querySelectorAll('.sc-img-pick').forEach(btn => {
          btn.addEventListener('click', async function() {
            const fd = new FormData();
            fd.append('action', 'toggle_model_showcase_image');
            fd.append('model_id', this.dataset.id);
            fd.append('image_idx', this.dataset.idx);
            const data = await fetch(ADMIN_PHP, { method: 'POST', body: fd }).then(r => r.json());
            if (!data.ok) return;
            this.classList.toggle('sc-img-pick-active', data.active);
            this.title = data.active ? 'Remove photo ' + (parseInt(this.dataset.idx)+1) + ' from showcase' : 'Add photo ' + (parseInt(this.dataset.idx)+1) + ' to showcase';
            // If all images deselected, also deactivate the star button
            if (data.featured === false) {
              const star = this.closest('.model-row, [class*="model-row"]')
                           ? this.parentElement.previousElementSibling
                           : null;
              if (star && star.classList.contains('mo-feature-btn')) {
                star.classList.remove('mo-feature-active');
                star.title = 'Add to showcase';
              }
              this.closest('.sc-img-picks').classList.add('sc-img-picks-hidden');
            }
          });
        });

        document.querySelectorAll('.fo-pin-btn').forEach(btn => {
          btn.addEventListener('click', async function() {
            const fd = new FormData();
            fd.append('action', 'toggle_force_pin');
            fd.append('force_id', this.dataset.id);
            const data = await fetch(ADMIN_PHP, {
              method: 'POST',
              body: fd
            }).then(r => r.json());
            if (!data.ok) return;
            if (data.unpinned_id) {
              const other = document.querySelector('.fo-pin-btn[data-id="' + data.unpinned_id + '"]');
              if (other) {
                other.classList.remove('fo-pin-active');
                other.title = 'Pin to top';
              }
            }
            this.classList.toggle('fo-pin-active', data.pinned);
            this.title = data.pinned ? 'Unpin' : 'Pin to top';
          });
        });

        const BENCH_STAGE_CYCLE = {
          built: 'primed',
          primed: 'basecoated',
          basecoated: 'washed',
          washed: 'highlighted',
          highlighted: 'based',
          based: 'varnished',
          varnished: 'done',
          done: 'built'
        };
        const BENCH_STAGE_LABEL = {
          built: 'Built',
          primed: 'Primed',
          basecoated: 'Basecoated',
          washed: 'Washed',
          highlighted: 'Highlighted',
          based: 'Based',
          varnished: 'Varnished',
          done: 'Done'
        };
        const BENCH_MAX_IMG = BENCH_MAX_IMAGES_JS;
        let selectedBn = new Set();
        let selectedBnBrushes = new Set();

        function buildListBn(filter) {
          const q = filter.toLowerCase();
          const list = document.getElementById('colorListBn');
          list.innerHTML = '';
          ALL_PAINTS.forEach(key => {
            const parts = key.split('|');
            const label = parts[1] + ' (' + parts[0] + (parts[2] ? ' — ' + parts[2] : '') + ')';
            if (q && !label.toLowerCase().includes(q)) return;
            const el = document.createElement('span');
            el.className = 'cp-item' + (selectedBn.has(key) ? ' selected' : '');
            el.textContent = label;
            el.dataset.key = key;
            el.addEventListener('click', () => {
              if (selectedBn.has(key)) {
                selectedBn.delete(key);
                el.classList.remove('selected');
              } else {
                selectedBn.add(key);
                el.classList.add('selected');
              }
              updateHiddenBn();
            });
            list.appendChild(el);
          });
        }

        function updateHiddenBn() {
          document.getElementById('selectedCountBn').textContent =
            selectedBn.size + ' colour' + (selectedBn.size !== 1 ? 's' : '') + ' selected';
          const wrap = document.getElementById('colorInputsBn');
          wrap.innerHTML = '';
          selectedBn.forEach(key => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'bench_colors[]';
            inp.value = key;
            wrap.appendChild(inp);
          });
        }

        function refreshBenchBrushPicker() {
          document.querySelectorAll('#benchBrushPicker .bbp-item').forEach(el => {
            el.classList.toggle('selected', selectedBnBrushes.has(el.dataset.id));
          });
          const wrap = document.getElementById('brushInputsBn');
          if (!wrap) return;
          wrap.innerHTML = '';
          selectedBnBrushes.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'bench_brushes[]';
            inp.value = id;
            wrap.appendChild(inp);
          });
        }

        document.getElementById('colorSearchBn')?.addEventListener('input', function() {
          buildListBn(this.value);
        });

        document.querySelectorAll('#benchBrushPicker .bbp-item').forEach(el => {
          el.addEventListener('click', () => {
            const id = el.dataset.id;
            if (selectedBnBrushes.has(id)) selectedBnBrushes.delete(id);
            else selectedBnBrushes.add(id);
            refreshBenchBrushPicker();
          });
        });

        function setBenchThumb(slot, path) {
          const el = document.getElementById('bn_img_thumb_' + slot);
          const del = document.getElementById('bn_img_del_label_' + slot);
          if (path) {
            el.style.backgroundImage = 'url("' + path + '")';
            del.style.display = 'inline-block';
          } else {
            el.style.backgroundImage = '';
            del.style.display = 'none';
          }
        }

        function resetBenchImageGrid(images) {
          for (let i = 1; i <= BENCH_MAX_IMG; i++) {
            const path = images[i - 1] || null;
            setBenchThumb(i, path);
            const fileInp = document.getElementById('bn_image_input_' + i);
            if (fileInp) fileInp.value = '';
            const delChk = document.querySelector('#bn_img_del_label_' + i + ' input[type=checkbox]');
            if (delChk) delChk.checked = false;
          }
        }

        function openBenchAdd() {
          if (window._adminShowSection) window._adminShowSection('section-bench');
          selectedBn = new Set();
          selectedBnBrushes = new Set();
          document.getElementById('benchFormTitle').textContent = 'Add Bench Entry';
          document.getElementById('benchAction').value = 'add_bench';
          document.getElementById('benchId').value = '';
          document.getElementById('bn_name').value = '';
          document.getElementById('bn_faction').value = '';
          document.getElementById('bn_system').value = '';
          document.getElementById('bn_stage').value = 'built';
          document.getElementById('bn_date_start').value = '';
          document.getElementById('bn_notes').value = '';
          document.getElementById('bn_codex_source').value = '';
          document.getElementById('benchSubmitBtn').textContent = 'Add Entry';
          buildListBn('');
          updateHiddenBn();
          refreshBenchBrushPicker();
          resetBenchImageGrid([]);
          if (typeof setRecipePickerSelection === 'function') setRecipePickerSelection('benchRecipePicker', 'benchRecipeInputs', 'bench_recipes[]', []);
          const wrap = document.getElementById('benchFormWrap');
          wrap.style.display = 'block';
          document.getElementById('bn_name').focus();
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        function openBenchEdit(btn) {
          if (window._adminShowSection) window._adminShowSection('section-bench');
          const colors  = (() => { try { return JSON.parse(btn.dataset.colors  || '[]'); } catch { return []; } })();
          const brushes = (() => { try { return JSON.parse(btn.dataset.brushes || '[]'); } catch { return []; } })();
          const images  = (() => { try { return JSON.parse(btn.dataset.images  || '[]'); } catch { return []; } })();
          selectedBn = new Set(colors);
          selectedBnBrushes = new Set(brushes);
          document.getElementById('benchFormTitle').textContent = 'Edit Bench Entry';
          document.getElementById('benchAction').value = 'edit_bench';
          document.getElementById('benchId').value = btn.dataset.id;
          document.getElementById('bn_name').value = btn.dataset.name;
          document.getElementById('bn_faction').value = btn.dataset.faction || '';
          document.getElementById('bn_sub_faction').value = btn.dataset.sub_faction || '';
          document.getElementById('bn_system').value = btn.dataset.system || '';
          document.getElementById('bn_stage').value = btn.dataset.stage || 'built';
          document.getElementById('bn_date_start').value = btn.dataset.date_start || '';
          document.getElementById('bn_notes').value = btn.dataset.notes || '';
          document.getElementById('bn_codex_source').value = btn.dataset.codex_source || '';
          document.getElementById('benchSubmitBtn').textContent = 'Save Changes';
          buildListBn('');
          updateHiddenBn();
          refreshBenchBrushPicker();
          resetBenchImageGrid(images);
          if (typeof setRecipePickerSelection === 'function') {
            const recipes = (() => { try { return JSON.parse(btn.dataset.recipes || '[]'); } catch { return []; } })();
            setRecipePickerSelection('benchRecipePicker', 'benchRecipeInputs', 'bench_recipes[]', recipes);
          }
          const wrap = document.getElementById('benchFormWrap');
          wrap.style.display = 'block';
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        document.getElementById('benchCancelBtn')?.addEventListener('click', () => {
          document.getElementById('benchFormWrap').style.display = 'none';
        });

        function toggleGoalForm(year) {
          const f = document.getElementById('goal-form-' + year);
          if (!f) return;
          const open = f.style.display === 'none';
          f.style.display = open ? 'flex' : 'none';
          if (open) document.getElementById('goal-input-' + year)?.focus();
        }
        async function saveGoal(year) {
          const input = document.getElementById('goal-input-' + year);
          const target = input ? +input.value : 0;
          if (!target || target < 1) { if (input) input.focus(); return; }
          const seedInput = document.getElementById('goal-seed-' + year);
          const seed = seedInput ? Math.max(0, +seedInput.value || 0) : 0;
          const fd = new FormData();
          fd.append('action', 'set_goal');
          fd.append('goal_year', year);
          fd.append('goal_target', target);
          fd.append('goal_seed', seed);
          await fetch(ADMIN_PHP, { method: 'POST', body: fd });
          window.location.href = ADMIN_PHP + '#section-stats';
        }
        async function deleteGoal(year) {
          const fd = new FormData();
          fd.append('action', 'set_goal');
          fd.append('goal_year', year);
          fd.append('goal_target', 0);
          await fetch(ADMIN_PHP, { method: 'POST', body: fd });
          window.location.href = ADMIN_PHP + '#section-stats';
        }

        async function cycleBenchStage(btn) {
          const bid = btn.dataset.bid;
          const cur = btn.dataset.stage;
          const next = BENCH_STAGE_CYCLE[cur] ?? 'built';
          const fd = new FormData();
          fd.append('action', 'set_bench_stage');
          fd.append('bench_id', bid);
          fd.append('stage', next);
          const res = await fetch(ADMIN_PHP, {
            method: 'POST',
            body: fd
          });
          const data = await res.json();
          if (data.ok) {
            btn.dataset.stage = next;
            btn.textContent = BENCH_STAGE_LABEL[next];
            btn.className = 'bench-stage-btn stage-' + next;
          }
        }

        const RC_TECHNIQUES = ['basecoat', 'wash', 'shade', 'layer', 'edge', 'highlight', 'glaze', 'drybrush', 'stipple', 'blend', 'special'];

        function recipeStepTpl(step) {
          step = step || {};
          const techOpts = RC_TECHNIQUES.map(t => `<option value="${t}"${step.technique === t ? ' selected' : ''}>${t.charAt(0).toUpperCase() + t.slice(1)}</option>`).join('');
          const esc = s => String(s ?? '').replace(/"/g, '&quot;');
          return `<div class="rc-step"><span class="rc-step-num"></span><input type="text" list="rc_paintList" name="step_paint[]"      value="${esc(step.paint)}"     placeholder="Brand|Name|Layer"><input type="text" list="rc_paintList" name="step_mix_paint[]"  value="${esc(step.mix_paint)}" placeholder="+ mix paint (opt)"><select name="step_technique[]">${techOpts}</select><input type="text" name="step_ratio[]" value="${esc(step.ratio)}" placeholder="ratio (e.g. 3:1)"><input type="text" name="step_note[]"  value="${esc(step.note)}"  placeholder="note"><input type="text" list="rc_brushList" name="step_brush[]" value="${esc(step.brush)}" placeholder="brush (opt)"><div class="rc-step-actions"><button type="button" onclick="rcStepUp(this)" title="Move up">▲</button><button type="button" onclick="rcStepDown(this)" title="Move down">▼</button><button type="button" class="rc-step-del" onclick="rcStepDel(this)" title="Remove">×</button></div></div>`;
        }

        function renumberRecipeSteps() {
          document.querySelectorAll('#rc_steps .rc-step').forEach((row, i) => {
            row.querySelector('.rc-step-num').textContent = (i + 1) + '.';
          });
        }

        function addRecipeStep(step) {
          const host = document.getElementById('rc_steps');
          host.insertAdjacentHTML('beforeend', recipeStepTpl(step));
          renumberRecipeSteps();
        }

        function rcStepUp(btn) {
          const row = btn.closest('.rc-step');
          if (row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
          renumberRecipeSteps();
        }

        function rcStepDown(btn) {
          const row = btn.closest('.rc-step');
          if (row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
          renumberRecipeSteps();
        }

        function rcStepDel(btn) {
          btn.closest('.rc-step').remove();
          renumberRecipeSteps();
        }

        function openRecipeAdd() {
          if (window._adminShowSection) window._adminShowSection('section-recipes');
          document.getElementById('recipeFormTitle').textContent = 'Add Recipe';
          document.getElementById('recipeAction').value = 'add_recipe';
          document.getElementById('recipeId').value = '';
          document.getElementById('rc_name').value = '';
          document.getElementById('rc_category').value = '';
          document.getElementById('rc_faction').value = '';
          document.getElementById('rc_description').value = '';
          document.getElementById('rc_notes').value = '';
          document.getElementById('rc_steps').innerHTML = '';
          document.getElementById('rc_image_preview').style.display = 'none';
          document.getElementById('rc_image_file').value = '';
          document.getElementById('delete_rc_image').checked = false;
          addRecipeStep();
          document.getElementById('recipeSubmitBtn').textContent = 'Add Recipe';
          const wrap = document.getElementById('recipeFormWrap');
          wrap.style.display = 'block';
          document.getElementById('rc_name').focus();
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        function openRecipeEdit(btn) {
          if (window._adminShowSection) window._adminShowSection('section-recipes');
          const steps = (() => { try { return JSON.parse(btn.dataset.steps || '[]'); } catch { return []; } })();
          document.getElementById('recipeFormTitle').textContent = 'Edit Recipe';
          document.getElementById('recipeAction').value = 'edit_recipe';
          document.getElementById('recipeId').value = btn.dataset.id;
          document.getElementById('rc_name').value = btn.dataset.name;
          document.getElementById('rc_category').value = btn.dataset.category || '';
          document.getElementById('rc_faction').value = btn.dataset.faction || '';
          document.getElementById('rc_description').value = btn.dataset.description || '';
          document.getElementById('rc_notes').value = btn.dataset.notes || '';
          document.getElementById('rc_steps').innerHTML = '';
          if (steps.length) steps.forEach(s => addRecipeStep(s));
          else addRecipeStep();
          const img = btn.dataset.image || '';
          const preview = document.getElementById('rc_image_preview');
          document.getElementById('delete_rc_image').checked = false;
          document.getElementById('rc_image_file').value = '';
          if (img) {
            document.getElementById('rc_image_thumb').src = img;
            preview.style.display = 'block';
          } else {
            preview.style.display = 'none';
          }
          document.getElementById('recipeSubmitBtn').textContent = 'Save Changes';
          const wrap = document.getElementById('recipeFormWrap');
          wrap.style.display = 'block';
          wrap.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }

        document.getElementById('recipeCancelBtn')?.addEventListener('click', () => {
          document.getElementById('recipeFormWrap').style.display = 'none';
        });

        function setRecipePickerSelection(pickerId, inputsId, inputName, ids) {
          const picker = document.getElementById(pickerId);
          const host = document.getElementById(inputsId);
          if (!picker || !host) return;
          const set = new Set(ids || []);
          picker.querySelectorAll('.rc-pill').forEach(el => {
            el.classList.toggle('selected', set.has(el.dataset.id));
          });
          host.innerHTML = '';
          set.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = inputName;
            inp.value = id;
            host.appendChild(inp);
          });
        }

        document.querySelectorAll('.rc-pill-picker').forEach(picker => {
          const form = picker.dataset.form; // gallery | planned | bench
          const inputName = form + '_recipes[]';
          const hostId = (form === 'gallery' ? 'galleryRecipeInputs' : form === 'planned' ? 'plannedRecipeInputs' : 'benchRecipeInputs');
          picker.addEventListener('click', e => {
            const pill = e.target.closest('.rc-pill');
            if (!pill) return;
            pill.classList.toggle('selected');
            const selected = [...picker.querySelectorAll('.rc-pill.selected')].map(el => el.dataset.id);
            const host = document.getElementById(hostId);
            host.innerHTML = '';
            selected.forEach(id => {
              const inp = document.createElement('input');
              inp.type = 'hidden';
              inp.name = inputName;
              inp.value = id;
              host.appendChild(inp);
            });
          });
        });

        (function() {
          const searchEl = document.getElementById('conv-search');
          const countEl = document.getElementById('conv-count');
          const rows = document.querySelectorAll('#conv-table tbody tr.conv-row');

          function filterConv() {
            const q = searchEl.value.trim().toLowerCase();
            let n = 0;
            rows.forEach(r => {
              const match = !q || r.textContent.toLowerCase().includes(q);
              r.style.display = match ? '' : 'none';
              if (match) n++;
            });
            countEl.textContent = n + ' of ' + rows.length;
          }
          searchEl.addEventListener('input', filterConv);
          filterConv();
        })();

      function convEdit(btn) {
        if (window._adminShowSection) window._adminShowSection('section-conversions');
        const r = btn.closest('tr');
        const valRaw = r.dataset.val;
        const paRaw = r.dataset.pa;
        const ttcRaw = r.dataset.ttc;
        document.getElementById('conv-form-title').textContent = 'Edit: ' + r.dataset.cit;
        document.getElementById('conv-action').value = 'edit_conversion';
        document.getElementById('cv_orig').value = r.dataset.cit;
        document.getElementById('cv_citadel').value = r.dataset.cit;
        document.getElementById('cv_vallejo').value = (valRaw === '-' ? '' : valRaw);
        document.getElementById('cv_pa').value = (paRaw === '-' ? '' : paRaw);
        document.getElementById('cv_ttc').value = (ttcRaw === '-' ? '' : ttcRaw);
        document.getElementById('cv_val_q').value = r.dataset.valQ;
        document.getElementById('cv_pa_q').value = r.dataset.paQ;
        document.getElementById('cv_ttc_q').value = r.dataset.ttcQ;
        document.getElementById('conv-save-btn').textContent = 'Save Changes';
        document.getElementById('conv-cancel-btn').style.display = '';
        document.getElementById('conv-form-wrap').scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }

      function convCancelEdit() {
        document.getElementById('conv-form-title').textContent = 'Add Row';
        document.getElementById('conv-action').value = 'add_conversion';
        document.getElementById('cv_orig').value = '';
        document.getElementById('conv-save-btn').textContent = 'Add Row';
        document.getElementById('conv-cancel-btn').style.display = 'none';
        document.getElementById('conv-form').reset();
      }

      function initAdminSections() {
        const headings = Array.from(document.querySelectorAll('h2[id^="section-"]'));
        if (!headings.length) return;

        headings.forEach(function(h2) {
          const body = document.createElement('div');
          body.className = 'admin-section-body';
          body.dataset.for = h2.id;
          let next = h2.nextSibling;
          while (next) {
            const cur = next;
            next = next.nextSibling;
            if (cur.nodeType === 1 && cur.tagName === 'H2' && cur.id && cur.id.indexOf('section-') === 0) break;
            body.appendChild(cur);
          }
          h2.after(body);
        });

        function showSection(id) {
          headings.forEach(function(h) {
            h.classList.remove('section-active');
            const b = h.nextElementSibling;
            if (b && b.classList.contains('admin-section-body')) b.classList.remove('section-active');
          });
          document.querySelectorAll('.as-link').forEach(function(l) { l.classList.remove('active'); });
          const h2 = document.getElementById(id);
          if (!h2) return;
          h2.classList.add('section-active');
          const body = h2.nextElementSibling;
          if (body && body.classList.contains('admin-section-body')) body.classList.add('section-active');
          const link = document.querySelector('.as-link[href="#' + id + '"]');
          if (link) link.classList.add('active');
          history.replaceState(null, '', '#' + id);
          window.scrollTo(0, 0);
        }

        window._adminShowSection = showSection;

        document.querySelectorAll('.as-link[href^="#section-"]').forEach(function(link) {
          link.addEventListener('click', function(e) {
            e.preventDefault();
            showSection(link.getAttribute('href').slice(1));
            const sidebar = document.getElementById('admin-sidebar');
            if (sidebar) sidebar.classList.remove('open');
          });
        });

        let targetId = 'section-stats';
        if (location.hash && location.hash.indexOf('#section-') === 0) {
          targetId = location.hash.slice(1);
        } else if (document.body.dataset.openSection) {
          targetId = document.body.dataset.openSection;
        }
        showSection(targetId);
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminSections);
      } else {
        initAdminSections();
      }

      document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('back-to-top');
        if (btn) {
          window.addEventListener('scroll', function() {
            btn.style.display = window.scrollY > 200 ? 'flex' : 'none';
          }, { passive: true });
          btn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
          });
        }

        (function() {
          const toggle = document.getElementById('admin-sidebar-toggle');
          const sidebar = document.getElementById('admin-sidebar');
          if (!toggle || !sidebar) return;
          toggle.addEventListener('click', function() {
            if (window.innerWidth > 760) {
              document.body.classList.remove('sidebar-collapsed');
              try { localStorage.setItem('admin-sidebar-collapsed', '0'); } catch(e) {}
            } else {
              sidebar.classList.toggle('open');
            }
          });
          document.addEventListener('click', function(e) {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle) {
              sidebar.classList.remove('open');
            }
          });
        })();

        (function() {
          var nav  = document.querySelector('.as-nav');
          var fade = document.getElementById('asnav-fade');
          if (!nav || !fade) return;
          function checkFade() { fade.classList.toggle('hidden', nav.scrollTop + nav.clientHeight >= nav.scrollHeight - 20); }
          nav.addEventListener('scroll', checkFade, { passive: true });
          window.addEventListener('resize', checkFade);
          window.addEventListener('load', checkFade);
          checkFade();
        })();

        (function() {
          var collapseBtn = document.getElementById('as-collapse-btn');
          if (!collapseBtn) return;
          try { if (localStorage.getItem('admin-sidebar-collapsed') === '1') document.body.classList.add('sidebar-collapsed'); } catch(e) {}
          collapseBtn.addEventListener('click', function() {
            document.body.classList.add('sidebar-collapsed');
            try { localStorage.setItem('admin-sidebar-collapsed', '1'); } catch(e) {}
          });
        })();
      });

      let _sessModalBid = '';
      function openSessionModal(btn) {
        _sessModalBid = btn.dataset.bid;
        document.getElementById('sess-modal-project').textContent = btn.dataset.bname || '';
        const today = new Date().toISOString().slice(0, 10);
        document.getElementById('sess-date').value = today;
        document.getElementById('sess-duration').value = '';
        document.getElementById('sess-note').value = '';
        document.getElementById('sess-modal-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
        document.getElementById('sess-duration').focus();
      }
      function closeSessionModal() {
        document.getElementById('sess-modal-overlay').classList.remove('open');
        document.body.style.overflow = '';
      }
      async function submitSessionLog() {
        const date = document.getElementById('sess-date').value.trim();
        if (!date) { document.getElementById('sess-date').focus(); return; }
        const fd = new FormData();
        fd.append('action', 'log_bench_session');
        fd.append('bench_id', _sessModalBid);
        fd.append('sess_date', date);
        const dur = document.getElementById('sess-duration').value.trim();
        if (dur) fd.append('sess_duration', dur);
        const note = document.getElementById('sess-note').value.trim();
        if (note) fd.append('sess_note', note);
        const res = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) closeSessionModal();
      }
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && document.getElementById('sess-modal-overlay').classList.contains('open')) closeSessionModal();
      });

      let _galSessMid = '';
      let _galSessIdx = -1;
      let _galSessMode = 'add';
      function openGallerySessionModal(btn) {
        _galSessMode = 'add';
        _galSessIdx = -1;
        _galSessMid = btn.dataset.mid;
        document.getElementById('gallery-sess-title').textContent = 'Log Painted Models';
        document.getElementById('gallery-sess-project').textContent = btn.dataset.mname || '';
        const today = new Date().toISOString().slice(0, 10);
        document.getElementById('gallery-sess-date').value = today;
        document.getElementById('gallery-sess-count').value = '';
        document.getElementById('gallery-sess-note').value = '';
        document.getElementById('gallery-sess-idx').value = '-1';
        document.getElementById('gallery-sess-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
        document.getElementById('gallery-sess-count').focus();
      }
      function openGallerySessionEdit(btn) {
        _galSessMode = 'edit';
        _galSessIdx = parseInt(btn.dataset.idx, 10);
        _galSessMid = btn.dataset.mid;
        document.getElementById('gallery-sess-title').textContent = 'Edit Session';
        document.getElementById('gallery-sess-project').textContent = btn.dataset.mname || '';
        document.getElementById('gallery-sess-date').value = btn.dataset.date || '';
        document.getElementById('gallery-sess-count').value = btn.dataset.count || '';
        document.getElementById('gallery-sess-note').value = btn.dataset.note || '';
        document.getElementById('gallery-sess-idx').value = _galSessIdx;
        document.getElementById('gallery-sess-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
        document.getElementById('gallery-sess-count').focus();
      }
      async function deleteGallerySession(btn) {
        if (!confirm('Delete this session?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_gallery_session');
        fd.append('model_id', btn.dataset.mid);
        fd.append('sess_idx', btn.dataset.idx);
        const res = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) location.reload();
      }
      function closeGallerySessionModal() {
        document.getElementById('gallery-sess-overlay').classList.remove('open');
        document.body.style.overflow = '';
      }
      async function submitGallerySessionLog() {
        const date = document.getElementById('gallery-sess-date').value.trim();
        const count = +document.getElementById('gallery-sess-count').value;
        if (!date) { document.getElementById('gallery-sess-date').focus(); return; }
        if (!count || count < 1) { document.getElementById('gallery-sess-count').focus(); return; }
        const fd = new FormData();
        fd.append('action', _galSessMode === 'edit' ? 'edit_gallery_session' : 'log_gallery_session');
        fd.append('model_id', _galSessMid);
        fd.append('sess_date', date);
        fd.append('sess_count', count);
        if (_galSessMode === 'edit') fd.append('sess_idx', _galSessIdx);
        const note = document.getElementById('gallery-sess-note').value.trim();
        if (note) fd.append('sess_note', note);
        const res = await fetch(ADMIN_PHP, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) { closeGallerySessionModal(); location.reload(); }
      }
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && document.getElementById('gallery-sess-overlay').classList.contains('open')) closeGallerySessionModal();
      });

      function openPlannedShopModal() {
        const mustBuy = {};
        PLANNED_DATA.forEach(pl => {
          (pl.colors || []).forEach(c => {
            const parts = c.split('|');
            const brand = parts[0];
            const name = parts.slice(1).join('|') || c;
            const lc = (brand + '|' + name).toLowerCase();
            const stock = paintStock.get(lc) || '';
            if (!paintOwned.has(lc) || stock === 'out') {
              if (!mustBuy[brand]) mustBuy[brand] = {};
              if (!mustBuy[brand][name]) mustBuy[brand][name] = [];
              if (!mustBuy[brand][name].includes(pl.name)) mustBuy[brand][name].push(pl.name);
            }
          });
        });

        const brands = Object.keys(mustBuy).sort();
        const total = brands.reduce((n, b) => n + Object.keys(mustBuy[b]).length, 0);
        let html = '';
        if (total === 0) {
          html = '<p style="color:#5a9a5a;font-family:Cinzel,serif;font-size:12px;text-align:center;padding:24px 0">All paints owned!</p>';
        } else {
          html += `<div class="adm-shop-section adm-shop-must">Must Buy — ${total} paint${total !== 1 ? 's' : ''}</div>`;
          for (const brand of brands) {
            html += `<div class="adm-shop-brand">${brand}</div><ul class="adm-shop-list">`;
            for (const [name, schemes] of Object.entries(mustBuy[brand]).sort()) {
              html += `<li><span class="adm-shop-paint">${name}</span><span class="adm-shop-schemes">${schemes.join(', ')}</span></li>`;
            }
            html += '</ul>';
          }
        }
        const schemeCount = PLANNED_DATA.filter(p => (p.colors || []).length > 0).length;
        document.getElementById('adm-shop-subtitle').textContent = schemeCount + ' scheme' + (schemeCount !== 1 ? 's' : '');
        document.getElementById('adm-shop-content').innerHTML = html;
        document.getElementById('adm-shop-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
      }

      function closeAdmShopModal() {
        document.getElementById('adm-shop-overlay').classList.remove('open');
        document.body.style.overflow = '';
      }
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeAdmShopModal();
      });

      function wishlistTypeChange() {
        const t = document.getElementById('wl_type') ? document.getElementById('wl_type').value : 'paint';
        const nameLabs = {
          paint: 'Paint Name *',
          model: 'Kit / Model Name *',
          brush: 'Series & Size *',
          codex: 'Title *',
          wd: 'Issue Number *'
        };
        const nl = document.getElementById('wl_name_label');
        if (nl) nl.textContent = nameLabs[t] || 'Name *';
        const show = (id, vis) => {
          const el = document.getElementById(id);
          if (el) el.style.display = vis ? '' : 'none';
        };
        show('wl_brand_row', t === 'paint' || t === 'brush');
        show('wl_faction_row', t === 'model' || t === 'codex');
        show('wl_system_row', t === 'model');
      }
      wishlistTypeChange();

      function openWishlistAdd() {
        if (window._adminShowSection) window._adminShowSection('section-wishlist');
        const fw = document.getElementById('wishlistFormWrap');
        if (!fw) return;
        fw.style.display = '';
        document.getElementById('wishlistFormTitle').textContent = 'Add Item';
        document.getElementById('wishlistAction').value = 'add_wishlist_item';
        document.getElementById('wlId').value = '';
        document.getElementById('wishlistSubmitBtn').textContent = 'Add Item';
        document.getElementById('wl_type').value = 'paint';
        document.getElementById('wl_priority').value = 'medium';
        ['wl_name', 'wl_brand', 'wl_faction', 'wl_notes', 'wl_url', 'wl_ordered_date'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.value = '';
        });
        const sys = document.getElementById('wl_system');
        if (sys) sys.value = '';
        wishlistTypeChange();
        const nm = document.getElementById('wl_name');
        if (nm) nm.focus();
      }

      function openWishlistEdit(btn) {
        if (window._adminShowSection) window._adminShowSection('section-wishlist');
        const fw = document.getElementById('wishlistFormWrap');
        if (!fw) return;
        fw.style.display = '';
        document.getElementById('wishlistFormTitle').textContent = 'Edit Item';
        document.getElementById('wishlistAction').value = 'edit_wishlist_item';
        document.getElementById('wlId').value = btn.dataset.id || '';
        document.getElementById('wishlistSubmitBtn').textContent = 'Save Changes';
        document.getElementById('wl_type').value = btn.dataset.type || 'paint';
        document.getElementById('wl_priority').value = btn.dataset.priority || 'medium';
        document.getElementById('wl_name').value = btn.dataset.name || '';
        document.getElementById('wl_brand').value = btn.dataset.brand || '';
        document.getElementById('wl_faction').value = btn.dataset.faction || '';
        const sys = document.getElementById('wl_system');
        if (sys) sys.value = btn.dataset.system || '';
        document.getElementById('wl_notes').value = btn.dataset.notes || '';
        document.getElementById('wl_url').value = btn.dataset.url || '';
        const od = document.getElementById('wl_ordered_date');
        if (od) od.value = btn.dataset.orderedDate || '';
        wishlistTypeChange();
        fw.scrollIntoView({
          behavior: 'smooth'
        });
      }

      function cancelWishlistEdit() {
        const fw = document.getElementById('wishlistFormWrap');
        if (fw) fw.style.display = 'none';
        document.getElementById('wishlistAction').value = 'add_wishlist_item';
        document.getElementById('wlId').value = '';
      }

      document.querySelectorAll('.bh-edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          document.getElementById('bh_action').value = 'edit_battle';
          document.getElementById('bh_id').value = this.dataset.id || '';
          document.getElementById('bh_date').value = this.dataset.date || '';
          document.getElementById('bh_result').value = this.dataset.result || 'draw';
          document.getElementById('bh_my_army').value = this.dataset.myArmy || '';
          const fid = document.getElementById('bh_force_id');
          if (fid) fid.value = this.dataset.forceId || '';
          document.getElementById('bh_system').value = this.dataset.system || '';
          document.getElementById('bh_points').value = this.dataset.points || '';
          document.getElementById('bh_opponent').value = this.dataset.opponent || '';
          document.getElementById('bh_opponent_army').value = this.dataset.opponentArmy || '';
          document.getElementById('bh_mission').value = this.dataset.mission || '';
          document.getElementById('bh_notes').value = this.dataset.notes || '';
          document.getElementById('bh-form-heading').textContent = 'Edit Battle';
          document.getElementById('bh-submit-btn').textContent = 'Save Changes';
          document.getElementById('bh-cancel-btn').style.display = '';
          document.getElementById('bh-form').scrollIntoView({ behavior: 'smooth' });
        });
      });

      function bhCancelEdit() {
        document.getElementById('bh_action').value = 'add_battle';
        document.getElementById('bh_id').value = '';
        document.getElementById('bh-form').reset();
        document.getElementById('bh_date').value = new Date().toISOString().slice(0, 10);
        document.getElementById('bh-form-heading').textContent = 'Log a Battle';
        document.getElementById('bh-submit-btn').textContent = 'Log Battle';
        document.getElementById('bh-cancel-btn').style.display = 'none';
      }
