<?php
session_start();
require_once __DIR__ . '/config.php';
if (empty($_SESSION['admin'])) {
    header('Location: ' . ADMIN_FILENAME);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Waaagh! Paint - User Guide</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: system-ui, -apple-system, sans-serif;
      font-size: 15px;
      line-height: 1.7;
      background: #0d0d1a;
      color: #c4b49a;
    }

    /* ── Header ── */
    header {
      background: radial-gradient(ellipse 90% 100% at 50% 40%, #1c1408 0%, #0e0b05 55%, #0d0d1a 100%);
      border-bottom: 2px solid #6a4f10;
      text-align: center;
      padding: 18px 20px 14px;
      position: relative;
    }
    header::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 5%; right: 5%;
      height: 1px;
      background: linear-gradient(to right, transparent, #c9a22766, #c9a227, #c9a22766, transparent);
      pointer-events: none;
    }
    .logo {
      display: block;
      height: 110px;
      margin: 0 auto 8px;
      -webkit-mask-image:
        linear-gradient(to right, transparent 0%, rgba(0,0,0,.5) 10%, black 22%, black 78%, rgba(0,0,0,.5) 90%, transparent 100%),
        linear-gradient(to bottom, transparent 0%, rgba(0,0,0,.5) 8%, black 18%, black 76%, rgba(0,0,0,.5) 88%, transparent 100%);
      -webkit-mask-composite: source-in;
      mask-image:
        linear-gradient(to right, transparent 0%, rgba(0,0,0,.5) 10%, black 22%, black 78%, rgba(0,0,0,.5) 90%, transparent 100%),
        linear-gradient(to bottom, transparent 0%, rgba(0,0,0,.5) 8%, black 18%, black 76%, rgba(0,0,0,.5) 88%, transparent 100%);
      mask-composite: intersect;
      filter: drop-shadow(0 0 18px rgba(201,162,39,0.7)) drop-shadow(0 0 6px rgba(201,162,39,0.4));
    }
    header p {
      font-family: 'Cinzel', serif;
      font-size: .65rem;
      color: #6a5a30;
      letter-spacing: .22em;
      text-transform: uppercase;
      margin-top: 2px;
    }

    /* ── Layout ── */
    main {
      max-width: 800px;
      margin: 0 auto;
      padding: 32px 20px 64px;
    }

    /* ── Section headers ── */
    .page-section {
      margin-bottom: 48px;
    }
    .page-section-title {
      font-family: 'Cinzel', serif;
      font-size: 1.15rem;
      color: #c9a227;
      letter-spacing: .12em;
      text-transform: uppercase;
      border-bottom: 1px solid #3a2e0f;
      padding-bottom: 8px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .page-section-title .section-icon {
      font-size: 1rem;
      opacity: .8;
    }

    h3 {
      font-family: 'Cinzel', serif;
      font-size: .9rem;
      color: #d4aa40;
      letter-spacing: .08em;
      text-transform: uppercase;
      margin: 28px 0 12px;
    }
    h3:first-child { margin-top: 0; }

    p { margin-bottom: 10px; color: #b8a88a; }

    /* ── Feature list ── */
    ul {
      list-style: none;
      margin-bottom: 14px;
    }
    ul li {
      position: relative;
      padding: 5px 0 5px 22px;
      color: #b8a88a;
      border-bottom: 1px solid #1a1820;
    }
    ul li::before {
      content: '›';
      position: absolute;
      left: 4px;
      color: #6a4f10;
      font-size: 1.1rem;
      line-height: 1.5;
    }
    ul li strong {
      color: #c9a227;
      font-weight: 600;
    }

    /* ── Tip / note boxes ── */
    .tip {
      background: #12101e;
      border-left: 3px solid #6a4f10;
      padding: 10px 14px;
      border-radius: 0 4px 4px 0;
      margin: 14px 0;
      color: #9a8a6a;
      font-size: .9rem;
    }

    /* ── Status dot legend ── */
    .dot-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 10px 24px;
      margin: 10px 0 14px;
    }
    .dot-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: .88rem;
      color: #b8a88a;
    }
    .dot {
      width: 11px; height: 11px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .dot.owned   { background: #22c55e; box-shadow: 0 0 5px #22c55e88; }
    .dot.low     { background: #f97316; box-shadow: 0 0 5px #f9731688; }
    .dot.out     { background: #ef4444; box-shadow: 0 0 5px #ef444488; }
    .dot.wanted  { background: #3b82f6; box-shadow: 0 0 5px #3b82f688; }
    .dot.retired { background: #4a4a4a; box-shadow: 0 0 5px #4a4a4a88; }
    .dot.missing { background: transparent; border: 2px solid #9ca3af; }

    /* ── Stock badge samples ── */
    .badge-row { display: flex; gap: 8px; flex-wrap: wrap; margin: 8px 0 14px; }
    .sbadge {
      font-size: .75rem;
      font-weight: 700;
      padding: 1px 7px;
      border-radius: 3px;
      letter-spacing: .06em;
    }
    .sbadge.low     { background: rgba(249,115,22,.18); color: #f97316; border: 1px solid #f9731644; }
    .sbadge.out     { background: rgba(239,68,68,.18);  color: #ef4444; border: 1px solid #ef444444; }
    .sbadge.wanted  { background: rgba(59,130,246,.18); color: #6ea8fe; border: 1px solid #3b82f644; }
    .sbadge.retired { background: rgba(74,74,74,.18);   color: #5a5a5a; border: 1px solid #4a4a4a44; }

    /* ── Divider ── */
    .divider {
      height: 1px;
      background: linear-gradient(to right, transparent, #3a2e0f88, #c9a22733, #3a2e0f88, transparent);
      margin: 48px 0;
    }

    /* ── Back link ── */
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #6a5a30;
      font-family: 'Cinzel', serif;
      font-size: .72rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      text-decoration: none;
      margin-bottom: 28px;
      transition: color .2s;
    }
    .back-link:hover { color: #c9a227; }

    /* ── Footer ── */
    footer {
      text-align: center;
      padding: 20px;
      font-size: .75rem;
      color: #3a3020;
      font-family: 'Cinzel', serif;
      letter-spacing: .1em;
      border-top: 1px solid #1e1a0e;
    }

    @media (max-width: 600px) {
      .dot-legend { gap: 8px 16px; }
    }
  </style>
</head>
<body>

<header>
  <img src="img/logo_sm.png" alt="Waaagh! Paint" class="logo">
  <p>User Guide</p>
</header>

<main>
  <a href="<?= ADMIN_FILENAME ?>" class="back-link">← Back to Admin</a>

  <!-- ══════════════ MAIN SITE ══════════════ -->
  <div class="page-section">
    <div class="page-section-title"><span class="section-icon">🎨</span> The Main Site</div>

    <h3>Looted Knowledge (landing tab)</h3>
    <p>The first thing you see. The hero area splits into two halves side by side:</p>
    <p><strong>Left half</strong> stacks three live widgets:</p>
    <ul>
      <li><strong>Under Da Brush</strong> - your most recently touched active bench project (photo, name, faction, current stage). Click to jump to the Bench tab. Only shown when you have active projects.</li>
      <li><strong>Hobby Activity heatmap</strong> - a GitHub-style contribution grid showing 52 weeks of activity across schemes, bench work, journal entries, and battles. Today is always visible at the right edge.</li>
      <li><strong>Annual Goal bar</strong> - progress toward your painted-model target for the current year. Only shown when a goal has been set in Admin Stats.</li>
    </ul>
    <p><strong>Right half</strong> is the <strong>Scrap Notes panel</strong> - your most recent journal entry displayed in handwritten style over an Ork clipboard background. Click anywhere on it to jump to the Scrap Notes tab. Only shown when Scrap Notes has been started and has at least one entry.</p>
    <p>The <strong>Waaagh! Index</strong> gauge lives in the <strong>Admin Stats</strong> section (below Collection by Brand). It shows your current momentum and a rolling 7-day <strong>Dis Week</strong> summary: models painted, bench sessions, battles fought, notes scribbled.</p>
    <p>Below the hero, <strong>The Pipeline</strong> shows Recipes → Paint Schemes → Planned → On the Bench as large stat cards with a green <strong>ready now</strong> count whenever you own every paint for a planned scheme. The last battle result also appears here if you have Battle Honours.</p>
    <p>Below the Pipeline, a <strong>Wots Next?</strong> panel surfaces your top recommended next actions automatically - bench projects close to completion, planned schemes with all paints ready, forces nearing their target, and rescue minis prepped and waiting. No setup required.</p>
    <p>Below that, the <strong>Colour Theory of the Day</strong> card picks a random paint from your inventory each day (seeded by date, so it stays stable for the whole day) and analyses it through a randomly chosen harmony type - Complementary, Triadic, Split-Complementary, or Analogous. The mini colour wheel shows the selected paint's position (gold arc) alongside the harmony target positions (coloured arcs) and the best owned paint for each role as a dot. Below the wheel: the paint name and brand, a one-line description of the harmony type, and the closest paints you own that fill each harmony role. Useful as a daily creative prompt.</p>
    <p>Below the pipeline, the page is laid out in sections:</p>
    <ul>
      <li><strong>The Handbook</strong> - Recipes, featured prominently at the top since it's the heart of the technique library</li>
      <li><strong>The Work</strong> - Paint Schemes, Factions, Pile of Shame, Planned, On the Bench, Battle Honours</li>
      <li><strong>The Collection</strong> - Paint Inventory, Brushes</li>
      <li><strong>Reference Tools</strong> - Equivalency</li>
      <li><strong>Reading &amp; Inspiration</strong> - Codices, Scrap Notes</li>
    </ul>
    <p>Each entry shows a one-line description plus a live count. Opt-in sections (Recipes, Brushes, Pile of Shame, Bench, Forces &amp; Rosters, Battle Honours, Codices, Scrap Notes) only appear once their data file has been started in Admin. Click any entry to jump straight to that tab.</p>

    <h3>The Commissar tab</h3>
    <p>A personalised tactical intelligence briefing drawn from all your data at once. Always visible in the sidebar under <strong>Your Armies</strong>.</p>
    <p>Below the hero banner, a <strong>Campaign Honours</strong> strip displays your earned milestones as colour-coded badges across 11 categories: gold for models painted, amber for bench sessions, green for completed bench projects, orange for rescues, muted red for Pile of Shame, purple for recipes, crimson for battles, dark green for forces, warm gold for scrap notes, blue for codices, and teal for paints owned. Each unlocked badge shows its name and a short flavour line. Locked milestones appear dim so you can see what's still to earn. The strip header shows how many you've unlocked out of the full 37.</p>
    <ul>
      <li><strong>Next Orders</strong> - your top 5 highest-priority painting tasks, scored automatically by how close to done each bench project or planned scheme is, whether all paints are in stock, and how long since you last touched it. Reason pills explain each score: "final stage," "fully stocked," "neglected 42d," etc.</li>
      <li><strong>Shame Debt</strong> - the pile-of-shame acquisition rate vs completion rate, net velocity (growing or shrinking), and estimated months to clear (when the pile is shrinking). Status breakdown across sealed / opened / partial boxes.</li>
      <li><strong>Session Patterns</strong> - which day of the week you paint longest on average, shown as a 7-bar mini chart with the best day highlighted gold. Trend line compares last 30 days to the previous 30-day period.</li>
      <li><strong>Stage Bottleneck</strong> - which bench stage your projects linger in longest (computed from actual stage transition history), and the current distribution of projects across all stages.</li>
      <li><strong>Force Forecasts</strong> - for any force with a model target set, a progress bar plus an estimated completion date based on your recent painting velocity (models painted over the last 30 days).</li>
      <li><strong>Battle Dossier</strong> - overall W/L/D record in large coloured numerals, plus win rate ranked per army so you can see which faction performs best.</li>
      <li><strong>Supply Risk</strong> - flags any of your 30 most-used paints that are currently marked low or out of stock, so you know what to order before it blocks a project.</li>
      <li><strong>Historical Record</strong> - the same stats from the Admin Stats panel (paint counts by brand, top 8 most-used paints) presented in the Commissar's aesthetic for quick reference.</li>
    </ul>
    <div class="tip">Cards only appear for the data features you have active. The Commissar tab is always visible and degrades gracefully - if you haven't started Battle Honours, the Battle Dossier card is simply omitted.</div>

    <h3>Waaagh! Index</h3>
    <p>A momentum gauge in the <strong>Admin Stats</strong> section (below Collection by Brand). It reads your current painting momentum from the past 7 days at a glance.</p>
    <ul>
      <li>Scores your activity over the rolling 7 days: models painted, bench sessions logged, journal entries, and how recently you last touched the bench</li>
      <li>Five states from dormant to frenzied: <strong>DOZIN'</strong> &rarr; <strong>STIRRIN'</strong> &rarr; <strong>ON DA WARPATH</strong> &rarr; <strong>WAAAGH!</strong> &rarr; <strong>FULL WAAAGH!!</strong></li>
      <li>The needle on the gauge face points to your current level; the top two states pulse to signal a hot streak</li>
      <li>A <strong>Dis Week</strong> summary below the state label shows what drove the score: models painted, bench sessions, battles fought, notes scribbled</li>
      <li>Entirely passive - updates automatically each page load, nothing to set up or configure</li>
    </ul>

    <h3>Global Search</h3>
    <p>Search everything at once - paints, schemes, recipes, planned schemes, bench projects, brushes, forces, battles, codices, and journal entries.</p>
    <ul>
      <li><strong>Open with</strong> Ctrl+K (Windows/Linux), Cmd+K (Mac), the <strong>/</strong> key when not typing in a field, or the <strong>Search All</strong> button at the bottom of the sidebar</li>
      <li>Results are grouped by type (Scheme, Recipe, Paint, Planned, etc.), each capped at 8 results per type</li>
      <li>Use <strong>arrow keys</strong> to navigate the list, <strong>Enter</strong> to jump to the highlighted result, <strong>Esc</strong> to close</li>
      <li>Clicking a result jumps directly to it - switching tab, scrolling to the entry, and pulsing it gold</li>
    </ul>

    <h3>Sharing &amp; Direct Links</h3>
    <ul>
      <li><strong>Tab links</strong> - click the small breadcrumb label at the left of any tab's controls bar (e.g. "Paint Schemes", "Equivalency") to copy a direct URL to that tab. The label briefly shows a checkmark to confirm. Works for all tabs</li>
      <li><strong>Scheme links</strong> - the chain-link icon on each Paint Schemes card copies a URL that deep-links directly to that card</li>
      <li><strong>Recipe links</strong> - the chain-link icon on each Recipe card copies a URL that deep-links to that recipe</li>
    </ul>

    <h3>Paint Inventory tab</h3>
    <p>Your full paint collection - browse, search, and filter everything you own.</p>
    <ul>
      <li><strong>Search box</strong> - type any part of a paint name or hue to narrow the list instantly</li>
      <li><strong>Brand / Colour / Layer dropdowns</strong> - filter by brand, broad colour category, or paint type (Base, Contrast, Shade, etc.)</li>
      <li><strong>Column headers</strong> - click any header to sort; click again to reverse the order</li>
      <li><strong>Click a row</strong> - opens a panel listing every paint scheme that uses that paint, with a "View →" button to jump straight to it</li>
      <li><strong>Stock badges</strong> - shown inline in the paint name cell</li>
      <li><strong>Notes / Stars button</strong> - a pencil icon appears on any paint with notes; a gold ★ appears on any paint with a quality rating. Click either to open a drawer showing the paint's notes and star rating. Both are read-only here - set them in Admin</li>
      <li><strong>Flagged filter</strong> - click the <em>Flagged</em> button in the controls bar to show only paints marked low, out, wanted, or retired. The button turns gold when active - useful for a quick restock check before sitting down to paint. Click again or hit Reset to clear it</li>
      <li><strong>Restock List</strong> - click the <em>Restock List</em> button for a full-screen shopping list of every flagged paint, split into three sections: Out, Low, and Wanted, each grouped by brand. Print or Copy to take it to the hobby shop. The same list is also one tap away from the landing page - the flagged count on the Paint Inventory entry opens it directly. <em>Retired paints do not appear on the Restock List</em> — they are kept for historical reference only</li>
      <li><strong>Colour Wheel</strong> - click the <em>&#11044; Wheel</em> button to switch from the table to an SVG hue wheel plotting every chromatic paint you own as a coloured dot at its hue position. Gaps in the ring are hues you don't have covered. All active filters (brand, colour, layer, search) apply to the wheel in real time. Hover a dot for a tooltip; click a dot to jump to that paint in the list view. Achromatic paints (metallics, washes, shades, primers, greyscale) appear in grouped swatches below the wheel sorted by hue then lightness. A <em>&#128279; Copy link</em> button lets you share a direct URL that opens straight to the wheel view</li>
      <li><strong>Colour Harmony Advisor</strong> - once the Wheel is active, a <em>&#9678; Harmony</em> button appears. Click it to enter harmony mode, then click any paint dot on the wheel to analyse its colour relationships. A panel shows the selected paint's temperature (warm / cool / neutral) and role (Shadow / Foundation / Highlight / Transition), then lists owned paints that fill each harmony position. Switch between five harmony types using the tabs:
        <ul style="margin-top:6px;margin-left:16px">
          <li><strong>Complement</strong> - directly opposite on the wheel; maximum contrast; use as an accent or spot colour</li>
          <li><strong>Triadic</strong> - three hues equally spaced at 120&deg;; vibrant and balanced; the classic heroic palette</li>
          <li><strong>Split</strong> - two hues flanking the complement at 150&deg; and 210&deg;; softer tension than pure complementary</li>
          <li><strong>Analogous</strong> - adjacent hues within 30&deg;; harmonious and naturalistic; great for organic subjects</li>
          <li><strong>Tetradic</strong> - four hues at 90&deg; intervals; complex and rich; the most spectacular when handled well</li>
        </ul>
        Harmony arcs appear on the reference ring at each target position; owned paints within those arcs glow. Click any swatch in the panel to pivot the analysis to that paint. Click a tab while a paint is selected to explore all five harmony types from the same anchor.</li>
    </ul>
    <div class="badge-row">
      <span class="sbadge low">low</span>
      <span class="sbadge out">out</span>
      <span class="sbadge wanted">wanted</span>
      <span class="sbadge retired">retired</span>
    </div>
    <p style="font-size:.88rem;color:#7a6a50;margin-top:-6px;"><em>low = running low &middot; out = none left &middot; wanted = catalogued but not yet bought &middot; retired = gone, not replacing</em></p>

    <h3>Brushes tab</h3>
    <p>A personal brush inventory - only visible if the inventory has been started in Admin.</p>
    <ul>
      <li><strong>Condition filter pills</strong> - filter to All / Prime / Workhorse / Retired at a glance</li>
      <li><strong>Search box</strong> - searches across brand, series, size, material, primary use, and notes</li>
      <li>Each entry shows brand and series name, size, material, primary use, condition badge, date started, and any notes you've recorded</li>
      <li>Condition badges are colour-coded: <strong>Prime</strong> (gold - in great shape), <strong>Workhorse</strong> (amber - still useful), <strong>Retired</strong> (grey - worn out)</li>
    </ul>

    <h3>Supplies tab</h3>
    <p>Track palettes, mats, lamps, holders, and other hobby tools - only visible once started in Admin.</p>
    <ul>
      <li><strong>Condition filter pills</strong> - same Prime / Workhorse / Retired system as brushes</li>
      <li><strong>Search box</strong> - searches across name, brand, type, and notes</li>
      <li>Each card shows the item name, type badge (palette, wet-palette, lamp, etc.), brand, acquisition date, condition badge, and notes</li>
      <li>Condition badges are colour-coded the same as brushes: <strong>Prime</strong> (gold), <strong>Workhorse</strong> (amber), <strong>Retired</strong> (grey)</li>
      <li>Appears in the global search (Ctrl/Cmd+K) under type "Supply"</li>
    </ul>

    <h3>Paint Schemes tab</h3>
    <p>Your gallery of completed models.</p>
    <ul>
      <li><strong>Cards</strong> show the model name, faction, game system badge, date painted, photos, and the paints used. A <strong>&times;N</strong> badge appears when a scheme covers more than one model</li>
      <li><strong>Click any photo</strong> to open a full-screen lightbox - arrow keys or swipe to navigate multiple photos</li>
      <li><strong>Search</strong> filters across name, faction, description, and paints used</li>
      <li><strong>Game System filter</strong> - dropdown in the controls bar to filter by 40k, Kill Team, Blood Bowl, etc.</li>
      <li><strong>Faction tags</strong> - click a tag on any card to filter the whole gallery to that faction; an active pill appears at the top to clear it</li>
      <li><strong>Colour pills</strong> - click any paint name on a card to jump straight to that paint in the Inventory tab</li>
      <li><strong>Pull list button</strong> - opens a checklist of every paint needed for that scheme; missing paints are flagged red, low-stock orange; Print or Copy to take shopping. When a scheme has linked recipes, the pull sheet becomes a sequenced step-by-step guide instead of a flat list. For any missing paint that has a hex colour set, a dimmed <em>≈ nearest owned</em> suggestion appears below it based on RGB colour distance</li>
      <li><strong>&#9678; Harmony button</strong> - opens the <strong>Scheme Doctor</strong> for that scheme. A modal shows a mini colour wheel plotting all the scheme's chromatic paints as dots, then analyses what it finds: the detected harmony type (Complementary, Triadic, Analogous, etc.), a temperature distribution bar showing the warm/cool/neutral split across the scheme, shadow and highlight temperature checks (cool shadows and warm highlights = good depth), and a Missing Harmony Roles section listing any gaps with owned paint suggestions to fill them. Use it to understand why a scheme works, or to find out what's making it feel flat</li>
      <li><strong>&#9876; Warpaint button</strong> - opens <strong>Warpaint Mode</strong>, a full-screen overlay designed to be used at arm's length while you actually paint. The left panel shows your scheme photo large with the scheme name overlaid in gold at the bottom; the right panel shows the summary block, your description formatted as readable sections and bullets, then every linked recipe rendered inline with all steps at 17px - technique badge, stock dot, paint swatch, paint name, brand, mix paint (with its own swatch and stock dot), ratio, and brush recommendation. Missing paints also show a nearest-owned colour suggestion. Each recipe has a <em>&#9654; Guide</em> button to launch step-by-step mode on top. A paint palette at the bottom covers any colours not already in a recipe. Arrow keys cycle through photos; ESC closes. The Pull list is accessible from inside Warpaint Mode too</li>
    </ul>

    <h3>Factions tab</h3>
    <p>A rolled-up view of everything connected to each army or faction - across painted work, recipes, in-progress projects, and planned schemes.</p>
    <ul>
      <li>Each faction card shows: painted schemes (with mini thumbnails), linked recipes, in-progress bench projects and planned schemes, and a full deduped paint palette for that faction</li>
      <li><strong>Search box</strong> - filter factions by name</li>
      <li>Clicking a scheme thumbnail jumps to it in Paint Schemes; clicking a recipe badge jumps to it in Recipes; clicking bench/planned chips jump to their respective tabs</li>
      <li>Paint palette pills show ownership dots and usage counts - click one to jump to that paint in Inventory</li>
      <li>A faction only appears if at least one scheme, bench project, planned scheme, or recipe has that faction set</li>
    </ul>

    <h3>Pile of Shame tab</h3>
    <p>The backlog before the backlog - boxes on the shelf that haven't been opened yet. Only visible once started in Admin.</p>
    <ul>
      <li>Cards show the system badge, box name, faction, status (Sealed / Opened / Partial), model count, acquisition date, and a <strong>"sitting X years/months"</strong> calculation so you know how long the guilt has been building</li>
      <li><strong>Filter pills</strong> - Active (not yet promoted) / Promoted / All; defaults to Active so finished boxes don't clutter the view</li>
      <li><strong>Search box</strong> filters across name, faction, system, notes, and acquired date</li>
      <li><strong>Promote buttons</strong> graduate a box to the pipeline without losing the shame record: <em>"→ Planned"</em> creates a new Planned entry; <em>"→ Bench"</em> creates a new On the Bench entry at the Built stage. The shame entry is kept with a gold "Promoted" badge for history</li>
    </ul>

    <h3>Rescue Tracker tab</h3>
    <p>A holding bay for eBay finds and second-hand minis going through the rescue process before they reach the bench. Only visible once started in Admin.</p>
    <ul>
      <li>Cards show the system badge, mini name, faction, source (eBay / Trade / LGS / Gift), strip difficulty badge, model count, acquisition date with a <strong>"sitting X months"</strong> age label, and before-photos showing the condition on arrival</li>
      <li><strong>Stage badges</strong> track the linear workflow: <em>Bidding</em> &rarr; <em>In Transit</em> &rarr; <em>Received</em> &rarr; <em>Stripping</em> &rarr; <em>Prepped</em>. Each stage has its own colour so you can tell at a glance where everything is</li>
      <li><strong>Filter pills</strong> - Active (not yet promoted) / Promoted / All; defaults to Active</li>
      <li><strong>Search box</strong> filters across name, faction, system, source, notes, and stage</li>
      <li><strong>Before photos</strong> - up to 4 photos per entry showing the minis as they arrived. Click any photo to open it in the lightbox</li>
      <li><strong>Promote buttons</strong> graduate a rescue out of the tracker: <em>"&rarr; Bench"</em> creates a new On the Bench entry at the Built stage; <em>"&rarr; Shame"</em> creates a Pile of Shame entry if you're not ready to start yet. The rescue entry is kept with a gold "Promoted" badge for history</li>
      <li>Model counts from active rescues feed into the Total Units Tracked stat in Admin - rescues that go straight to bench without ever touching the Pile of Shame are still counted</li>
    </ul>
    <div class="tip">The workflow: add when you win a bid &rarr; advance to In Transit when you pay &rarr; mark Received when it arrives &rarr; Stripping when you start prep &rarr; Prepped when it's clean and ready. Then promote to Bench and paint it.</div>

    <h3>Planned tab</h3>
    <p>Schemes you want to paint in the future - before you've built or bought the model.</p>
    <ul>
      <li>Cards show the scheme name, kit, faction, game system badge, a <strong>readiness badge</strong>, and the paints you've tagged</li>
      <li>Colour pills show green (owned), amber (low stock), or red (missing/out) at a glance</li>
      <li><strong>Readiness badges</strong> - every card shows <em>Ready</em> (green, you own everything), <em>Almost</em> (amber, 1-2 paints missing), or <em>Needs Work</em> (dark red, 3+ missing). The list sorts READY schemes to the top by default.</li>
      <li><strong>Shopping Impact line</strong> - ALMOST cards show exactly which 1-2 paints you need to buy to unlock that scheme: <em>"Buy: Agrax Earthshade, Nuln Oil - then ready"</em></li>
      <li><strong>Readiness filter pills</strong> - All / Ready / Almost / Needs Work; filter to exactly what you're looking for</li>
      <li><strong>Search box</strong> - filters cards by name, faction, kit, or description</li>
      <li><strong>Game System filter</strong> - dropdown to filter by 40k, Kill Team, etc.</li>
      <li><strong>Shopping List button</strong> - aggregates every missing or low paint across <em>all</em> planned schemes into one printable/copyable list, grouped by brand and split into "Must Buy" and "Consider Restocking"</li>
    </ul>

    <h3>On the Bench tab</h3>
    <p>Active painting projects in flight - the workflow piece between "planned" and "done." Only visible if the workbench has been started in Admin.</p>
    <ul>
      <li>Cards show the project name, faction, game system badge, current stage, "touched" date, gradient progress bar, WIP photo strip, notes, paint queue, and brushes you're using</li>
      <li><strong>Session Sheet button</strong> - appears on any project that has paints set. Opens the same pull sheet modal as Gallery schemes: paint queue with owned/low/missing flags, recipe steps in order (if a recipe is linked), current and next stage in the header. Print or Copy to take to the painting table.</li>
      <li><strong>Next Step preview</strong> - if a recipe is linked, a single italic line under the stage badge shows the first step: e.g. <em>Next: basecoat: Death Guard Green</em></li>
      <li><strong>Stage filter pills</strong> - All / Active / Done at a glance; "Active" hides finished projects</li>
      <li><strong>Search box</strong> - filters across project name, faction, and notes</li>
      <li><strong>Stage badge colours</strong>: built (grey), primed (light grey), basecoated/washed (blue), highlighted (gold), based (brown), varnished (cream), done (green); the card border-left also tints by stage</li>
      <li><strong>Paint queue pills</strong> use the same colour code as Planned: green owned, amber low, red missing/out - at a glance you know what to grab before sitting down</li>
      <li><strong>WIP photos</strong> - click any photo to open it in the lightbox</li>
    </ul>

    <h3>Forces &amp; Rosters tab</h3>
    <p>Named rosters that group your painted gallery schemes into deployable forces - for Kill Team squads, OPR armies, Blood Bowl teams, and any other game. Only visible once started in Admin.</p>
    <ul>
      <li>Each force card shows the game system badge, a progress bar (painted models vs your target), scheme thumbnails, and notes</li>
      <li><strong>Readiness progress bar</strong> - fills as your painted model count approaches the target. Model count is the actual number of miniatures (using the scheme's "Models Painted" count), not just the number of scheme entries</li>
      <li><strong>Scheme thumbnails</strong> - click any thumbnail to jump to that scheme in the Paint Schemes tab</li>
      <li><strong>W/L/D record</strong> - if Battle Honours is active and any battles are linked to this force, a win/loss/draw chip appears on the card</li>
      <li><strong>Search box</strong> - filters by force name, system, or notes</li>
    </ul>

    <h3>Battle Honours tab</h3>
    <p>Your game log - every battle recorded with result, opponent, army, mission, and notes. Only visible once started in Admin.</p>
    <ul>
      <li><strong>Filter pills</strong> - All / Win / Loss / Draw to narrow the list instantly</li>
      <li><strong>Cards</strong> show: date, colour-coded result badge (green Win / red Loss / amber Draw), your army vs your opponent and their army, game system badge, points, mission, and notes</li>
      <li><strong>Force chip</strong> - if the battle is linked to one of your Forces &amp; Rosters, the force name appears as a small chip next to your army. That force's card in Forces &amp; Rosters will show the cumulative W/L/D record</li>
      <li><strong>Search</strong> filters across opponent, army, mission, and notes</li>
      <li><strong>Global search</strong> (Ctrl+K) finds battles by matchup text and jumps directly to the card</li>
    </ul>

    <h3>Recipes tab</h3>
    <p>Your personal painting handbook - reusable technique recipes (&quot;How I Paint Ork Flesh,&quot; &quot;NMM Gold,&quot; &quot;Blood Angels Red&quot;) that schemes can reference instead of duplicating the steps. Only visible if the library has been started in Admin.</p>
    <ul>
      <li><strong>Cards</strong> show the recipe name, category badge, faction, description, and the ordered step list</li>
      <li>Each step shows a <strong>technique badge</strong> (Basecoat / Wash / Shade / Layer / Edge / Highlight / Glaze / Drybrush / Stipple / Blend / Special) plus the paint with its ownership/low/missing dot, the paint swatch, and any ratio / note / brush meta. For missing paints that have a hex colour set, a dimmed <em>≈ nearest owned</em> colour suggestion appears below the step based on RGB colour distance across your inventory</li>
      <li><strong>Search</strong> filters across recipe names, descriptions, factions, notes, and step paint names / techniques</li>
      <li><strong>Category filter pills</strong> appear automatically from whatever categories you use - only the ones in use are shown</li>
      <li><strong>&quot;Used in&quot; footer</strong> lists every gallery / planned / bench entry that references the recipe; click a gallery entry to jump to it</li>
      <li>Gallery, planned, and bench cards get <strong>&quot;Uses: [Recipe]&quot;</strong> badges when they reference recipes - click to jump straight to the recipe and watch it pulse gold</li>
      <li><strong>Pull Sheet</strong> on any scheme that uses recipes becomes a <em>sequenced step-by-step guide</em> - each recipe as a numbered section, each step labelled with technique + paint + ratio/note. Paints not covered by a recipe fall under &quot;Other paints.&quot;</li>
    </ul>

    <h3>Codices tab</h3>
    <p>A reference shelf for your Codexes and Supplements - only visible if the Codex Library has been started in Admin.</p>
    <ul>
      <li>Two-column card grid; each card has a coloured vertical spine label ("Codex" in teal, "Supplement" in green)</li>
      <li>Card body shows: faction name large in gold (the army the book covers), title, edition (e.g. "10th Edition"), publisher/credit, and notes for paint scheme references</li>
      <li>Sorted by faction then title - all your Space Marine codexes sit together, etc.</li>
      <li>Category headers in notes (lines ending in <strong>:</strong>) are rendered in gold - useful for "Painting Schemes:", "Key Colours:", etc.</li>
      <li><strong>Codex Reference badge</strong> - any gallery scheme, planned scheme, or On the Bench entry can have a <em>Codex Reference</em> field (e.g. "Codex: Death Guard p.42") which renders as a teal badge on the card, visible alongside the WD badge</li>
    </ul>

    <h3>Scrap Notes tab</h3>
    <p>A date-stamped diary of your hobby sessions - not tied to any single project. Capture a great painting evening, a technique you just figured out, or a rough session where nothing went right. Only visible if the journal has been started in Admin.</p>
    <ul>
      <li>Entries show the date (gold), mood badge, optional title, and the full body text</li>
      <li><strong>Mood badges</strong> are colour-coded: Great (green), Good (muted green), Okay (amber), Rough (red)</li>
      <li><strong>Month navigation</strong> - the tab shows one calendar month at a time. Use the <strong>&lsaquo;</strong> and <strong>&rsaquo;</strong> arrows to step back or forward a month. Click the month label to jump to a specific year</li>
      <li><strong>Search box</strong> - type anything to search across all months at once (date, title, body). Clearing the search returns to month view</li>
      <li>Entries within a month are sorted newest first</li>
      <li><strong>@mentions</strong> - in the body, type <code>@</code> to get a picker that lets you tag a Paint Scheme, Recipe, or On the Bench project. The tag renders as a clickable coloured badge that jumps straight to the referenced entry</li>
    </ul>
    <div class="tip">The journal is the narrative thread the rest of the app lacks. On the Bench notes are tied to a specific project and go away when it's done. Journal entries are for everything else - sessions, discoveries, reflections across multiple projects at once.</div>

    <h3>Equivalency tab</h3>
    <p>A cross-brand conversion reference - which Citadel paints match which Vallejo or Pro Acryl paints.</p>
    <ul>
      <li><strong>Search</strong> to find any paint by name - type "grey" to see all grey conversions, or type a specific paint name</li>
      <li>Each paint name shows a coloured status dot:</li>
    </ul>
    <div class="dot-legend">
      <div class="dot-item"><div class="dot owned"></div> Owned</div>
      <div class="dot-item"><div class="dot low"></div> Low stock</div>
      <div class="dot-item"><div class="dot out"></div> Out of stock</div>
      <div class="dot-item"><div class="dot wanted"></div> Wanted (not bought)</div>
      <div class="dot-item"><div class="dot missing"></div> Not in inventory</div>
    </div>
    <ul>
      <li><strong>Match quality badges</strong> tell you how close the conversion is: <em>near identical</em>, <em>usable</em>, or <em>avoid</em></li>
    </ul>

    <h3>Wishlist tab</h3>
    <p>Track everything you want to acquire - paints, kits, brushes, and codices. Only visible once started in Admin.</p>
    <ul>
      <li>Cards show a coloured vertical spine (type), priority badge, name, brand/faction/system, notes, and a product link if set</li>
      <li><strong>Type filter pills</strong> - filter to just Paints, Models, Brushes, or Codices</li>
      <li><strong>In Transit pill</strong> - shows only items you've already ordered but haven't received yet</li>
      <li><strong>Priority filter pills</strong> - High / Medium / Low to focus on what matters right now</li>
      <li><strong>Search box</strong> - filters across name, brand, faction, system, notes, type, and order date</li>
      <li><strong>Stock dot</strong> (paint-type items) - shows whether you already own the paint and at what stock level</li>
      <li><strong>Copy / Print buttons</strong> - export the current wishlist as plain text, grouped by type, for shopping</li>
    </ul>

    <h3>Installing as an App</h3>
    <p>On Android/Chrome, a banner will offer to install Waaagh! Paint to your home screen as a standalone app - tap <strong>Install</strong>. On iPhone: open in Safari &rarr; Share &rarr; Add to Home Screen.</p>
    <p><strong>Android home screen widget</strong> - a companion native widget (<a href="https://github.com/waaagh-co/waaaghWidget" target="_blank">WaaaghWidget</a>) shows a three-column strip on your home screen: this week's hobby minutes, your current streak in days, and models painted this year. Updates every 30 minutes automatically; tap to open the web app. Sideloaded as an APK. Enter your <code>widget.php</code> URL when the widget's config screen appears on first placement.</p>
    <p><strong>WaaaghQuick Android app</strong> - a companion app (<a href="https://github.com/waaagh-co/waaaghQuick" target="_blank">WaaaghQuick</a>) for quick data entry without opening the admin panel. Log bench sessions (pick project, set duration, add a note), browse your paint shopping list (Out / Low / Wanted grouped by status), and mark paints as purchased directly from the list. Connects to <code>api.php</code> on your server using your admin password. Sideloaded as an APK.</p>
  </div>

  <div class="divider"></div>

  <!-- ══════════════ SHOWCASE ══════════════ -->
  <div class="page-section">
    <div class="page-section-title"><span class="section-icon">🖼️</span> Public Showcase (<code>showcase.php</code>)</div>
    <p>A password-free portfolio page showing only the models you have hand-picked. Share the URL on social media, in your painting club, or at a tournament - visitors see a clean masonry photo grid with no admin controls, no inventory data, nothing but your painted work.</p>
    <ul>
      <li><strong>Enabling</strong> - set <code>SHOWCASE_PUBLIC = true</code> in <code>config.php</code>. Off by default; navigating to <code>showcase.php</code> while disabled returns a 403</li>
      <li><strong>Curating</strong> - in the Admin gallery list, each scheme now has a <strong>★</strong> button. Click it to feature that scheme; click again to remove it. Only starred entries appear on the showcase</li>
      <li><strong>Per-photo selection</strong> - for schemes with multiple photos, clicking ★ selects all photos by default and reveals a row of small image thumbnails next to the star. Each thumbnail is gold when included and dim when excluded. Click a thumbnail to toggle that individual photo in or out of the showcase. This means a single "Flesh Tearers" scheme entry with four photos can contribute four separate cards - or just the two you want to highlight</li>
      <li><strong>One card per photo</strong> - each selected photo appears as its own card in the masonry grid. Clicking any card opens the full lightbox for that scheme, starting at the photo you clicked, with arrows to browse the rest</li>
      <li><strong>System filter pills</strong> - visitors can filter the grid by game system (40k, Kill Team, AoS, etc.) without a page reload</li>
      <li><strong>Hover details</strong> - game system badge, faction, and the scheme's finish/technique summary appear on hover so the grid stays clean at first glance</li>
      <li><strong>Each model's <code>theme_hex</code></strong> drives a coloured glow on the card border on hover - schemes with a warm theme glow warm, dark schemes glow dark</li>
      <li><strong>Open Graph meta tags</strong> - the page generates <code>og:image</code>, <code>og:title</code>, and <code>og:description</code> so link previews in Discord, Slack, and social apps show a photo and caption automatically</li>
      <li><strong>Title override</strong> - set <code>SHOWCASE_TITLE</code> in <code>config.php</code> to use a different heading on the showcase page (e.g. "Ray's Painted Army"); defaults to <code>SITE_TITLE</code></li>
    </ul>
    <p>The Admin gallery section header shows a running count - <em>★ N photos across M schemes</em> - so you always know exactly what is live on the showcase without leaving admin.</p>
  </div>

  <div class="divider"></div>

  <!-- ══════════════ ADMIN ══════════════ -->
  <div class="page-section">
    <div class="page-section-title"><span class="section-icon">⚙️</span> Admin (<?= ADMIN_FILENAME ?>)</div>
    <p>A sticky quick-nav bar at the top lets you jump to any section instantly. The order follows your workflow: Recipes &rarr; Add Scheme &rarr; Edit Scheme &rarr; Planned &rarr; On the Bench, then Forces / Battle Honours, then the collection (Paint Inventory, Brush Inventory, Pile of Shame, Wishlist), then reference tools (Equivalency, Codices, Scrap Notes, Paint Checker). Links for opt-in features only appear once you have started them.</p>
    <p>All sections are <strong>collapsed by default</strong> - click a section heading or a quicknav link to expand it. Only one section is open at a time.</p>

    <h3>Hobby Stats</h3>
    <p>The first thing you see after logging in - a live summary computed from your data:</p>
    <ul>
      <li><strong>Key numbers</strong> - paints owned, recorded schemes, models painted (shown when total across all schemes exceeds the scheme count), planned schemes, books read, active brushes, recipes, journal entries (cards only appear for active features). Also: <strong>Low / Out</strong> (paints needing a restock) and <strong>Missing (Planned)</strong> (unique paints referenced in planned schemes you don't own yet - click the card to see a full list grouped by brand)</li>
      <li><strong>Collection by Brand</strong> - horizontal bars showing how many paints you own per brand</li>
      <li><strong>Waaagh! Index gauge</strong> - the momentum gauge sits between Collection by Brand and Most Used Paints; see the <em>Waaagh! Index</em> section above for details</li>
      <li><strong>Gallery by Faction</strong> - scheme counts per faction with model counts; year breakdown appears once you have schemes across multiple years</li>
      <li><strong>Most Used Paints</strong> - top 8 paints ranked by how many gallery schemes reference them</li>
      <li><strong>Tab Visits</strong> - horizontal bars showing how many times each tab has been clicked; appears automatically once you've used the site (no setup needed)</li>
      <li><strong>Annual Goals (By Year)</strong> - each year row has a <strong>+ Goal</strong> (or pencil) button that opens an inline form with two fields: <em>Target</em> (how many models you want to paint this year) and <em>Baseline</em> (models you'd already painted before you started logging sessions - use this if you back-logged entries and need an accurate starting count). Saving writes a progress bar + painted/target count beneath the year. The current year always shows even if nothing is logged yet. When your session count hits the target, a <strong>Goal reached!</strong> badge appears and the bar fills gold. The goal also shows as a slim progress strip on the landing page of the main site. Remove a goal with the <strong>&times;</strong> button.</li>
    </ul>

    <h3>Add a Scheme</h3>
    <p>Add or edit a completed painting scheme.</p>
    <ul>
      <li><strong>Model Name</strong> - required</li>
      <li><strong>Faction / Army</strong> - optional; enables faction filtering on the main site and populates the Factions tab</li>
      <li><strong>Game System</strong> - optional; 40k / Horus Heresy / Age of Sigmar / Kill Team / Blood Bowl / Necromunda / One Page Rules / Other. Shows as a coloured badge on the card and enables system filtering. Also determines which Forces &amp; Rosters this scheme can be added to</li>
      <li><strong>Date Completed</strong> - optional; gallery sorts newest first</li>
      <li><strong>Models Painted</strong> - optional count; use this when one scheme covers a whole squad or unit (e.g. 10 Guardsmen). Shown as a <strong>&times;N</strong> badge on the card and summed in hobby stats</li>
      <li><strong>Notes / Description</strong> - structured text that renders as labelled step rows on the card. Format: ALL CAPS lines become section headers; <code>- Label: value</code> lines render as Cinzel label + large value; <code>&nbsp;&nbsp;- item</code> (2+ leading spaces) becomes a recessed sub-bullet. A format hint is shown below the textarea in admin</li>
      <li><strong>Summary fields</strong> (Finish / Primary / Contrast / Technique Bias) - optional one-line spec block shown above the description on the gallery card. Use these for a quick-scan overview: e.g. Finish: "Worn, field-used", Technique: "Sponge texture, oil wash, pigments"</li>
      <li><strong>Photos</strong> - up to 4 images per entry; tap or drag to upload. Images are resized to 1000px on the longest side at high quality on the server</li>
      <li><strong>Colours Used</strong> - scrollable paint picker; type in the filter box to narrow it down; click paints to select/deselect (they turn gold). These power the colour pills, pull sheets, and the "used in" count on the main site</li>
      <li><strong>Recipes</strong> - optional; link one or more recipes from the Recipe Library. Linked recipes turn the pull sheet into a sequenced painting guide</li>
    </ul>

    <h3>Edit Scheme</h3>
    <p>A scrollable list of all gallery entries with thumbnail, name, faction, and paint counts.</p>
    <ul>
      <li><strong>+ Log</strong> - records a painting session against that gallery entry: date, how many models you finished in that session, and an optional note. Session counts accumulate toward your annual goal progress. This is different from the "Models Painted" count field on the entry itself (which is a lifetime total) - sessions are how you track what you completed <em>this year</em></li>
      <li><strong>Edit</strong> - modify the entry; all logged sessions are preserved</li>
      <li><strong>Delete</strong> - removes the entry with confirmation</li>
    </ul>
    <div class="tip">The annual goal on the Stats page is driven by session logs (+ Baseline seed), not by the entry date or the "Models Painted" count. Log a session each time you finish a batch of models to keep your progress accurate.</div>

    <h3>Paint Catalog</h3>
    <p>A browse-and-import catalog of every paint in the bundled brand CSV files - ideal for new users who want to quickly populate their inventory without typing every paint manually. Accessible at <code>catalog.php</code> (same admin password) and linked from the admin quicknav under Tools.</p>
    <ul>
      <li><strong>Filter bar</strong> - narrow by brand, colour category, layer type, or free-text search across name and hue</li>
      <li><strong>Hex swatches</strong> - pre-populated from the bundled <code>inventory/paint_hex.json</code> reference; paints without a known hex show a category-colour fallback swatch</li>
      <li><strong>Owned indicator</strong> - paints already in your inventory are greyed out and pre-checked (but can't be re-imported). Toggle <em>Hide owned</em> to focus on what you still need to add</li>
      <li><strong>Select visible / Deselect all</strong> - bulk-select all paints matching the current filters, then hit <strong>Import Selected</strong> to add them to your inventory in one go. Hex values are carried through on import so your Colour Wheel works immediately</li>
      <li><strong>Sortable columns</strong> - click Brand, Name, Colour, or Layer headers to sort</li>
    </ul>

    <h3>Paint Inventory</h3>
    <p>Manage your full paint list.</p>
    <ul>
      <li><strong>Import from CSVs</strong> - first-time setup only: reads all brand CSV files and builds your inventory. The button disappears once the inventory exists</li>
      <li><strong>Search and brand filter</strong> - find paints quickly in the table</li>
      <li><strong>+ Add Paint</strong> - opens an inline form: Brand, Name, Colour Category, Hue, Layer/Type, and Swatch Hex</li>
      <li><strong>Swatch Hex</strong> - click the colour box or type a <code>#rrggbb</code> value. This drives the Colour Match tab and the more accurate paint-table swatch in admin. Skip it if you don't care about colour matching for that paint</li>
      <li><strong>Apply Hex Seed</strong> - appears only when <code>data/paint_hex_seed.json</code> exists. Bulk-fills hex values for any paints that don't have one yet (never overwrites your manual edits). Delete the seed file after running if you're done with it. Header strip shows "X of Y paints have hex values"</li>
      <li><strong>Stock toggle</strong> (the dot button next to each paint) - click to cycle through normal &rarr; low &rarr; out &rarr; wanted &rarr; normal</li>
      <li><strong>Quality Rating</strong> - optional 1-5 star rating in the add/edit form. Stars appear in the paint table and in the notes drawer on the main site. Use this to flag paints that are a joy to work with (5 stars) vs ones you tolerate (1-2)</li>
      <li><strong>Notes</strong> - optional freeform text in the add/edit form (consistency tips, thinning ratios, brand quirks). Shows in the notes drawer on the main site via the pencil icon</li>
      <li><strong>Edit / &times;</strong> - inline edit form or delete with confirmation</li>
    </ul>

    <h3>Brush Inventory</h3>
    <p>Click <strong>Start Brush Inventory</strong> to activate it - creates the data file and makes the Brushes tab appear on the main site. Once active:</p>
    <ul>
      <li><strong>+ Add Brush</strong> - enter brand (required), series/line, size, material, primary use, condition, date started (YYYY-MM), and notes</li>
      <li><strong>Condition toggle button</strong> - click on any entry to cycle through Prime &rarr; Workhorse &rarr; Retired &rarr; Prime without a page reload; the table re-sorts automatically</li>
      <li><strong>Condition levels</strong> - <em>Prime</em> = in great shape, <em>Workhorse</em> = still useful but showing wear, <em>Retired</em> = worn out; entries sort Prime first, then Workhorse, then Retired</li>
      <li>Use the notes field for anything worth recording - stiffness, split tips, what it's good for now that it's worn</li>
      <li><strong>Edit / &times;</strong> - update or remove any entry</li>
    </ul>

    <h3>Supplies</h3>
    <p>Click <strong>Start Supplies Inventory</strong> to activate it - creates the data file and makes the Supplies tab appear on the main site. Once active:</p>
    <ul>
      <li><strong>+ Add Supply</strong> - enter name (required), brand, type (free text with suggestions: palette, wet-palette, dry-palette, cutting-mat, lamp, holder, storage, tool, other), condition, acquired date (YYYY-MM), and notes</li>
      <li><strong>Condition toggle button</strong> - cycles Prime &rarr; Workhorse &rarr; Retired &rarr; Prime without a page reload; same pattern as brushes</li>
      <li>Entries sort by condition rank (Prime first), then by type, then by name</li>
      <li><strong>Edit / &times;</strong> - update or remove any entry</li>
    </ul>

    <h3>Paint Checker</h3>
    <p>Paste a list of paint names and instantly see what you own.</p>
    <ul>
      <li>Select a brand from the dropdown</li>
      <li>Paste paint names, one per line, into the text box</li>
      <li>Hit <strong>Check List</strong></li>
    </ul>
    <p>Each paint gets a status: ✓ owned, ▲ low stock, ✗ out, ◇ wanted, or ✗ not found. For missing or unavailable paints the checker suggests the closest substitute you <em>do</em> own - checking the equivalency table first, then falling back to same-colour-category matches from another brand.</p>

    <h3>Hobby Wishlist</h3>
    <p>Click <strong>Start Hobby Wishlist</strong> to activate it. Once active:</p>
    <ul>
      <li>Add items of any type (Paint, Model, Brush, Codex, WD) with name, brand/faction/system (type-dependent), priority, product URL, and notes</li>
      <li><strong>Mark Ordered button</strong> - click on any wishlist row to stamp today's date as the order date, without opening the edit form. The row switches to show an amber "Ordered YYYY-MM-DD" badge so you know it's in transit. Click <em>Clear</em> to remove the order date if it arrives or you cancel</li>
      <li><strong>Order Date field</strong> - also available in the add/edit form if you want to back-date an order or set a specific date</li>
      <li><strong>Promote to Shame</strong> (model-type items only) - when the kit arrives, promote it directly to the Pile of Shame. The wishlist entry is kept with a gold "Promoted" badge for history</li>
      <li>All items are visible in the Wishlist tab on the main site, where they can be filtered by type, priority, or "In Transit" status</li>
    </ul>
    <div class="tip">The workflow: Add to Wishlist &rarr; click "Mark Ordered" when you buy it &rarr; when the box arrives, promote models to Pile of Shame (or clear the order date for paints/brushes and update your inventory separately).</div>

    <h3>Pile of Shame</h3>
    <p>Click <strong>Start Pile of Shame</strong> to activate it. Once active:</p>
    <ul>
      <li>Add boxes with: Name, System (40k / 30k / HH / AoS / Epic / Other), Faction, Count (model count in box), Status (Sealed / Opened / Partial), Acquired (YYYY-MM), Notes</li>
      <li><strong>Promote to Planned</strong> - creates a new entry in Planned Schemes (name + faction pre-filled)</li>
      <li><strong>Promote to Bench</strong> - creates a new On the Bench entry at the Built stage (name + faction pre-filled)</li>
      <li>Promoted entries are kept in the shame list with a gold "Promoted" badge - the history stays intact</li>
      <li>Sorted oldest acquisition first so the longest-sitting boxes bubble to the top</li>
    </ul>

    <h3>Rescue Tracker</h3>
    <p>Click <strong>Start Rescue Tracker</strong> to activate it - creates the data file and makes the Rescue Tracker tab appear on the main site. Once active:</p>
    <ul>
      <li><strong>+ Add Rescue</strong> - name (required), faction, game system, model count, source (eBay / Trade / LGS / Gift / Other), strip difficulty (Bare Metal / Primed Only / Light Paint / Medium Paint / Heavy Paint), acquired date (YYYY-MM), current stage, notes, and up to 4 before-photos</li>
      <li><strong>Stage cycle button</strong> on each entry - click to advance through the linear workflow: <em>Bidding &rarr; In Transit &rarr; Received &rarr; Stripping &rarr; Prepped</em>. Stops at Prepped - use the Promote buttons to move it forward from there</li>
      <li><strong>Before photos</strong> - 4 positional slots. Upload to capture the condition on arrival; photos persist through the full lifecycle so you can compare before and after</li>
      <li><strong>Promote to Bench</strong> - creates a new On the Bench entry at the Built stage with the name, faction, system, and count pre-filled. Sets a "Promoted &rarr; Bench" badge on the rescue card</li>
      <li><strong>Promote to Shame</strong> - creates a Pile of Shame entry if you want to hold off before painting. Sets a "Promoted &rarr; Shame" badge on the rescue card</li>
      <li>Active rescues (not yet promoted) are sorted by stage order first (so Prepped rescues bubble to the top), then by acquisition date oldest first</li>
      <li><strong>Edit / &times;</strong> - update or remove any entry; deleting also removes the before-photo files</li>
    </ul>
    <div class="tip">Model counts from active rescues appear in the Total Units Tracked stat card so you always have an accurate picture of how many minis you're responsible for - even the ones still in a bath of Dettol.</div>

    <h3>Planned Schemes</h3>
    <p>Build a paint scheme for a model you haven't started yet.</p>
    <ul>
      <li>Add a name, kit, faction, game system, notes, and tag paints from the colour picker</li>
      <li><strong>Recipes</strong> - optionally link recipes the same way as gallery entries</li>
      <li>Plans appear on the Planned tab of the main site and feed into the Shopping List</li>
      <li>Edit or delete any scheme from the list below the form</li>
    </ul>

    <h3>On the Bench</h3>
    <p>Active painting projects in flight - track each model from primer through "done."</p>
    <p>Click <strong>Start Workbench</strong> to activate it - creates the data file and makes the On the Bench tab appear on the main site. Once active:</p>
    <ul>
      <li><strong>+ Add Bench Entry</strong> - name (required), faction, game system, current stage, date started, notes, paint queue (same picker style as planned schemes), brushes (multi-select pills, non-retired only), and up to 8 WIP photos in the photo grid</li>
      <li><strong>Stage cycle button</strong> on each entry - click to advance: Built &rarr; Primed &rarr; Basecoated &rarr; Washed &rarr; Highlighted &rarr; Based &rarr; Varnished &rarr; Done &rarr; back to Built; cycles in place without a page reload, stamps "last touched" automatically, and logs the transition to a stage history on the entry</li>
      <li><strong>+ Session</strong> on each bench entry - logs a painting session for that project: date, duration in minutes (optional), and notes. This tracks <em>time spent</em> on a WIP project and feeds the "Sessions" and "Hobby Hours" stat cards. It does <em>not</em> affect your annual model count goal - use <strong>+ Log</strong> on Existing Entries (gallery) for that</li>
      <li><strong>Stage colours</strong> match the front-end: Built (grey) &rarr; Primed (light grey) &rarr; Basecoated/Washed (blue) &rarr; Highlighted (gold) &rarr; Based (brown) &rarr; Varnished (cream) &rarr; Done (green)</li>
      <li><strong>WIP photos</strong> - 8 positional slots. Upload to a slot to add, tick the Delete checkbox in a slot to clear it</li>
      <li><strong>Edit / &times;</strong> - update or remove any entry; deleting also removes the photo files</li>
      <li>Entries sort with non-Done first, then by most recently touched</li>
    </ul>

    <h3>Forces &amp; Rosters</h3>
    <p>Group your painted schemes into named rosters for specific games. Click <strong>Start Forces &amp; Rosters</strong> to activate it. Once active:</p>
    <ul>
      <li><strong>+ Add Force</strong> - name the roster (required), pick a game system, set a target model count, and check off which gallery schemes belong to this force from the scheme picker</li>
      <li><strong>Target Models</strong> - optional; sets the denominator for the readiness progress bar on the main site. Leave blank if you don't need a completion target</li>
      <li><strong>Game System</strong> - optional; one of: 40,000 / Horus Heresy / Age of Sigmar / Kill Team / Blood Bowl / Necromunda / One Page Rules / Other. Drives the coloured system badge</li>
      <li>The model count shown on the main site sums the actual miniature count from each linked scheme (the "Models Painted" field), not just the number of scheme entries - so a scheme with 12 Ork Kommandos counts as 12, not 1</li>
      <li><strong>Edit / &times;</strong> - update or remove any roster</li>
    </ul>

    <h3>Battle Honours</h3>
    <p>Click <strong>Start Battle Honours</strong> to activate it. Once active, every game you play can be logged here.</p>
    <ul>
      <li><strong>Date &amp; Result</strong> (required) - the date and whether you won, lost, or drew</li>
      <li><strong>My Army</strong> - free text; what you fielded (e.g. "Death Guard")</li>
      <li><strong>Linked Force</strong> - optionally link the battle to one of your Forces &amp; Rosters. Once linked, the force card on the main site shows a cumulative W/L/D record chip</li>
      <li><strong>Game System</strong> - optional; drives the coloured system badge on the card</li>
      <li><strong>Points</strong> - optional game size</li>
      <li><strong>Opponent &amp; Opponent's Army</strong> - who you played and what they brought</li>
      <li><strong>Mission</strong> - optional; the mission or scenario name</li>
      <li><strong>Notes</strong> - freeform; how it went, key moments, what to do differently</li>
      <li><strong>Edit</strong> - click Edit on any row to pre-fill the form; <strong>Cancel</strong> returns to add mode without saving</li>
    </ul>
    <div class="tip">Linking battles to Forces is optional but recommended if you want to track a force's record over time - the W/L/D chip on the force card is a nice at-a-glance stat.</div>

    <h3>Recipe Library</h3>
    <p>Reusable technique recipes - author "How I Paint X" once and reference it from every model that uses it.</p>
    <p>Click <strong>Start Recipe Library</strong> to activate - creates the data file and reveals the Recipes tab on the main site. Once active:</p>
    <ul>
      <li><strong>+ Add Recipe</strong> - enter a name, optional category (Flesh / Metal / Cloth / Armour / etc.), optional faction, short description, and your ordered steps</li>
      <li><strong>Step builder</strong> - each step has: the paint (autocompletes from your inventory), technique (basecoat, wash, layer, edge, etc.), optional ratio (&quot;2:1 water&quot;), optional note, optional brush (autocompletes from your non-retired brushes). Use the <strong>&uarr; &darr;</strong> arrows to reorder, <strong>&times;</strong> to remove, <strong>+ Add Step</strong> to append</li>
      <li><strong>Referencing recipes from schemes</strong> - Gallery / Planned / Bench forms show a <strong>Recipes</strong> picker with one pill per recipe. Click to toggle; selected recipes are saved with the scheme</li>
      <li><strong>Delete safety</strong> - deleting a recipe doesn't break schemes that reference it; the reference silently drops</li>
      <li>When a scheme with recipes uses the <strong>Pull Sheet</strong>, it becomes a sequenced step-by-step painting guide grouped by recipe, instead of a flat brand-grouped checklist</li>
    </ul>
    <div class="tip">Keep recipes <em>universal</em> where possible (&quot;Ork Skin,&quot; &quot;NMM Gold&quot;) so they compose across models. Scheme-specific variants (&quot;Death Guard Armour&quot;) work fine too - use the category/faction fields to tell them apart.</div>

    <h3>Codex Library</h3>
    <p>Click <strong>Start Codex Library</strong> to activate it. Codex-only reference shelf. Once active:</p>
    <ul>
      <li><strong>Type</strong> - Codex / Army Book or Supplement / Campaign; drives the spine label colour on the main site</li>
      <li><strong>Faction</strong> - the army the book covers; shown large and gold on the card (most important field)</li>
      <li><strong>Title</strong> - the book title (e.g. "Codex: Death Guard")</li>
      <li><strong>Publisher / Credit</strong> - optional author or publisher credit line</li>
      <li><strong>Edition</strong> - e.g. "10th Edition"; shown below the title on the card</li>
      <li><strong>Notes</strong> - use for paint scheme references, key colour page numbers, anything you'd want to find quickly</li>
      <li><strong>Edit / &times;</strong> - update or remove any entry</li>
    </ul>
    <div class="tip">Category headers in notes (e.g. <strong>Painting Schemes:</strong> or <strong>Key Colours:</strong>) render in gold on the main site - great for structuring codex paint notes.</div>

    <h3>Scrap Notes</h3>
    <p>A running diary of hobby sessions, not tied to any specific project. Click <strong>Start Hobby Journal</strong> to activate it (displays as "Scrap Notes" on the main site). Once active:</p>
    <ul>
      <li><strong>+ Add Entry</strong> - date (defaults to today), optional mood (Great / Good / Okay / Rough), optional title, and the entry body (required)</li>
      <li>Use the body for anything: what you painted, a technique discovery, a session debrief across multiple projects, or just a note that you sat down and it didn't go anywhere</li>
      <li>Type <code>@</code> in the body to open the mention picker - tag a Paint Scheme, Recipe, or On the Bench project by name</li>
      <li>Entries sort newest first; the entry list is scroll-capped in admin so it doesn't swamp the page</li>
      <li><strong>Filter box</strong> above the entry list - type a date or keyword to find a specific entry instantly without scrolling through everything</li>
      <li><strong>Edit / &times;</strong> - update or remove any entry</li>
    </ul>

    <h3>Export &amp; Import Backup</h3>
    <p>The <strong>Export Backup</strong> button in the sidebar footer downloads a single JSON file containing all your data - paints, schemes, planned, bench, recipes, brushes, journal, Codex Library, shame pile, wishlist, forces, battles, supplies, rescues, goals, and tab stats. Images are not included.</p>
    <p>The <strong>Import Backup</strong> button (next to Export) reveals a file upload form. Choose a previously exported backup JSON and click Restore - each recognised data file is overwritten with the backup contents. A flash message confirms what was restored. Images are not restored (they live on disk and are not included in the backup).</p>

    <h3>config.php settings</h3>
    <p>All instance settings live in <code>config.php</code>. In addition to the standard identity and password fields, the following are available:</p>
    <ul>
      <li><code>HERO_IMAGE</code> - web-relative path to the mast image on the landing page (default: <code>img/looted.png</code>)</li>
      <li><code>SITE_TAGLINE</code> - the italic line beneath the mast image (default: "Dis is not a blog...")</li>
      <li><code>SHOW_HEATMAP</code> - set to <code>false</code> to hide the Hobby Activity heatmap on the landing page</li>
      <li><code>SHOW_WC_NEWS</code> - set to <code>false</code> to hide the Latest from Warhammer Community widget</li>
      <li><code>SHOW_COMMISSAR</code> - set to <code>false</code> to hide The Commissar's Dossier tab entirely (sidebar link and tab panel)</li>
    </ul>
    <p>All feature toggles default to <code>true</code> if omitted from config, so existing installs without these lines are unaffected.</p>

    <div class="tip">The main site is read-only - all edits and additions go through admin.</div>
  </div>
</main>

<footer>Waaagh! Paint - Personal Hobby Tool</footer>

</body>
</html>
