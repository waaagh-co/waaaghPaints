      <h2 id="section-checker" style="margin-top:40px">Paint Checker</h2>
      <p style="font-size:12px;color:#6a5a30;margin-bottom:14px;line-height:1.6">
        Paste a list of paint names (one per line) to see which you own, which are low or out, and which are missing entirely.
      </p>
      <div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:center">
        <select id="checkerBrand" style="padding:7px 10px;background:#130f08;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;font-family:inherit;outline:none">
          <option value="">- Select brand -</option>
          <?php
          $checkerBrands = array_unique(array_column($paints, 'brand'));
          sort($checkerBrands);
          foreach ($checkerBrands as $b) echo '<option value="' . e($b) . '">' . e($b) . '</option>';
          ?>
        </select>
        <button type="button" class="btn btn-sm" onclick="checkPaints()">Check List</button>
        <button type="button" class="btn btn-sm" style="color:#6a5a30;border-color:#2a2010" onclick="clearChecker()">Clear</button>
      </div>
      <textarea id="checkerInput" rows="7"
        placeholder="Mephiston Red&#10;Agrax Earthshade&#10;Nuln Oil&#10;Incubi Darkness&#10;…"
        style="width:100%;background:#0a0806;border:1px solid #2a2010;border-radius:3px;color:#c4b49a;font-size:13px;padding:10px;font-family:inherit;resize:vertical;outline:none"></textarea>
      <div id="checkerResults"></div>
