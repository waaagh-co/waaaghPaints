    let paintUsage = new Map(); // populated after MODELS is defined
    // 3-part unique key: brand|name|layer
    const paintKey = p => p.brand + '|' + p.name + '|' + (p.layer || '');
    const paintOwned = new Set(PAINTS.filter(p => p.stock !== 'wanted').map(paintKey));
    const paintStock = new Map(PAINTS.filter(p => p.stock).map(p => [paintKey(p), p.stock]));
    const paintByKeyLC = new Map(PAINTS.map(p => [(p.brand + '|' + p.name).toLowerCase(), p.stock || '']));
    const _paintByKey       = new Map(PAINTS.map(p => [paintKey(p), p]));
    const _ACHROMATIC_CATS  = new Set(['White','Grey','Black','Metallic','Wash','Shade','Contrast','Ink','Texture','Primer','Pigment','Medium','Effect','Fluid','Utility','Special','Transparent','Fluorescent']);
    const _CAT_HUE          = {Red:0,Orange:25,Yellow:55,Green:120,Blue:220,Purple:270,Pink:320,Brown:30};

    // Upgrade legacy 2-part stored keys to 3-part where unambiguous
    const _legacyUpgrade = new Map();
    PAINTS.forEach(p => {
      const k2 = p.brand + '|' + p.name;
      _legacyUpgrade.set(k2, _legacyUpgrade.has(k2) ? null : paintKey(p));
    });

    function upgradeKey(c) {
      if (c.split('|').length >= 3) return c;
      return _legacyUpgrade.get(c) || c;
    }

    function brandSlug(b) {
      return b.toLowerCase().replace(/\s+/g, '');
    }

    let sortCol = 'name';
    let sortDir = 1; // 1=asc, -1=desc
    let stockFilter = false;
    let wheelActive = false;
    let harmonyActive = false;
    let harmonySelectedKey = null;
    let harmonyType = 'complementary';

    const searchEl = document.getElementById('search');
    const filterBrand = document.getElementById('filter-brand');
    const filterColor = document.getElementById('filter-color');
    const filterLayer = document.getElementById('filter-layer');
    const resetBtn = document.getElementById('reset');
    const flaggedBtn = document.getElementById('filter-flagged');
    const countEl = document.getElementById('count');
    const tbody = document.getElementById('tbody');
    const emptyEl = document.getElementById('empty');
    const table = document.getElementById('paint-table');
    const headers     = document.querySelectorAll('th[data-col]');
    const wheelBtn    = document.getElementById('inv-wheel-btn');
    const wheelView   = document.getElementById('wheel-view');
    const wheelSvgWrap = document.getElementById('wheel-svg-wrap');
    const wheelAchEl  = document.getElementById('wheel-achromatic');
    const wheelTip    = document.getElementById('wheel-tooltip');
    const harmonyBtn  = document.getElementById('inv-harmony-btn');
    const harmonyPanel = document.getElementById('harmony-panel');

    function render() {
      const q = searchEl.value.toLowerCase();
      const brand = filterBrand.value;
      const color = filterColor.value;
      const layer = filterLayer.value;

      let filtered = PAINTS.filter(p => {
        if (brand && p.brand !== brand) return false;
        if (color && p.color !== color) return false;
        if (layer && p.layer !== layer) return false;
        if (stockFilter && !p.stock) return false;
        if (q && !p.name.toLowerCase().includes(q) &&
          !p.hue.toLowerCase().includes(q) &&
          !p.brand.toLowerCase().includes(q)) return false;
        return true;
      });

      filtered.sort((a, b) => {
        if (sortCol === 'schemes') {
          const av = paintUsage.get(paintKey(a)) || 0;
          const bv = paintUsage.get(paintKey(b)) || 0;
          return (av - bv) * sortDir || a.name.localeCompare(b.name);
        }
        let av = a[sortCol] || '';
        let bv = b[sortCol] || '';
        const primary = av.localeCompare(bv) * sortDir;
        if (primary !== 0) return primary;
        return a.name.localeCompare(b.name);
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '';
        table.style.display = 'none';
        emptyEl.style.display = 'block';
      } else {
        table.style.display = '';
        emptyEl.style.display = 'none';
        tbody.innerHTML = filtered.map(p => {
          const slug = brandSlug(p.brand);
          const swatchClass = 'swatch swatch-' + p.color.replace(/\s+/g, '');
          const badgeClass = 'badge badge-' + p.layer.replace(/\s+/g, '');
          const used = paintUsage.get(paintKey(p)) || 0;
          const countHtml = used > 0 ?
            `<span class="scheme-count${used > 1 ? ' workhorse' : ''}">${used}</span>` :
            '';
          const stockVal = p.stock || '';
          const stockBadge = stockVal ?
            `<span class="inv-stock-badge inv-stock-${stockVal}">${stockVal}</span>` :
            '';
          const pid = paintKey(p);
          const starVal = p.stars || 0;
          const notesBtn = `<button class="notes-btn${p.notes ? ' has-notes' : ''}" title="${p.notes ? 'View notes' : 'No notes'}" data-pid="${esc(pid)}" data-stars="${starVal}" data-notes-brand="${esc(p.brand)}" data-notes-name="${esc(p.name)}" data-notes-text="${esc(p.notes||'')}">&#9998;</button>`;
          const starBtn = `<button class="star-rate-btn${starVal ? ' has-stars' : ''}" title="${starVal ? starVal + ' stars' : 'Rate this paint'}" data-pid="${esc(pid)}" data-stars="${starVal}" data-notes-brand="${esc(p.brand)}" data-notes-name="${esc(p.name)}" data-notes-text="${esc(p.notes||'')}">★</button>`;
          return `<tr class="brand-${slug}" data-brand="${esc(p.brand)}" data-name="${esc(p.name)}" data-layer="${esc(p.layer||'')}" data-key="${esc(pid)}" title="Show schemes using this paint">
        <td><span class="${swatchClass}" title="${p.color}"></span></td>
        <td>${esc(p.brand)}</td>
        <td>${esc(p.name)}${stockBadge}${notesBtn}${starBtn}</td>
        <td class="col-colour">${esc(p.color)}</td>
        <td class="col-hue">${esc(p.hue)}</td>
        <td><span class="${badgeClass}">${esc(p.layer)}</span></td>
        <td class="col-used" style="text-align:center">${countHtml}</td>
      </tr>`;
        }).join('');
      }

      countEl.textContent = filtered.length + ' of ' + PAINTS.length + ' paints';

      headers.forEach(th => {
        const icon = th.querySelector('.sort-icon');
        if (!icon) return;
        if (th.dataset.col === sortCol) {
          th.classList.add('active');
          icon.textContent = sortDir === 1 ? ' ▲' : ' ▼';
        } else {
          th.classList.remove('active');
          icon.textContent = '';
        }
      });
    }

    function renderWheel() {
      const q = searchEl.value.toLowerCase();
      const brand = filterBrand.value;
      const color = filterColor.value;
      const layer = filterLayer.value;
      const filtered = PAINTS.filter(p => {
        if (brand && p.brand !== brand) return false;
        if (color && p.color !== color) return false;
        if (layer && p.layer !== layer) return false;
        if (stockFilter && !p.stock) return false;
        if (q) { const s = (p.name+' '+(p.hue||'')+' '+p.brand).toLowerCase(); if (!s.includes(q)) return false; }
        return true;
      });
      const chromatic  = filtered.filter(p => getHue(p) !== null);
      const achromatic = filtered.filter(p => getHue(p) === null);
      if (wheelSvgWrap) wheelSvgWrap.innerHTML = buildWheelSvg(chromatic);
      if (wheelAchEl)  wheelAchEl.innerHTML   = buildAchromatic(achromatic);
      wireWheelEvents();
      countEl.textContent = filtered.length + ' of ' + PAINTS.length + ' paints';
    }

    function buildWheelSvg(paints) {
      const CX=450, CY=450, SEGS=24, DEG=15, BASE_R=100, RING=11;
      let bg = '';
      for (let i=0; i<SEGS; i++) {
        const hd=i*DEG, aS=(hd-90-DEG/2)*Math.PI/180, aE=(hd-90+DEG/2)*Math.PI/180;
        const R1=248, R2=280;
        const x1=CX+R1*Math.cos(aS), y1=CY+R1*Math.sin(aS);
        const x2=CX+R2*Math.cos(aS), y2=CY+R2*Math.sin(aS);
        const x3=CX+R2*Math.cos(aE), y3=CY+R2*Math.sin(aE);
        const x4=CX+R1*Math.cos(aE), y4=CY+R1*Math.sin(aE);
        bg += `<path d="M${x1.toFixed(1)},${y1.toFixed(1)} L${x2.toFixed(1)},${y2.toFixed(1)} A${R2},${R2} 0 0,1 ${x3.toFixed(1)},${y3.toFixed(1)} L${x4.toFixed(1)},${y4.toFixed(1)} A${R1},${R1} 0 0,0 ${x1.toFixed(1)},${y1.toFixed(1)}Z" fill="hsl(${hd},70%,55%)" opacity=".18"/>`;
      }
      const buckets = Array.from({length:SEGS}, ()=>[]);
      paints.forEach(p => {
        const h = getHue(p);
        const seg = Math.floor(((h%360)+360)%360/DEG)%SEGS;
        buckets[seg].push(p);
      });
      let dots = '';
      buckets.forEach((bucket, seg) => {
        bucket.forEach((p, ring) => {
          const h = getHue(p);
          const angle = (h-90)*Math.PI/180;
          const r = BASE_R + ring*RING;
          const x = CX+r*Math.cos(angle), y = CY+r*Math.sin(angle);
          const fill   = p.hex || `hsl(${h},60%,45%)`;
          const stroke = p.stock==='out'?'#8a2020':p.stock==='low'?'#c9a227':'rgba(0,0,0,.4)';
          dots += `<circle class="paint-dot" cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="7" fill="${fill}" stroke="${stroke}" stroke-width="2" data-key="${esc(paintKey(p))}"/>`;
        });
      });
      const n = paints.length;
      const legend = `<g transform="translate(${CX},${CY+320})">
        <circle cx="-52" cy="0" r="6" fill="#2a2010" stroke="#c9a227" stroke-width="2"/>
        <text x="-42" y="4" font-family="sans-serif" font-size="11" fill="#6a5828">Low stock</text>
        <circle cx="38" cy="0" r="6" fill="#2a0808" stroke="#8a2020" stroke-width="2"/>
        <text x="48" y="4" font-family="sans-serif" font-size="11" fill="#6a5828">Out of stock</text>
      </g>`;
      return `<svg viewBox="0 0 900 900" xmlns="http://www.w3.org/2000/svg" id="wheel-svg">${bg}${dots}<circle cx="${CX}" cy="${CY}" r="88" fill="#0a0806" opacity=".92"/><text x="${CX}" y="${CY-4}" text-anchor="middle" font-family="Cinzel,serif" font-size="30" fill="#c9a227">${n}</text><text x="${CX}" y="${CY+20}" text-anchor="middle" font-family="Cinzel,serif" font-size="10" fill="#5a4a28" letter-spacing="3">CHROMATIC</text>${legend}</svg>`;
    }

    function buildAchromatic(paints) {
      if (!paints.length) return '';
      const _L_DEFAULTS = {Black:8,Shade:18,Wash:22,Contrast:38,Ink:28,Grey:52,Metallic:48,Texture:54,Medium:55,Primer:68,Effect:48,Fluid:52,Utility:52,Special:50,Transparent:62,Pigment:50,Fluorescent:76,White:92};
      const _H_DEFAULTS = {Black:0,Grey:0,White:0,Metallic:35,Wash:25,Shade:20,Contrast:30,Ink:230,Medium:0,Effect:300,Fluid:200,Utility:0,Special:120,Transparent:180,Fluorescent:65,Primer:0,Texture:30,Pigment:50};
      function _lightness(p) {
        if (p.hex && /^#[0-9a-f]{6}$/i.test(p.hex)) return hexToHsl(p.hex)[2];
        return _L_DEFAULTS[p.color] || 50;
      }
      function _hue(p) {
        if (p.hex && /^#[0-9a-f]{6}$/i.test(p.hex)) {
          const [h, s] = hexToHsl(p.hex);
          return s < 5 ? 210 : h;
        }
        return _H_DEFAULTS[p.color] ?? 0;
      }
      const byHueThenLight = arr => [...arr].sort((a, b) => {
        const ha = Math.round(_hue(a) / 30), hb = Math.round(_hue(b) / 30);
        if (ha !== hb) return ha - hb;
        return _lightness(a) - _lightness(b);
      });
      const GRP = {
        'Metallic':        ['Metallic'],
        'Wash & Shade':    ['Wash','Shade'],
        'Contrast':        ['Contrast'],
        'Primer & Texture':['Primer','Texture'],
        'Greyscale':       ['White','Grey','Black'],
      };
      const used = new Set();
      const CAT_FB = {Metallic:'#888',Wash:'#4a3a28',Shade:'#3a3028',Contrast:'#6a5a40',Primer:'#aaa',Texture:'#8a7a60',White:'#f0eee8',Grey:'#888',Black:'#2a2a2a',Ink:'#3a3060',Medium:'#6a5030',Effect:'#6a4a60',Fluid:'#506080',Utility:'#5a5a5a',Special:'#4a6a3a',Transparent:'#5a8a8a',Fluorescent:'#c8e040',Pigment:'#8a7a40'};
      let html = `<div class="wheel-ach-hd">Achromatic &amp; Special (${paints.length})</div>`;
      for (const [label, cats] of Object.entries(GRP)) {
        const group = byHueThenLight(paints.filter(p => cats.includes(p.color)));
        if (!group.length) continue;
        group.forEach(p => used.add(paintKey(p)));
        const isGrey = label === 'Greyscale';
        html += `<div class="wheel-ach-group${isGrey?' wheel-ach-grey':''}"><div class="wheel-ach-group-lbl">${label}</div><div class="wheel-ach-swatches${isGrey?' wheel-ach-swatches-grey':''}">`;
        group.forEach(p => {
          const fill = p.hex || CAT_FB[p.color] || '#5a5a5a';
          const sc = p.stock==='out'?' stock-out':p.stock==='low'?' stock-low':'';
          html += `<div class="wheel-ach-dot${sc}" style="background:${fill}" data-key="${esc(paintKey(p))}"></div>`;
        });
        html += '</div></div>';
      }
      const other = byHueThenLight(paints.filter(p => !used.has(paintKey(p))));
      if (other.length) {
        html += `<div class="wheel-ach-group"><div class="wheel-ach-group-lbl">Other</div><div class="wheel-ach-swatches">`;
        other.forEach(p => {
          const fill = p.hex || CAT_FB[p.color] || '#5a5a5a';
          const sc = p.stock==='out'?' stock-out':p.stock==='low'?' stock-low':'';
          html += `<div class="wheel-ach-dot${sc}" style="background:${fill}" data-key="${esc(paintKey(p))}"></div>`;
        });
        html += '</div></div>';
      }
      return html;
    }

    function _wheelJumpToPaint(p) {
      wheelActive = false;
      if (wheelBtn) wheelBtn.classList.remove('active');
      if (wheelView)  wheelView.style.display = 'none';
      if (wheelTip)   wheelTip.style.display  = 'none';
      searchEl.value      = p.name;
      filterBrand.value   = p.brand;
      filterLayer.value   = p.layer || '';
      filterColor.value   = '';
      render();
      setTimeout(() => {
        const row = tbody.querySelector(`tr[data-key="${CSS.escape(paintKey(p))}"]`);
        if (row) {
          row.scrollIntoView({block:'center', behavior:'smooth'});
          row.style.boxShadow = 'inset 0 0 0 2px #c9a227';
          setTimeout(() => { row.style.boxShadow = ''; }, 1400);
        }
      }, 80);
    }

    function wireWheelEvents() {
      const svg = document.getElementById('wheel-svg');
      if (svg) {
        svg.addEventListener('mousemove', e => {
          const dot = e.target.closest('.paint-dot');
          if (!dot || !wheelTip) return;
          const p = _paintByKey.get(dot.dataset.key);
          if (!p) return;
          const stock = p.stock ? ` <span class="inv-stock-badge inv-stock-${p.stock}">${p.stock}</span>` : '';
          wheelTip.innerHTML = `<strong>${esc(p.name)}</strong>${stock}<br><span style="color:#6a5828">${esc(p.brand)} &middot; ${esc(p.layer||'')}</span>`;
          wheelTip.style.display = 'block';
          wheelTip.style.left = (e.clientX+14)+'px';
          wheelTip.style.top  = (e.clientY-10)+'px';
        });
        svg.addEventListener('mouseleave', () => { if (wheelTip) wheelTip.style.display='none'; });
        svg.addEventListener('click', e => {
          const dot = e.target.closest('.paint-dot');
          if (!dot) return;
          const p = _paintByKey.get(dot.dataset.key);
          if (!p) return;
          if (harmonyActive) selectHarmonyPaint(p); else _wheelJumpToPaint(p);
        });
      }
      if (wheelAchEl) {
        wheelAchEl.addEventListener('mouseover', e => {
          const dot = e.target.closest('.wheel-ach-dot');
          if (!dot || !wheelTip) return;
          const p = _paintByKey.get(dot.dataset.key);
          if (!p) return;
          const stock = p.stock ? ` <span class="inv-stock-badge inv-stock-${p.stock}">${p.stock}</span>` : '';
          wheelTip.innerHTML = `<strong>${esc(p.name)}</strong>${stock}<br><span style="color:#6a5828">${esc(p.brand)} &middot; ${esc(p.layer||'')}</span>`;
          wheelTip.style.display = 'block';
          wheelTip.style.left = (e.clientX+14)+'px';
          wheelTip.style.top  = (e.clientY-10)+'px';
        });
        wheelAchEl.addEventListener('mouseleave', () => { if (wheelTip) wheelTip.style.display='none'; });
        wheelAchEl.addEventListener('click', e => {
          const dot = e.target.closest('.wheel-ach-dot');
          if (!dot) return;
          const p = _paintByKey.get(dot.dataset.key);
          if (p) _wheelJumpToPaint(p);
        });
      }
    }

    function esc(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function harmonyPositions(baseHue, type) {
      const h = ((baseHue % 360) + 360) % 360;
      const n = x => ((x % 360) + 360) % 360;
      if (type === 'complementary') return [{hue: n(h+180), label:'Complement', role:'Accent'}];
      if (type === 'triadic')       return [{hue: n(h+120), label:'Second', role:'Secondary'}, {hue: n(h+240), label:'Third', role:'Secondary'}];
      if (type === 'split')         return [{hue: n(h+150), label:'Split A', role:'Secondary'}, {hue: n(h+210), label:'Split B', role:'Secondary'}];
      if (type === 'analogous')     return [{hue: n(h-30),  label:'Warm Analog', role:'Transition'}, {hue: n(h+30), label:'Cool Analog', role:'Transition'}];
      if (type === 'tetradic')      return [{hue: n(h+90),  label:'Second', role:'Secondary'}, {hue: n(h+180), label:'Third', role:'Accent'}, {hue: n(h+270), label:'Fourth', role:'Secondary'}];
      return [];
    }

    function paintsNearHue(targetHue, tolerance, excludeKey) {
      const t = ((targetHue % 360) + 360) % 360;
      return PAINTS.filter(p => {
        if (excludeKey && paintKey(p) === excludeKey) return false;
        const h = getHue(p);
        if (h === null) return false;
        return Math.min(Math.abs(h - t), 360 - Math.abs(h - t)) <= tolerance;
      }).sort((a, b) => {
        const da = Math.min(Math.abs(getHue(a) - t), 360 - Math.abs(getHue(a) - t));
        const db = Math.min(Math.abs(getHue(b) - t), 360 - Math.abs(getHue(b) - t));
        return da - db;
      });
    }

    function paintTemperature(hue) {
      const h = ((hue % 360) + 360) % 360;
      if ((h >= 0 && h < 60) || h >= 300) return 'warm';
      if (h >= 120 && h < 270) return 'cool';
      return 'neutral';
    }

    function paintRole(h, s, l) {
      if (l < 28) return 'Shadow';
      if (l > 72) return 'Highlight';
      if (s > 50) return 'Foundation';
      return 'Transition';
    }

    function clearHarmonyArcs() {
      const svg = document.getElementById('wheel-svg');
      if (!svg) return;
      svg.querySelectorAll('.harmony-arc').forEach(el => el.remove());
      svg.querySelectorAll('.paint-dot').forEach(dot => dot.classList.remove('harmony-match','harmony-selected'));
    }

    function drawHarmonyArcs(selectedHue, type) {
      clearHarmonyArcs();
      const svg = document.getElementById('wheel-svg');
      if (!svg) return;
      const CX=450, CY=450, R1=240, R2=292, ARC=20;
      const firstDot = svg.querySelector('.paint-dot');
      harmonyPositions(selectedHue, type).forEach(pos => {
        const aS=(pos.hue-90-ARC)*Math.PI/180, aE=(pos.hue-90+ARC)*Math.PI/180;
        const x1=CX+R1*Math.cos(aS),y1=CY+R1*Math.sin(aS),x2=CX+R2*Math.cos(aS),y2=CY+R2*Math.sin(aS);
        const x3=CX+R2*Math.cos(aE),y3=CY+R2*Math.sin(aE),x4=CX+R1*Math.cos(aE),y4=CY+R1*Math.sin(aE);
        const path = document.createElementNS('http://www.w3.org/2000/svg','path');
        path.setAttribute('class','harmony-arc');
        path.setAttribute('d',`M${x1.toFixed(1)},${y1.toFixed(1)} L${x2.toFixed(1)},${y2.toFixed(1)} A${R2},${R2} 0 0,1 ${x3.toFixed(1)},${y3.toFixed(1)} L${x4.toFixed(1)},${y4.toFixed(1)} A${R1},${R1} 0 0,0 ${x1.toFixed(1)},${y1.toFixed(1)}Z`);
        path.setAttribute('fill',`hsl(${pos.hue.toFixed(0)},80%,60%)`);
        path.setAttribute('opacity','0.4');
        if (firstDot) svg.insertBefore(path, firstDot); else svg.appendChild(path);
        PAINTS.forEach(p => { const h=getHue(p); if (h===null) return; if (Math.min(Math.abs(h-pos.hue),360-Math.abs(h-pos.hue)) <= 22) { const d=svg.querySelector(`.paint-dot[data-key="${CSS.escape(paintKey(p))}"]`); if (d) d.classList.add('harmony-match'); } });
      });
      const selDot = harmonySelectedKey && svg.querySelector(`.paint-dot[data-key="${CSS.escape(harmonySelectedKey)}"]`);
      if (selDot) selDot.classList.add('harmony-selected');
    }

    function renderHarmonyPanel(p) {
      if (!harmonyPanel) return;
      const hue = getHue(p);
      if (hue === null) { harmonyPanel.innerHTML = '<div class="hp-hint">This paint has no chromatic hue — try a coloured paint on the wheel.</div>'; harmonyPanel.style.display='block'; return; }
      const hsl = p.hex ? hexToHsl(p.hex) : [hue,60,45];
      const temp = paintTemperature(hue);
      const role = paintRole(hsl[0],hsl[1],hsl[2]);
      const fill = p.hex || `hsl(${hue},60%,45%)`;
      const types = [{id:'complementary',label:'Complement'},{id:'triadic',label:'Triadic'},{id:'split',label:'Split'},{id:'analogous',label:'Analogous'},{id:'tetradic',label:'Tetradic'}];
      const tabs = types.map(t => `<button class="hp-tab${harmonyType===t.id?' active':''}" data-htype="${t.id}">${t.label}</button>`).join('');
      const advice = role==='Shadow' ? (temp==='cool'?'✓ Cool shadows create depth':'⚠ Warm shadows flatten — try cooler alternatives') : role==='Highlight' ? (temp==='warm'?'✓ Warm highlights pop naturally':'⚠ Cool highlights feel flat — try warmer tones') : `${temp.charAt(0).toUpperCase()+temp.slice(1)}-toned ${role.toLowerCase()}`;
      const posHtml = harmonyPositions(hue, harmonyType).map(pos => {
        const matches = paintsNearHue(pos.hue, 22, paintKey(p));
        const ownedCount = matches.filter(m => paintOwned.has(paintKey(m))).length;
        const swatches = matches.slice(0,7).map(m => { const mf=m.hex||`hsl(${getHue(m)},60%,45%)`; const own=paintOwned.has(paintKey(m)); const sc=m.stock==='out'?' hp-sw-out':m.stock==='low'?' hp-sw-low':''; return `<span class="hp-sw${sc}${own?'':' hp-sw-miss'}" style="background:${mf}" title="${esc(m.name)} (${esc(m.brand)})${own?'':' — not owned'}" data-key="${esc(paintKey(m))}"></span>`; }).join('');
        return `<div class="hp-position"><div class="hp-pos-hd"><span class="hp-pos-dot" style="background:hsl(${pos.hue.toFixed(0)},75%,58%)"></span><span class="hp-pos-label">${esc(pos.label)}</span><span class="hp-pos-role">${esc(pos.role)}</span><span class="hp-pos-count">${ownedCount} owned</span></div><div class="hp-swatches">${swatches||'<span class="hp-no-match">No matches in collection</span>'}</div></div>`;
      }).join('');
      harmonyPanel.style.display = 'block';
      harmonyPanel.innerHTML = `<div class="hp-header"><span class="hp-swatch-lg" style="background:${fill}"></span><div class="hp-paint-info"><div class="hp-paint-name">${esc(p.name)}</div><div class="hp-paint-meta">${esc(p.brand)} · ${esc(p.layer||'')}</div><div class="hp-badges"><span class="hp-temp hp-temp-${temp}">${temp}</span><span class="hp-role-badge">${role}</span></div></div></div><div class="hp-advice">${esc(advice)}</div><div class="hp-tabs">${tabs}</div><div class="hp-positions">${posHtml}</div>`;
      harmonyPanel.querySelectorAll('.hp-tab').forEach(tab => { tab.addEventListener('click', () => { harmonyType=tab.dataset.htype; drawHarmonyArcs(hue,harmonyType); renderHarmonyPanel(p); }); });
      harmonyPanel.querySelectorAll('.hp-sw[data-key]').forEach(sw => { sw.style.cursor='pointer'; sw.addEventListener('click', () => { const mp=_paintByKey.get(sw.dataset.key); if (mp) selectHarmonyPaint(mp); }); });
    }

    function selectHarmonyPaint(p) {
      harmonySelectedKey = paintKey(p);
      drawHarmonyArcs(getHue(p), harmonyType);
      renderHarmonyPanel(p);
    }

    function collectAllPaints(direct, recipeIds) {
      const seen = new Set();
      const out = [];
      (direct || []).forEach(c => {
        if (!seen.has(c)) {
          seen.add(c);
          out.push(c);
        }
      });
      if (recipeIds && recipeIds.length && typeof window._RECIPE_BY_ID !== 'undefined') {
        recipeIds.forEach(rid => {
          const r = window._RECIPE_BY_ID.get(rid);
          if (!r) return;
          (r.steps || []).forEach(s => {
            if (s.paint && !seen.has(s.paint)) {
              seen.add(s.paint);
              out.push(s.paint);
            }
            if (s.mix_paint && !seen.has(s.mix_paint)) {
              seen.add(s.mix_paint);
              out.push(s.mix_paint);
            }
          });
        });
      }
      return out;
    }

    function renderRecipeRefs(recipeIds) {
      if (typeof window._RECIPE_BY_ID === 'undefined' || !recipeIds || !recipeIds.length) return '';
      const badges = recipeIds.map(rid => {
        const r = window._RECIPE_BY_ID.get(rid);
        if (!r) return '';
        return `<span class="recipe-ref-badge" onclick="_jumpToRecipe('${esc(rid)}');event.stopPropagation();">${esc(r.name)}</span>`;
      }).filter(Boolean).join('');
      return badges ? `<div class="recipe-ref-row"><span style="font-family:'Cinzel',serif;font-size:9px;letter-spacing:.06em;color:#4a3a1a;margin-right:4px">Uses</span>${badges}</div>` : '';
    }

    headers.forEach(th => {
      if (!th.querySelector('.sort-icon')) return; // swatch column has no icon
      th.addEventListener('click', () => {
        const col = th.dataset.col;
        if (sortCol === col) {
          sortDir *= -1;
        } else {
          sortCol = col;
          sortDir = 1;
        }
        render();
      });
    });

    const _invRender = () => wheelActive ? renderWheel() : render();

    searchEl.addEventListener('input', _invRender);
    filterBrand.addEventListener('change', _invRender);
    filterColor.addEventListener('change', _invRender);
    filterLayer.addEventListener('change', _invRender);

    if (flaggedBtn) {
      flaggedBtn.addEventListener('click', () => {
        stockFilter = !stockFilter;
        flaggedBtn.classList.toggle('active', stockFilter);
        _invRender();
      });
    }

    resetBtn.addEventListener('click', () => {
      searchEl.value = '';
      filterBrand.value = '';
      filterColor.value = '';
      filterLayer.value = '';
      sortCol = 'name';
      sortDir = 1;
      stockFilter = false;
      if (flaggedBtn) flaggedBtn.classList.remove('active');
      _invRender();
    });

    const wheelCopyBtn = document.getElementById('inv-wheel-copy');

    function _wheelUrl() {
      const u = new URL(location.href);
      u.searchParams.set('tab', 'inventory');
      u.searchParams.set('wheel', '1');
      return u.toString();
    }

    function _setWheelUrlState(on) {
      const u = new URL(location.href);
      if (on) { u.searchParams.set('tab', 'inventory'); u.searchParams.set('wheel', '1'); }
      else     { u.searchParams.delete('wheel'); }
      history.replaceState(null, '', u.toString());
      if (wheelCopyBtn) wheelCopyBtn.style.display = on ? '' : 'none';
    }

    if (harmonyBtn) {
      harmonyBtn.style.display = 'none';
      harmonyBtn.addEventListener('click', () => {
        if (!wheelActive) return;
        harmonyActive = !harmonyActive;
        harmonyBtn.classList.toggle('active', harmonyActive);
        if (!harmonyActive) {
          clearHarmonyArcs();
          harmonySelectedKey = null;
          if (harmonyPanel) { harmonyPanel.style.display='none'; harmonyPanel.innerHTML=''; }
        } else {
          if (harmonyPanel) { harmonyPanel.innerHTML='<div class="hp-hint">◎ Click any paint on the wheel to analyse its harmony</div>'; harmonyPanel.style.display='block'; }
        }
      });
    }

    if (wheelBtn) {
      wheelBtn.addEventListener('click', () => {
        wheelActive = !wheelActive;
        wheelBtn.classList.toggle('active', wheelActive);
        _setWheelUrlState(wheelActive);
        if (harmonyBtn) harmonyBtn.style.display = wheelActive ? '' : 'none';
        if (wheelActive) {
          table.style.display = 'none';
          emptyEl.style.display = 'none';
          if (wheelView) wheelView.style.display = 'block';
          renderWheel();
        } else {
          if (harmonyActive) { harmonyActive=false; harmonySelectedKey=null; if (harmonyBtn) harmonyBtn.classList.remove('active'); clearHarmonyArcs(); if (harmonyPanel) { harmonyPanel.style.display='none'; harmonyPanel.innerHTML=''; } }
          if (wheelView)  wheelView.style.display = 'none';
          if (wheelTip)   wheelTip.style.display  = 'none';
          render();
        }
      });
    }

    if (wheelCopyBtn) {
      wheelCopyBtn.style.display = 'none';
      wheelCopyBtn.addEventListener('click', () => {
        navigator.clipboard.writeText(_wheelUrl()).then(() => {
          const prev = wheelCopyBtn.textContent;
          wheelCopyBtn.textContent = '✓';
          setTimeout(() => { wheelCopyBtn.textContent = prev; }, 1500);
        });
      });
    }

    MODELS.forEach(m => {
      const mc = Math.max(1, parseInt(m.count || 1, 10));
      (m.colors || []).forEach(c => {
        const k = upgradeKey(c);
        paintUsage.set(k, (paintUsage.get(k) || 0) + mc);
      });
    });

    // Initial render - must be after MODELS loop so paintUsage is populated
    render();

    // Auto-activate wheel view if ?wheel=1 in URL
    if (new URLSearchParams(location.search).get('wheel') === '1') {
      wheelActive = true;
      if (wheelBtn) wheelBtn.classList.add('active');
      _setWheelUrlState(true);
      table.style.display = 'none';
      emptyEl.style.display = 'none';
      if (wheelView) wheelView.style.display = 'block';
      renderWheel();
    }

    // Returns the full effective paint list for a scheme: own colors + step paints from referenced recipes (deduped).
    // _RECIPE_BY_ID is set lazily by the Recipes IIFE; falls back to own colors when recipes aren't loaded yet.
    function effectiveColors(m) {
      const own = m.colors || [];
      if (!m.recipes || !m.recipes.length || !window._RECIPE_BY_ID) return own;
      const seen = new Set(own.map(c => upgradeKey(c).toLowerCase()));
      const extra = [];
      for (const rid of m.recipes) {
        const r = window._RECIPE_BY_ID.get(rid);
        if (!r) continue;
        for (const step of (r.steps || [])) {
          if (!step.paint) continue;
          const uk = upgradeKey(step.paint).toLowerCase();
          if (!seen.has(uk)) {
            seen.add(uk);
            extra.push(step.paint);
          }
        }
      }
      return extra.length ? own.concat(extra) : own;
    }

    let factionFilter = '';
    let gallerySystemFilter = '';
    let showAllGallery = false;
    let gallerySearch = '';
    let readyFilter = false;

    const SYS_COLORS = {
      '40k': '#5a1a1a',
      '30k / HH': '#4a3a0a',
      'AoS': '#1a2a5a',
      'Kill Team': '#0a3a3a',
      'Blood Bowl': '#0a3a1a',
      'Necromunda': '#3a0a5a',
      'OPR': '#1a2a3a',
      'Old World': '#3a2a0a',
      'Other': '#2a2a2a'
    };
    const sysSlug = s => ({
      '40k': '40k',
      '30k / HH': '30k',
      'AoS': 'aos',
      'Kill Team': 'kt',
      'Blood Bowl': 'bb',
      'Necromunda': 'necro',
      'Epic': 'epic',
      'OPR': 'opr',
      'Old World': 'ow'
    } [s] || 'other');

    function modelReadiness(m) {
      const cols = effectiveColors(m);
      if (!cols.length) return null;
      let blocked = 0,
        hasLow = false;
      for (const c of cols) {
        const uk = upgradeKey(c);
        if (!paintOwned.has(uk) || paintStock.get(uk) === 'out') blocked++;
        else if (paintStock.get(uk) === 'low') hasLow = true;
      }
      if (blocked > 0) return {
        state: 'blocked',
        blocked
      };
      if (hasLow) return {
        state: 'low'
      };
      return {
        state: 'ready'
      };
    }

    function toggleReadyFilter() {
      readyFilter = !readyFilter;
      showAllGallery = false;
      document.getElementById('ready-filter-btn').classList.toggle('active', readyFilter);
      renderGallery();
    }

    function formatDesc(raw) {
      if (!raw) return '';
      const lines = raw.split(/\r?\n/);
      let out = '';
      for (const line of lines) {
        const t = line.trim();
        if (!t) continue;
        const sub = /^\s{2,}-/.test(line);
        if (/^[A-Z]{2,}/.test(t) && !t.startsWith('-')) {
          out += `<div class="desc-hd">${esc(t)}</div>`;
          continue;
        }
        const step = t.match(/^-\s+([^:]+):\s+(.+)$/);
        if (step) {
          out += `<div class="desc-step${sub ? ' desc-sub' : ''}"><span class="desc-lbl">${esc(step[1])}</span><span class="desc-val">${esc(step[2])}</span></div>`;
          continue;
        }
        if (t.startsWith('-')) {
          out += `<div class="desc-step${sub ? ' desc-sub' : ''}"><span class="desc-val">${esc(t.slice(1).trim())}</span></div>`;
          continue;
        }
        out += `<span>${esc(t)}</span><br>`;
      }
      return out ? `<div class="model-desc">${out}</div>` : '';
    }

    function renderGallery() {
      const grid = document.getElementById('gallery-grid');
      const emptyEl = document.getElementById('gallery-empty');
      const moreEl = document.getElementById('gallery-more');
      const factionPill = document.getElementById('active-faction-pill');
      const factionPullBtn = document.getElementById('faction-pull-btn');

      if (factionFilter) {
        factionPill.textContent = factionFilter + ' \u00d7';
        factionPill.style.display = 'inline-block';
        factionPullBtn.style.display = 'inline-block';
      } else {
        factionPill.style.display = 'none';
        factionPullBtn.style.display = 'none';
      }

      let list = factionFilter ?
        MODELS.filter(m => (m.faction || '') === factionFilter) :
        MODELS;

      if (gallerySystemFilter) {
        list = list.filter(m => (m.system || '') === gallerySystemFilter);
      }

      const q = gallerySearch.toLowerCase().trim();
      if (q) {
        list = list.filter(m =>
          (m.name || '').toLowerCase().includes(q) ||
          (m.faction || '').toLowerCase().includes(q) ||
          (m.description || '').toLowerCase().includes(q) ||
          (m.colors || []).some(c => c.toLowerCase().includes(q)) ||
          Object.values(m.summary || {}).some(v => (v || '').toLowerCase().includes(q))
        );
      }

      if (readyFilter) {
        list = list.filter(m => {
          const r = modelReadiness(m);
          return r && r.state !== 'blocked';
        });
      }

      if (!list.length) {
        grid.innerHTML = '';
        moreEl.style.display = 'none';
        emptyEl.style.display = 'block';
        emptyEl.innerHTML = q ?
          `No schemes match &ldquo;${esc(gallerySearch)}&rdquo;` :
          `No models yet - add one in admin.`;
        return;
      }
      emptyEl.style.display = 'none';

      // Sort by date descending; entries without a date fall to the end
      const sorted = list.slice().sort((a, b) => (b.date || '').localeCompare(a.date || ''));
      const limited = (!showAllGallery && !q && !readyFilter && sorted.length > 12) ? sorted.slice(0, 12) : sorted;

      if (!showAllGallery && !q && !readyFilter && sorted.length > 12) {
        const remaining = sorted.length - 12;
        moreEl.style.display = 'block';
        moreEl.innerHTML = `<div class="gallery-more-fade"></div><button class="gallery-more-btn" onclick="showAllGallery=true;renderGallery()"><span class="gallery-more-count">Showing 12 of ${sorted.length} schemes</span><span class="gallery-more-label">Reveal the remaining ${remaining} <span class="gallery-more-chevron">&#9662;</span></span></button>`;
      } else {
        moreEl.style.display = 'none';
      }

      grid.innerHTML = limited.map(m => {
        const imgs = (m.images || []).slice(0, 4);
        const imgClass = 'model-images imgs-' + (imgs.length || 0);
        const imgHtml = imgs.length ?
          imgs.map((src, i) => `<img src="${esc(src)}" alt="" loading="lazy" data-index="${i}">`).join('') :
          `<div class="model-no-image">No Images</div>`;

        const colors = (() => {
          const sorted = effectiveColors(m).slice().sort((a, b) => {
            const [ab, an = ''] = a.split('|');
            const [bb, bn = ''] = b.split('|');
            return ab.localeCompare(bb) || an.localeCompare(bn);
          });
          let lastBrand = '';
          return sorted.map(c => {
            const [brand, paintName = c] = c.split('|');
            const label = brand !== lastBrand ?
              `<span class="pill-brand-label">${esc(brand)}</span>` :
              '';
            lastBrand = brand;
            return label + `<span class="color-pill" data-paint="${esc(c)}">${esc(paintName)}</span>`;
          }).join('');
        })();

        const factionHtml = m.faction ?
          `<span class="faction-tag${factionFilter === m.faction ? ' active' : ''}" data-faction="${esc(m.faction)}">${esc(m.faction)}</span>` :
          '';
        const sysHtml = m.system ?
          `<span class="sys-game-badge sys-${sysSlug(m.system)}">${esc(m.system)}</span>` :
          '';
        const dateHtml = m.date ? esc(m.date) : '';
        const metaParts = [factionHtml, sysHtml, dateHtml].filter(Boolean);
        const meta = metaParts.join(' ');

        const r = modelReadiness(m);
        const readyDot = r ? `<span class="ready-dot ${r.state}" title="${
          r.state === 'ready'   ? 'Ready to paint' :
          r.state === 'low'     ? 'Ready \u2014 some paints running low' :
          `${r.blocked} paint${r.blocked > 1 ? 's' : ''} missing or out of stock`
        }"></span>` : '';

        const hasBody = m.description || colors;
        const recipeRefs = renderRecipeRefs(m.recipes);
        const hasBodyExt = hasBody || recipeRefs;
        const metaHtml = meta ? `<div class="model-meta">${meta}</div>` : '';
        const linkBtn = `<button class="model-link-btn" title="Copy link" onclick="copyModelLink(event,'${esc(m.id)}')"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>`;
        const countN = Math.max(1, parseInt(m.count || 1, 10));
        const countBadge = countN > 1 ? `<span class="model-count-badge" title="${countN} miniatures painted under this scheme">&times;${countN}</span>` : '';
        const descHtml = formatDesc(m.description);
        const summaryHtml = (() => {
          const s = m.summary;
          if (!s) return '';
          const rows = [
            ['Finish', s.finish],
            ['Primary', s.primary],
            ['Contrast', s.contrast],
            ['Technique', s.technique]
          ].filter(([, v]) => v);
          if (!rows.length) return '';
          return `<div class="model-summary">${rows.map(([l, v]) => `<span class="model-summary-lbl">${esc(l)}</span><span class="model-summary-val">${esc(v)}</span>`).join('')}</div>`;
        })();
        const codexBadge = m.codex_source ? `<span class="codex-source-badge">${esc(m.codex_source)}</span>` : '';
        const colorsHtml = colors ? (() => {
          const issues = effectiveColors(m).filter(c => {
            const uk = upgradeKey(c);
            return !paintOwned.has(uk) || paintStock.get(uk);
          }).length;
          const badge = issues > 0 ? `<span class="pull-issue-badge">${issues} issue${issues > 1 ? 's' : ''}</span>` : '';
          return `<div class="model-colors">${colors}</div><div class="model-card-btns"><button class="warpaint-btn" onclick="openWarpaint('${esc(m.id)}')">&#9876; Warpaint</button><button class="pull-btn" onclick="openPull('${esc(m.id)}')">Pull list${badge}</button><button class="harmony-btn" onclick="openSchemeDoctor('${esc(m.id)}','gallery')">&#9678; Harmony</button></div>`;
        })() : '';
        const bodyHtml = hasBodyExt || codexBadge || summaryHtml ? `<div class="model-info">${summaryHtml}${descHtml}${codexBadge}${recipeRefs}${colorsHtml}</div>` : '';
        const stripeHtml = m.theme_hex ? `<div class="model-theme-stripe" style="background:linear-gradient(to right,${esc(m.theme_hex)} 0%,transparent 100%)"></div>` : '';
        return `<div class="model-card" data-id="${esc(m.id)}"><div class="model-header"><div class="model-name">${esc(m.name)}${countBadge}${readyDot}</div>${metaHtml}${linkBtn}</div><div class="${imgClass}">${imgHtml}</div>${stripeHtml}${bodyHtml}</div>`;
      }).join('');
    }

    // Gallery click delegation: lightbox images, faction tags, color pills
    document.getElementById('gallery-grid').addEventListener('click', e => {
      // Lightbox
      const img = e.target.closest('.model-images img');
      if (img) {
        const card = img.closest('.model-card');
        const allImgs = Array.from(card.querySelectorAll('.model-images img')).map(i => i.src);
        openLightbox(allImgs, parseInt(img.dataset.index) || 0);
        return;
      }
      // Faction filter - toggle off if already active
      const ftag = e.target.closest('.faction-tag');
      if (ftag) {
        factionFilter = factionFilter === ftag.dataset.faction ? '' : ftag.dataset.faction;
        showAllGallery = false;
        renderGallery();
        return;
      }
      // Color pill → inventory
      const pill = e.target.closest('.color-pill');
      if (!pill) return;
      const parts = pill.dataset.paint.split('|');
      const brand = parts[0] || '';
      const name = parts[1] || parts[0];
      activateTab('inventory');
      filterBrand.value = brand;
      searchEl.value = name;
      sortCol = 'name';
      sortDir = 1;
      render();
    });

    // Gallery search
    document.getElementById('gallery-search').addEventListener('input', e => {
      gallerySearch = e.target.value;
      showAllGallery = false;
      renderGallery();
    });

    document.getElementById('active-faction-pill').addEventListener('click', () => {
      factionFilter = '';
      showAllGallery = false;
      renderGallery();
    });

    function activateTab(tabName) {
      const panel = document.getElementById('tab-' + tabName);
      if (!panel) return false;
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      document.querySelectorAll('.sidebar-nav [data-tab]').forEach(a => a.classList.remove('active'));
      panel.classList.add('active');
      const navLink = document.querySelector(`.sidebar-nav [data-tab="${tabName}"]`);
      if (navLink) navLink.classList.add('active');
      if (tabName === 'books'    && window._renderBooks)    window._renderBooks();
      if (tabName === 'journals' && window._renderJournals) window._renderJournals();
      if (tabName === 'brushes'  && window._renderBrushes)  window._renderBrushes();
      if (tabName === 'supplies' && window._renderSupplies) window._renderSupplies();
      if (tabName === 'bench'    && window._renderBench)    window._renderBench();
      if (tabName === 'forces'   && window._renderForces)   window._renderForces();
      if (tabName === 'battles'  && window._renderBattles)  window._renderBattles();
      if (tabName === 'shame'    && window._renderShame)    window._renderShame();
      if (tabName === 'rescues'  && window._renderRescues)  window._renderRescues();
      if (tabName === 'wishlist' && window._renderWishlist) window._renderWishlist();
      if (tabName === 'recipes'  && window._renderRecipes)  window._renderRecipes();
      if (tabName === 'factions' && window._renderFactions) window._renderFactions();
      fetch('index.php', { method: 'POST', body: new URLSearchParams({ action: 'track_tab', tab: tabName }) });
      if (typeof gtag !== 'undefined') gtag('event', 'tab_view', { tab_name: tabName });
      const _tabUrl = new URL(location.href);
      if (tabName === 'contents') { _tabUrl.searchParams.delete('tab'); } else { _tabUrl.searchParams.set('tab', tabName); }
      if (tabName !== 'inventory') { _tabUrl.searchParams.delete('wheel'); if (wheelActive) { wheelActive=false; if (wheelBtn) wheelBtn.classList.remove('active'); if (harmonyActive) { harmonyActive=false; harmonySelectedKey=null; if (harmonyBtn) { harmonyBtn.classList.remove('active'); harmonyBtn.style.display='none'; } clearHarmonyArcs(); if (harmonyPanel) { harmonyPanel.style.display='none'; harmonyPanel.innerHTML=''; } } if (wheelView) wheelView.style.display='none'; if (wheelTip) wheelTip.style.display='none'; if (wheelCopyBtn) wheelCopyBtn.style.display='none'; } }
      history.replaceState(null, '', _tabUrl.toString());
      return true;
    }

    function closeSidebar() {
      document.getElementById('sidebar').classList.remove('open');
      document.getElementById('sidebar-backdrop').classList.remove('visible');
    }

    function switchToTab(tabName) {
      const ok = activateTab(tabName);
      if (ok) window.scrollTo({ top: 0, behavior: 'smooth' });
      return ok;
    }

    document.querySelectorAll('.sidebar-nav [data-tab]').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        switchToTab(link.dataset.tab);
        if (window.innerWidth <= 768) closeSidebar();
      });
    });

    document.querySelectorAll('[data-jump]').forEach(el => {
      el.addEventListener('click', e => {
        e.preventDefault();
        switchToTab(el.dataset.jump);
      });
    });


    (function() {
      const el = document.getElementById('hero-heatmap');
      if (!el) return;
      const act = new Map();

      const addMins = (dateStr, mins) => {
        if (dateStr && /^\d{4}-\d{2}-\d{2}$/.test(dateStr)) act.set(dateStr, (act.get(dateStr) || 0) + mins);
      };
      if (BENCH_DATA) BENCH_DATA.forEach(b => {
        (b.sessions || []).forEach(s => {
          if (!s.date) return;
          const m = s.duration != null ? s.duration : ([0,6].includes(new Date(s.date + 'T12:00').getDay()) ? 180 : 90);
          addMins(s.date, m);
        });
      });
      const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      function iso(d) {
        return d.toISOString().slice(0, 10);
      }
      const gridStart = new Date(today);
      gridStart.setDate(today.getDate() - today.getDay() - 51 * 7);
      const gridEnd = new Date(today);
      gridEnd.setDate(today.getDate() + (6 - today.getDay()));
      const weeks = [];
      const cur = new Date(gridStart);
      while (cur <= gridEnd) {
        const wk = [];
        for (let d = 0; d < 7; d++) {
          wk.push({
            iso: iso(cur),
            mins: act.get(iso(cur)) || 0,
            future: cur > today,
            dt: new Date(cur)
          });
          cur.setDate(cur.getDate() + 1);
        }
        weeks.push(wk);
      }
      const isoToday = iso(today);
      const isoStart = iso(gridStart);
      const activeDays = [...act.keys()].filter(d => d >= isoStart && d <= isoToday).length;

      function lvl(m, f) {
        if (f || m <= 0) return 0;
        return m <= 60 ? 1 : m <= 120 ? 2 : 3;
      }
      let lastMon = -1;
      const monthRow = weeks.map(wk => {
        const m = wk[0].dt.getMonth();
        const lbl = m !== lastMon ? MON[m] : '';
        lastMon = m;
        return `<span>${lbl}</span>`;
      }).join('');
      const gridHtml = weeks.map(wk => `<div class="hm-week">${wk.map(day => { const l = lvl(day.mins, day.future); const cls = 'hm-day ' + (day.future ? 'hm-future' : `hm-lv${l}`) + (day.iso === isoToday ? ' hm-today' : ''); const tip = day.future ? '' : day.mins ? `${DAYS[day.dt.getDay()]} ${MON[day.dt.getMonth()]} ${day.dt.getDate()}, ${day.dt.getFullYear()} · ${day.mins} min${day.mins === 1 ? '' : 's'}` : `${DAYS[day.dt.getDay()]} ${MON[day.dt.getMonth()]} ${day.dt.getDate()}, ${day.dt.getFullYear()}`; return `<div class="${cls.trim()}"${tip ? ` title="${tip}"` : ''}></div>`; }).join('')}</div>`).join('');
      el.innerHTML = `<div class="hm-header">Hobby Activity &middot; <span class="hm-count">${activeDays} active day${activeDays === 1 ? '' : 's'} this year</span></div><div class="hm-scroll"><div class="hm-inner"><div class="hm-months">${monthRow}</div><div class="hm-grid">${gridHtml}</div></div></div><div class="hm-legend"><span>Less</span><div class="hm-lv0"></div><div class="hm-lv1"></div><div class="hm-lv2"></div><div class="hm-lv3"></div><span>More</span></div>`;
      const hmScroll = el.querySelector('.hm-scroll');
      if (hmScroll) hmScroll.scrollLeft = hmScroll.scrollWidth;
    })();

    let lbImages = [],
      lbIdx = 0;
    const lbOverlay = document.getElementById('lightbox');
    const lbImg = document.getElementById('lb-img');
    const lbPrev = document.getElementById('lb-prev');
    const lbNext = document.getElementById('lb-next');
    const lbCounter = document.getElementById('lb-counter');

    function openLightbox(images, startIdx) {
      lbImages = images;
      lbIdx = startIdx;
      showLbSlide();
      lbOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lbOverlay.classList.remove('open');
      document.body.style.overflow = '';
      lbImg.src = '';
    }

    function showLbSlide() {
      lbImg.src = lbImages[lbIdx];
      lbPrev.hidden = lbImages.length <= 1 || lbIdx === 0;
      lbNext.hidden = lbImages.length <= 1 || lbIdx === lbImages.length - 1;
      lbCounter.textContent = lbImages.length > 1 ? (lbIdx + 1) + ' / ' + lbImages.length : '';
    }

    document.getElementById('lb-close').addEventListener('click', closeLightbox);
    lbPrev.addEventListener('click', e => {
      e.stopPropagation();
      if (lbIdx > 0) {
        lbIdx--;
        showLbSlide();
      }
    });
    lbNext.addEventListener('click', e => {
      e.stopPropagation();
      if (lbIdx < lbImages.length - 1) {
        lbIdx++;
        showLbSlide();
      }
    });
    lbOverlay.addEventListener('click', e => {
      if (e.target === lbOverlay) closeLightbox();
    });
    (function() {
      let tx = 0,
        ty = 0;
      lbOverlay.addEventListener('touchstart', e => {
        tx = e.touches[0].clientX;
        ty = e.touches[0].clientY;
      }, {
        passive: true
      });
      lbOverlay.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - tx;
        const dy = e.changedTouches[0].clientY - ty;
        if (Math.abs(dy) > Math.abs(dx) && dy > 60) {
          closeLightbox();
          return;
        }
        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
          if (dx < 0 && lbIdx < lbImages.length - 1) {
            lbIdx++;
            showLbSlide();
          } else if (dx > 0 && lbIdx > 0) {
            lbIdx--;
            showLbSlide();
          }
        }
      }, {
        passive: true
      });
    })();
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && document.getElementById('notes-overlay').classList.contains('open')) {
        closeNotes();
        return;
      }
      if (e.key === 'Escape' && document.getElementById('used-in-overlay').classList.contains('open')) {
        closeUsedIn();
        return;
      }
      if (document.getElementById('recipe-guide-overlay').classList.contains('open')) {
        if (e.key === 'Escape') {
          closeRecipeGuide();
          return;
        }
        if (e.key === 'ArrowRight') {
          stepGuide(1);
          return;
        }
        if (e.key === 'ArrowLeft') {
          stepGuide(-1);
          return;
        }
        return;
      }
      if (e.key === 'Escape' && document.getElementById('pull-overlay').classList.contains('open')) {
        closePull();
        return;
      }
      if (e.key === 'Escape' && document.getElementById('shop-overlay').classList.contains('open')) {
        closeShoppingList();
        return;
      }
      if (e.key === 'Escape' && document.getElementById('restock-overlay').classList.contains('open')) {
        closeRestockList();
        return;
      }
      if (document.getElementById('warpaint-overlay').classList.contains('open')) {
        if (e.key === 'Escape') { closeWarpaint(); return; }
        if (e.key === 'ArrowLeft')  { _wpShiftImg(-1); return; }
        if (e.key === 'ArrowRight') { _wpShiftImg(1);  return; }
      }
      if (!lbOverlay.classList.contains('open')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft' && lbIdx > 0) {
        lbIdx--;
        showLbSlide();
      }
      if (e.key === 'ArrowRight' && lbIdx < lbImages.length - 1) {
        lbIdx++;
        showLbSlide();
      }
    });

    document.getElementById('warpaint-overlay').addEventListener('click', function(e) {
      const thumb = e.target.closest('.wp-thumb');
      if (thumb) {
        document.querySelectorAll('.wp-thumb').forEach(function(t) { t.classList.remove('active'); });
        thumb.classList.add('active');
        document.getElementById('wp-main-img').src = thumb.dataset.src;
        _wpImgIdx = parseInt(thumb.dataset.idx) || 0;
        return;
      }
      if (e.target.id === 'wp-main-img') {
        const m = MODELS.find(function(x) { return x.id === _wpId; });
        if (m && m.images && m.images.length) openLightbox(m.images.map(function(s) { return new URL(s, location.href).href; }), _wpImgIdx);
        return;
      }
    });

    function showUsedIn(brand, name, layer) {
      const key3 = brand + '|' + name + '|' + (layer || '');
      const schemes = MODELS.filter(m => effectiveColors(m).some(c => upgradeKey(c) === key3));
      document.getElementById('used-in-paint-name').textContent = name;
      document.getElementById('used-in-brand').textContent = brand;
      let html = '';
      if (schemes.length === 0) {
        html = '<div class="used-in-empty">Not used in any documented scheme yet.</div>';
      } else {
        html = schemes.map(m => {
          const meta = [m.faction, m.date].filter(Boolean).join(' \u2014 ');
          return `<div class="used-in-item">
            <div>
              <div class="used-in-model-name">${esc(m.name)}</div>
              ${meta ? `<div class="used-in-model-meta">${esc(meta)}</div>` : ''}
            </div>
            <button class="used-in-goto" onclick="gotoModel('${esc(m.id)}')">View &rarr;</button>
          </div>`;
        }).join('');
      }
      document.getElementById('used-in-content').innerHTML = html;
      document.getElementById('used-in-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeUsedIn() {
      document.getElementById('used-in-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    function gotoModel(id) {
      closeUsedIn();
      activateTab('gallery');
      showAllGallery = true;
      factionFilter = '';
      renderGallery();
      const card = document.querySelector(`.model-card[data-id="${id}"]`);
      if (card) {
        card.classList.add('highlight');
        card.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }
    }

    document.getElementById('used-in-overlay').addEventListener('click', e => {
      if (e.target === document.getElementById('used-in-overlay')) closeUsedIn();
    });

    document.getElementById('tbody').addEventListener('click', e => {
      const notesBtn = e.target.closest('.notes-btn');
      if (notesBtn) {
        openNotes(notesBtn.dataset.pid, notesBtn.dataset.notesBrand, notesBtn.dataset.notesName, notesBtn.dataset.stars, notesBtn.dataset.notesText);
        return;
      }
      const starBtn = e.target.closest('.star-rate-btn');
      if (starBtn) {
        openNotes(starBtn.dataset.pid, starBtn.dataset.notesBrand, starBtn.dataset.notesName, starBtn.dataset.stars, starBtn.dataset.notesText);
        return;
      }
      const row = e.target.closest('tr[data-brand]');
      if (!row) return;
      showUsedIn(row.dataset.brand, row.dataset.name, row.dataset.layer);
    });

    function renderPullLi(raw, name) {
      const key = upgradeKey(raw);
      const stock = paintStock.get(key) || '';
      if (!paintOwned.has(key)) return `<li>${esc(name)}<span class="pull-flag missing">missing</span>${nearestHintHtml(raw)}</li>`;
      if (stock === 'out') return `<li>${esc(name)}<span class="pull-flag out">out</span></li>`;
      if (stock === 'low') return `<li>${esc(name)}<span class="pull-flag low">low</span></li>`;
      return `<li>${esc(name)}</li>`;
    }

    function populatePullSheet(title, subtitle, colors, recipeIds) {
      document.getElementById('pull-title').textContent = title;
      document.getElementById('pull-faction').textContent = subtitle;

      // Recipe-aware path
      const recipesMap = window._RECIPE_BY_ID;
      const recipes = (recipeIds && recipesMap) ? recipeIds.map(id => recipesMap.get(id)).filter(Boolean) : [];
      const coversColor = new Set();
      let html = '';

      if (recipes.length) {
        recipes.forEach(r => {
          html += `<div class="pull-brand-heading" style="color:#c9a227">${esc(r.name)}${r.category ? ` <span style="font-weight:normal;color:#999">(${esc(r.category)})</span>` : ''}</div>`;
          html += `<ul class="pull-paint-list">`;
          (r.steps || []).forEach((s, i) => {
            const parts = (s.paint || '').split('|');
            const brand = parts[0] || '';
            const name = parts[1] || s.paint || '';
            coversColor.add((s.paint || '').toLowerCase());
            const key = upgradeKey(s.paint || '');
            const stock = paintStock.get(key) || '';
            let flag = '';
            if (!paintOwned.has(key)) flag = `<span class="pull-flag missing">missing</span>`;
            else if (stock === 'out') flag = `<span class="pull-flag out">out</span>`;
            else if (stock === 'low') flag = `<span class="pull-flag low">low</span>`;
            const techLabel = (s.technique || 'special');
            const extras = [s.ratio, s.note].filter(Boolean).map(esc).join(' · ');
            const nearHint = !paintOwned.has(key) ? nearestHintHtml(s.paint || '') : '';
            html += `<li><span style="font-size:9px;text-transform:uppercase;letter-spacing:.06em;color:#999;margin-right:6px;font-family:'Cinzel',serif">${i + 1}. ${esc(techLabel)}</span><strong>${esc(name)}</strong>${brand ? ` <span style="color:#aaa;font-size:11px">${esc(brand)}</span>` : ''}${extras ? ` <em style="color:#888;font-size:11px">${extras}</em>` : ''}${flag}${nearHint}</li>`;
          });
          html += `</ul>`;
        });
        // Other paints in colors not covered by recipes
        const leftover = colors.filter(c => !coversColor.has((c || '').toLowerCase()));
        if (leftover.length) {
          html += `<div class="pull-brand-heading" style="color:#999">Other paints</div>`;
          html += `<ul class="pull-paint-list">` + leftover.map(raw => {
            const parts = raw.split('|');
            return renderPullLi(raw, parts[1] || raw);
          }).join('') + `</ul>`;
        }
      } else {
        // Legacy fallback: flat brand-grouped list
        const sorted = colors.slice().sort((a, b) => {
          const [ab, an = ''] = a.split('|');
          const [bb, bn = ''] = b.split('|');
          return ab.localeCompare(bb) || an.localeCompare(bn);
        });
        const groups = {};
        sorted.forEach(c => {
          const parts = c.split('|');
          const brand = parts[0];
          (groups[brand] = groups[brand] || []).push({
            raw: c,
            name: parts[1] || c
          });
        });
        for (const [brand, paints] of Object.entries(groups)) {
          html += `<div class="pull-brand-heading">${esc(brand)}</div>`;
          html += `<ul class="pull-paint-list">` + paints.map(({
            raw,
            name
          }) => renderPullLi(raw, name)).join('') + `</ul>`;
        }
      }

      document.getElementById('pull-content').innerHTML = html;
      document.getElementById('pull-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function openPull(id) {
      const m = MODELS.find(x => x.id === id);
      if (!m || !(m.colors || []).length) return;
      populatePullSheet(
        m.name,
        [m.faction, m.date].filter(Boolean).join(' \u2014 '),
        m.colors,
        m.recipes
      );
    }

    let _wpId = null;
    let _wpImgIdx = 0;

    function _wpHex(key) {
      const uk = upgradeKey(key);
      for (const p of PAINTS) {
        if ((p.brand + '|' + p.name + '|' + (p.layer || '')).toLowerCase() === uk) return p.hex || null;
      }
      return null;
    }

    function _hexToRgb(h) { return [parseInt(h.slice(1,3),16), parseInt(h.slice(3,5),16), parseInt(h.slice(5,7),16)]; }

    function hexToHsl(hex) {
      let [r,g,b] = _hexToRgb(hex);
      r/=255; g/=255; b/=255;
      const max=Math.max(r,g,b), min=Math.min(r,g,b), l=(max+min)/2;
      if (max===min) return [0,0,Math.round(l*100)];
      const d=max-min, s=d/(l>0.5?2-max-min:max+min);
      let h=max===r?(g-b)/d+(g<b?6:0):max===g?(b-r)/d+2:(r-g)/d+4;
      return [h*60, Math.round(s*100), Math.round(l*100)];
    }


    function getHue(p) {
      if (_ACHROMATIC_CATS.has(p.color)) return null;
      if (p.hex && /^#[0-9a-f]{6}$/i.test(p.hex)) {
        const [h,s] = hexToHsl(p.hex);
        if (s >= 8) return h;
      }
      return _CAT_HUE[p.color] !== undefined ? _CAT_HUE[p.color] : null;
    }

    function nearestOwnedHex(paintKey) {
      const uk = upgradeKey(paintKey).toLowerCase();
      let tHex = null;
      for (const p of PAINTS) {
        if ((p.brand+'|'+p.name+'|'+(p.layer||'')).toLowerCase()===uk || (p.brand+'|'+p.name).toLowerCase()===uk) { tHex = p.hex||null; break; }
      }
      if (!tHex) return null;
      const [tr,tg,tb] = _hexToRgb(tHex);
      let best = null, bestDist = Infinity;
      for (const p of PAINTS) {
        if (!p.hex) continue;
        const pKey = p.brand+'|'+p.name;
        if ((pKey+'|'+(p.layer||'')).toLowerCase()===uk || pKey.toLowerCase()===uk) continue;
        if (!paintOwned.has(pKey)) continue;
        const st = paintStock.get(pKey)||'';
        if (st==='out'||st==='wanted') continue;
        const [r,g,b] = _hexToRgb(p.hex);
        const d = Math.sqrt((r-tr)**2+(g-tg)**2+(b-tb)**2);
        if (d < bestDist) { bestDist=d; best=p; }
      }
      return best ? {name:best.name, brand:best.brand, hex:best.hex} : null;
    }

    function nearestHintHtml(paintKey) {
      const m = nearestOwnedHex(paintKey);
      if (!m) return '';
      return `<span class="nearest-hint"><span class="nh-swatch" style="background:${m.hex}"></span>≈ ${esc(m.name)}</span>`;
    }

    function _wpShiftImg(dir) {
      const m = MODELS.find(function(x) { return x.id === _wpId; });
      if (!m || !m.images || m.images.length < 2) return;
      _wpImgIdx = (_wpImgIdx + dir + m.images.length) % m.images.length;
      document.querySelectorAll('.wp-thumb').forEach(function(t, i) { t.classList.toggle('active', i === _wpImgIdx); });
      document.getElementById('wp-main-img').src = m.images[_wpImgIdx];
    }

    function formatWPDesc(text) {
      const blocks = text.split(/\r?\n\s*\r?\n/);
      return blocks.map(function(block) {
        const lines = block.split(/\r?\n/).map(function(l) { return l.trim(); }).filter(Boolean);
        if (!lines.length) return '';
        const first = lines[0];
        const isHdr = first.length < 40 && first === first.toUpperCase() && /[A-Z]/.test(first);
        const hdr = isHdr ? `<div class="wp-desc-hdr">${esc(first)}</div>` : '';
        const body = isHdr ? lines.slice(1) : lines;
        const bullets = body.filter(function(l) { return l.startsWith('-'); });
        const prose = body.filter(function(l) { return !l.startsWith('-'); });
        let inner = '';
        if (prose.length) inner += `<p class="wp-desc-p">${esc(prose.join(' '))}</p>`;
        if (bullets.length) inner += `<ul class="wp-desc-list">${bullets.map(function(l) { return `<li>${esc(l.replace(/^-\s*/, ''))}</li>`; }).join('')}</ul>`;
        return `<div class="wp-desc-block">${hdr}${inner}</div>`;
      }).join('');
    }

    const _WP_TECH = {basecoat:'Basecoat',wash:'Wash',shade:'Shade',layer:'Layer',edge:'Edge',highlight:'Highlight',glaze:'Glaze',drybrush:'Drybrush',stipple:'Stipple',blend:'Blend',special:'Special'};

    function _wpSetImages(imgs) {
      const mainImg = document.getElementById('wp-main-img');
      const noImg   = document.getElementById('wp-no-img');
      const thumbs  = document.getElementById('wp-thumbs');
      if (imgs.length) {
        mainImg.src = imgs[0]; mainImg.style.display = ''; noImg.style.display = 'none';
        thumbs.innerHTML = imgs.length > 1 ? imgs.map(function(src,i){ return `<img class="wp-thumb${i===0?' active':''}" src="${esc(src)}" data-src="${esc(src)}" data-idx="${i}" alt="">`; }).join('') : '';
        thumbs.style.display = imgs.length > 1 ? '' : 'none';
      } else {
        mainImg.src = ''; mainImg.style.display = 'none'; noImg.style.display = 'flex';
        thumbs.innerHTML = ''; thumbs.style.display = 'none';
      }
    }

    function _wpRecipePalette(recipeIds, colors, paletteLabel) {
      const recipes = (recipeIds && window._RECIPE_BY_ID) ? recipeIds.map(function(id){ return window._RECIPE_BY_ID.get(id); }).filter(Boolean) : [];
      const recipePaintKeys = new Set();
      let html = '';
      recipes.forEach(function(r) {
        html += `<div class="wp-recipe"><div class="wp-recipe-hd"><span class="wp-recipe-name">${esc(r.name)}${r.category ? ` <span class="wp-recipe-cat">(${esc(r.category)})</span>` : ''}</span>`;
        if (r.steps && r.steps.length) html += `<button class="wp-guide-btn" onclick="openRecipeGuide('${esc(r.id)}')">&#9654; Guide</button>`;
        html += `</div>`;
        if (r.description) html += `<div class="wp-recipe-desc">${esc(r.description)}</div>`;
        if (r.steps && r.steps.length) {
          html += `<ol class="wp-steps">`;
          r.steps.forEach(function(s, i) {
            const parts = (s.paint||'').split('|'); const name=parts[1]||s.paint||''; const brand=parts[0]||''; const layer=parts[2]||'';
            const key = upgradeKey(s.paint||''); recipePaintKeys.add((s.paint||'').toLowerCase());
            const stock=paintStock.get(key)||''; const owned=paintOwned.has(key);
            const dotCls = !owned?'missing':(stock==='out'?'out':stock==='low'?'low':'owned');
            const hex=_wpHex(s.paint||''); const swatch=hex?`<span class="wp-swatch" style="background:${hex}"></span>`:'';
            const tech=s.technique||'special'; const techLabel=_WP_TECH[tech]||tech;
            const note=s.note?`<div class="wp-step-note">${esc(s.note)}</div>`:'';
            const ratio=s.ratio?` <span class="wp-step-meta">${esc(s.ratio)}</span>`:'';
            let mixHtml='';
            if (s.mix_paint) { const mp=s.mix_paint.split('|'); const mName=mp[1]||s.mix_paint; const mKey=upgradeKey(s.mix_paint); const mDotCls=!paintOwned.has(mKey)?'missing':paintStock.get(mKey)==='out'?'out':paintStock.get(mKey)==='low'?'low':'owned'; const mHex=_wpHex(s.mix_paint); mixHtml=` <span class="wp-step-mix"><span class="wp-mix-sep">+</span>${mHex?`<span class="wp-swatch" style="background:${mHex}"></span>`:''}<span class="wp-stock-dot wp-dot-${mDotCls}"></span>${esc(mName)}</span>`; }
            let brushHtml='';
            if (s.brush&&typeof BRUSHES_DATA!=='undefined'&&BRUSHES_DATA) { const bE=BRUSHES_DATA.find(function(x){return x.id===s.brush;}); if(bE) brushHtml=`<div class="wp-step-brush">${esc([bE.brand,bE.series,bE.size].filter(Boolean).join(' \xb7 '))}</div>`; }
            const wpNear=dotCls==='missing'?nearestHintHtml(s.paint||''):'';
            html += `<li class="wp-step-row"><span class="wp-step-num">${i+1}</span><span class="recipe-step-tech recipe-tech-${esc(tech)}">${esc(techLabel)}</span><div class="wp-step-body"><div class="wp-step-paint"><span class="wp-stock-dot wp-dot-${dotCls}"></span>${swatch}<strong>${esc(name)}</strong><span class="wp-step-brand">${esc(brand)}${layer?' \xb7 '+esc(layer):''}</span>${ratio}${mixHtml}</div>${brushHtml}${note}${wpNear}</div></li>`;
          });
          html += `</ol>`;
        }
        html += `</div>`;
      });
      const palette = (colors||[]).filter(function(c){ return !recipePaintKeys.has(c.toLowerCase()); });
      if (palette.length) {
        html += `<div class="wp-palette-hd">${paletteLabel||'Paint Palette'}</div><div class="wp-palette">`;
        palette.forEach(function(c) {
          const parts=c.split('|'); const name=parts[1]||c; const brand=parts[0]||''; const key=upgradeKey(c);
          const stock=paintStock.get(key)||''; const owned=paintOwned.has(key);
          const dotCls=!owned?'missing':stock==='out'?'out':stock==='low'?'low':'owned';
          const hex=_wpHex(c); const swatch=`<span class="wp-palette-swatch" style="background:${hex||'#2a1e08'}"></span>`;
          html += `<span class="wp-palette-pill">${swatch}<span class="wp-stock-dot wp-dot-${dotCls}"></span>${esc(name)}<span class="wp-palette-brand">${esc(brand)}</span></span>`;
        });
        html += `</div>`;
      }
      return html;
    }

    function openWarpaint(mId) {
      const m = MODELS.find(function(x) { return x.id === mId; });
      if (!m) return;
      _wpId = mId; _wpImgIdx = 0;
      _wpSetImages(m.images || []);
      document.getElementById('wp-scheme-name').textContent = m.name;
      let badges = '';
      if (m.system && typeof SYS_COLORS !== 'undefined') badges += `<span class="sys-game-badge" style="background:${SYS_COLORS[m.system]||'#2a2a2a'}">${esc(m.system)}</span>`;
      if (m.faction) badges += ` <span class="faction-tag">${esc(m.faction)}</span>`;
      document.getElementById('wp-badges').innerHTML = badges;
      let html = '';
      if (m.summary) {
        const rows = [['Finish',m.summary.finish],['Primary',m.summary.primary],['Contrast',m.summary.contrast],['Technique',m.summary.technique]].filter(function(r){return r[1];});
        if (rows.length) html += `<div class="wp-summary">${rows.map(function(r){return `<span class="wp-sum-lbl">${esc(r[0])}</span><span class="wp-sum-val">${esc(r[1])}</span>`;}).join('')}</div>`;
      }
      if (m.description) html += `<div class="wp-desc">${formatWPDesc(m.description)}</div>`;
      html += _wpRecipePalette(m.recipes, m.colors);
      document.getElementById('wp-content-body').innerHTML = html;
      document.getElementById('wp-pull-btn').onclick = function() { openPull(mId); };
      document.getElementById('warpaint-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function openBenchWarpaint(bid) {
      if (typeof BENCH_DATA === 'undefined' || !BENCH_DATA) return;
      const b = BENCH_DATA.find(function(x) { return x.id === bid; });
      if (!b) return;
      _wpId = bid; _wpImgIdx = 0;
      _wpSetImages(b.wip_images || []);
      document.getElementById('wp-scheme-name').textContent = b.name;
      let badges = '';
      if (b.system && typeof SYS_COLORS !== 'undefined') badges += `<span class="sys-game-badge" style="background:${SYS_COLORS[b.system]||'#2a2a2a'}">${esc(b.system)}</span>`;
      if (b.faction) badges += ` <span class="faction-tag">${esc(b.faction)}</span>`;
      const _SLBL = {built:'Built',primed:'Primed',basecoated:'Basecoated',washed:'Washed',highlighted:'Highlighted',based:'Based',varnished:'Varnished',done:'Done'};
      badges += ` <span class="bench-stage-label stage-${esc(b.stage||'built')}" style="font-size:11px">${esc(_SLBL[b.stage||'built']||b.stage||'built')}</span>`;
      document.getElementById('wp-badges').innerHTML = badges;
      let html = '';
      if (b.notes) html += `<div class="wp-desc">${formatWPDesc(b.notes)}</div>`;
      html += _wpRecipePalette(b.recipes, b.colors, 'Paint Queue');
      document.getElementById('wp-content-body').innerHTML = html;
      document.getElementById('wp-pull-btn').onclick = function() { if (window.openBenchPull) window.openBenchPull(bid); };
      document.getElementById('warpaint-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeWarpaint() {
      document.getElementById('warpaint-overlay').classList.remove('open');
      document.body.style.overflow = '';
      _wpId = null;
    }

    function openFactionPull(faction) {
      const schemes = MODELS.filter(m => (m.faction || '') === faction && (m.colors || []).length);
      if (!schemes.length) return;
      const allColors = [...new Set(schemes.flatMap(m => m.colors))];
      populatePullSheet(
        faction,
        `${allColors.length} unique paint${allColors.length !== 1 ? 's' : ''} across ${schemes.length} scheme${schemes.length !== 1 ? 's' : ''}`,
        allColors
      );
    }

    function closePull() {
      document.getElementById('pull-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    function copyPullList() {
      const title = document.getElementById('pull-title').textContent;
      const faction = document.getElementById('pull-faction').textContent;
      let text = title + (faction ? '\n' + faction : '') + '\n\n';
      document.getElementById('pull-content').querySelectorAll('.pull-brand-heading, .pull-paint-list li').forEach(el => {
        text += el.classList.contains('pull-brand-heading') ? el.textContent + '\n' : '  ' + el.textContent + '\n';
      });
      navigator.clipboard.writeText(text.trim()).then(() => {
        const btn = document.getElementById('pull-copy-btn');
        const prev = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = prev, 2000);
      });
    }

    document.getElementById('pull-overlay').addEventListener('click', e => {
      if (e.target === document.getElementById('pull-overlay')) closePull();
    });

    const plannedSearchEl = document.getElementById('planned-search');
    plannedSearchEl.addEventListener('input', renderPlanned);
    let plannedSystemFilter = '';
    let plannedReadyFilter = '';
    let benchSystemFilter = '';

    function schemeReadiness(pl) {
      const colors = pl.colors || [];
      if (!colors.length) return {
        level: 'ready',
        missing: 0,
        missingNames: []
      };
      let missing = 0;
      const missingNames = [];
      colors.forEach(c => {
        const uk = upgradeKey(c);
        const stock = paintStock.get(uk) || '';
        if (!paintOwned.has(uk) || stock === 'out') {
          missing++;
          const parts = c.split('|');
          missingNames.push(parts[1] || c);
        }
      });
      const level = missing === 0 ? 'ready' : missing <= 2 ? 'almost' : 'needs';
      return {
        level,
        missing,
        missingNames
      };
    }

    function renderPlanned() {
      const grid = document.getElementById('planned-grid');
      const emptyEl = document.getElementById('planned-empty');
      const countEl = document.getElementById('planned-count');
      if (!PLANNED.length) {
        grid.innerHTML = '';
        emptyEl.style.display = 'block';
        countEl.textContent = '';
        return;
      }
      const q = plannedSearchEl.value.trim().toLowerCase();
      let visible = q ?
        PLANNED.filter(pl =>
          (pl.name || '').toLowerCase().includes(q) ||
          (pl.faction || '').toLowerCase().includes(q) ||
          (pl.model || '').toLowerCase().includes(q) ||
          (pl.description || '').toLowerCase().includes(q)
        ) :
        PLANNED.slice();
      if (plannedSystemFilter) {
        visible = visible.filter(pl => (pl.system || '') === plannedSystemFilter);
      }
      if (plannedReadyFilter) {
        visible = visible.filter(pl => schemeReadiness(pl).level === plannedReadyFilter);
      }

      // Sort: READY first when not already filtered by readiness
      if (!plannedReadyFilter) {
        const ORDER = {
          ready: 0,
          almost: 1,
          needs: 2
        };
        visible.sort((a, b) => {
          const ra = ORDER[schemeReadiness(a).level];
          const rb = ORDER[schemeReadiness(b).level];
          if (ra !== rb) return ra - rb;
          return (a.name || '').localeCompare(b.name || '');
        });
      }

      emptyEl.style.display = 'none';
      const isFiltered = q || plannedSystemFilter || plannedReadyFilter;
      countEl.textContent = isFiltered ?
        visible.length + ' of ' + PLANNED.length + ' scheme' + (PLANNED.length !== 1 ? 's' : '') :
        PLANNED.length + ' scheme' + (PLANNED.length !== 1 ? 's' : '');

      if (!visible.length) {
        grid.innerHTML = '<div class="grid-empty">No schemes match.</div>';
        return;
      }

      grid.innerHTML = visible.map(pl => {
        const colors = collectAllPaints(pl.colors, pl.recipes);
        let missing = 0,
          low = 0;
        colors.forEach(c => {
          const uk = upgradeKey(c);
          const stock = paintStock.get(uk) || '';
          if (!paintOwned.has(uk) || stock === 'out') missing++;
          else if (stock === 'low') low++;
        });

        const {
          level: readyLevel,
          missingNames
        } = schemeReadiness(pl);
        const readyBadgeLabel = readyLevel === 'ready' ? 'Ready' : readyLevel === 'almost' ? 'Almost' : 'Needs Work';
        const readyBadge = `<span class="ready-badge ${readyLevel}">${readyBadgeLabel}</span>`;

        const shopImpact = (readyLevel === 'almost' && missingNames.length) ?
          `<div class="planned-shop-impact">Buy: ${missingNames.map(n => `<strong>${esc(n)}</strong>`).join(', ')} - then ready</div>` : '';

        const sortedColors = colors.slice().sort((a, b) => {
          const [ab, an = ''] = a.split('|');
          const [bb, bn = ''] = b.split('|');
          return ab.localeCompare(bb) || an.localeCompare(bn);
        });
        let lastBrand = '';
        const pillsHtml = sortedColors.map(c => {
          const [brand, name = c] = c.split('|');
          const uk = upgradeKey(c);
          const stock = paintStock.get(uk) || '';
          let cls = 'pcol-pill owned';
          if (!paintOwned.has(uk) || stock === 'out') cls = 'pcol-pill missing';
          else if (stock === 'low') cls = 'pcol-pill low';
          const label = brand !== lastBrand ? `<span class="pill-brand-label">${esc(brand)}</span>` : '';
          lastBrand = brand;
          return label + `<span class="${cls}" title="${esc(c)}">${esc(name)}</span>`;
        }).join('');

        const statusParts = [];
        if (missing > 0) statusParts.push(`<span style="color:#c94040">${missing} missing</span>`);
        if (low > 0) statusParts.push(`<span style="color:#c97a20">${low} low</span>`);

        const plannedRecipeRefs = renderRecipeRefs(pl.recipes);
        return `<div class="planned-card" data-id="${esc(pl.id || '')}">
          <div class="planned-card-header">
            <div class="planned-card-name">${esc(pl.name)}</div>
            ${pl.model   ? `<div class="planned-card-kit">${esc(pl.model)}</div>` : ''}
            ${pl.faction ? `<div class="planned-card-faction">${esc(pl.faction)}</div>` : ''}
            ${readyBadge}
            ${pl.system ? `<span class="sys-game-badge sys-${sysSlug(pl.system)}">${esc(pl.system)}</span>` : ''}
            ${pl.codex_source ? `<span class="codex-source-badge">${esc(pl.codex_source)}</span>` : ''}
          </div>
          <div class="planned-card-body">
            ${shopImpact}
            ${plannedRecipeRefs}
            ${formatDesc(pl.description)}
            ${colors.length  ? `<div class="planned-colors">${pillsHtml}</div>` : ''}
            <div class="planned-card-footer">
              <span class="planned-card-summary">${colors.length} paint${colors.length !== 1 ? 's' : ''}${statusParts.length ? ' - ' + statusParts.join(', ') : ''}</span>
              ${colors.length ? `<button class="pull-btn planned-pull-btn" onclick="openPlannedPull('${esc(pl.id || '')}')">Pull list${missing > 0 ? ` <span class="pull-issue-badge">${missing} issue${missing !== 1 ? 's' : ''}</span>` : ''}</button><button class="harmony-btn" onclick="openSchemeDoctor('${esc(pl.id || '')}','planned')">&#9678; Harmony</button>` : ''}
            </div>
          </div>
        </div>`;
      }).join('');
    }

    window.openPlannedPull = function(id) {
      const pl = PLANNED.find(x => x.id === id);
      if (!pl) return;
      const subtitle = [pl.faction, pl.model].filter(Boolean).join(' — ');
      populatePullSheet(pl.name, subtitle, pl.colors || [], pl.recipes);
    };

    function schemeHues(colors) {
      return (colors || []).map(c => { const key=upgradeKey(c); const p=_paintByKey.get(key); if (!p) return null; const h=getHue(p); if (h===null) return null; const hsl=p.hex?hexToHsl(p.hex):[h,60,45]; return {key,p,hue:h,hsl}; }).filter(Boolean);
    }

    function detectHarmonyType(hues) {
      if (!hues.length) return 'Unknown';
      if (hues.length === 1) return 'Monochromatic';
      const norm = hues.map(h => ((h%360)+360)%360).sort((a,b)=>a-b);
      const gaps = norm.map((h,i) => i<norm.length-1 ? norm[i+1]-h : 360-h+norm[0]);
      const spread = 360 - Math.max(...gaps);
      if (spread <= 30) return 'Monochromatic';
      if (spread <= 70) return 'Analogous';
      const clusters = [];
      norm.forEach(h => { const ex=clusters.find(c=>Math.min(Math.abs(c-h),360-Math.abs(c-h))<=30); if (!ex) clusters.push(h); });
      if (clusters.length === 2) { const d=Math.min(Math.abs(clusters[0]-clusters[1]),360-Math.abs(clusters[0]-clusters[1])); if (Math.abs(d-180)<35) return 'Complementary'; if (Math.abs(d-150)<30||Math.abs(d-210)<30) return 'Split-Complementary'; }
      if (clusters.length === 3) { const s=clusters.slice().sort((a,b)=>a-b); const diffs=[s[1]-s[0],s[2]-s[1],360-s[2]+s[0]]; if (diffs.every(d=>Math.abs(d-120)<35)) return 'Triadic'; }
      if (clusters.length >= 4) return 'Tetradic';
      return 'Mixed';
    }

    function buildMiniWheelSvg(hueData) {
      const CX=150,CY=150,R1=100,R2=118,SEGS=24,DEG=15;
      let bg='';
      for (let i=0;i<SEGS;i++) { const hd=i*DEG,aS=(hd-90-DEG/2)*Math.PI/180,aE=(hd-90+DEG/2)*Math.PI/180; const x1=CX+R1*Math.cos(aS),y1=CY+R1*Math.sin(aS),x2=CX+R2*Math.cos(aS),y2=CY+R2*Math.sin(aS),x3=CX+R2*Math.cos(aE),y3=CY+R2*Math.sin(aE),x4=CX+R1*Math.cos(aE),y4=CY+R1*Math.sin(aE); bg+=`<path d="M${x1.toFixed(1)},${y1.toFixed(1)} L${x2.toFixed(1)},${y2.toFixed(1)} A${R2},${R2} 0 0,1 ${x3.toFixed(1)},${y3.toFixed(1)} L${x4.toFixed(1)},${y4.toFixed(1)} A${R1},${R1} 0 0,0 ${x1.toFixed(1)},${y1.toFixed(1)}Z" fill="hsl(${hd},70%,55%)" opacity=".18"/>`; }
      const bkts=Array.from({length:SEGS},()=>[]);
      hueData.forEach(d => { const seg=Math.floor(((d.hue%360)+360)%360/DEG)%SEGS; bkts[seg].push(d); });
      let dots='';
      bkts.forEach(bkt => { bkt.forEach((d,ring) => { const angle=(d.hue-90)*Math.PI/180,r=55+ring*11,x=CX+r*Math.cos(angle),y=CY+r*Math.sin(angle); const fill=d.p.hex||`hsl(${d.hue},60%,45%)`; dots+=`<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="6" fill="${fill}" stroke="rgba(255,255,255,.35)" stroke-width="1.5"/>`; }); });
      return `<svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">${bg}<circle cx="${CX}" cy="${CY}" r="42" fill="#0a0806" opacity=".95"/>${dots}</svg>`;
    }

    window.openSchemeDoctor = function(id, source) {
      const scheme = source==='planned' ? PLANNED.find(x=>x.id===id) : MODELS.find(x=>x.id===id);
      if (!scheme) return;
      const modal = document.getElementById('scheme-doctor-modal');
      if (!modal) return;
      const allColors = collectAllPaints(scheme.colors, scheme.recipes);
      const hueData = schemeHues(allColors);
      const hues = hueData.map(d=>d.hue);
      const harmType = detectHarmonyType(hues);
      const typeColors = {Complementary:'#6a2020',Triadic:'#1a4a18',Analogous:'#1a2a4a','Split-Complementary':'#4a2a10',Tetradic:'#3a1a4a',Monochromatic:'#3a3010',Mixed:'#2a2a2a',Unknown:'#1a1a1a'};
      const typeBg = typeColors[harmType]||'#2a2a2a';
      const miniSvg = buildMiniWheelSvg(hueData);
      const warm=hueData.filter(d=>paintTemperature(d.hue)==='warm').length, cool=hueData.filter(d=>paintTemperature(d.hue)==='cool').length, tot=hueData.length||1, neutral=tot-warm-cool;
      const warmPct=Math.round(warm/tot*100), coolPct=Math.round(cool/tot*100), neutralPct=100-warmPct-coolPct;
      const shadows=hueData.filter(d=>d.hsl[2]<28), highlights=hueData.filter(d=>d.hsl[2]>72);
      const shadowTemp=shadows.length?(shadows.filter(d=>paintTemperature(d.hue)==='cool').length>=shadows.length/2?'cool':'warm'):null;
      const highlightTemp=highlights.length?(highlights.filter(d=>paintTemperature(d.hue)==='warm').length>=highlights.length/2?'warm':'cool'):null;
      const tempRowHtml=(shadowTemp?`<span class="sd-temp-tag ${shadowTemp==='cool'?'sd-ok':'sd-warn'}">Shadows: ${shadowTemp}${shadowTemp==='cool'?' ✓':' ⚠'}</span>`:'')+( highlightTemp?`<span class="sd-temp-tag ${highlightTemp==='warm'?'sd-ok':'sd-warn'}">Highlights: ${highlightTemp}${highlightTemp==='warm'?' ✓':' ⚠'}</span>`:'');
      const primaryData = hueData.length ? hueData.reduce((best,d)=>d.hsl[1]>best.hsl[1]?d:best, hueData[0]) : null;
      const typeKey = harmType.toLowerCase().replace(/ |-/g,'').replace('splitcomplementary','split');
      const expectedPos = primaryData ? harmonyPositions(primaryData.hue, typeKey) : [];
      const missingPos = expectedPos.filter(pos => !hueData.some(d=>Math.min(Math.abs(d.hue-pos.hue),360-Math.abs(d.hue-pos.hue))<=30));
      const missingHtml = missingPos.length ? `<div class="sd-section">Missing Harmony Roles</div>${missingPos.map(pos => { const sug=paintsNearHue(pos.hue,22,null).slice(0,5); const sugHtml=sug.map(m=>{const fill=m.hex||`hsl(${getHue(m)},60%,45%)`;const own=paintOwned.has(paintKey(m));return `<span class="hp-sw${own?'':' hp-sw-miss'}" style="background:${fill}" title="${esc(m.name)} (${esc(m.brand)})${own?'':' — not owned'}"></span>`;}).join(''); return `<div class="sd-missing"><span class="hp-pos-dot" style="background:hsl(${pos.hue.toFixed(0)},75%,58%)"></span><span class="sd-miss-label">${esc(pos.label)} — ${esc(pos.role)}</span><div class="hp-swatches">${sugHtml}</div></div>`; }).join('')}` : '<div class="sd-complete">✓ All harmony roles are covered</div>';
      const palHtml = hueData.map(d=>{ const fill=d.p.hex||`hsl(${d.hue},60%,45%)`;const role=paintRole(d.hsl[0],d.hsl[1],d.hsl[2]); return `<div class="sd-pal-item"><span class="hp-swatch-lg" style="background:${fill}" title="${esc(d.p.name)}"></span><span class="sd-pal-role">${role}</span></div>`; }).join('');
      const achromatic = allColors.map(c=>{const p=_paintByKey.get(upgradeKey(c));return p&&getHue(p)===null?p:null;}).filter(Boolean);
      const achHtml = achromatic.length ? `<div class="sd-section">Achromatic Paints</div><div class="hp-swatches">${achromatic.map(p=>`<span class="hp-sw" style="background:${p.hex||'#5a5a5a'}" title="${esc(p.name)} — ${esc(p.color)}"></span>`).join('')}</div>` : '';
      document.getElementById('sd-content').innerHTML = `<div class="sd-header"><div class="sd-name">${esc(scheme.name)}</div><span class="sd-type" style="background:${typeBg}">${esc(harmType)}</span></div><div class="sd-body"><div class="sd-mini-wheel">${miniSvg}</div><div class="sd-right"><div class="sd-temp-row">${tempRowHtml}</div><div class="sd-section">Temperature Distribution</div><div class="sd-prop-bar"><div class="sd-prop-warm" style="width:${warmPct}%"></div><div class="sd-prop-neutral" style="width:${neutralPct}%"></div><div class="sd-prop-cool" style="width:${coolPct}%"></div></div><div class="sd-prop-labels"><span>Warm ${warmPct}%</span><span>Neutral ${neutralPct}%</span><span>Cool ${coolPct}%</span></div>${missingHtml}${palHtml?`<div class="sd-section">Scheme Palette</div><div class="sd-palette">${palHtml}</div>`:''}${achHtml}</div></div>`;
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    };

    window.closeSchemeDoctor = function() {
      const modal = document.getElementById('scheme-doctor-modal');
      if (modal) modal.style.display = 'none';
      document.body.style.overflow = '';
    };

    document.querySelectorAll('.planned-rp').forEach(pill => {
      pill.addEventListener('click', () => {
        document.querySelectorAll('.planned-rp').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        plannedReadyFilter = pill.dataset.ready === 'all' ? '' : pill.dataset.ready;
        renderPlanned();
      });
    });

        (function() {
          const blSearchEl = document.getElementById('bl-search');
          const blCountEl = document.getElementById('bl-count');
          const blListEl = document.getElementById('bl-list');
          const blEmptyEl = document.getElementById('bl-empty');
          if (!BOOKS_DATA) return;
          const BL_TOTAL = BOOKS_DATA.length;

          function hlBL(text, q) {
            if (!q) return esc(text);
            const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            return text.split(re).map((part, i) => i % 2 === 1 ? '<mark>' + esc(part) + '</mark>' : esc(part)).join('');
          }

          function formatBLNotes(raw, q) {
            return raw.replace(/\r/g, '').split('\n').map(line => {
              const isCat = /^\s*[\w &]+:\s*$/.test(line);
              const rendered = hlBL(line, q);
              return isCat ? `<span class="label-cinzel-gold">${rendered}</span>` : rendered;
            }).join('\n');
          }

          function renderBookRow(b, q) {
            const btype = b.type || 'codex';
            const spine = btype === 'supplement' ? 'Supplement' : 'Codex';
            const edition = b.series ? `<div class="bl-edition">${hlBL(b.series, q)}</div>` : '';
            const author = b.author ? `<div class="bl-author">${hlBL(b.author, q)}</div>` : '';
            const notes = b.notes ? `<div class="bl-notes">${formatBLNotes(b.notes, q)}</div>` : '';
            return `<div class="bl-row bl-row-${esc(btype)}" data-id="${esc(b.id || '')}"><div class="bl-type-spine">${spine}</div><div class="bl-body">${b.faction ? `<div class="bl-codex-faction">${hlBL(b.faction, q)}</div>` : ''}<div class="bl-title">${hlBL(b.title, q)}</div>${edition}${author}${notes}</div></div>`;
          }

          function renderBooks() {
            const q = blSearchEl.value.trim().toLowerCase();
            let filtered = BOOKS_DATA.slice();
            if (q) {
              filtered = filtered.filter(b =>
                (b.title || '').toLowerCase().includes(q) ||
                (b.faction || '').toLowerCase().includes(q) ||
                (b.series || '').toLowerCase().includes(q) ||
                (b.author || '').toLowerCase().includes(q) ||
                (b.notes || '').toLowerCase().includes(q)
              );
            }
            blCountEl.textContent = filtered.length + ' of ' + BL_TOTAL + ' cod' + (BL_TOTAL !== 1 ? 'ices' : 'ex');
            if (!BL_TOTAL) {
              blListEl.innerHTML = '';
              blEmptyEl.style.display = 'block';
              return;
            }
            blEmptyEl.style.display = 'none';
            if (!filtered.length) {
              blListEl.innerHTML = '<div class="grid-empty">No codices match.</div>';
              return;
            }
            const sorted = filtered.slice().sort((a, b) => {
              const fc = (a.faction || '').localeCompare(b.faction || '');
              return fc !== 0 ? fc : (a.title || '').localeCompare(b.title || '');
            });
            blListEl.innerHTML = sorted.map(b => renderBookRow(b, q)).join('');
          }

          blSearchEl.addEventListener('input', renderBooks);
          window._renderBooks = renderBooks;
          renderBooks();
        })();

        (function() {
          const jnSearchEl = document.getElementById('jn-search');
          const jnCountEl = document.getElementById('jn-count');
          const jnListEl = document.getElementById('jn-list');
          const jnEmptyEl = document.getElementById('jn-empty');
          const jnMonthNavEl = document.getElementById('jn-month-nav');
          const jnPrevBtn = document.getElementById('jn-prev');
          const jnNextBtn = document.getElementById('jn-next');
          const jnMonthLbl = document.getElementById('jn-month-label');
          const jnYearPicker = document.getElementById('jn-year-picker');
          if (!JOURNAL_DATA) return;
          const journalData = JOURNAL_DATA;

          const JN_MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
          const JN_MONTHS_LONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

          // current YYYY-MM cursor; null = search mode (all entries)
          const now = new Date();
          let jnCursor = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

          const jnMonthsWithData = [...new Set(journalData.map(e => e.date ? e.date.slice(0, 7) : null).filter(Boolean))].sort();

          function fmtCursor(ym) {
            const [y, m] = ym.split('-');
            return JN_MONTHS_LONG[parseInt(m, 10) - 1] + ' ' + y;
          }

          function fmtJnDate(d) {
            if (!d) return '';
            const parts = d.split('-');
            if (parts.length < 3) return d;
            return JN_MONTHS_SHORT[parseInt(parts[1], 10) - 1] + ' ' + parseInt(parts[2], 10) + ', ' + parts[0];
          }

          function stepMonth(ym, delta) {
            let [y, m] = ym.split('-').map(Number);
            m += delta;
            if (m > 12) {
              m = 1;
              y++;
            }
            if (m < 1) {
              m = 12;
              y--;
            }
            return y + '-' + String(m).padStart(2, '0');
          }

          const currentYM = jnCursor;

          function updateNav() {
            const q = jnSearchEl.value.trim();
            jnMonthNavEl.style.display = q ? 'none' : '';
            if (q) return;
            jnMonthLbl.textContent = fmtCursor(jnCursor);
            jnPrevBtn.disabled = jnCursor <= (jnMonthsWithData[0] || jnCursor);
            jnNextBtn.disabled = jnCursor >= currentYM;
          }

          const MOOD_CLASS = {
            great: 'jn-mood-great',
            good: 'jn-mood-good',
            okay: 'jn-mood-okay',
            rough: 'jn-mood-rough'
          };

          function esc(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
          }

          function decodeHtml(s) {
            const d = document.createElement('div');
            d.innerHTML = s;
            return d.textContent;
          }

          function mentionBadge(type, id, label) {
            return `<span class="jn-mention jn-mention-${type}" data-mtype="${esc(type)}" data-mid="${esc(id)}" title="${esc(label)}">${esc(label)}</span>`;
          }

          function renderBody(raw) {
            if (!raw) return '';
            const TOKEN = /@\[(\w+):([^\]|]+)\|([^\]]+)\]/g;
            let result = '',
              last = 0,
              m;
            while ((m = TOKEN.exec(raw)) !== null) {
              result += esc(raw.slice(last, m.index));
              result += mentionBadge(m[1], m[2], decodeHtml(m[3]));
              last = m.index + m[0].length;
            }
            result += esc(raw.slice(last));
            return result;
          }

          function renderJournals() {
            if (!journalData) return;
            const q = jnSearchEl.value.trim().toLowerCase();
            let filtered;
            if (q) {
              filtered = journalData.filter(e => (e.title || '').toLowerCase().includes(q) || (e.body || '').toLowerCase().includes(q) || (e.date || '').includes(q));
              jnCountEl.textContent = filtered.length + ' entr' + (filtered.length !== 1 ? 'ies' : 'y') + ' matching';
            } else {
              filtered = journalData.filter(e => e.date && e.date.slice(0, 7) === jnCursor);
              jnCountEl.textContent = filtered.length + ' entr' + (filtered.length !== 1 ? 'ies' : 'y');
            }

            updateNav();

            if (!journalData.length) {
              jnListEl.innerHTML = '';
              jnEmptyEl.style.display = 'block';
              return;
            }
            jnEmptyEl.style.display = 'none';

            if (!filtered.length) {
              jnListEl.innerHTML = `<div class="grid-empty">${q ? 'No entries match.' : 'No entries for ' + fmtCursor(jnCursor) + '.'}</div>`;
              return;
            }

            jnListEl.innerHTML = filtered.map(e => {
              const moodCls = e.mood ? (MOOD_CLASS[e.mood] || '') : '';
              const moodBadge = e.mood ? `<span class="jn-mood ${moodCls}">${esc(e.mood.charAt(0).toUpperCase() + e.mood.slice(1))}</span>` : '';
              const titleBit = e.title ? `<span class="jn-title">${esc(e.title)}</span>` : '';
              return `<div class="jn-card"><div class="jn-card-header"><span class="jn-date">${esc(fmtJnDate(e.date))}</span>${moodBadge}${titleBit}</div><div class="jn-body">${renderBody(e.body || '')}</div></div>`;
            }).join('');
          }

          function showYearPicker() {
            if (!jnYearPicker.classList.contains('hidden')) {
              jnYearPicker.classList.add('hidden');
              return;
            }
            const years = [...new Set(jnMonthsWithData.map(ym => ym.slice(0, 4)))].sort((a, b) => b - a);
            jnYearPicker.innerHTML = years.map(y => `<button class="jn-year-btn${jnCursor.startsWith(y) ? ' active' : ''}" data-year="${y}">${y}</button>`).join('');
            jnYearPicker.classList.remove('hidden');
          }

          jnPrevBtn.addEventListener('click', () => {
            jnCursor = stepMonth(jnCursor, -1);
            renderJournals();
          });
          jnNextBtn.addEventListener('click', () => {
            jnCursor = stepMonth(jnCursor, 1);
            renderJournals();
          });

          jnMonthLbl.addEventListener('click', showYearPicker);

          jnYearPicker.addEventListener('click', ev => {
            const btn = ev.target.closest('[data-year]');
            if (!btn) return;
            jnYearPicker.classList.add('hidden');
            // jump to the most recent month in that year that has data, or Jan if none
            const y = btn.dataset.year;
            const monthsInYear = jnMonthsWithData.filter(ym => ym.startsWith(y));
            jnCursor = monthsInYear.length ? monthsInYear[monthsInYear.length - 1] : y + '-01';
            renderJournals();
          });

          jnSearchEl.addEventListener('input', () => {
            jnYearPicker.classList.add('hidden');
            renderJournals();
          });

          jnListEl.addEventListener('click', ev => {
            const m = ev.target.closest('[data-mtype]');
            if (!m) return;
            const type = m.dataset.mtype,
              id = m.dataset.mid;
            if (type === 'scheme' && window._jumpToScheme) window._jumpToScheme(id);
            else if (type === 'recipe' && window._jumpToRecipe) window._jumpToRecipe(id);
            else if (type === 'bench' && typeof switchToTab !== 'undefined') switchToTab('bench');
          });

          window._renderJournals = renderJournals;
        })();

        (function() {
          const brSearchEl = document.getElementById('brush-search');
          const brCountEl = document.getElementById('brush-count');
          const brListEl = document.getElementById('brush-list');
          const brEmptyEl = document.getElementById('brush-empty');
          if (!BRUSHES_DATA) return;
          const BR_TOTAL = BRUSHES_DATA.length;
          let brCondFilter = 'all';

          const BR_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

          function fmtBRMonth(m) {
            if (!m) return '';
            const [y, mo] = m.split('-');
            return (BR_MONTHS[parseInt(mo, 10) - 1] || mo) + ' ' + y;
          }

          const COND_LABEL = {
            prime: 'Prime',
            workhorse: 'Workhorse',
            retired: 'Retired'
          };

          function renderBrushes() {
            const q = brSearchEl.value.trim().toLowerCase();

            let filtered = brCondFilter === 'all' ?
              BRUSHES_DATA.slice() :
              BRUSHES_DATA.filter(b => (b.condition || 'prime') === brCondFilter);

            if (q) {
              filtered = filtered.filter(b =>
                (b.brand || '').toLowerCase().includes(q) ||
                (b.series || '').toLowerCase().includes(q) ||
                (b.size || '').toLowerCase().includes(q) ||
                (b.material || '').toLowerCase().includes(q) ||
                (b.use || '').toLowerCase().includes(q) ||
                (b.notes || '').toLowerCase().includes(q)
              );
            }

            brCountEl.textContent = filtered.length + ' of ' + BR_TOTAL + ' brush' + (BR_TOTAL !== 1 ? 'es' : '');

            if (!BR_TOTAL) {
              brListEl.innerHTML = '';
              brEmptyEl.style.display = 'block';
              return;
            }
            brEmptyEl.style.display = 'none';

            if (!filtered.length) {
              brListEl.innerHTML = '<div class="grid-empty">No brushes match.</div>';
              return;
            }

            const byBrand = new Map();
            filtered.forEach(b => {
              const k = b.brand || '-';
              if (!byBrand.has(k)) byBrand.set(k, []);
              byBrand.get(k).push(b);
            });
            const sortedBrands = [...byBrand.keys()].sort();

            brListEl.innerHTML = sortedBrands.map(brand => {
              const brushes = byBrand.get(brand);
              const entriesHtml = brushes.map(b => {
                const cond = b.condition || 'prime';
                const condLabel = COND_LABEL[cond] || cond;
                const seriesSize = [b.series, b.size].filter(Boolean).join(' · ');
                const matUse = [b.material, b.use].filter(Boolean).join(' · ');
                const date = fmtBRMonth(b.date_start || '');
                const starsN = b.stars || 0;
                const starsHtml = starsN ? `<span class="brush-stars">${Array.from({length:5},(_,i)=>`<span class="br-star${i<starsN?' on':''}">★</span>`).join('')}</span>` : '';
                const seriesHtml = seriesSize ? esc(seriesSize) : '<span style="color:#3a2a10">-</span>';
                const dateHtml = date ? `<span class="brush-cinzel-date">${esc(date)}</span>` : '';
                const notesHtml = b.notes ? `<div class="brush-entry-notes">${esc(b.notes)}</div>` : '';
                return `<div class="brush-entry" data-id="${esc(b.id || '')}"><div class="brush-entry-top"><span class="brush-entry-series">${seriesHtml}</span><span class="brush-entry-right">${starsHtml}<span class="brush-cond-badge cond-${esc(cond)}">${esc(condLabel)}</span></span></div><div class="brush-entry-bottom"><span>${esc(matUse)}</span>${dateHtml}</div>${notesHtml}</div>`;
              }).join('');
              return `<div class="brush-card">
            <div class="brush-card-header">
              <span>${esc(brand)}</span>
              <span class="brush-card-count">${brushes.length} brush${brushes.length !== 1 ? 'es' : ''}</span>
            </div>
            <div class="brush-card-body">${entriesHtml}</div>
          </div>`;
            }).join('');
          }

          document.querySelectorAll('.brush-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
              document.querySelectorAll('.brush-filter-pill').forEach(p => p.classList.remove('active'));
              pill.classList.add('active');
              brCondFilter = pill.dataset.cond;
              renderBrushes();
            });
          });

          brSearchEl.addEventListener('input', renderBrushes);
          window._renderBrushes = renderBrushes;
          renderBrushes();
        })();

        (function() {
          if (!SUPPLIES_DATA) return;

          const spSearchEl = document.getElementById('supply-search');
          const spListEl   = document.getElementById('supply-list');
          const spEmptyEl  = document.getElementById('supply-empty');
          const spCountEl  = document.getElementById('supply-count');
          if (!spSearchEl || !spListEl || !spEmptyEl) return;

          const SP_TOTAL = SUPPLIES_DATA.length;
          let spCondFilter = 'all';

          const SP_COND_LABEL = { prime: 'Prime', workhorse: 'Workhorse', retired: 'Retired' };
          const SP_MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

          function fmtSPMonth(m) {
            if (!m) return '';
            const [y, mo] = m.split('-');
            return (SP_MONTHS[parseInt(mo, 10) - 1] || mo) + ' ' + y;
          }

          function renderSupplies() {
            const q = spSearchEl.value.trim().toLowerCase();

            let filtered = spCondFilter === 'all' ? SUPPLIES_DATA.slice() : SUPPLIES_DATA.filter(s => (s.condition || 'prime') === spCondFilter);

            if (q) {
              filtered = filtered.filter(s =>
                (s.name   || '').toLowerCase().includes(q) ||
                (s.brand  || '').toLowerCase().includes(q) ||
                (s.type   || '').toLowerCase().includes(q) ||
                (s.notes  || '').toLowerCase().includes(q)
              );
            }

            spCountEl.textContent = filtered.length + ' of ' + SP_TOTAL + ' item' + (SP_TOTAL !== 1 ? 's' : '');

            if (!SP_TOTAL) { spListEl.innerHTML = ''; spEmptyEl.style.display = 'block'; return; }
            spEmptyEl.style.display = 'none';
            if (!filtered.length) { spListEl.innerHTML = '<div class="grid-empty">No supplies match.</div>'; return; }

            spListEl.innerHTML = filtered.map(s => {
              const cond      = s.condition || 'prime';
              const condLabel = SP_COND_LABEL[cond] || cond;
              const date      = fmtSPMonth(s.acquired || '');
              const typeHtml  = s.type ? `<span class="supply-type-badge">${esc(s.type)}</span>` : '';
              const brandHtml = s.brand ? `<span class="supply-card-brand">${esc(s.brand)}</span>` : '';
              const dateHtml  = date ? `<span class="supply-card-date">${esc(date)}</span>` : '';
              const notesHtml = s.notes ? `<div class="supply-card-notes">${esc(s.notes)}</div>` : '';
              return `<div class="supply-card" data-id="${esc(s.id || '')}"><div class="supply-card-top">${typeHtml}<span class="supply-card-name">${esc(s.name)}</span></div><div class="supply-card-meta">${brandHtml}${dateHtml}</div><div class="supply-card-bottom"><span class="brush-cond-badge cond-${esc(cond)}">${esc(condLabel)}</span></div>${notesHtml}</div>`;
            }).join('');
          }

          document.querySelectorAll('.supply-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
              document.querySelectorAll('.supply-filter-pill').forEach(p => p.classList.remove('active'));
              pill.classList.add('active');
              spCondFilter = pill.dataset.cond;
              renderSupplies();
            });
          });

          spSearchEl.addEventListener('input', renderSupplies);
          window._renderSupplies = renderSupplies;
          renderSupplies();
        })();

        (function() {
          const gridEl = document.getElementById('wishlist-grid');
          const emptyEl = document.getElementById('wishlist-empty');
          const countEl = document.getElementById('wishlist-count');
          const searchEl = document.getElementById('wishlist-search');
          const typePills = document.getElementById('wishlist-type-pills');
          const priPills = document.getElementById('wishlist-pri-pills');
          if (!gridEl) return;

          const WTYPE_LABEL = {
            paint: 'Paint',
            model: 'Model',
            brush: 'Brush',
            codex: 'Codex'
          };
          const WTYPE_COLOR = {
            paint: '#1a4a4a',
            model: '#1a3a1a',
            brush: '#3a1a10',
            codex: '#2a1a4a'
          };

          let typeFilter = 'all';
          let priFilter = 'all';
          let wStatusFilter = 'all';

          const usedTypes = [...new Set(WISHLIST_DATA.map(w => w.type || 'paint'))];
          const allPill = document.createElement('button');
          allPill.className = 'wish-fp active';
          allPill.dataset.wtype = 'all';
          allPill.textContent = 'All';
          typePills.appendChild(allPill);
          usedTypes.forEach(t => {
            const b = document.createElement('button');
            b.className = 'wish-fp';
            b.dataset.wtype = t;
            b.textContent = WTYPE_LABEL[t] || t;
            typePills.appendChild(b);
          });
          const orderedPill = document.createElement('button');
          orderedPill.className = 'wish-fp';
          orderedPill.dataset.wtype = '_ordered';
          orderedPill.textContent = 'In Transit';
          typePills.appendChild(orderedPill);

          typePills.addEventListener('click', ev => {
            const b = ev.target.closest('.wish-fp');
            if (!b) return;
            if (b.dataset.wtype === '_ordered') {
              wStatusFilter = wStatusFilter === 'ordered' ? 'all' : 'ordered';
              b.classList.toggle('active', wStatusFilter === 'ordered');
            } else {
              typeFilter = b.dataset.wtype;
              wStatusFilter = 'all';
              orderedPill.classList.remove('active');
              typePills.querySelectorAll('.wish-fp:not([data-wtype="_ordered"])').forEach(x => x.classList.toggle('active', x === b));
            }
            renderWishlist();
          });
          priPills.addEventListener('click', ev => {
            const b = ev.target.closest('.wish-pp');
            if (!b) return;
            priFilter = b.dataset.wpri;
            priPills.querySelectorAll('.wish-pp').forEach(x => x.classList.toggle('active', x === b));
            renderWishlist();
          });
          searchEl.addEventListener('input', renderWishlist);

          document.getElementById('wishlist-copy-btn').addEventListener('click', copyWishlist);
          document.getElementById('wishlist-print-btn').addEventListener('click', () => {
            document.body.classList.add('print-wishlist');
            window.print();
            document.body.classList.remove('print-wishlist');
          });

          function stockDot(w) {
            if ((w.type || 'paint') !== 'paint') return '';
            const key = ((w.brand || '') + '|' + (w.name || '')).toLowerCase();
            if (typeof paintByKeyLC === 'undefined') return '';
            const st = paintByKeyLC.get(key);
            if (st === undefined) return '<span class="stock-dot stock-dot-unknown" title="Not in inventory"></span>';
            if (st === 'low') return '<span class="stock-dot stock-dot-low" title="Low stock"></span>';
            if (st === 'out') return '<span class="stock-dot stock-dot-out" title="Out of stock"></span>';
            if (st === 'wanted') return '<span class="stock-dot stock-dot-wanted" title="Wanted"></span>';
            return '<span class="stock-dot stock-dot-owned" title="Owned"></span>';
          }

          function cardHtml(w) {
            const t = w.type || 'paint';
            const pri = w.priority || 'medium';
            const meta = [w.brand, w.faction, w.system].filter(Boolean).join(' · ');
            const urlHtml = w.url ? `<div><a href="${esc(w.url)}" target="_blank" rel="noopener" class="wl-card-url">&#128279; Link</a></div>` : '';
            const noteHtml = w.notes ? `<div class="wl-card-notes">${esc(w.notes)}</div>` : '';
            const metaHtml = meta ? `<div class="wl-card-meta">${esc(meta)}</div>` : '';
            const addHtml = w.added ? `<div class="wl-card-timestamp">${esc(w.added)}</div>` : '';
            const orderedHtml = w.ordered_date ? `<div><span class="wish-ordered-badge">In Transit &middot; ${esc(w.ordered_date)}</span></div>` : '';
            return `<div class="wishlist-card wtype-${esc(t)}" data-id="${esc(w.id||'')}"><div class="wish-spine">${esc(WTYPE_LABEL[t]||t)}</div><div class="wish-body"><div class="wl-badge-row"><span class="wpri-badge wpri-${esc(pri)}">${esc(pri.charAt(0).toUpperCase()+pri.slice(1))}</span>${stockDot(w)}</div>${orderedHtml}<div class="wl-card-name">${esc(w.name||'')}</div>${metaHtml}${noteHtml}${urlHtml}${addHtml}</div></div>`;
          }

          function copyWishlist() {
            const typeOrder = ['paint', 'model', 'brush', 'codex', 'wd'];
            const groups = {};
            WISHLIST_DATA.forEach(w => {
              const t = w.type || 'paint';
              if (!groups[t]) groups[t] = [];
              groups[t].push(w);
            });
            const lines = [];
            typeOrder.forEach(t => {
              if (!groups[t]) return;
              lines.push('=== ' + (WTYPE_LABEL[t] || t).toUpperCase() + 'S ===');
              groups[t].forEach(w => {
                let ln = (w.brand ? w.brand + ' - ' : '') + w.name;
                if (w.faction) ln += ' (' + w.faction + ')';
                if (w.notes) ln += ' - ' + w.notes;
                if (w.ordered_date) ln += ' (Ordered ' + w.ordered_date + ')';
                lines.push('□ ' + ln);
              });
              lines.push('');
            });
            const text = 'Hobby Wishlist\n\n' + lines.join('\n');
            const btn = document.getElementById('wishlist-copy-btn');
            const flash = () => {
              if (btn) {
                btn.textContent = 'Copied!';
                setTimeout(() => btn.textContent = 'Copy', 2000);
              }
            };
            if (navigator.clipboard) {
              navigator.clipboard.writeText(text).then(flash).catch(() => _legacyCopy(text, flash));
            } else {
              _legacyCopy(text, flash);
            }
          }

          function _legacyCopy(text, cb) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
              document.execCommand('copy');
              if (cb) cb();
            } catch (e) {}
            document.body.removeChild(ta);
          }

          function renderWishlist() {
            const q = (searchEl.value || '').trim().toLowerCase();
            let list = WISHLIST_DATA.slice();
            if (typeFilter !== 'all') list = list.filter(w => (w.type || 'paint') === typeFilter);
            if (priFilter !== 'all') list = list.filter(w => (w.priority || 'medium') === priFilter);
            if (wStatusFilter === 'ordered') list = list.filter(w => !!w.ordered_date);
            if (q) list = list.filter(w => [w.name, w.brand, w.faction, w.system, w.notes, w.type, w.ordered_date].filter(Boolean).join(' ').toLowerCase().includes(q));
            const total = WISHLIST_DATA.length;
            countEl.textContent = list.length === total ? total + ' item' + (total !== 1 ? 's' : '') : list.length + ' of ' + total + ' items';
            if (list.length === 0) {
              gridEl.innerHTML = '';
              emptyEl.style.display = 'block';
              return;
            }
            emptyEl.style.display = 'none';
            gridEl.innerHTML = list.map(cardHtml).join('');
          }

          window._renderWishlist = renderWishlist;
          renderWishlist();
        })();

        (function() {
          const gridEl = document.getElementById('shame-grid');
          const emptyEl = document.getElementById('shame-empty');
          const summaryEl = document.getElementById('shame-summary');
          const searchEl = document.getElementById('shame-search');
          const moreEl = document.getElementById('shame-more');
          if (!gridEl) return;

          const STATUS_LABEL = {
            sealed: 'Sealed',
            opened: 'Opened',
            partial: 'Partial'
          };

          let filterState = 'active';
          let showAllShame = false;

          document.getElementById('shame-filter-pills').addEventListener('click', ev => {
            const fp = ev.target.closest('.shame-fp');
            if (!fp) return;
            filterState = fp.dataset.filter;
            showAllShame = false;
            document.querySelectorAll('.shame-fp').forEach(b => {
              b.classList.toggle('active', b.dataset.filter === filterState);
            });
            renderShame();
          });

          function sittingSince(acq) {
            if (!acq) return '';
            const [y, m] = acq.split('-').map(Number);
            const now = new Date();
            const months = (now.getFullYear() - y) * 12 + (now.getMonth() + 1 - m);
            if (months < 1) return 'just acquired';
            if (months < 12) return months + ' month' + (months !== 1 ? 's' : '');
            const yrs = Math.floor(months / 12),
              rem = months % 12;
            return yrs + ' yr' + (yrs !== 1 ? 's' : '') + (rem ? ' ' + rem + ' mo' : '');
          }

          function esc(s) {
            const d = document.createElement('div');
            d.textContent = String(s);
            return d.innerHTML;
          }

          function renderShame() {
            const q = (searchEl.value || '').trim().toLowerCase();
            let list = SHAME_DATA.slice();
            if (filterState === 'active') list = list.filter(s => !s.promoted_to);
            if (filterState === 'promoted') list = list.filter(s => s.promoted_to);
            if (q) list = list.filter(s => [s.name, s.faction, s.system, s.notes, s.acquired, s.status].filter(Boolean).join(' ').toLowerCase().includes(q));

            // Slim summary line in controls bar
            const active = SHAME_DATA.filter(s => !s.promoted_to);
            const totalModels = active.reduce((n, s) => n + (parseInt(s.count) || 1), 0);
            const oldest = active.filter(s => s.acquired).sort((a, b) => a.acquired.localeCompare(b.acquired))[0];
            const oldestAge = oldest ? sittingSince(oldest.acquired) : null;
            summaryEl.textContent = active.length + ' boxes · ~' + totalModels + ' models' + (oldestAge ? ' · longest wait ' + oldestAge : '');

            emptyEl.style.display = 'none';
            moreEl.innerHTML = '';
            if (!list.length) {
              gridEl.innerHTML = '';
              emptyEl.style.display = 'block';
              return;
            }

            // Pin newest active entry first when in active/all filter and not searching
            const newestId = filterState !== 'promoted' && !q ?
              active.reduce((a, b) => a.id > b.id ? a : b, active[0] || {}).id :
              null;
            if (newestId) {
              const idx = list.findIndex(s => s.id === newestId);
              if (idx > 0) {
                const [n] = list.splice(idx, 1);
                list.unshift(n);
              }
            }

            const PAGE = 12;
            const limited = (!showAllShame && !q && list.length > PAGE) ? list.slice(0, PAGE) : list;

            const SHAME_SYS_SLUG = {
              '40k': '40k',
              '30k / HH': '30k',
              'AoS': 'AoS',
              'Old World': 'OldWorld',
              'Epic': 'Epic',
              'Blood Bowl': 'BB',
              'Necromunda': 'Necromunda',
              'Kill Team': 'KT',
              'OPR': 'OPR',
              'Other': 'Other'
            };
            const SHAME_SYS_SHORT = {
              '40k': '40k',
              '30k / HH': '30k',
              'AoS': 'AoS',
              'Old World': 'OW',
              'Epic': 'Epic',
              'Blood Bowl': 'BB',
              'Necromunda': 'Necro',
              'Kill Team': 'KT',
              'OPR': 'OPR',
              'Other': 'Other'
            };

            function cardHtml(s, isNewest) {
              const sysSlug = SHAME_SYS_SLUG[s.system] || (s.system || 'Other').replace(/[\s\/]+/g, '');
              const stClass = s.status || 'sealed';
              const sysLabel = `<div class="shame-sys-label shame-sys-${sysSlug}">${esc(SHAME_SYS_SHORT[s.system] || s.system || 'Other')}</div>`;
              const stBadge = `<span class="shame-badge shame-st-${stClass}">${STATUS_LABEL[s.status] || 'Sealed'}</span>`;
              const promoted = s.promoted_to ? `<span class="shame-promoted">Promoted &rarr; ${s.promoted_to === 'planned' ? 'Planned' : 'Bench'}</span>` : '';
              const metaParts = [s.faction ? esc(s.faction) : '', s.count ? esc(s.count) + ' models' : ''].filter(Boolean).join(' &middot; ');
              const sitting = sittingSince(s.acquired);
              const sittingHtml = sitting ? `<div class="shame-card-sitting">${sitting === 'just acquired' ? 'just acquired' : 'sitting for ' + sitting}</div>` : '';
              const acqHtml = s.acquired ? `<span class="shame-acquired">${esc(s.acquired)}</span>` : '';
              const notesHtml = s.notes ? `<div class="shame-notes">${esc(s.notes)}</div>` : '';
              const newestLabel = isNewest ? `<div class="shame-card-newest-label">&#9650; Just added</div>` : '';
              const headerRow = promoted ? `<div class="promoted-badge-row">${promoted}</div>` : '';
              return `<div class="shame-card shame-st-${stClass}${isNewest ? ' shame-card-newest' : ''}" data-id="${esc(s.id)}">${sysLabel}${newestLabel}${headerRow}<div class="shame-card-name">${esc(s.name)}</div>${sittingHtml}<div class="shame-card-meta">${stBadge}${metaParts ? '<span>' + metaParts + '</span>' : ''}${acqHtml}</div>${notesHtml}</div>`;
            }

            gridEl.innerHTML = limited.map(s => cardHtml(s, s.id === newestId)).join('');

            if (!showAllShame && !q && list.length > PAGE) {
              const remaining = list.length - PAGE;
              moreEl.innerHTML = `<div class="gallery-more-fade"></div><button class="gallery-more-btn"><span class="gallery-more-count">Showing ${PAGE} of ${list.length} boxes</span><span class="gallery-more-label">Reveal the remaining ${remaining} <span class="gallery-more-chevron">&#9662;</span></span></button>`;
            } else {
              moreEl.innerHTML = '';
            }
          }

          moreEl.addEventListener('click', e => {
            if (e.target.closest('.gallery-more-btn')) {
              showAllShame = true;
              renderShame();
            }
          });
          searchEl.addEventListener('input', () => {
            showAllShame = false;
            renderShame();
          });
          window._renderShame = renderShame;
        })();

        (function() {
          if (typeof RESCUE_DATA === 'undefined' || RESCUE_DATA === null) return;
          const gridEl = document.getElementById('rescues-grid');
          const emptyEl = document.getElementById('rescues-empty');
          const summaryEl = document.getElementById('rescues-summary');
          const searchEl = document.getElementById('rescues-search');
          if (!gridEl) return;

          const RESCUE_STAGES = ['bidding', 'in_transit', 'received', 'stripping', 'prepped'];
          const STAGE_LABEL = { bidding: 'Bidding', in_transit: 'In Transit', received: 'Received', stripping: 'Stripping', prepped: 'Prepped' };
          const COND_LABEL = { bare: 'Bare', primed_only: 'Primed Only', light: 'Light Strip', medium: 'Medium Strip', heavy: 'Heavy Strip' };
          const SHAME_SYS_SLUG = { '40k': '40k', '30k / HH': '30k', 'AoS': 'AoS', 'Old World': 'OldWorld', 'Epic': 'Epic', 'Blood Bowl': 'BB', 'Necromunda': 'Necromunda', 'Kill Team': 'KT', 'OPR': 'OPR', 'Other': 'Other' };
          const SHAME_SYS_SHORT = { '40k': '40k', '30k / HH': '30k', 'AoS': 'AoS', 'Old World': 'OW', 'Epic': 'Epic', 'Blood Bowl': 'BB', 'Necromunda': 'Necro', 'Kill Team': 'KT', 'OPR': 'OPR', 'Other': 'Other' };

          let filterState = 'active';

          function esc(s) { const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; }

          function sittingSince(acq) {
            if (!acq) return '';
            const [y, m] = acq.split('-').map(Number);
            const now = new Date();
            const months = (now.getFullYear() - y) * 12 + (now.getMonth() + 1 - m);
            if (months < 1) return 'just acquired';
            if (months < 12) return months + ' month' + (months !== 1 ? 's' : '');
            const yrs = Math.floor(months / 12), rem = months % 12;
            return yrs + ' yr' + (yrs !== 1 ? 's' : '') + (rem ? ' ' + rem + ' mo' : '');
          }

          function cardHtml(r) {
            const sysSlug = SHAME_SYS_SLUG[r.system] || (r.system || 'Other').replace(/[\s\/]+/g, '');
            const stageCls = 'rescue-st-' + (r.stage || 'received');
            const sysLabel = r.system ? `<div class="shame-sys-label shame-sys-${sysSlug}">${esc(SHAME_SYS_SHORT[r.system] || r.system)}</div>` : '';
            const stageBadge = `<span class="rescue-stage-badge rescue-stage-${r.stage || 'received'}">${esc(STAGE_LABEL[r.stage] || r.stage || 'Received')}</span>`;
            const condBadge = r.condition ? `<span class="rescue-cond-badge">${esc(COND_LABEL[r.condition] || r.condition)}</span>` : '';
            const srcBadge = r.source ? `<span class="rescue-source-badge">${esc(r.source)}</span>` : '';
            const sitting = sittingSince(r.acquired);
            const sittingHtml = sitting ? `<div class="rescue-card-sitting">${sitting === 'just acquired' ? 'just acquired' : 'sitting ' + sitting}</div>` : '';
            const countHtml = r.count > 1 ? `<span>${esc(r.count)} models</span>` : '';
            const factionHtml = r.faction ? `<span>${esc(r.faction)}</span>` : '';
            const acqHtml = r.acquired ? `<span>${esc(r.acquired)}</span>` : '';
            const promoted = r.promoted_to ? `<div class="promoted-badge-row"><span class="rescue-promoted-badge">Promoted &rarr; ${esc(r.promoted_to === 'bench' ? 'Bench' : 'Shame')}</span></div>` : '';
            const notesHtml = r.notes ? `<div class="rescue-card-notes">${esc(r.notes)}</div>` : '';
            const photos = (r.before_images || []).filter(Boolean);
            const photosHtml = photos.length ? `<div class="rescue-photos">${photos.map((p, i) => `<div class="rescue-photo" style="background-image:url('${esc(p)}')" data-lightbox-src="${esc(p)}" data-lightbox-all='${JSON.stringify(photos)}' data-lightbox-idx="${i}"></div>`).join('')}</div>` : '';
            return `<div class="rescue-card ${stageCls}" data-id="${esc(r.id)}">${sysLabel}${promoted}<div class="rescue-card-name">${esc(r.name)}</div>${sittingHtml}<div class="rescue-card-meta">${stageBadge}${condBadge}${srcBadge}${countHtml}${factionHtml}${acqHtml}</div>${photosHtml}${notesHtml}</div>`;
          }

          function renderRescues() {
            const q = (searchEl ? searchEl.value || '' : '').trim().toLowerCase();
            let list = RESCUE_DATA.slice();
            if (filterState === 'active') list = list.filter(r => !r.promoted_to);
            if (filterState === 'promoted') list = list.filter(r => r.promoted_to);
            if (q) list = list.filter(r => [r.name, r.faction, r.system, r.notes, r.acquired, r.source, r.condition, r.stage].filter(Boolean).join(' ').toLowerCase().includes(q));

            const active = RESCUE_DATA.filter(r => !r.promoted_to);
            const totalUnits = active.reduce((n, r) => n + (parseInt(r.count) || 1), 0);
            if (summaryEl) summaryEl.textContent = active.length + ' rescue' + (active.length !== 1 ? 's' : '') + ' · ~' + totalUnits + ' unit' + (totalUnits !== 1 ? 's' : '');

            emptyEl.style.display = 'none';
            if (!list.length) { gridEl.innerHTML = ''; emptyEl.style.display = 'block'; return; }
            gridEl.innerHTML = list.map(r => cardHtml(r)).join('');

            gridEl.querySelectorAll('.rescue-photo[data-lightbox-src]').forEach(el => {
              el.addEventListener('click', () => {
                try { const all = JSON.parse(el.dataset.lightboxAll || '[]'); const idx = parseInt(el.dataset.lightboxIdx) || 0; if (all.length) openLightbox(all, idx); } catch(e) {}
              });
            });
          }

          document.getElementById('rescues-filter-pills').addEventListener('click', ev => {
            const fp = ev.target.closest('.rsc-fp');
            if (!fp) return;
            filterState = fp.dataset.filter;
            document.querySelectorAll('.rsc-fp').forEach(b => b.classList.toggle('active', b.dataset.filter === filterState));
            renderRescues();
          });
          if (searchEl) searchEl.addEventListener('input', renderRescues);
          window._renderRescues = renderRescues;
          renderRescues();
        })();

        (function() {
          const STAGES = ['built', 'primed', 'basecoated', 'washed', 'highlighted', 'based', 'varnished', 'done'];
          const STAGE_LABEL = {
            built: 'Built',
            primed: 'Primed',
            basecoated: 'Basecoated',
            washed: 'Washed',
            highlighted: 'Highlighted',
            based: 'Based',
            varnished: 'Varnished',
            done: 'Done'
          };
          const BR_LOOKUP = new Map((BRUSHES_DATA || []).map(b => [b.id, b]));
          const BN_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

          function fmtBnDate(d) {
            if (!d) return '';
            const parts = d.split('-');
            if (parts.length < 2) return d;
            const m = BN_MONTHS[parseInt(parts[1], 10) - 1] || parts[1];
            return parts[2] ? m + ' ' + parseInt(parts[2], 10) + ', ' + parts[0] : m + ' ' + parts[0];
          }

          function daysAgoStr(d) {
            if (!d) return '';
            const days = Math.floor((Date.now() - new Date(d).getTime()) / 86400000);
            if (days <= 0) return 'today';
            if (days === 1) return 'yesterday';
            if (days < 7) return days + ' days ago';
            if (days < 30) {
              const w = Math.floor(days / 7);
              return w + ' wk' + (w !== 1 ? 's' : '') + ' ago';
            }
            const mo = Math.floor(days / 30);
            return mo + ' mo ago';
          }
          const grid = document.getElementById('bench-grid');
          const emptyEl = document.getElementById('bench-empty');
          const countEl = document.getElementById('bench-count');
          const searchEl = document.getElementById('bench-search');
          if (!BENCH_DATA) return;
          let stageFilter = 'all';

          function renderBench() {
            const TOTAL = BENCH_DATA.length;
            if (!TOTAL) {
              grid.innerHTML = '';
              emptyEl.style.display = 'block';
              countEl.textContent = '';
              return;
            }
            emptyEl.style.display = 'none';
            const q = searchEl.value.trim().toLowerCase();
            let filtered = BENCH_DATA.slice();
            if (stageFilter === 'active') filtered = filtered.filter(b => (b.stage || 'built') !== 'done');
            else if (stageFilter === 'done') filtered = filtered.filter(b => (b.stage || 'built') === 'done');
            if (benchSystemFilter) filtered = filtered.filter(b => (b.system || '') === benchSystemFilter);
            if (q) {
              filtered = filtered.filter(b =>
                (b.name || '').toLowerCase().includes(q) ||
                (b.faction || '').toLowerCase().includes(q) ||
                (b.notes || '').toLowerCase().includes(q)
              );
            }
            countEl.textContent = filtered.length + ' of ' + TOTAL + ' project' + (TOTAL !== 1 ? 's' : '');
            if (!filtered.length) {
              grid.innerHTML = '<div class="grid-empty">No projects match.</div>';
              return;
            }
            grid.innerHTML = filtered.map(b => {
              const stage = b.stage || 'built';
              const stageIdx = STAGES.indexOf(stage);
              const progress = Math.round((stageIdx / (STAGES.length - 1)) * 100);
              const colors = collectAllPaints(b.colors, b.recipes);
              let missing = 0,
                low = 0;
              colors.forEach(c => {
                const uk = upgradeKey(c);
                const stock = paintStock.get(uk) || '';
                if (!paintOwned.has(uk) || stock === 'out') missing++;
                else if (stock === 'low') low++;
              });
              const pillsHtml = colors.slice(0, 12).map(c => {
                const [, name = c] = c.split('|');
                const uk = upgradeKey(c);
                const stock = paintStock.get(uk) || '';
                let cls = 'pcol-pill owned';
                if (!paintOwned.has(uk) || stock === 'out') cls = 'pcol-pill missing';
                else if (stock === 'low') cls = 'pcol-pill low';
                return `<span class="${cls}" title="${esc(c)}">${esc(name)}</span>`;
              }).join('');
              const overflow = colors.length > 12 ?
                `<span class="pcol-pill" style="background:#0a0a0a;border:1px solid #1a1a1a;color:#3a2a10">+${colors.length - 12} more</span>` :
                '';
              const statusParts = [];
              if (missing > 0) statusParts.push(`<span style="color:#c94040">${missing} missing</span>`);
              if (low > 0) statusParts.push(`<span style="color:#c97a20">${low} low</span>`);
              const brushList = (b.brushes || []).map(id => BR_LOOKUP.get(id)).filter(Boolean);
              const brushHtml = brushList.length ?
                `<div class="bench-brush-row">${brushList.map(br => {
                const lbl = [br.brand, br.series, br.size].filter(Boolean).join(' \u00b7 ');
                return `<span class="bench-brush-pill">${esc(lbl)}</span>`;
              }).join('')}</div>` :
                '';
              const imgs = b.wip_images || [];
              const imgsHtml = imgs.length ?
                `<div class="bench-wip-row">${imgs.map((p, i) => `<img src="${esc(p)}" loading="lazy" onclick="openLightbox(${esc(JSON.stringify(imgs))}, ${i})" alt="WIP">`).join('')}</div>` :
                '';
              const benchRecipeRefs = renderRecipeRefs(b.recipes);
              const nextStep = getBenchNextStep(b);
              return `<div class="bench-card stage-${esc(stage)}" data-id="${esc(b.id || '')}">
            <div class="bench-card-header">
            <div class="bench-card-name">${esc(b.name)}</div>
            ${b.faction ? `<div class="bench-card-faction">${esc(b.faction)}</div>` : ''}
            ${b.system ? `<span class="sys-game-badge sys-${sysSlug(b.system)}">${esc(b.system)}</span>` : ''}
            ${b.codex_source ? `<span class="codex-source-badge">${esc(b.codex_source)}</span>` : ''}${benchRecipeRefs}
            </div>
            <div class="bench-card-body">
            <div class="bench-stage-row">
              <span class="bench-stage-label stage-${esc(stage)}">${esc(STAGE_LABEL[stage] || stage)}</span>
              ${b.last_touched ? `<span class="bench-touched">touched ${esc(daysAgoStr(b.last_touched))}</span>` : ''}
            </div>
            ${nextStep ? `<div class="bench-next-step">Next: ${esc(nextStep)}</div>` : ''}
            <div class="bench-progress"><div class="bench-progress-fill" style="width:${progress}%"></div></div>
            ${imgsHtml}
            ${b.notes ? `<div class="bench-card-notes">${esc(b.notes).replace(/\r?\n/g, '<br>')}</div>` : ''}
            ${colors.length ? `<div class="bench-colors">${pillsHtml}${overflow}</div>` : ''}
            ${brushHtml}
            <div class="card-footer">
              <span class="planned-card-summary">${colors.length} paint${colors.length !== 1 ? 's' : ''}${statusParts.length ? ' - ' + statusParts.join(', ') : ''}</span>
              <button class="warpaint-btn" onclick="openBenchWarpaint('${esc(b.id || '')}')">&#9876; Warpaint</button>
              ${colors.length ? `<button class="pull-btn planned-pull-btn" onclick="openBenchPull('${esc(b.id || '')}')">Pull list${missing > 0 ? ` <span class="pull-issue-badge">${missing} issue${missing !== 1 ? 's' : ''}</span>` : ''}</button>` : ''}
            </div>
            ${(b.history && b.history.length) ? `<button class="bench-hist-toggle" onclick="const n=this.nextElementSibling;const o=n.classList.toggle('bench-hist-open');this.textContent=o?'Timeline ↑':'Timeline ↓'">Timeline ↓</button><div class="bench-hist-list">${[...b.history].reverse().map(h=>`<div class="bench-hist-row"><span>${esc(STAGE_LABEL[h.from]||h.from)}</span><span class="bench-hist-arrow">→</span><span>${esc(STAGE_LABEL[h.to]||h.to)}</span><span class="bench-hist-when">${esc(daysAgoStr(h.date)||h.date)}</span></div>`).join('')}</div>` : ''}
            ${(b.sessions && b.sessions.length) ? `<button class="bench-sess-toggle" onclick="const n=this.nextElementSibling;const o=n.classList.toggle('bench-sess-open');this.textContent=o?'Sessions ↑ (${b.sessions.length})':'Sessions ↓ (${b.sessions.length})'">Sessions ↓ (${b.sessions.length})</button><div class="bench-sess-list">${b.sessions.map(s=>`<div class="bench-sess-row"><span class="bench-sess-date">${esc(s.date)}</span>${s.duration?`<span class="bench-sess-dur">${s.duration>=60?Math.floor(s.duration/60)+'h'+(s.duration%60?' '+s.duration%60+'m':''):s.duration+'m'}</span>`:''} ${s.note?`<span class="bench-sess-note">${esc(s.note)}</span>`:''}</div>`).join('')}</div>` : ''}
            </div>
          </div>`;
            }).join('');
          }

          window.openBenchPull = function openBenchPull(id) {
            const b = BENCH_DATA.find(x => x.id === id);
            if (!b) return;
            const STAGES_LIST = ['built', 'primed', 'basecoated', 'washed', 'highlighted', 'based', 'varnished', 'done'];
            const stage = b.stage || 'built';
            const nextIdx = STAGES_LIST.indexOf(stage) + 1;
            const nextStage = nextIdx < STAGES_LIST.length ? STAGES_LIST[nextIdx] : null;
            const subtitle = [b.faction, 'Stage: ' + (STAGE_LABEL[stage] || stage) + (nextStage ? ' - Next: ' + (STAGE_LABEL[nextStage] || nextStage) : '')].filter(Boolean).join(' — ');
            populatePullSheet(b.name, subtitle, b.colors || [], b.recipes);
          }

          function getBenchNextStep(b) {
            if (!b.recipes || !b.recipes.length || !window._RECIPE_BY_ID) return '';
            const recipe = window._RECIPE_BY_ID.get(b.recipes[0]);
            if (!recipe || !(recipe.steps || []).length) return '';
            const step = recipe.steps[0];
            const parts = (step.paint || '').split('|');
            const paintName = parts[1] || step.paint || '';
            const technique = step.technique || '';
            return (technique ? technique + ': ' : '') + paintName;
          }

          document.querySelectorAll('.bench-filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
              document.querySelectorAll('.bench-filter-pill').forEach(p => p.classList.remove('active'));
              pill.classList.add('active');
              stageFilter = pill.dataset.stage;
              renderBench();
            });
          });
          searchEl.addEventListener('input', renderBench);
          window._renderBench = renderBench;
          renderBench();
        })();

        (function() {
          if (!RECIPES_DATA) return;
          const RECIPE_BY_ID = new Map(RECIPES_DATA.map(r => [r.id, r]));
          window._RECIPE_BY_ID = RECIPE_BY_ID; // used by card badges

          // Backfill paintUsage: recipe step paints count toward scheme usage when the scheme references that recipe
          MODELS.forEach(m => {
            if (!m.recipes || !m.recipes.length) return;
            const mc = Math.max(1, parseInt(m.count || 1, 10));
            const seen = new Set((m.colors || []).map(c => upgradeKey(c)));
            for (const rid of m.recipes) {
              const r = RECIPE_BY_ID.get(rid);
              if (!r) continue;
              for (const step of (r.steps || [])) {
                if (step.paint) {
                  const k = upgradeKey(step.paint);
                  if (!seen.has(k)) {
                    seen.add(k);
                    paintUsage.set(k, (paintUsage.get(k) || 0) + mc);
                  }
                }
                if (step.mix_paint) {
                  const mk = upgradeKey(step.mix_paint);
                  if (!seen.has(mk)) {
                    seen.add(mk);
                    paintUsage.set(mk, (paintUsage.get(mk) || 0) + mc);
                  }
                }
              }
            }
          });

          // Build "used in" index: recipeId -> [{kind, name, id}]
          const USED_IN = new Map();
          const tally = (arr, kind) => arr.forEach(e => {
            (e.recipes || []).forEach(rid => {
              if (!USED_IN.has(rid)) USED_IN.set(rid, []);
              USED_IN.get(rid).push({
                kind,
                name: e.name,
                id: e.id
              });
            });
          });
          tally(MODELS, 'gallery');
          tally(PLANNED, 'planned');
          if (BENCH_DATA) tally(BENCH_DATA, 'bench');

          const TECH_LABEL = {
            basecoat: 'Basecoat',
            wash: 'Wash',
            shade: 'Shade',
            layer: 'Layer',
            edge: 'Edge',
            highlight: 'Highlight',
            glaze: 'Glaze',
            drybrush: 'Drybrush',
            stipple: 'Stipple',
            blend: 'Blend',
            special: 'Special'
          };

          function paintStatusCls(paintKey) {
            const uk = upgradeKey(paintKey);
            const stock = paintStock.get(uk) || '';
            if (!paintOwned.has(uk) || stock === 'out') return 'missing';
            if (stock === 'low') return 'low';
            return 'owned';
          }

          function paintHex(paintKey) {
            const uk = upgradeKey(paintKey);
            for (const p of PAINTS) {
              const k = (p.brand + '|' + p.name + '|' + (p.layer || '')).toLowerCase();
              if (k === uk) return p.hex || '';
            }
            return '';
          }

          function renderSteps(steps) {
            return '<ol class="recipe-steps-list">' + steps.map((s, i) => {
              const parts = (s.paint || '').split('|');
              const brand = parts[0] || '';
              const name = parts[1] || s.paint || '';
              const layer = parts[2] || '';
              const statusCls = paintStatusCls(s.paint || '');
              const hex = paintHex(s.paint || '');
              const swatch = hex ? `<span class="recipe-step-swatch" style="background:${esc(hex)}"></span>` : '';
              let mixHtml = '';
              if (s.mix_paint) {
                const mp = s.mix_paint.split('|');
                const mName = mp[1] || s.mix_paint;
                const mStatusCls = paintStatusCls(s.mix_paint);
                const mHex = paintHex(s.mix_paint);
                const mSwatch = mHex ? `<span class="recipe-step-swatch" style="background:${esc(mHex)}"></span>` : '';
                mixHtml = ` <span class="rc-mix-sep">+</span> <span class="recipe-step-paint ${mStatusCls}">${mSwatch}${esc(mName)}</span>`;
              }
              const meta = [];
              if (s.ratio) meta.push(`<span class="rc-ratio">${esc(s.ratio)}</span>`);
              if (s.note) meta.push(esc(s.note));
              if (s.brush) {
                const b = BRUSHES_DATA ? BRUSHES_DATA.find(x => x.id === s.brush) : null;
                if (b) {
                  const bl = [b.brand, b.series, b.size].filter(Boolean).join(' \u00b7 ');
                  meta.push(`<span class="rc-brush">${esc(bl)}</span>`);
                }
              }
              const tech = s.technique || 'special';
              const nearHint = statusCls === 'missing' ? nearestHintHtml(s.paint || '') : '';
              return `<li class="recipe-step-row">
            <span class="recipe-step-num">${i + 1}.</span>
            <div class="recipe-step-body">
              <div class="recipe-step-line">
                <span class="recipe-step-tech recipe-tech-${esc(tech)}">${esc(TECH_LABEL[tech] || tech)}</span>
                <span class="recipe-step-paint ${statusCls}">${swatch}${esc(name)}${brand ? ` <span style="color:#4a3a1a;font-size:9px">${esc(brand)}${layer ? ' · ' + esc(layer) : ''}</span>` : ''}</span>${mixHtml}
              </div>
              ${meta.length ? `<div class="recipe-step-meta">${meta.join(' &middot; ')}</div>` : ''}${nearHint}
            </div>
          </li>`;
            }).join('') + '</ol>';
          }

          function renderUsedIn(rid) {
            const uses = USED_IN.get(rid) || [];
            if (!uses.length) return '';
            const links = uses.slice(0, 8).map(u => {
              const label = esc(u.name);
              if (u.kind === 'gallery') return `<a href="#" onclick="_jumpToScheme('${esc(u.id)}');return false;">${label}</a>`;
              return `<span style="color:#6a5a30">${label}</span>`;
            }).join(', ');
            const more = uses.length > 8 ? ` +${uses.length - 8}` : '';
            return `<div class="recipe-used-in">Used in: ${links}${more}</div>`;
          }

          const searchEl = document.getElementById('recipes-search');
          const countEl = document.getElementById('recipes-count');
          const grid = document.getElementById('recipes-grid');
          const emptyEl = document.getElementById('recipes-empty');
          const pillsEl = document.getElementById('recipes-filter-pills');
          let catFilter = 'all';
          let orphanFilter = false;

          // Build category pills dynamically
          const cats = [...new Set(RECIPES_DATA.map(r => r.category).filter(Boolean))].sort();
          cats.forEach(cat => {
            const btn = document.createElement('button');
            btn.className = 'recipes-filter-pill';
            btn.dataset.cat = cat;
            btn.textContent = cat;
            // Insert before the Unused pill (last child)
            pillsEl.insertBefore(btn, pillsEl.lastElementChild);
          });
          pillsEl.addEventListener('click', e => {
            const btn = e.target.closest('.recipes-filter-pill');
            if (!btn) return;
            pillsEl.querySelectorAll('.recipes-filter-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            catFilter = btn.dataset.cat;
            orphanFilter = catFilter === '__orphan__';
            renderRecipes();
          });

          function renderRecipes() {
            const TOTAL = RECIPES_DATA.length;
            if (!TOTAL) {
              grid.innerHTML = '';
              emptyEl.style.display = 'block';
              countEl.textContent = '';
              return;
            }
            emptyEl.style.display = 'none';
            const q = searchEl.value.trim().toLowerCase();
            let list = RECIPES_DATA.slice();
            if (orphanFilter) list = list.filter(r => !USED_IN.has(r.id));
            else if (catFilter !== 'all') list = list.filter(r => (r.category || '') === catFilter);
            if (q) {
              list = list.filter(r => {
                if ((r.name || '').toLowerCase().includes(q)) return true;
                if ((r.description || '').toLowerCase().includes(q)) return true;
                if ((r.faction || '').toLowerCase().includes(q)) return true;
                if ((r.notes || '').toLowerCase().includes(q)) return true;
                return (r.steps || []).some(s =>
                  (s.paint || '').toLowerCase().includes(q) ||
                  (s.note || '').toLowerCase().includes(q) ||
                  (s.technique || '').toLowerCase().includes(q)
                );
              });
            }
            countEl.textContent = orphanFilter ?
              list.length + ' unused recipe' + (list.length !== 1 ? 's' : '') :
              list.length + ' of ' + TOTAL + ' recipe' + (TOTAL !== 1 ? 's' : '');
            if (!list.length) {
              grid.innerHTML = '<div class="grid-empty">No recipes match.</div>';
              return;
            }
            grid.innerHTML = list.map(r => `
          <div class="recipe-card" id="recipe-${esc(r.id)}">
            <button class="recipe-link-btn" title="Copy link" onclick="copyRecipeLink(event,'${esc(r.id)}')"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg></button>
            ${r.image ? `<img class="recipe-hero" src="${esc(r.image)}" alt="" onclick="openLightbox(['${esc(r.image)}'],0)">` : ''}
            <div class="recipe-card-header">
              <div class="recipe-card-header-left"><span class="recipe-card-name">${esc(r.name)}</span>${r.category ? `<span class="recipe-cat-badge">${esc(r.category)}</span>` : ''}${r.faction ? `<span class="recipe-faction-badge">${esc(r.faction)}</span>` : ''}</div>
            </div>
            <div class="recipe-card-body">
            ${r.description ? `<div class="recipe-card-desc">${esc(r.description)}</div>` : ''}
            ${(r.steps && r.steps.length) ? renderSteps(r.steps) : '<div style="color:#3a2a10;font-style:italic;font-size:11px;padding:6px 0">No steps defined yet.</div>'}
            ${r.notes ? `<div class="recipe-notes">${esc(r.notes)}</div>` : ''}
            ${renderUsedIn(r.id)}
            ${(r.steps && r.steps.length) ? `<div class="recipe-card-footer"><button class="recipe-guide-btn" onclick="openRecipeGuide('${esc(r.id)}')">&#9654; Guide</button></div>` : ''}
            </div>
          </div>
        `).join('');
          }

          searchEl.addEventListener('input', renderRecipes);
          window._renderRecipes = renderRecipes;
          window._jumpToRecipe = function(rid) {
            activateTab('recipes');
            renderRecipes();
            const el = document.getElementById('recipe-' + rid);
            if (el) {
              el.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });
              el.classList.remove('highlight');
              void el.offsetWidth;
              el.classList.add('highlight');
            }
          };
          window._jumpToScheme = function(mid) {
            activateTab('gallery');
            if (typeof showAllGallery !== 'undefined') {
              showAllGallery = true;
              if (typeof renderGallery === 'function') renderGallery();
            }
            setTimeout(() => {
              const el = document.querySelector('.model-card[data-id="' + mid + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 120);
          };

          renderRecipes();

          (function() {
            let _gr = null,
              _gi = 0;

            function renderGuideStep() {
              const steps = _gr.steps || [];
              const s = steps[_gi];
              const total = steps.length;
              document.getElementById('recipe-guide-title').textContent = _gr.name;
              document.getElementById('recipe-guide-counter').textContent = 'Step ' + (_gi + 1) + ' of ' + total;

              const tech = s.technique || 'special';
              const parts = (s.paint || '').split('|');
              const paintName = parts[1] || s.paint || '';
              const brand = parts[0] || '';
              const layer = parts[2] || '';
              const hex = paintHex(s.paint || '');
              const statusCls = paintStatusCls(s.paint || '');

              let html = `<div class="recipe-guide-tech recipe-tech-${esc(tech)}">${esc(TECH_LABEL[tech] || tech)}</div>`;
              html += `<div class="recipe-guide-paint-row">${hex ? `<span class="recipe-guide-swatch" style="background:${esc(hex)}"></span>` : ''}<span class="recipe-guide-paint-name ${statusCls}">${esc(paintName)}</span></div>`;
              if (brand) html += `<div class="recipe-guide-brand">${esc(brand)}${layer ? ' · ' + esc(layer) : ''}</div>`;
              const meta = [];
              if (s.ratio) meta.push(esc(s.ratio));
              if (s.note) meta.push(esc(s.note));
              if (meta.length) html += `<div class="recipe-guide-meta">${meta.join(' · ')}</div>`;
              if (s.brush && BRUSHES_DATA) {
                const b = BRUSHES_DATA.find(x => x.id === s.brush);
                if (b) html += `<div class="recipe-guide-brush">${esc([b.brand, b.series, b.size].filter(Boolean).join(' · '))}</div>`;
              }
              document.getElementById('recipe-guide-step-content').innerHTML = html;

              document.getElementById('recipe-guide-dots').innerHTML = steps.map((_, i) =>
                `<span class="recipe-guide-dot${i === _gi ? ' active' : i < _gi ? ' done' : ''}"></span>`
              ).join('');

              const prevBtn = document.getElementById('recipe-guide-prev');
              const nextBtn = document.getElementById('recipe-guide-next');
              prevBtn.disabled = _gi === 0;
              const isLast = _gi === total - 1;
              nextBtn.textContent = isLast ? '✓ Done' : '›';
              nextBtn.classList.toggle('done-btn', isLast);
            }

            window.openRecipeGuide = function(rid) {
              const r = RECIPES_DATA.find(x => x.id === rid);
              if (!r || !r.steps || !r.steps.length) return;
              _gr = r;
              _gi = 0;
              renderGuideStep();
              document.getElementById('recipe-guide-overlay').classList.add('open');
              document.body.style.overflow = 'hidden';
            };

            window.closeRecipeGuide = function() {
              document.getElementById('recipe-guide-overlay').classList.remove('open');
              document.body.style.overflow = '';
            };

            window.stepGuide = function(dir) {
              if (!_gr) return;
              const steps = _gr.steps || [];
              if (dir > 0 && _gi >= steps.length - 1) {
                closeRecipeGuide();
                return;
              }
              _gi = Math.max(0, Math.min(steps.length - 1, _gi + dir));
              renderGuideStep();
            };

            // Swipe support
            let _touchX = 0;
            const overlay = document.getElementById('recipe-guide-overlay');
            overlay.addEventListener('touchstart', e => {
              _touchX = e.touches[0].clientX;
            }, {
              passive: true
            });
            overlay.addEventListener('touchend', e => {
              const dx = e.changedTouches[0].clientX - _touchX;
              if (Math.abs(dx) > 50) stepGuide(dx < 0 ? 1 : -1);
            }, {
              passive: true
            });
            overlay.addEventListener('click', e => {
              if (e.target === overlay) closeRecipeGuide();
            });
          })();

        })(); // end recipes IIFE

      if (!RECIPES_DATA) {
        window._jumpToRecipe = function() {};
        window.openRecipeGuide = function() {};
        window.closeRecipeGuide = function() {};
        window.stepGuide = function() {};
      }

      (function() {
        const wrap = document.getElementById('factions-wrap');
        const searchEl = document.getElementById('factions-search');
        const countEl = document.getElementById('factions-count');
        const emptyEl = document.getElementById('factions-empty');
        if (!wrap || !searchEl) return;

        const hasRecipes = RECIPES_DATA !== null;
        const hasBench = BENCH_DATA !== null;

        // Build faction index: factionName -> { schemes, recipes, bench, planned, palette }
        const INDEX = new Map();

        function bucket(name) {
          const key = (name || '').trim();
          if (!key) return null;
          if (!INDEX.has(key)) INDEX.set(key, {
            name: key,
            schemes: [],
            recipes: [],
            bench: [],
            planned: [],
            palette: new Map()
          });
          return INDEX.get(key);
        }

        function addColorsToPalette(b, colors) {
          (colors || []).forEach(c => {
            const uk = upgradeKey(c);
            b.palette.set(uk, (b.palette.get(uk) || 0) + 1);
          });
        }

        MODELS.forEach(m => {
          const b = bucket(m.faction);
          if (!b) return;
          b.schemes.push(m);
          addColorsToPalette(b, effectiveColors(m));
        });
        PLANNED.forEach(p => {
          const b = bucket(p.faction);
          if (!b) return;
          b.planned.push(p);
          addColorsToPalette(b, p.colors);
        });
        if (hasBench) BENCH_DATA.forEach(x => {
          const b = bucket(x.faction);
          if (!b) return;
          b.bench.push(x);
          addColorsToPalette(b, x.colors);
        });
        if (hasRecipes) RECIPES_DATA.forEach(r => {
          const b = bucket(r.faction);
          if (!b) return;
          b.recipes.push(r);
          (r.steps || []).forEach(s => {
            if (s.paint) addColorsToPalette(b, [s.paint]);
          });
        });

        const FACTIONS = Array.from(INDEX.values()).sort((a, b) => a.name.localeCompare(b.name));

        // Paint lookup for palette pills (brand/name/layer + hex)
        const PAINT_BY_KEY = new Map(PAINTS.map(p => [p.brand + '|' + p.name + '|' + (p.layer || ''), p]));

        function paintStatusCls(uk) {
          if (!paintOwned.has(uk)) return 'missing';
          const s = paintStock.get(uk);
          if (s === 'low') return 'low';
          if (s === 'out') return 'out';
          if (s === 'wanted') return 'wanted';
          return 'owned';
        }

        function schemeThumb(m) {
          const img = (m.images || [])[0] || '';
          const imgContent = img ?
            `<img src="${esc(img)}" loading="lazy" alt="${esc(m.name)}">` :
            'NO PHOTO';
          return `<a class="faction-scheme-mini" onclick="_jumpToScheme('${esc(m.id)}')" title="${esc(m.name)}"><div class="faction-scheme-mini-img">${imgContent}</div><div class="faction-scheme-mini-name">${esc(m.name)}</div></a>`;
        }

        function recipeChip(r) {
          return `<span class="faction-chip" onclick="_jumpToRecipe('${esc(r.id)}')" title="${esc(r.description || '')}"><span class="faction-chip-kind kind-recipe">Doctrine</span>${esc(r.name)}</span>`;
        }

        function benchChip(b) {
          const stage = b.stage || 'built';
          return `<span class="faction-chip" data-bench-id="${esc(b.id)}" title="${esc(stage)}"><span class="faction-chip-kind kind-bench">${esc(stage)}</span>${esc(b.name)}</span>`;
        }

        function plannedChip(p) {
          return `<span class="faction-chip" data-planned-id="${esc(p.id)}"><span class="faction-chip-kind kind-planned">Pending</span>${esc(p.name)}</span>`;
        }

        function palettePill(uk, count) {
          const parts = uk.split('|');
          const name = parts[1] || uk;
          const brand = parts[0] || '';
          const paint = PAINT_BY_KEY.get(uk);
          const hex = paint && /^#[0-9a-fA-F]{6}$/.test(paint.hex || '') ? paint.hex : '#1a1408';
          const cls = paintStatusCls(uk);
          const countBadge = count > 1 ? `<span class="faction-palette-count">&times;${count}</span>` : '';
          return `<span class="faction-palette-pill" data-paint-name="${esc(name)}" title="${esc(brand)} ${esc(name)} - used in ${count} entr${count === 1 ? 'y' : 'ies'}"><span class="faction-palette-swatch" style="background:${esc(hex)}"></span><span class="faction-palette-dot ${cls}"></span>${esc(name)}${countBadge}</span>`;
        }

        function renderFaction(f) {
          const schemeCount = f.schemes.length;
          const recipeCount = f.recipes.length;
          const benchCount = f.bench.length;
          const plannedCount = f.planned.length;
          const modelCount = f.schemes.reduce((n, m) => n + Math.max(1, parseInt(m.count || 1, 10)), 0);

          const summaryParts = [];
          if (schemeCount) summaryParts.push(`<strong>${schemeCount}</strong> painted`);
          if (modelCount > schemeCount) summaryParts.push(`<strong>${modelCount}</strong> models`);
          if (benchCount) summaryParts.push(`<strong>${benchCount}</strong> in progress`);
          if (plannedCount) summaryParts.push(`<strong>${plannedCount}</strong> planned`);
          if (recipeCount) summaryParts.push(`<strong>${recipeCount}</strong> recipe${recipeCount === 1 ? '' : 's'}`);

          const schemesBlock = schemeCount ?
            `<div class="faction-section"><div class="faction-section-title">Field Record</div><div class="faction-scheme-grid">${f.schemes.map(schemeThumb).join('')}</div></div>` :
            '';

          const recipesBlock = recipeCount ?
            `<div class="faction-section"><div class="faction-section-title">Doctrine</div><div class="faction-chips">${f.recipes.map(recipeChip).join('')}</div></div>` :
            '';

          const flightChips = [...f.bench.map(benchChip), ...f.planned.map(plannedChip)].join('');
          const flightBlock = flightChips ?
            `<div class="faction-section"><div class="faction-section-title">Active Operations</div><div class="faction-chips">${flightChips}</div></div>` :
            '';

          let paletteBlock = '';
          if (f.palette.size) {
            const sorted = [...f.palette.entries()].sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0]));
            const pills = sorted.map(([uk, n]) => palettePill(uk, n)).join('');
            paletteBlock = `<div class="faction-section"><div class="faction-section-title">Materiel &middot; ${sorted.length}</div><div class="faction-palette">${pills}</div></div>`;
          }

          return `<div class="faction-card" id="faction-${esc(f.name.toLowerCase().replace(/[^a-z0-9]+/g, '-'))}"><div class="faction-header"><div class="faction-name">${esc(f.name)}</div><div class="faction-summary">${summaryParts.join(' &middot; ')}</div></div><div class="faction-body">${schemesBlock}${recipesBlock}${flightBlock}${paletteBlock}</div></div>`;
        }

        function render() {
          const q = searchEl.value.trim().toLowerCase();
          const filtered = q ? FACTIONS.filter(f => f.name.toLowerCase().includes(q)) : FACTIONS;
          if (!FACTIONS.length) {
            wrap.innerHTML = '';
            emptyEl.style.display = 'block';
            countEl.textContent = '';
            return;
          }
          emptyEl.style.display = 'none';
          countEl.textContent = filtered.length + ' of ' + FACTIONS.length + ' faction' + (FACTIONS.length === 1 ? '' : 's');
          wrap.innerHTML = filtered.length ? filtered.map(renderFaction).join('') : '<div style="padding:40px 16px;text-align:center;font-family:Georgia,serif;font-style:italic;color:#4a3a10">No files match.</div>';
        }

        // Delegation: paint palette pills → inventory filter, bench/planned chips → their tabs with pulse
        wrap.addEventListener('click', e => {
          const paintEl = e.target.closest('[data-paint-name]');
          if (paintEl) {
            switchToTab('inventory');
            const s = document.getElementById('search');
            if (s) {
              s.value = paintEl.dataset.paintName;
              s.dispatchEvent(new Event('input'));
            }
            return;
          }
          const b = e.target.closest('[data-bench-id]');
          if (b) {
            switchToTab('bench');
            setTimeout(() => {
              const el = document.querySelector('.bench-card[data-id="' + b.dataset.benchId + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 150);
            return;
          }
          const p = e.target.closest('[data-planned-id]');
          if (p) {
            switchToTab('planned');
            setTimeout(() => {
              const el = document.querySelector('.planned-card[data-id="' + p.dataset.plannedId + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 150);
          }
        });

        searchEl.addEventListener('input', render);
        window._renderFactions = render;
        render();
      })();

    function openShoppingList() {
      const mustBuy = {};
      const restock = {};

      PLANNED.forEach(pl => {
        (pl.colors || []).forEach(c => {
          const [brand, name = c] = c.split('|');
          const uk = upgradeKey(c);
          const stock = paintStock.get(uk) || '';
          if (!paintOwned.has(uk) || stock === 'out') {
            if (!mustBuy[brand]) mustBuy[brand] = {};
            if (!mustBuy[brand][name]) mustBuy[brand][name] = [];
            if (!mustBuy[brand][name].includes(pl.name)) mustBuy[brand][name].push(pl.name);
          } else if (stock === 'low') {
            if (!restock[brand]) restock[brand] = {};
            if (!restock[brand][name]) restock[brand][name] = [];
            if (!restock[brand][name].includes(pl.name)) restock[brand][name].push(pl.name);
          }
        });
      });

      const mustBrands = Object.keys(mustBuy).sort();
      const restockBrands = Object.keys(restock).sort();
      const totalMust = mustBrands.reduce((n, b) => n + Object.keys(mustBuy[b]).length, 0);
      const totalRestock = restockBrands.reduce((n, b) => n + Object.keys(restock[b]).length, 0);

      let html = '';
      if (totalMust === 0 && totalRestock === 0) {
        html = '<div class="shop-all-good">All set \u2014 no paints needed!</div>';
      } else {
        if (totalMust > 0) {
          html += `<div class="shop-section-heading shop-must">Must Buy \u2014 ${totalMust} paint${totalMust !== 1 ? 's' : ''}</div>`;
          for (const brand of mustBrands) {
            html += `<div class="shop-brand">${esc(brand)}</div><ul class="shop-paint-list">`;
            for (const [name, schemes] of Object.entries(mustBuy[brand]).sort()) {
              html += `<li><span class="shop-paint-name">${esc(name)}</span><span class="shop-schemes">${esc(schemes.join(', '))}</span></li>`;
            }
            html += '</ul>';
          }
        }
        if (totalRestock > 0) {
          html += `<div class="shop-section-heading shop-consider">Consider Restocking \u2014 ${totalRestock} paint${totalRestock !== 1 ? 's' : ''}</div>`;
          for (const brand of restockBrands) {
            html += `<div class="shop-brand">${esc(brand)}</div><ul class="shop-paint-list">`;
            for (const [name, schemes] of Object.entries(restock[brand]).sort()) {
              html += `<li><span class="shop-paint-name">${esc(name)}</span><span class="shop-schemes">${esc(schemes.join(', '))}</span></li>`;
            }
            html += '</ul>';
          }
        }
      }

      const schemeCount = PLANNED.filter(p => (p.colors || []).length > 0).length;
      document.getElementById('shop-subtitle').textContent = schemeCount + ' scheme' + (schemeCount !== 1 ? 's' : '');
      document.getElementById('shop-content').innerHTML = html;
      document.getElementById('shop-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeShoppingList() {
      document.getElementById('shop-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    function printShopList() {
      document.body.classList.add('print-shop');
      window.print();
      document.body.classList.remove('print-shop');
    }

    function copyShoppingList() {
      let text = 'Shopping List\n\n';
      document.getElementById('shop-content').querySelectorAll('.shop-section-heading, .shop-brand, .shop-paint-list li').forEach(el => {
        if (el.classList.contains('shop-section-heading')) text += '\n' + el.textContent.trim() + '\n';
        else if (el.classList.contains('shop-brand')) text += '\n' + el.textContent.trim() + '\n';
        else {
          const name = el.querySelector('.shop-paint-name')?.textContent || '';
          const schemes = el.querySelector('.shop-schemes')?.textContent || '';
          text += '  \u25a1 ' + name + (schemes ? '  \u2192 ' + schemes : '') + '\n';
        }
      });
      navigator.clipboard.writeText(text.trim()).then(() => {
        const btn = document.getElementById('shop-copy-btn');
        const prev = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = prev, 2000);
      });
    }

    document.getElementById('shop-overlay').addEventListener('click', e => {
      if (e.target === document.getElementById('shop-overlay')) closeShoppingList();
    });

    function openRestockList() {
      const out = {}, low = {}, wanted = {};
      PAINTS.forEach(p => {
        if (!p.stock) return;
        const bucket = p.stock === 'out' ? out : p.stock === 'low' ? low : p.stock === 'wanted' ? wanted : null;
        if (!bucket) return;
        if (!bucket[p.brand]) bucket[p.brand] = [];
        bucket[p.brand].push(p.name);
      });
      const section = (obj, label, cls) => {
        const brands = Object.keys(obj).sort();
        if (!brands.length) return '';
        const total = brands.reduce((n, b) => n + obj[b].length, 0);
        let h = `<div class="shop-section-heading ${cls}">${label} — ${total} paint${total !== 1 ? 's' : ''}</div>`;
        brands.forEach(b => {
          h += `<div class="shop-brand">${esc(b)}</div><ul class="shop-paint-list">`;
          obj[b].sort().forEach(name => { h += `<li><span class="shop-paint-name">${esc(name)}</span></li>`; });
          h += '</ul>';
        });
        return h;
      };
      let html = section(out, 'Out', 'shop-must') + section(low, 'Low', 'shop-consider') + section(wanted, 'Wanted', 'shop-wanted');
      if (!html) html = '<div class="shop-all-good">All stocked — nothing flagged!</div>';
      const total = Object.values(out).flat().length + Object.values(low).flat().length + Object.values(wanted).flat().length;
      document.getElementById('restock-subtitle').textContent = total + ' paint' + (total !== 1 ? 's' : '') + ' flagged';
      document.getElementById('restock-content').innerHTML = html;
      document.getElementById('restock-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeRestockList() {
      document.getElementById('restock-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    function printRestockList() { document.body.classList.add('print-shop'); window.print(); document.body.classList.remove('print-shop'); }

    function copyRestockList() {
      let text = 'Restock List\n\n';
      document.getElementById('restock-content').querySelectorAll('.shop-section-heading, .shop-brand, .shop-paint-list li').forEach(el => {
        if (el.classList.contains('shop-section-heading')) text += '\n' + el.textContent.trim() + '\n';
        else if (el.classList.contains('shop-brand')) text += '\n' + el.textContent.trim() + '\n';
        else text += '  □ ' + (el.querySelector('.shop-paint-name')?.textContent || '') + '\n';
      });
      navigator.clipboard.writeText(text.trim()).then(() => {
        const btn = document.getElementById('restock-copy-btn');
        const prev = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = prev, 2000);
      });
    }

    document.getElementById('restock-overlay').addEventListener('click', e => {
      if (e.target.id === 'restock-overlay') closeRestockList();
    });

    function drawerStarSet(n) {
      document.querySelectorAll('#notes-star-picker .nsp-star').forEach(s => {
        s.classList.toggle('on', parseInt(s.dataset.val) <= n);
      });
    }

    function openNotes(pid, brand, name, stars, notes) {
      document.getElementById('notes-paint-name').textContent = name;
      document.getElementById('notes-paint-brand').textContent = brand;
      const body = document.getElementById('notes-body');
      body.innerHTML = notes ?
        `<div class="notes-body">${esc(notes)}</div>` :
        `<div class="notes-empty">No notes yet - add them in admin.</div>`;
      drawerStarSet(parseInt(stars) || 0);
      document.getElementById('notes-overlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeNotes() {
      document.getElementById('notes-overlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    document.getElementById('notes-overlay').addEventListener('click', e => {
      if (e.target === document.getElementById('notes-overlay')) closeNotes();
    });

    const BH_FORCE_RECORD = new Map();
    (function() {
      if (!BATTLES_DATA) return;
      BATTLES_DATA.forEach(b => {
        if (!b.force_id) return;
        if (!BH_FORCE_RECORD.has(b.force_id)) BH_FORCE_RECORD.set(b.force_id, {w:0,l:0,d:0});
        const r = BH_FORCE_RECORD.get(b.force_id);
        if (b.result === 'win') r.w++; else if (b.result === 'loss') r.l++; else r.d++;
      });
    })();

        (function() {
          const grid = document.getElementById('forces-grid');
          const emptyEl = document.getElementById('forces-empty');
          const countEl = document.getElementById('forces-count');
          const searchEl = document.getElementById('forces-search');
          if (!FORCES_DATA) return;

          // Build a lookup from model ID → model object
          const MODEL_BY_ID = new Map(MODELS.map(m => [m.id, m]));

          function renderForces() {
            const q = searchEl.value.trim().toLowerCase();
            let list = FORCES_DATA.slice();
            if (q) {
              list = list.filter(f =>
                (f.name || '').toLowerCase().includes(q) ||
                (f.faction || '').toLowerCase().includes(q) ||
                (f.system || '').toLowerCase().includes(q) ||
                (f.notes || '').toLowerCase().includes(q)
              );
            }
            list.sort((a, b) => (b.pinned ? 1 : 0) - (a.pinned ? 1 : 0));

            countEl.textContent = q ?
              list.length + ' of ' + FORCES_DATA.length + ' force' + (FORCES_DATA.length !== 1 ? 's' : '') :
              FORCES_DATA.length + ' force' + (FORCES_DATA.length !== 1 ? 's' : '');

            if (!list.length) {
              grid.innerHTML = '';
              emptyEl.style.display = 'block';
              return;
            }
            emptyEl.style.display = 'none';

            grid.innerHTML = list.map(f => {
              const painted = (f.models || []).reduce((sum, mid) => {
                const m = MODEL_BY_ID.get(mid);
                return sum + Math.max(1, parseInt((m && m.count) || 1, 10));
              }, 0);
              const target = f.target_count || 0;
              const pct = target ? Math.min(100, Math.round(painted / target * 100)) : 0;
              const sysHtml = f.system ?
                `<span class="sys-game-badge sys-${sysSlug(f.system)}">${esc(f.system)}</span>` : '';
              const metaParts = [
                f.faction ? esc(f.faction) : '',
                painted + ' painted' + (target ? ' / ' + target + ' target' : ''),
                f.target_points ? f.target_points + ' pts' : '',
              ].filter(Boolean);

              // First image from any linked scheme, used as full-width hero
              let heroMid = null,
                heroImg = null;
              for (const mid of (f.models || [])) {
                const m = MODEL_BY_ID.get(mid);
                const img = m && (m.images || [])[0];
                if (img) {
                  heroMid = mid;
                  heroImg = img;
                  break;
                }
              }
              const heroHtml = heroImg ?
                `<img class="force-card-hero" src="${esc(heroImg)}" loading="lazy" alt="${esc(f.name)}" onclick="_jumpToScheme('${esc(heroMid)}')">` :
                '';

              const progressBar = target ?
                `<div class="force-progress"><div class="force-progress-fill" style="width:${pct}%"></div></div>` : '';

              const isPinned = !!f.pinned;
              const rec = BH_FORCE_RECORD.get(f.id);
              const recordHtml = rec ? `<span class="bh-force-record">${rec.w}W&nbsp;${rec.l}L&nbsp;${rec.d}D</span>` : '';
              const forceBodyContent = progressBar + (f.notes ? `<div class="force-card-notes">${esc(f.notes)}</div>` : '') + (f.roster_url ? `<a class="force-roster-link" href="${esc(f.roster_url)}" target="_blank" rel="noopener">View Roster ↗</a>` : '');
              return `<div class="force-card${isPinned ? ' force-card-pinned' : ''}" data-id="${esc(f.id)}">
            ${heroHtml}
            <div class="force-card-header">
              <div class="force-card-name"><span>${esc(f.name)}</span>${isPinned ? '<span class="force-pin-indicator" title="Pinned">★</span>' : ''}</div>
              <div class="force-card-meta">${sysHtml}${metaParts.map(p => `<span>${p}</span>`).join('')}${recordHtml}</div>
            </div>
            ${forceBodyContent ? `<div class="force-card-body">${forceBodyContent}</div>` : ''}
          </div>`;
            }).join('');
          }

          searchEl.addEventListener('input', renderForces);
          window._renderForces = renderForces;
          renderForces();
        })();

        (function() {
          if (!BATTLES_DATA) return;
          const grid    = document.getElementById('battles-grid');
          const emptyEl = document.getElementById('battles-empty');
          const countEl = document.getElementById('bh-count');
          const searchEl = document.getElementById('bh-search');
          let bhResultFilter = '', bhSearch = '';

          const BH_FORCE_NAMES = new Map();
          (function() {
            if (!FORCES_DATA) return;
            FORCES_DATA.forEach(f => BH_FORCE_NAMES.set(f.id, f.name));
          })();

          document.querySelectorAll('#bh-filter-pills .bh-fp').forEach(btn => {
            btn.addEventListener('click', function() {
              document.querySelectorAll('#bh-filter-pills .bh-fp').forEach(b => b.classList.remove('active'));
              this.classList.add('active');
              bhResultFilter = this.dataset.bhr;
              renderBattles();
            });
          });

          function renderBattles() {
            if (!BATTLES_DATA) { grid.innerHTML = ''; emptyEl.classList.remove('hidden'); return; }
            const q = bhSearch.toLowerCase();
            let list = BATTLES_DATA.filter(b => {
              if (bhResultFilter && (b.result || '') !== bhResultFilter) return false;
              if (!q) return true;
              return [b.date, b.my_army, b.opponent, b.opponent_army, b.mission, b.notes].some(f => f && f.toLowerCase().includes(q));
            });
            countEl.textContent = list.length + ' battle' + (list.length !== 1 ? 's' : '');
            if (!list.length) { grid.innerHTML = ''; emptyEl.classList.remove('hidden'); return; }
            emptyEl.classList.add('hidden');
            const resultLabel = {win:'Win',loss:'Loss',draw:'Draw'};
            grid.innerHTML = list.map(b => {
              const res = b.result || 'draw';
              const forceName = b.force_id ? BH_FORCE_NAMES.get(b.force_id) : null;
              const armyDisplay = b.my_army || forceName || '';
              const armyHtml = armyDisplay ? `<span class="bh-my-army">${esc(armyDisplay)}</span>` : '';
              const forceChip = forceName && b.my_army && b.force_id ? `<span class="bh-force-chip bh-force-chip-link" onclick="event.stopPropagation();switchToTab('forces');setTimeout(()=>{const el=document.querySelector('.force-card[data-id=\\'${esc(b.force_id)}\\']');if(el){el.scrollIntoView({behavior:'smooth',block:'start'});el.classList.add('highlight');setTimeout(()=>el.classList.remove('highlight'),1200);}},150)" title="Jump to ${esc(forceName)} in Forces &amp; Rosters">${esc(forceName)}</span>` : '';
              const oppHtml = (b.opponent || b.opponent_army) ?
                `<span class="bh-vs">vs</span><span class="bh-opp">${esc(b.opponent || '')}${b.opponent && b.opponent_army ? ' &mdash; ' : ''}${esc(b.opponent_army || '')}</span>` : '';
              const sysHtml = b.system ? `<span class="bh-sys-chip" style="background:${SYS_COLORS[b.system]||'#2a2a2a'}">${esc(b.system)}</span>` : '';
              const ptsHtml = b.points ? `<span class="bh-pts-chip">${esc(String(b.points))}pts</span>` : '';
              const missionHtml = b.mission ? `<div class="bh-mission">${esc(b.mission)}</div>` : '';
              const notesHtml = b.notes ? `<div class="bh-notes">${esc(b.notes)}</div>` : '';
              return `<div class="battle-card bh-result-border-${res}" data-id="${esc(b.id)}">
  <div class="bh-card-header">
    <span class="bh-date">${esc(b.date||'')}</span>
    <span class="bh-result-badge bh-result-${res}">${resultLabel[res]||res}</span>
    ${sysHtml}${ptsHtml}
  </div>
  <div class="bh-matchup">${armyHtml}${forceChip}${oppHtml}</div>
  ${missionHtml}${notesHtml}
</div>`;
            }).join('');
          }

          if (searchEl) searchEl.addEventListener('input', function() { bhSearch = this.value; renderBattles(); });
          window._renderBattles = renderBattles;
          renderBattles();
        })();

    const equivSearchEl = document.getElementById('equiv-search');
    const equivCountEl = document.getElementById('equiv-count');
    const equivTbody = document.getElementById('equiv-tbody');
    const equivEmptyEl = document.getElementById('equiv-empty');

    function equivStatus(brand, name) {
      const key = (brand + '|' + name).toLowerCase();
      if (!paintByKeyLC.has(key)) return 'missing';
      const s = paintByKeyLC.get(key);
      if (s === 'out') return 'out';
      if (s === 'low') return 'low';
      if (s === 'wanted') return 'wanted';
      return 'owned';
    }

    function equivDot(brand, name) {
      const st = equivStatus(brand, name);
      const labels = {
        owned: 'Owned',
        low: 'Low stock',
        out: 'Out of stock',
        wanted: 'Wanted',
        missing: 'Not in inventory'
      };
      return `<span class="eq-dot ${st}" title="${labels[st]}"></span>`;
    }

    function matchDot(quality) {
      if (!quality) return '';
      const q = quality.toLowerCase();
      if (q === 'near identical') return `<span class="eq-match-dot eq-match-near" title="Near identical"></span>`;
      if (q === 'avoid') return `<span class="eq-match-dot eq-match-avoid" title="Avoid"></span>`;
      if (q === 'usable') return `<span class="eq-match-dot eq-match-usable" title="Usable"></span>`;
      return '';
    }

    function renderEquiv() {
      const q = equivSearchEl.value.trim().toLowerCase();
      const rows = q ?
        CONVERSIONS_DATA.filter(r =>
          r.citadel.toLowerCase().includes(q) ||
          (r.vallejo && r.vallejo.toLowerCase().includes(q)) ||
          (r.proAcryl && r.proAcryl.toLowerCase().includes(q)) ||
          (r.ttc && r.ttc.toLowerCase().includes(q)) ||
          (r.valMatch && r.valMatch.toLowerCase().includes(q)) ||
          (r.paMatch && r.paMatch.toLowerCase().includes(q)) ||
          (r.ttcMatch && r.ttcMatch.toLowerCase().includes(q))
        ) :
        CONVERSIONS_DATA;

      equivCountEl.textContent = rows.length + ' of ' + CONVERSIONS_DATA.length + ' equivalencies';

      if (!rows.length) {
        equivTbody.innerHTML = '';
        equivEmptyEl.style.display = 'block';
        return;
      }
      equivEmptyEl.style.display = 'none';

      equivTbody.innerHTML = rows.map(r => {
        const citCell = `<td><div class="eq-cell">${equivDot('Citadel', r.citadel)}<span>${esc(r.citadel)}</span></div></td>`;
        const valCell = r.vallejo ?
          `<td><div class="eq-cell">${matchDot(r.valMatch)}${equivDot('Vallejo', r.vallejo)}<span>${esc(r.vallejo)}</span></div></td>` :
          `<td><span class="eq-nil">-</span></td>`;
        const paCell = r.proAcryl ?
          `<td><div class="eq-cell">${matchDot(r.paMatch)}${equivDot('Pro Acryl', r.proAcryl)}<span>${esc(r.proAcryl)}</span></div></td>` :
          `<td><span class="eq-nil">-</span></td>`;
        const ttcCell = r.ttc ?
          `<td><div class="eq-cell">${matchDot(r.ttcMatch)}${equivDot('Two Thin Coats', r.ttc)}<span>${esc(r.ttc)}</span></div></td>` :
          `<td><span class="eq-nil">-</span></td>`;
        return `<tr>${citCell}${valCell}${paCell}${ttcCell}</tr>`;
      }).join('');
    }

    equivSearchEl.addEventListener('input', renderEquiv);

    document.querySelectorAll('.equiv-bp').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.equiv-bp').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const panel = document.getElementById('tab-equiv');
        panel.classList.remove('compare-pa', 'compare-ttc');
        if (btn.dataset.compare === 'pa') panel.classList.add('compare-pa');
        if (btn.dataset.compare === 'ttc') panel.classList.add('compare-ttc');
      });
    });

    renderGallery();
    renderPlanned();
    renderEquiv();

    // Direct model links: ?model=<id>
    function copyModelLink(e, id) {
      e.stopPropagation();
      const url = location.origin + location.pathname + '?model=' + id;
      navigator.clipboard.writeText(url).then(() => {
        const btn = e.currentTarget;
        const prev = btn.title;
        btn.title = 'Copied!';
        setTimeout(() => btn.title = prev, 2000);
      });
    }

    function copyRecipeLink(e, id) {
      e.stopPropagation();
      const url = location.origin + location.pathname + '?recipe=' + id;
      navigator.clipboard.writeText(url).then(() => {
        const btn = e.currentTarget;
        const prev = btn.title;
        btn.title = 'Copied!';
        setTimeout(() => btn.title = prev, 2000);
      });
    }

    const urlTab = new URLSearchParams(location.search).get('tab');
    if (urlTab) switchToTab(urlTab);

    function copyTabLink(e, tabKey) {
      e.preventDefault();
      const el = e.currentTarget;
      const url = location.origin + location.pathname + '?tab=' + tabKey;
      navigator.clipboard.writeText(url).then(() => {
        const prev = el.textContent;
        el.textContent = '✓';
        setTimeout(() => {
          el.textContent = prev;
        }, 1500);
      });
    }

    const urlModel = new URLSearchParams(location.search).get('model');
    if (urlModel) {
      activateTab('gallery');
      showAllGallery = true;
      renderGallery();
      const card = document.querySelector(`.model-card[data-id="${urlModel}"]`);
      if (card) {
        card.classList.add('highlight');
        card.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }
    }

    const urlRecipe = new URLSearchParams(location.search).get('recipe');
    if (urlRecipe && typeof window._jumpToRecipe === 'function') {
      window._jumpToRecipe(urlRecipe);
    }

    // Track whichever tab is active on page load (covers default + direct links)
    (function() {
      const active = document.querySelector('.sidebar-nav [data-tab].active');
      if (active) fetch('index.php', { method: 'POST', body: new URLSearchParams({ action: 'track_tab', tab: active.dataset.tab }) });
    })();

    (function() {
      const trigger = document.getElementById('gs-trigger');
      const overlay = document.getElementById('gs-overlay');
      const input = document.getElementById('gs-input');
      const listEl = document.getElementById('gs-results');
      if (!trigger || !overlay || !input || !listEl) return;

      const hasRecipes = RECIPES_DATA !== null;
      const hasBench = BENCH_DATA !== null;
      const hasBrushes  = BRUSHES_DATA !== null;
      const hasSupplies = SUPPLIES_DATA !== null;
      const hasBooks = BOOKS_DATA !== null;
      const hasShame = SHAME_DATA !== null;
      const hasForces = FORCES_DATA !== null;
      const hasRescues = typeof RESCUE_DATA !== 'undefined' && RESCUE_DATA !== null;

      const WTYPE_LABEL_GS = {
        paint: 'Paint',
        model: 'Model',
        brush: 'Brush',
        codex: 'Codex'
      };
      const TYPE_LABEL = {
        paint: 'Paint',
        scheme: 'Scheme',
        recipe: 'Recipe',
        planned: 'Planned',
        bench: 'On Bench',
        brush: 'Brush',
        supply: 'Supply',
        book: 'Codex',
        shame: 'Shame Pile',
        rescue: 'Rescue',
        force: 'Force',
        battle: 'Battle',
        wish: 'Wishlist'
      };
      const TYPE_ORDER = ['scheme', 'recipe', 'paint', 'planned', 'shame', 'rescue', 'bench', 'force', 'battle', 'brush', 'supply', 'book', 'wish'];
      const PER_TYPE_CAP = 8;

      let selectedIdx = 0;
      let flatResults = [];

      function runSearch(q) {
        q = q.trim().toLowerCase();
        if (!q) return [];
        const out = [];
        const match = (hay) => hay && hay.toLowerCase().indexOf(q) !== -1;

        PAINTS.forEach(p => {
          const hay = [p.brand, p.name, p.hue, p.layer, p.color].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'paint',
            key: p.brand + '|' + p.name + '|' + (p.layer || ''),
            name: p.name,
            meta: [p.brand, p.layer].filter(Boolean).join(' · ')
          });
        });

        MODELS.forEach(m => {
          const hay = [m.name, m.faction, m.description, (m.colors || []).join(' ')].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'scheme',
            key: m.id,
            name: m.name,
            meta: [m.faction, m.date].filter(Boolean).join(' · ')
          });
        });

        PLANNED.forEach(pl => {
          const hay = [pl.name, pl.model, pl.faction, pl.description, (pl.colors || []).join(' ')].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'planned',
            key: pl.id,
            name: pl.name,
            meta: [pl.faction, pl.model].filter(Boolean).join(' · ')
          });
        });

        if (hasRecipes) RECIPES_DATA.forEach(r => {
          const stepHay = (r.steps || []).map(s => [s.paint, s.technique, s.note, s.ratio].filter(Boolean).join(' ')).join(' ');
          const hay = [r.name, r.category, r.faction, r.description, r.notes, stepHay].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'recipe',
            key: r.id,
            name: r.name,
            meta: [r.category, r.faction].filter(Boolean).join(' · ')
          });
        });

        if (hasBench) BENCH_DATA.forEach(b => {
          const hay = [b.name, b.faction, b.notes, (b.colors || []).join(' ')].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'bench',
            key: b.id,
            name: b.name,
            meta: [b.faction, b.stage].filter(Boolean).join(' · ')
          });
        });

        if (hasBrushes) BRUSHES_DATA.forEach(br => {
          const hay = [br.brand, br.series, br.size, br.material, br.use, br.notes].filter(Boolean).join(' ');
          if (match(hay)) {
            const nameParts = [br.brand, br.series].filter(Boolean).join(' ');
            out.push({
              type: 'brush',
              key: br.id,
              name: nameParts || br.brand,
              meta: [br.size, br.material, br.use].filter(Boolean).join(' · ')
            });
          }
        });

        if (hasSupplies) SUPPLIES_DATA.forEach(s => {
          const hay = [s.name, s.brand, s.type, s.notes].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'supply',
            key: s.id,
            name: s.name,
            meta: [s.brand, s.type].filter(Boolean).join(' · ')
          });
        });

        if (hasBooks) BOOKS_DATA.forEach(b => {
          const hay = [b.title, b.author, b.series, b.faction, b.notes].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'book',
            key: b.id,
            name: b.title,
            meta: [b.faction, b.series].filter(Boolean).join(' · ')
          });
        });

        if (hasShame) SHAME_DATA.forEach(s => {
          const hay = [s.name, s.faction, s.system, s.notes, s.acquired].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'shame',
            key: s.id,
            name: s.name,
            meta: [s.system, s.faction, s.status].filter(Boolean).join(' · ')
          });
        });

        if (hasRescues) RESCUE_DATA.forEach(r => {
          const hay = [r.name, r.faction, r.system, r.notes, r.source, r.condition, r.stage].filter(Boolean).join(' ');
          if (match(hay)) out.push({ type: 'rescue', key: r.id, name: r.name, meta: [r.system, r.faction, r.stage].filter(Boolean).join(' · ') });
        });

        if (hasForces) FORCES_DATA.forEach(f => {
          const hay = [f.name, f.faction, f.system, f.notes].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'force',
            key: f.id,
            name: f.name,
            meta: [f.system, f.faction].filter(Boolean).join(' · ')
          });
        });

        if (BATTLES_DATA) BATTLES_DATA.forEach(b => {
          const hay = [b.date, b.my_army, b.opponent, b.opponent_army, b.mission, b.notes].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'battle',
            key: b.id,
            name: (b.my_army || 'Battle') + ' vs ' + (b.opponent_army || b.opponent || '?'),
            meta: [b.date, b.result ? b.result.charAt(0).toUpperCase() + b.result.slice(1) : '', b.system].filter(Boolean).join(' · ')
          });
        });

        if (WISHLIST_DATA) WISHLIST_DATA.forEach(w => {
          const hay = [w.name, w.brand, w.faction, w.system, w.notes, w.type].filter(Boolean).join(' ');
          if (match(hay)) out.push({
            type: 'wish',
            key: w.id,
            name: w.name,
            meta: [WTYPE_LABEL_GS[w.type || 'paint'], w.brand || w.faction].filter(Boolean).join(' · ')
          });
        });

        return out;
      }

      function render(q) {
        const results = runSearch(q);
        if (!q) {
          listEl.innerHTML = '<div class="gs-empty">Type to search paints, schemes, recipes, bench projects, brushes, and more.</div>';
          flatResults = [];
          return;
        }
        if (!results.length) {
          listEl.innerHTML = `<div class="gs-empty">No matches for "${esc(q)}".</div>`;
          flatResults = [];
          return;
        }
        const groups = {};
        results.forEach(r => {
          (groups[r.type] = groups[r.type] || []).push(r);
        });

        let html = '';
        flatResults = [];
        TYPE_ORDER.forEach(t => {
          const g = groups[t];
          if (!g || !g.length) return;
          const shown = g.slice(0, PER_TYPE_CAP);
          const extra = g.length - shown.length;
          html += `<div class="gs-group-title">${TYPE_LABEL[t]}s &middot; ${g.length}${extra > 0 ? ` <span style="color:#4a3a10">(showing ${shown.length})</span>` : ''}</div>`;
          shown.forEach(r => {
            const idx = flatResults.length;
            flatResults.push(r);
            html += `<button type="button" class="gs-result" data-idx="${idx}"><span class="gs-result-type gs-type-${r.type}">${TYPE_LABEL[t]}</span><span class="gs-result-name">${esc(r.name)}</span><span class="gs-result-meta">${esc(r.meta || '')}</span></button>`;
          });
        });
        listEl.innerHTML = html;
        selectedIdx = 0;
        highlightSelected();
      }

      function highlightSelected() {
        const rows = listEl.querySelectorAll('.gs-result');
        rows.forEach((r, i) => r.classList.toggle('selected', i === selectedIdx));
        const sel = rows[selectedIdx];
        if (sel) sel.scrollIntoView({
          block: 'nearest'
        });
      }

      function open() {
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        input.value = '';
        render('');
        setTimeout(() => input.focus(), 10);
      }

      function close() {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }

      function jump(r) {
        close();
        setTimeout(() => {
          if (r.type === 'paint') {
            switchToTab('inventory');
            const searchEl = document.getElementById('search');
            const parts = r.key.split('|');
            if (searchEl) {
              searchEl.value = parts[1] || '';
              searchEl.dispatchEvent(new Event('input'));
            }
          } else if (r.type === 'scheme') {
            if (typeof _jumpToScheme === 'function') _jumpToScheme(r.key);
          } else if (r.type === 'recipe') {
            if (typeof _jumpToRecipe === 'function') _jumpToRecipe(r.key);
          } else if (r.type === 'planned') {
            switchToTab('planned');
            setTimeout(() => {
              const el = document.querySelector('.planned-card[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 120);
          } else if (r.type === 'bench') {
            switchToTab('bench');
            setTimeout(() => {
              const el = document.querySelector('.bench-card[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 150);
          } else if (r.type === 'brush') {
            switchToTab('brushes');
            setTimeout(() => {
              const el = document.querySelector('.brush-entry[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight');
              }
            }, 150);
          } else if (r.type === 'supply') {
            switchToTab('supplies');
            setTimeout(() => {
              const el = document.querySelector('.supply-card[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight');
              }
            }, 150);
          } else if (r.type === 'book') {
            switchToTab('books');
            setTimeout(() => {
              const el = document.querySelector('.bl-row[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 150);
          } else if (r.type === 'shame') {
            switchToTab('shame');
            setTimeout(() => {
              const el = document.querySelector('.shame-card[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 150);
          } else if (r.type === 'force') {
            switchToTab('forces');
            setTimeout(() => {
              const el = document.querySelector('.force-card[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 150);
          } else if (r.type === 'battle') {
            switchToTab('battles');
            setTimeout(() => {
              const el = document.querySelector('.battle-card[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.remove('highlight');
                void el.offsetWidth;
                el.classList.add('highlight');
              }
            }, 150);
          } else if (r.type === 'rescue') {
            switchToTab('rescues');
            setTimeout(() => {
              const el = document.querySelector('.rescue-card[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                el.classList.remove('highlight'); void el.offsetWidth; el.classList.add('highlight');
              }
            }, 150);
          } else if (r.type === 'wish') {
            switchToTab('wishlist');
            setTimeout(() => {
              const el = document.querySelector('.wishlist-card[data-id="' + r.key + '"]');
              if (el) {
                el.scrollIntoView({
                  behavior: 'smooth',
                  block: 'center'
                });
                el.style.transition = 'background .3s';
                el.style.background = '#3a2a08';
                setTimeout(() => el.style.background = '', 700);
              }
            }, 200);
          }
        }, 40);
      }

      trigger.addEventListener('click', open);
      overlay.addEventListener('click', e => {
        if (e.target === overlay) close();
      });
      input.addEventListener('input', () => render(input.value));

      input.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
          e.preventDefault();
          close();
          return;
        }
        if (!flatResults.length) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          selectedIdx = Math.min(flatResults.length - 1, selectedIdx + 1);
          highlightSelected();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          selectedIdx = Math.max(0, selectedIdx - 1);
          highlightSelected();
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (flatResults[selectedIdx]) jump(flatResults[selectedIdx]);
        }
      });

      listEl.addEventListener('click', e => {
        const btn = e.target.closest('.gs-result');
        if (!btn) return;
        const idx = parseInt(btn.dataset.idx, 10);
        if (flatResults[idx]) jump(flatResults[idx]);
      });

      // Keyboard shortcuts: Ctrl/Cmd+K or "/" to open, Esc to close
      document.addEventListener('keydown', e => {
        const isOpen = overlay.classList.contains('open');
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
          e.preventDefault();
          isOpen ? close() : open();
          return;
        }
        if (e.key === '/' && !isOpen) {
          const t = e.target;
          const typing = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable);
          if (typing) return;
          e.preventDefault();
          open();
          return;
        }
        if (e.key === 'Escape' && isOpen) {
          e.preventDefault();
          close();
        }
      });
    })();

    // Register service worker
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js');
    }

    document.addEventListener('DOMContentLoaded', function() {
      (function() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebar-toggle');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar || !toggle || !backdrop) return;

        toggle.addEventListener('click', () => {
          if (window.innerWidth > 768) {
            document.body.classList.remove('sidebar-collapsed');
            try { localStorage.setItem('sidebar-collapsed', '0'); } catch(e) {}
          } else {
            sidebar.classList.add('open');
            backdrop.classList.add('visible');
          }
        });

        backdrop.addEventListener('click', closeSidebar);

        (function() {
          var collapseBtn = document.getElementById('sidebar-collapse-btn');
          if (!collapseBtn) return;
          try { if (localStorage.getItem('sidebar-collapsed') === '1') document.body.classList.add('sidebar-collapsed'); } catch(e) {}
          collapseBtn.addEventListener('click', function() {
            document.body.classList.add('sidebar-collapsed');
            try { localStorage.setItem('sidebar-collapsed', '1'); } catch(e) {}
          });
        })();

        (function() {
          var nav  = document.querySelector('.sidebar-nav');
          var fade = document.getElementById('nav-fade');
          if (!nav || !fade) return;
          function checkFade() { fade.classList.toggle('hidden', nav.scrollTop + nav.clientHeight >= nav.scrollHeight - 20); }
          nav.addEventListener('scroll', checkFade, { passive: true });
          window.addEventListener('resize', checkFade);
          window.addEventListener('load', checkFade);
          checkFade();
        })();

        var logoLink = document.getElementById('sidebar-logo-link');
        if (logoLink) {
          logoLink.addEventListener('click', function(e) {
            e.preventDefault();
            switchToTab('contents');
            if (window.innerWidth <= 768) closeSidebar();
          });
        }

        const GROUPS_KEY = 'sidebar-groups';
        const saved = JSON.parse(localStorage.getItem(GROUPS_KEY) || '{}');

        document.querySelectorAll('.sidebar-group').forEach(group => {
          const id = group.dataset.group;
          if (saved[id] === true) group.classList.add('collapsed');

          group.querySelector('.sg-header').addEventListener('click', () => {
            group.classList.toggle('collapsed');
            const state = JSON.parse(localStorage.getItem(GROUPS_KEY) || '{}');
            state[id] = group.classList.contains('collapsed');
            localStorage.setItem(GROUPS_KEY, JSON.stringify(state));
          });
        });
      })();

      (function() {
        const installBanner = document.getElementById('install-banner');
        const installBtn = document.getElementById('install-btn');
        const installDismiss = document.getElementById('install-dismiss');
        if (!installBanner || !installBtn || !installDismiss) return;

        let deferredInstallPrompt = null;

        window.addEventListener('beforeinstallprompt', e => {
          e.preventDefault();
          deferredInstallPrompt = e;
          installBanner.classList.add('visible');
        });

        installBtn.addEventListener('click', async () => {
          if (!deferredInstallPrompt) return;
          deferredInstallPrompt.prompt();
          const {
            outcome
          } = await deferredInstallPrompt.userChoice;
          deferredInstallPrompt = null;
          installBanner.classList.remove('visible');
        });

        installDismiss.addEventListener('click', () => {
          installBanner.classList.remove('visible');
          deferredInstallPrompt = null;
        });

        window.addEventListener('appinstalled', () => {
          installBanner.classList.remove('visible');
          deferredInstallPrompt = null;
        });
      })();

      (function() {
        const dctEl = document.getElementById('daily-colour-card');
        if (!dctEl || typeof PAINTS === 'undefined') return;
        const chromatic = PAINTS.filter(p => { if (!p.hex || !/^#[0-9a-f]{6}$/i.test(p.hex)) return false; const [,s] = hexToHsl(p.hex); return s >= 8 && !_ACHROMATIC_CATS.has(p.color); });
        if (!chromatic.length) return;
        const dateStr = new Date().toDateString();
        let seed = 0;
        for (const c of dateStr) seed = ((seed << 5) - seed) + c.charCodeAt(0) | 0;
        seed = Math.abs(seed);
        const hTypes = ['complementary','triadic','split','analogous'];
        const hLabels = {complementary:'Complementary',triadic:'Triadic',split:'Split-Complementary',analogous:'Analogous'};
        const hDescs = {complementary:'Maximum contrast — opposite on the wheel. The accent colour that makes the primary pop.',triadic:'Three equally-spaced hues — vibrant, balanced, and full of energy.',split:'Two near-complements flanking the opposite — softer tension than pure complementary.',analogous:'Adjacent hues — harmonious, naturalistic, and satisfying to look at.'};
        const paint = chromatic[seed % chromatic.length];
        const hType = hTypes[(seed >> 6) % hTypes.length];
        const hue = getHue(paint);
        if (hue === null) return;
        const [ph, ps, pl] = hexToHsl(paint.hex);
        const temp = paintTemperature(hue);
        const role = paintRole(ph, ps, pl);
        const positions = harmonyPositions(hue, hType);
        const picks = positions.map(pos => { const matches = paintsNearHue(pos.hue, 30, paintKey(paint)); const owned = matches.filter(m => paintOwned.has(paintKey(m))); return {pos, paint: owned[0] || matches[0] || null}; });
        function buildDCTWheelSvg() {
          const CX=120,CY=120,R1=78,R2=96,SEGS=24,DEG=15,ARC=20;
          let bg=''; for (let i=0;i<SEGS;i++) { const hd=i*DEG,aS=(hd-90-DEG/2)*Math.PI/180,aE=(hd-90+DEG/2)*Math.PI/180; const x1=CX+R1*Math.cos(aS),y1=CY+R1*Math.sin(aS),x2=CX+R2*Math.cos(aS),y2=CY+R2*Math.sin(aS),x3=CX+R2*Math.cos(aE),y3=CY+R2*Math.sin(aE),x4=CX+R1*Math.cos(aE),y4=CY+R1*Math.sin(aE); bg+=`<path d="M${x1.toFixed(1)},${y1.toFixed(1)} L${x2.toFixed(1)},${y2.toFixed(1)} A${R2},${R2} 0 0,1 ${x3.toFixed(1)},${y3.toFixed(1)} L${x4.toFixed(1)},${y4.toFixed(1)} A${R1},${R1} 0 0,0 ${x1.toFixed(1)},${y1.toFixed(1)}Z" fill="hsl(${hd},70%,55%)" opacity=".2"/>`; }
          const paS=(hue-90-ARC)*Math.PI/180,paE=(hue-90+ARC)*Math.PI/180;
          const px1=CX+R1*Math.cos(paS),py1=CY+R1*Math.sin(paS),px2=CX+R2*Math.cos(paS),py2=CY+R2*Math.sin(paS),px3=CX+R2*Math.cos(paE),py3=CY+R2*Math.sin(paE),px4=CX+R1*Math.cos(paE),py4=CY+R1*Math.sin(paE);
          const primaryArc=`<path d="M${px1.toFixed(1)},${py1.toFixed(1)} L${px2.toFixed(1)},${py2.toFixed(1)} A${R2},${R2} 0 0,1 ${px3.toFixed(1)},${py3.toFixed(1)} L${px4.toFixed(1)},${py4.toFixed(1)} A${R1},${R1} 0 0,0 ${px1.toFixed(1)},${py1.toFixed(1)}Z" fill="#c9a227" opacity=".75"/>`;
          let harmArcs=''; positions.forEach(pos => { const aS=(pos.hue-90-ARC)*Math.PI/180,aE=(pos.hue-90+ARC)*Math.PI/180; const x1=CX+R1*Math.cos(aS),y1=CY+R1*Math.sin(aS),x2=CX+R2*Math.cos(aS),y2=CY+R2*Math.sin(aS),x3=CX+R2*Math.cos(aE),y3=CY+R2*Math.sin(aE),x4=CX+R1*Math.cos(aE),y4=CY+R1*Math.sin(aE); harmArcs+=`<path d="M${x1.toFixed(1)},${y1.toFixed(1)} L${x2.toFixed(1)},${y2.toFixed(1)} A${R2},${R2} 0 0,1 ${x3.toFixed(1)},${y3.toFixed(1)} L${x4.toFixed(1)},${y4.toFixed(1)} A${R1},${R1} 0 0,0 ${x1.toFixed(1)},${y1.toFixed(1)}Z" fill="hsl(${pos.hue.toFixed(0)},80%,62%)" opacity=".55"/>`; });
          const pAngle=(hue-90)*Math.PI/180, dr=54;
          let dots=`<circle cx="${(CX+dr*Math.cos(pAngle)).toFixed(1)}" cy="${(CY+dr*Math.sin(pAngle)).toFixed(1)}" r="10" fill="${paint.hex}" stroke="#c9a227" stroke-width="2.5"/>`;
          picks.forEach(({pos,paint:mp}) => { if (!mp) return; const mh=getHue(mp); if (mh===null) return; const mAngle=(mh-90)*Math.PI/180; const mFill=mp.hex||`hsl(${mh},60%,45%)`; dots+=`<circle cx="${(CX+dr*Math.cos(mAngle)).toFixed(1)}" cy="${(CY+dr*Math.sin(mAngle)).toFixed(1)}" r="7" fill="${mFill}" stroke="rgba(255,255,255,.5)" stroke-width="1.5"/>`; });
          return `<svg viewBox="0 0 240 240" xmlns="http://www.w3.org/2000/svg">${bg}${primaryArc}${harmArcs}${dots}</svg>`;
        }
        const picksHtml = picks.map(({pos,paint:mp}) => { if (!mp) return ''; const fill=mp.hex||`hsl(${getHue(mp)||0},60%,45%)`; const own=paintOwned.has(paintKey(mp)); return `<div class="dct-strip-match"><span class="dct-match-role">${pos.label}</span><span class="dct-match-swatch" style="background:${fill}${own?'':';opacity:.4'}" title="${esc(mp.name)} (${esc(mp.brand)})${own?'':' · not owned'}"></span><span class="dct-match-name">${esc(mp.name)}</span></div>`; }).join('');
        dctEl.innerHTML = `<div class="dct-strip"><div class="dct-strip-label"><div class="dct-eyebrow">Colour Theory of the Day</div><div class="dct-type-label">${hLabels[hType]}</div></div><div class="dct-strip-primary"><span class="dct-primary-swatch" style="background:${paint.hex}" title="${esc(paint.name)}"></span><div><div class="dct-primary-name">${esc(paint.name)}</div><div class="dct-primary-meta">${esc(paint.brand)} · <span class="hp-temp hp-temp-${temp}">${temp}</span></div></div></div><div class="dct-strip-matches">${picksHtml}</div><div class="dct-strip-desc">${esc(hDescs[hType])}</div><div class="dct-strip-wheel">${buildDCTWheelSvg()}</div></div>`;
        dctEl.style.display = '';
      })();

      (function() {
        var btn = document.getElementById('back-to-top');
        if (!btn) return;
        window.addEventListener('scroll', function() {
          btn.style.display = window.scrollY > 200 ? 'flex' : 'none';
        }, {
          passive: true
        });
        btn.addEventListener('click', function() {
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        });
      })();
    });
