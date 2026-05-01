# Waaagh! Paint - Project Notes

## What this is
A personal Warhammer 40k hobby paint collection manager. Single-developer, personal use. PHP flat-file app, no framework, no database.

## File structure
```
index.php                - entire front-end app (tabs: What's Inside [default landing], Paint Inventory, [Brushes,] Paint Schemes, Equivalency, Color Match, [Recipes,] Planned, [On the Bench,] [White Dwarf,] [Codices])
admin.php                - password-protected admin (stats, gallery, inventory, brushes, paint checker, planned schemes, [bench,] [recipes,] [White Dwarf,] [Codices])
guide.html               - static user guide page; linked from admin quicknav bar (opens in new tab)
favicon.ico              - generated from logo_sm.png (16x16 + 32x32 PNG-in-ICO)
data/models.json         - flat-file store for gallery/scheme entries
data/paints.json         - flat-file paint inventory (written by admin; read by both index + admin)
data/planned.json        - flat-file planned schemes (written by admin; read by index)
data/whitedwarf.json     - optional; White Dwarf magazine log (only exists on instances that use it; absence hides the feature entirely)
data/books.json          - optional; Codex Library (army books, supplements, campaigns; same opt-in pattern as whitedwarf.json)
data/brushes.json        - optional; brush inventory (same opt-in pattern as whitedwarf.json)
data/bench.json          - optional; active workbench projects (same opt-in pattern; tracks WIP through stages)
data/recipes.json        - optional; reusable technique recipes ("How I paint X") referenced by ID from gallery/planned/bench entries
data/journal.json        - optional; Hobby Journal entries (same opt-in pattern as whitedwarf.json)
data/shame.json          - optional; Pile of Shame box tracking (same opt-in pattern; tracks unbuilt/unprimed kits before planning)
data/forces.json         - optional; Forces & Rosters - named game rosters grouping gallery schemes into deployable forces (same opt-in pattern)
data/tab_stats.json      - auto-created on first tab click; cumulative visit counts per tab (no manual setup needed)
data/paint_hex_seed.json - optional one-time seed of `Brand|Name|Layer → #hex` values; admin's "Apply Hex Seed" button merges into paints.json and the file can then be deleted
img/logo.png             - original logo (2.3 MB, do not use directly)
img/logo_sm.png          - resized logo (600x400, 195 KB PNG - must stay PNG for alpha/glow)
img/models/              - uploaded model photos (written by admin.php)
img/bench/               - uploaded WIP bench photos (written by admin.php; auto-created on load)
inventory/*.csv          - source CSV files; imported into paints.json via admin
inventory/conversions.csv - Citadel ↔ Vallejo ↔ Pro Acryl equivalency table (used by Equivalency tab + substitute suggestions)
```

## Inventory CSV format
Five pipe-delimited fields, space-padded: `Brand | Name | Color | Hue | Layer`

- **Color** - broad colour category (White, Grey, Black, Brown, Red, Orange, Yellow, Green, Blue, Purple, Pink, Metallic, Wash, Shade, Contrast, Ink, Effect, Medium, Texture, Primer, Pigment, Fluid, Utility, Fluorescent, Special, Transparent)
- **Hue** - descriptive variant (e.g. "Pure White", "Warm Earth")
- **Layer** - paint type / line (Base, Contrast, Shade, Air, Technical, Metallic, Transparent, Fluorescent, Special, Speedpaint, Model Color, Model Air, Ink, Varnish, X-Opaque, Oil, …)
- Blank separator lines between colour groups are fine - the parser skips rows with fewer than 5 non-empty fields

Current brands / files:
| File | Brand string in CSV |
|------|-------------------|
| citadel.csv | `Citadel` |
| proacryl.csv | `Pro Acryl` |
| vallejo.csv | `Vallejo` |
| armypainter.csv | `Army Painter` |
| oils.csv | `Gamblin Artist Oils` |
| - (manual only) | `AK Interactive` |
| - (manual only) | `Scale75` |
| - (manual only) | `Two Thin Coats` |

## conversions.csv format
Five pipe-delimited fields: `Citadel | Vallejo Equivalent | ProAcryl Equivalent | Two Thin Coats Equivalent | Match Quality`

- Header row is skipped by the parser (first field = "Citadel" triggers skip)
- `-` means no equivalent for that brand
- Match quality values: `near identical`, `usable`, `avoid`
- Used in two places: Equivalency Search tab (display) and Paint Checker substitute suggestions (avoid entries skipped there)
- admin.php builds bidirectional Citadel↔Vallejo, Citadel↔ProAcryl, and Citadel↔TwoThinCoats maps

## paints.json schema
```json
[
  {
    "brand": "Citadel",
    "name": "Mephiston Red",
    "color": "Red",
    "hue": "Base Red",
    "layer": "Base",
    "stock": "low",
    "hex": "#a02020",
    "stars": 4,
    "notes": "Freeform text"
  }
]
```

Unique key is `brand|name|layer`. The import deduplicates by this key (first occurrence wins), so duplicate rows in CSVs are silently dropped. After import, paints are sorted by `brand + name`.

The JS color key used in `models.json` and `planned.json` colors arrays is also `brand|name|layer`. Legacy 2-part `brand|name` keys in stored data are upgraded transparently at runtime via `upgradeKey()` (unambiguous matches only). The admin picker labels show the layer as a disambiguator when two paints share the same brand+name (e.g., "Leadbelcher (Citadel - Base)" vs "Leadbelcher (Citadel - Primer)").

`stock` is optional - omitted when normal, `"low"`, `"out"`, or `"wanted"` when flagged. Set via the stock toggle button in admin (cycles `'' → low → out → wanted → ''`). `wanted` means catalogued but not yet purchased - shows in color pickers but counts as "missing" everywhere stock is checked.

`hex` is optional - `#rrggbb` representing the dried-paint colour, used by the Color Match tab and (when present) the admin paint-table swatch. Validated against `/^#[0-9a-f]{6}$/`. Set via the colour picker in the admin add/edit form, or bulk-seeded via the "Apply Hex Seed" button which merges entries from `data/paint_hex_seed.json` into paints lacking a hex (never overwrites existing values).

`stars` is optional - integer 1-5 quality rating (5 = perfect paint). Omitted when unset. Editable only in admin (add/edit form uses `.bsp-star` pill picker, stored via `p_stars` hidden input, same pattern as brush stars). Read-only on index.php - shown in the notes slide-up drawer via `drawerStarSet()` visual helper (no click events, no write path on the public side).

`notes` is optional - freeform text about the paint (e.g. consistency tips, brand-specific behaviour). Editable only in admin via a textarea in the add/edit form. Read-only on index.php - shown in the notes slide-up drawer accessed via the pencil icon on each paint row. **index.php is a public read-only view and must never write data** - all paint edits (notes, stars, stock, hex) go through admin.php only.

## Paint inventory workflow
- **First run**: admin.php → scroll to "Paint Inventory" → click "Import N Paints from CSVs"
  - Reads all 5 CSVs, deduplicates, writes `data/paints.json`
  - Both `index.php` and `admin.php` then read from `paints.json` (CSVs become source-of-truth backup only)
- **Add paint**: "+ Add Paint" button → inline form → submit
- **Edit paint**: "Edit" button on any row → inline form pre-fills → "Save Changes"
- **Delete paint**: "×" button → confirm → removed
- **Re-import**: "Re-import from CSVs" button - **replaces all manual edits** with fresh CSV parse

## Adding a new brand
1. Create `inventory/<brand>.csv` in the 5-column format above (or skip if manual-only)
2. Add the path to `$paths` inside `loadPaintsFromCsvs()` in `admin.php` (if CSV exists)
3. Add the brand `<option>` to `#filter-brand` in `index.php`
4. Add the brand `<option>` to the brand filter datalist in `admin.php`
5. Add an `<optgroup>` for any new Layer types to `#filter-layer` in `index.php`
6. Add `tr.brand-<slug> td` background tint CSS in **both** `index.php` and `admin.php` (`.paint-table` section)
7. Add `.badge-<LayerType>` in `index.php` and `.pb-<LayerType>` in `admin.php` for any new layer types
8. Re-import from CSVs in admin to pull in the new brand (or add manually)

## models.json schema
```json
[
  {
    "id": "1234567890",        // time() string, used as filename prefix for images
    "name": "Model Name",
    "faction": "Army Name",    // optional - drives faction-tag filter on gallery
    "system": "40k",           // optional - game system; "40k" | "30k / HH" | "AoS" | "Kill Team" | "Blood Bowl" | "Necromunda" | "OPR" | "Other"
    "date": "YYYY-MM-DD",      // optional
    "description": "Notes…",  // optional
    "images": [                // web-relative paths, up to 4, stored positionally
      "img/models/1234567890_1.jpg"
    ],
    "colors": [                // "Brand|PaintName" strings
      "Citadel|Mephiston Red"
    ],
    "count": 20,               // optional; how many miniatures painted under this scheme. Absent/1 means "one model". Only persisted when > 1
    "summary": {               // optional; 4-field spec block shown above description on gallery cards
      "finish":    "Worn, field-used",
      "primary":   "Muted green over dark base",
      "contrast":  "Grey/red camo, dark tracks",
      "technique": "Sponge texture, oil wash, pigments"
    }
  }
]
```

All keys except `id` and `name` are optional. The `summary` sub-object is also optional; any of its four keys (`finish`, `primary`, `contrast`, `technique`) may be omitted and the remainder will still render. `array_filter` strips empty strings so blank fields are never stored. The `array_filter` in the save handler omits empty-string or empty-array keys rather than saving them as null.

The `count` field is read as `max(1, (int)$m['count'] ?? 1)` everywhere it's summed, so existing entries without the key silently count as 1. Admin stats sum counts for "Models Painted" (only shown when the sum exceeds scheme count), the By-Year and By-Faction rollups sum counts, and the index scheme card + landing "Schemes" line get an extra "N models painted" when the sum differs from the scheme count.

## planned.json schema
```json
[
  {
    "id": "1234567890",
    "name": "Scheme Name",
    "model": "Model kit name",   // optional
    "faction": "Army Name",      // optional
    "system": "40k",             // optional - game system; "40k" | "30k / HH" | "AoS" | "Kill Team" | "Blood Bowl" | "Necromunda" | "OPR" | "Other"
    "description": "Notes…",    // optional
    "wd_source": "513",          // optional - WD issue number; renders as a clickable badge on the card
    "codex_source": "Codex: Death Guard p.42",  // optional - free text codex reference; renders as teal badge
    "colors": [                  // "Brand|PaintName" strings
      "Citadel|Mephiston Red"
    ]
  }
]
```

Sorted by name. Used by the Planned tab in index.php and the Planned Schemes section in admin. The `wd_source` badge links to the White Dwarf tab and pre-filters to that issue number.

## whitedwarf.json schema
```json
[
  {
    "id": "1234567890",
    "issue": "500",
    "format": "both",       // "physical" | "digital" | "both"
    "month": "2024-01",     // optional, YYYY-MM
    "notes": "p.42 - Blood Angels scheme…"  // optional, freeform multiline
  }
]
```

Sorted by issue number descending. **Opt-in**: the file must exist for the feature to appear - if `data/whitedwarf.json` is absent, the White Dwarf tab and admin section are completely hidden. Create via admin ("Start White Dwarf Log" button). Not linked to the paint inventory - pure freeform notes for magazine reference. Tab layout: CSS grid 2 columns (`#wd-list`), collapses to 1 column at ≤600px.

## books.json schema
```json
[
  {
    "id": "1234567890",
    "title": "Codex: Death Guard",
    "type": "codex",                   // "codex" | "supplement"; always stored (no default omission)
    "author": "Games Workshop",        // optional - publisher/credit
    "series": "10th Edition",          // optional - edition (e.g. "10th Edition", "9th Edition")
    "faction": "Death Guard",          // optional - free text; faction/legion tag; prominent on cards
    "notes": "Freeform notes…"         // optional; paint scheme references, page notes, etc.
  }
]
```

`type` is always stored (`"codex"` or `"supplement"`); novel/status/date_read fields are no longer used. `bookSort()` sorts by faction ascending then title ascending. **Opt-in**: same pattern as `whitedwarf.json` - file must exist for the tab and admin section to appear. Create via admin ("Start Codex Library" button). Tab display name: **Codices**; admin section heading: **Codex Library**.

## journal.json schema
```json
[
  {
    "id": "1234567890",
    "date": "2026-04-21",
    "title": "Found a better wet-blending ratio",  // optional
    "body": "Spent the evening on the Death Guard…",
    "mood": "good"                                  // optional - "great" | "good" | "okay" | "rough"
  }
]
```

`journalSort()` sorts by `date` descending, then `id` descending (newest same-day entry first). `body` is required; `title` and `mood` are optional and omitted from JSON when blank. **Opt-in**: file must exist for the tab and admin section to appear. Create via admin ("Start Hobby Journal" button). Journal data is embedded as `JOURNAL_DATA` JS constant at page load (same pattern as `SHAME_DATA` / `FORCES_DATA`). Previously lazy-fetched; changed to embedded so that `.htaccess` restrictions on `/data/` do not break the tab.

## shame.json schema
```json
[
  {
    "id": "1234567890",
    "name": "Death Guard Plague Marines",
    "system": "40k",               // "40k" | "30k / HH" | "AoS" | "Kill Team" | "Blood Bowl" | "Necromunda" | "Epic" | "OPR" | "Other"
    "faction": "Death Guard",      // optional
    "count": 7,                    // optional int - model count in box
    "status": "sealed",            // "sealed" | "opened" | "partial"
    "acquired": "2024-03",         // optional YYYY-MM - when the box was purchased
    "notes": "Picked up at Adepticon",  // optional
    "promoted_to": "planned"       // optional - set when promoted; "planned" | "bench"
  }
]
```

`shameSort()` sorts by `acquired` ascending (oldest first; nulls/empty last), then `id` ascending. `name` and `status` are required; all other fields optional. `promoted_to` is set (never deleted) when the box is promoted - the entry is kept for history. **Opt-in**: file must exist for the tab and admin section to appear. Create via admin ("Start Pile of Shame" button). `SHAME_DATA` is embedded as a JS constant at page load (data is small and needed immediately for card rendering, unlike Journal).

## forces.json schema
```json
[
  {
    "id": "1234567890",
    "name": "Ork Kommandos - Kill Team",
    "system": "Kill Team",          // optional - "40k" | "30k / HH" | "AoS" | "Kill Team" | "Blood Bowl" | "Necromunda" | "OPR" | "Other"
    "target_models": 10,            // optional int - target model count for readiness progress bar
    "models": [                     // array of gallery model IDs (from models.json `id` field)
      "1776362488"
    ],
    "notes": "Freeform notes"       // optional
  }
]
```

`forcesSort()` sorts by name ascending. The `models` array references gallery entry IDs - if a referenced ID is later deleted, rendering silently skips it (no error). **Painted count** is computed by summing the `count` field on each referenced gallery entry (`Math.max(1, parseInt(m.count || 1, 10))`), not just counting IDs - so a scheme with `count: 12` contributes 12 painted models. **Readiness progress bar** shows painted/target_models percentage (capped at 100%). `MODEL_BY_ID = new Map(MODELS.map(m => [m.id, m]))` used for O(1) lookups. **Opt-in**: file must exist for the tab and admin section to appear. Create via admin ("Start Forces & Rosters" button). `FORCES_DATA` is embedded as a JS constant at page load (like SHAME_DATA, not lazy-fetched). POST handlers: `create_forces_file`, `add_force`, `edit_force` (combined branch via `in_array`), `delete_force`. Edit mode uses `?edit_force=ID` GET param (same pattern as `?edit=ID` for gallery) - body tag gets `data-open-section="section-forces"` when `$editForce` is set.

**Model picker in admin**: PHP-rendered checkboxes in a scrollable div (not the JS pill-picker pattern). Each gallery entry is a `<label><input type="checkbox" name="force_models[]" value="{id}">` row. PHP pre-checks via `in_array($m['id'], $editForce['models'] ?? [])` in edit mode - simpler than the JS-state approach used for colors/recipes.

## brushes.json schema
```json
[
  {
    "id": "1234567890",
    "brand": "Citadel",
    "series": "STC Drybrush",         // optional - brush line/range
    "size": "M",                       // optional - free text (S, M, L, 0, 2/0, etc.)
    "material": "Synthetic",           // optional - Synthetic, Natural, Mixed
    "use": "Layering",                 // optional - Primary Use (Basecoating, Layering, Detail, Drybrushing, etc.)
    "condition": "prime",              // "prime" | "workhorse" | "retired" - defaults "prime"
    "date_start": "2024-01",           // optional, YYYY-MM - when you started using it
    "notes": "Freeform notes…"         // optional
  }
]
```

`condition` defaults to `"prime"` for new brushes; toggled via AJAX (`set_brush_condition` handler) without page reload - cycles `prime → workhorse → retired → prime`. `brushSort()` sorts by condition rank (prime=0, workhorse=1, retired=2), then brand+series+size. **Opt-in**: file must exist for the feature to appear. Create via admin ("Start Brush Inventory" button).

## bench.json schema
```json
[
  {
    "id": "1234567890",
    "name": "Death Guard Plague Marines",
    "stage": "highlighted",            // built | primed | basecoated | washed | highlighted | based | varnished | done
    "last_touched": "2026-04-19",      // YYYY-MM-DD; auto-stamped on every save and stage cycle
    "faction": "Death Guard",          // optional
    "system": "40k",                   // optional - game system; "40k" | "30k / HH" | "AoS" | "Kill Team" | "Blood Bowl" | "Necromunda" | "OPR" | "Other"
    "date_start": "2026-04-12",        // optional, YYYY-MM-DD
    "notes": "Trying wet-blending…",   // optional, freeform
    "colors": [                         // optional - paint queue, "Brand|Name|Layer" strings (3-part keys)
      "Citadel|Death Guard Green|Base"
    ],
    "brushes": ["1776362488"],         // optional - brush ID refs from brushes.json
    "wip_images": [                    // optional - web-relative paths, up to 8, stored positionally
      "img/bench/1234567890_1.jpg"
    ],
    "history": [                       // optional - auto-appended on every stage cycle; never written manually
      { "from": "primed", "to": "basecoated", "date": "2026-04-19" }
    ]
  }
]
```

Stages cycle in order: `built → primed → basecoated → washed → highlighted → based → varnished → done → built`. Toggled via AJAX (`set_bench_stage` handler) without page reload; `last_touched` is auto-updated and a `{from, to, date}` object is appended to `history` on every cycle. `benchSort()` puts non-done first, then sorts by `last_touched` desc, then name. **Opt-in**: file must exist for the tab and admin section to appear. Create via admin ("Start Workbench" button). Photos stored in `img/bench/` (auto-created), with up to 8 positional slots per entry. Constants `BENCH_STAGES` and `BENCH_MAX_IMAGES` defined in `admin.php`.

## recipes.json schema
```json
[
  {
    "id": "1776xxxxxx",
    "name": "Ork Skin Recipe",
    "category": "Flesh",               // optional - Flesh, Metal, Cloth, Armour, Base, Leather, Bone, NMM, Skin, Cloak, Fur, Weapon, Eye, Wood, Stone, Gem, Fire, Glow
    "faction": "Orks",                 // optional - freeform
    "description": "Short summary",    // optional
    "steps": [
      {
        "paint":     "Citadel|Waaagh! Flesh|Base",   // 3-part paint key; same format as scheme color arrays
        "technique": "basecoat",                       // see fixed vocabulary below
        "ratio":     "2:1 water",                      // optional
        "note":      "Two thin coats",                 // optional
        "brush":     "1776362591"                      // optional - brush ID from brushes.json
      }
    ],
    "notes": "End-of-recipe commentary"  // optional
  }
]
```

**Fixed technique vocabulary** (used for colour-coded badges and filtering): `basecoat | wash | shade | layer | edge | highlight | glaze | drybrush | stipple | blend | special`. `special` is the escape hatch; any other technique value is coerced to `special` server-side.

**Opt-in**: file must exist for the tab and admin section to appear. Create via admin ("Start Recipe Library" button). `recipeSort()` sorts by `name` asc. POST handlers: `create_recipes_file`, `add_recipe`, `edit_recipe`, `delete_recipe` (add/edit share one handler branch via `in_array` check).

**Scheme → recipe reference**: `models.json`, `planned.json`, and `bench.json` gain an optional `recipes` field - array of recipe IDs. Purely additive; entries without it behave exactly as before. When a scheme has `recipes`, the pull sheet renders them as sequenced step-by-step sections (technique · paint · ratio/note) followed by any paints in `colors` not covered by a recipe. Without recipes, the pull sheet falls back to the original brand-grouped flat checklist.

**Orphan safety**: if a referenced recipe ID is later deleted, rendering silently drops the missing entry (no error, no broken card).

## Admin
- Password: stored in `define('ADMIN_PASSWORD', …)` at top of `admin.php`
- **Sticky quicknav bar**: links jump to Stats / Gallery Form / Entries / Inventory / (Brushes) / Paint Checker / Conversions / Planned Schemes / On the Bench / Recipes / (Forces) / (White Dwarf) / (Black Library); `scroll-margin-top: 52px` on h2 offsets the sticky bar. Brushes, Forces, White Dwarf, and Black Library links only appear when their respective data files exist; the On the Bench and Recipes links are always visible (their sections show a "Start Workbench" / "Start Recipe Library" button when the files don't yet exist).
- **Collapsible sections**: every `h2[id^="section-"]` is wrapped at runtime in a `.admin-section-body` div (JS-side DOM surgery in the DOMContentLoaded block at the bottom of admin.php's main `<script>`). All sections start collapsed. Clicking an `h2.collapsible` toggles that section only. Clicking a quicknav link collapses every other section, expands the target, then the native anchor jump scrolls to it. When `$editModel` is set the server renders `<body data-open-section="section-gallery">`; when `$editForce` is set, `<body data-open-section="section-forces">` - so edit forms auto-expand on re-render. `location.hash === '#section-X'` on load also auto-expands that section.
- **Scroll-capped entry lists**: `.model-list` (used by Gallery Entries, Planned, Bench, Recipes, WD, Books) has `max-height: min(80vh, 1200px)` + `overflow-y: auto` + gold-tinted webkit scrollbar. The Brushes table wrap gets the same cap via an inline `max-height:min(80vh, 1200px)` override of `.paint-table-wrap`'s default 520px.
- **Back-to-top button**: fixed bottom-right, appears after scrolling 300px

### Hobby Stats section
- First section visible after login - computed live from all loaded data
- **Key number cards**: Paints Owned, Recorded Schemes, Planned, WD Issues (if `$hasWD`), Books Read (if `$hasBooks`), Active Brushes (if `$hasBrushes` - count of non-retired), Recipes (if `$hasRecipes`), Wanted (if any), Low / Out (if any paints flagged low or out), Missing (Planned) (if any - clickable, opens a modal listing missing paints grouped by brand with the planned scheme names they come from)
- **Collection by Brand**: horizontal gold bars; each brand scaled to the largest collection
- **Gallery by Faction**: list with scheme counts; By Year breakdown shown when schemes span multiple years
- **Most Used Paints**: top 8 paints across all gallery schemes, ranked with count
- **Tab Visits**: horizontal bars showing cumulative tab click counts, sorted by most-visited; only shown when `data/tab_stats.json` exists. Counts are written by a fire-and-forget `fetch()` POST in `index.php` on every tab switch (no page reload, no auth required).
- PHP computation runs inline in the HTML block using `$paints`, `$models`, `$planned`, `$wdData`, `$booksData`, `$brushesData`, `$tabStats`

### Gallery section
- Add, Edit, Delete model entries with up to 4 images and paint color tags
- Images saved to `img/models/` using `__DIR__` paths; web-relative paths stored in JSON
- Edit mode: triggered by `?edit=<id>` GET param; redirects with session flash on success
- Color picker: click paint names to toggle; `updateHidden()` called on click AND page load (preserves edit-mode pre-selection)
- **System field**: optional game system select (40k / 30k / HH / AoS / Kill Team / Blood Bowl / Necromunda / OPR / Other); stored as `system` in models.json; renders as a colour-coded `.sys-game-badge` on gallery cards; system filter dropdown in gallery controls bar

### Backup export
- Top-right "Export Backup" button (next to Log out) POSTs `action=export_backup`
- Handler (top of admin.php, just above `add_model`) streams a JSON download named `waaagh-paint-backup-YYYY-MM-DD.json` containing `_meta` + every existing `data/*.json` file keyed by short name (paints, models, planned, brushes, bench, recipes, whitedwarf, books, journal, tab_stats). Missing files are silently omitted. Does NOT include images on disk
- Uses `while (ob_get_level()) ob_end_clean();` before headers so the download isn't corrupted by stray output buffers. `exit` after `echo` so no further admin HTML is emitted

### Paint Inventory section
- Import, Add, Edit, Delete paints in `data/paints.json`
- Inline add/edit form (no page reload for edit - JS populates form from `data-*` attributes)
- Filter by search text (name + hue) and brand dropdown
- All write operations re-sort by brand+name and use `LOCK_EX`
- PHP helpers: `loadPaintsFromCsvs()`, `swatchColor()`, `brandSlug()`, `layerBadge()`
- **Stock toggle**: `·` / `low` / `out` / `wanted` button per row; cycles via `fetch()` POST to `set_stock` handler (no page reload); CSS classes: `.stock-wanted` (blue)
- **Hex picker**: native `<input type="color">` paired with a text field in the add/edit form; both stay in sync. Validated against `/^#[0-9a-f]{6}$/`. The paint-table swatch column prefers `hex` when present and falls back to `swatchColor($p['color'])` from the broad category.
- **Apply Hex Seed**: button visible only when `data/paint_hex_seed.json` exists. POST handler `apply_hex_seed` reads the seed (flat `Brand|Name|Layer → #hex` lookup) and merges into paints.json, only filling paints that don't already have a hex value (never overwrites). After applying, the seed file can be safely deleted. The header strip shows "X of Y paints have hex values" so you can see coverage at a glance.
- **Quality Rating (stars)**: optional 1-5 star rating in the add/edit form. Uses `.bsp-star` pill picker (`#paintStarPicker`) with `#p_stars` hidden input and `paintStarSet(n)` JS function - mirrors the brush stars pattern exactly. Stars display in the paint table as filled `★` glyphs (`.br-stars-cell`). Edit button carries `data-stars` attribute; `openPaintEdit()` calls `paintStarSet()` to pre-seed the picker. `add_paint` and `edit_paint` POST handlers read `$_POST['p_stars']`, clamp to 0-5, only store when > 0.
- **Notes**: optional freeform textarea in the add/edit form. Edit button carries `data-notes` attribute. Pencil icon (✎) in the paint table row opens an inline preview. Notes displayed read-only on index.php in the slide-up drawer.

### Brush Inventory section
- Gated by `$hasBrushes` - "Start Brush Inventory" button creates `data/brushes.json` if absent
- Add/Edit form: Brand (required), Series, Size, Material, Primary Use, Condition (select: Prime/Workhorse/Retired), Date Started (text `YYYY-MM`), Notes (textarea)
- Entry list shows brand+series, size, material, use, condition toggle button, date, and notes preview (✎ icon)
- **Condition toggle**: AJAX `set_brush_condition` handler - no page reload; cycles `prime → workhorse → retired → prime`; button styled with `.cond-prime` (gold), `.cond-workhorse` (amber), `.cond-retired` (grey) CSS classes
- All saves use `brushSort()` - sorts by condition rank (prime=0, workhorse=1, retired=2), then brand+series+size
- POST handlers: `create_brushes_file`, `add_brush`, `edit_brush`, `delete_brush`, `set_brush_condition`

### Paint Checker section
- Paste a list of paint names (one per line, prefixed with brand) and get instant owned/missing/low/out/wanted status per paint
- **Substitute suggestions**: for paints not owned or flagged out/wanted, suggests closest owned alternative
  - Checks `CONVERSIONS` map first (from conversions.csv, bidirectional - Citadel↔Vallejo, Citadel↔ProAcryl)
  - Falls back to algorithmic match: same Color category + Layer type from another brand
  - `MASTER_PAINTS` JS object covers all CSV paints (including ones not yet imported to inventory) for color/layer lookups
  - `CONVERSIONS` JS object: `"brand|name" (lowercase) → [{brand, name, quality}]`

### Planned Schemes section
- Add, Edit, Delete planned paint schemes (models not yet built/bought)
- Fields: name (required), model/kit, faction, system (select - same values as Gallery), WD Issue (`wd_source`), description, colors
- `wd_source` - optional WD issue number; stored in `planned.json`; renders as a clickable gold badge on the main site card that switches to WD tab and pre-filters to that issue
- Separate color picker from gallery picker - uses `selectedPl`, `buildListPl()`, `updateHiddenPl()` variables to avoid conflict
- Written to `data/planned.json`, sorted by name

### On the Bench section
- Gated by `$hasBench` - "Start Workbench" button creates `data/bench.json` if absent
- Add/Edit form: Project Name (required), Faction, System (select - same values as Gallery), Stage (select 8 options), Date Started (`YYYY-MM-DD`), Notes (textarea), Paint Queue (third color picker - `selectedBn` / `buildListBn()` / `updateHiddenBn()` to avoid conflict with gallery + planned), Brushes (multi-select pill picker - non-retired brushes only when `$hasBrushes`), WIP Photos (8-slot grid with per-slot delete checkbox, mirrors gallery's edit_model upload pattern, photos saved to `img/bench/{id}_{slot}.{ext}` via `saveModelImage()`)
- Entry list row shows: name, meta line (faction · paint count · photo count · last touched), `bench-stage-btn` for cycling (mirrors brush condition AJAX pattern via `set_bench_stage` handler), Edit, Delete
- **Stage cycle button**: AJAX `set_bench_stage` - no page reload; cycles `built → primed → basecoated → washed → highlighted → based → varnished → done → built`; auto-stamps `last_touched`; appends `{from, to, date}` to `history` array; styled with `.bench-stage-btn.stage-<stage>` per-stage colour classes
- All saves use `benchSort()` - non-done first, then by `last_touched` desc, then name
- POST handlers: `create_bench_file`, `add_bench`, `edit_bench`, `delete_bench`, `set_bench_stage` (`add_bench` and `edit_bench` share one handler via action check)
- Constants: `BENCH_STAGES` (8-string array), `BENCH_MAX_IMAGES` (8), `BENCH_FILE`, `BENCH_IMG_DIR`, `BENCH_IMG_WEB`

### Forces & Rosters section
- Gated by `$hasForces` - "Start Forces & Rosters" button creates `data/forces.json` if absent
- Add/Edit form: Name (required), Game System (select), Target Models (number), Notes (textarea), **model picker** (scrollable PHP-rendered checkbox list of all gallery entries; pre-checked in edit mode via PHP `in_array`)
- Edit mode: `?edit_force=ID` GET param; `$editForce` detected after `$editModel`; body tag gets `data-open-section="section-forces"` when set
- Entry list (`.model-list` scroll cap): force name, system badge, model count vs target, Edit/Delete
- All saves use `forcesSort()` - by name ascending
- POST handlers: `create_forces_file`, `add_force` + `edit_force` (combined branch), `delete_force`
- Constants: `FORCES_FILE`
- Quicknav link: appears when `$hasForces` is true

### Recipe Library section
- Gated by `$hasRecipes` - "Start Recipe Library" button creates `data/recipes.json` if absent
- Add/Edit form: Name (required), Category (datalist), Faction (freeform), Description, **step builder**, Notes (textarea)
- **Step builder** (`#rc_steps`): each step row is rendered client-side via `recipeStepTpl(step)` and uses parallel-array inputs (`step_paint[]`, `step_technique[]`, `step_ratio[]`, `step_note[]`, `step_brush[]`). Server-side `buildRecipeSteps($_POST)` zips them into step objects. Per-row Up/Down buttons reorder (reorders all parallel fields since they're siblings); Remove deletes; `renumberRecipeSteps()` keeps the displayed index in sync. Paint and brush inputs use `<datalist>` autocomplete (ids `rc_paintList` / `rc_brushList`) - no custom search UI needed
- **Recipe picker on scheme forms**: `.rc-pill-picker` with one pill per recipe. Multi-select - clicking toggles the `selected` class and updates hidden `gallery_recipes[]` / `planned_recipes[]` / `bench_recipes[]` inputs. Shared helper `setRecipePickerSelection(pickerId, inputsId, inputName, ids)` pre-seeds selection from `data-recipes` attribute on Edit buttons. Gallery form pre-seeds via PHP directly from `$editModel['recipes']` since that form is already server-rendered for edit mode.
- All saves use `recipeSort()` - by `name` asc
- POST handlers: `create_recipes_file`, `add_recipe` + `edit_recipe` (combined branch), `delete_recipe`
- Constants: `RECIPES_FILE`, `RECIPE_TECHNIQUES` (11-element array)

### White Dwarf Log section
- Gated by `$hasWD` - "Start White Dwarf Log" button creates `data/whitedwarf.json` if absent
- Add/Edit form: Issue # (required), Format (Physical/Digital/Both), Month (text `YYYY-MM`), Notes (textarea)
- Entry list shows issue #, format badge, month, and notes preview
- All saves sort descending by issue number (`intval`)
- POST handlers: `create_wd_file`, `add_wd`, `edit_wd`, `delete_wd`

### Codex Library section (admin heading: Codex Library)
- Gated by `$hasBooks` - "Start Codex Library" button creates `data/books.json` if absent
- Add/Edit form: Type select (Codex/Army Book, Supplement/Campaign), Faction, Title, Publisher/Credit (author field), Edition (series field), Notes textarea
- No Status, Date Read, or novel-related fields - codex-only
- Entry list: teal `Codex`/`Supplement` type badge, faction badge (gold), edition/publisher meta line, notes preview
- `bookSort()` sorts by faction ascending then title ascending
- `type` is always stored explicitly (`"codex"` or `"supplement"`)
- POST handlers: `create_books_file`, `add_book`, `edit_book`, `delete_book`

### Scrap Notes section (internal id: journal; admin heading: Scrap Notes)
- Gated by `$hasJournal` - "Start Scrap Notes" button creates `data/journal.json` if absent
- Add/Edit form: Date (required, `<input type="date">`, defaults to today), Mood (optional select: Great/Good/Okay/Rough), Title (optional), Body (required textarea 8 rows)
- **@mention picker**: type `@` in the body textarea to open a floating picker pre-populated from `JN_MENTIONABLES` (PHP-rendered array of all schemes, recipes, WD issues, bench projects). Filtering is case-insensitive substring. Selecting inserts `@[type:id|Label]` token at the cursor. Token format: `type` is `scheme | recipe | wd | bench`; `id` is the entry id (or WD issue number); `Label` is the display text
- Entry list shows date (gold Cinzel), mood badge (colour-coded), title (if present), body preview (100 chars); edit + delete per row
- **Admin filter**: real-time JS filter input (`#jn-admin-filter`) above the `.model-list`; filters by `data-jnsearch` attribute (date + title + body concatenated) on each row. Count span (`#jn-admin-count`) updates to "X of N entries" while filtering. No server round-trip.
- All saves use `journalSort()` - date descending, then id descending (same-date entries in reverse insertion order)
- POST handlers: `create_journal_file`, `add_journal`, `edit_journal`, `delete_journal` (`add_journal`/`edit_journal` handled in one block via `in_array`)
- Quicknav link: appears when `$hasJournal` is true
- `JOURNAL_DATA` is embedded as a JS constant at page load (changed from lazy fetch - direct `/data/` HTTP access is blocked by `.htaccess` on the production server)

### Pile of Shame section
- Gated by `$hasShame` - "Start Pile of Shame" button creates `data/shame.json` if absent
- Add/Edit form fields: Name (required), System (select: same standard values as all other forms - 40k/30k / HH/AoS/Kill Team/Blood Bowl/Necromunda/Epic/OPR/Other), Faction, Count (number), Status (select: Sealed/Opened/Partial), Acquired (text YYYY-MM), Notes (textarea)
- Entry list (`.model-list` scroll cap): acquired date (gold Cinzel, leftmost), system badge (colour-coded inline styles), name, faction, status badge, model count, Promote buttons, Edit/Delete
- **Promote buttons** (only shown when `promoted_to` is not set): "→ Planned" and "→ Bench" - AJAX POST to `promote_shame`; on success replaces buttons with a gold "Promoted → Planned/Bench" badge (no page reload)
- `promote_shame` handler: sets `promoted_to` on the shame entry, appends a new entry to `planned.json` (name + faction) or `bench.json` (name + faction + `stage: built` + `last_touched`), re-sorts both files, returns `{ok:true, promote_to}`
- **Shame spine CSS uses short labels via `SHAME_SYS_SHORT` lookup** (JS map): "30k / HH" displays as "30k", "Blood Bowl" as "BB", "Necromunda" as "Necro", "Kill Team" as "KT" - keeps the 22px vertical spine readable. CSS slug lookup via `SHAME_SYS_SLUG` maps stored values to existing class names (`.shame-sys-30k`, `.shame-sys-BB`, etc.)
- System spine CSS colours: 40k `#8a2020`, 30k/HH `#4a3a10`, AoS `#1a2a5a`, Epic `#1a3a1a`, Blood Bowl `#2a1a4a`, Necromunda `#1a3a3a`, Kill Team `#0a3a3a`, OPR `#1a2a3a`, Other `#2a2a2a`
- Status badge classes: `.shame-status-sealed` (grey), `.shame-status-opened` (amber), `.shame-status-partial` (gold)
- All saves use `shameSort()` - acquired ascending (nulls last), then id ascending
- POST handlers: `create_shame_file`, `add_shame`, `edit_shame` (combined branch), `delete_shame`, `promote_shame` (AJAX JSON response)
- Quicknav link: appears when `$hasShame` is true

## index.php architecture
PHP runs at the top, loads paints + models + planned + conversions + (optionally) WD and Books data, and embeds all as JS constants. Everything after that is client-side.

Paint loading priority: reads `data/paints.json` if it exists; otherwise falls back to parsing all 5 CSVs directly.

**JS constants**: `PAINTS`, `MODELS`, `PLANNED`, `CONVERSIONS_DATA`  
**Conditional JS constants** (only emitted when respective file exists):
- `WD_DATA` - gated by `$hasWD`
- `BOOKS_DATA` - gated by `$hasBooks`
- `BRUSHES_DATA` - gated by `$hasBrushes`
- `BENCH_DATA` - gated by `$hasBench`
- `RECIPES_DATA` - gated by `$hasRecipes`
- `SHAME_DATA` - gated by `$hasShame`
- `FORCES_DATA` - gated by `$hasForces`

**JS maps** (computed on load):
- `paintOwned` - Set of `"brand|name"` for paints with stock !== 'wanted'
- `paintStock` - Map of `"brand|name" → stock value` for flagged paints
- `paintByKeyLC` - Map of `"brand|name" (lowercase) → stock value`, used by Equivalency tab
- `paintUsage` - Map of `"brand|name" → scheme count`, computed from MODELS

**Tab render functions exposed globally** (for tab-switching hook):
- `window._renderWD` - set by the WD IIFE; called when WD tab is activated
- `window._renderBooks` - set by the Books IIFE; called when Codices tab is activated
- `window._renderBrushes` - set by the Brushes IIFE; called when Brushes tab is activated
- `window._renderBench` - set by the Bench IIFE; called when On the Bench tab is activated
- `window._renderMatch` - set by the Color Match IIFE; called when Color Match tab is activated (no-op; data is prepared on load)
- `window._renderRecipes` - set by the Recipes IIFE; called when Recipes tab is activated
- `window._renderFactions` - set by the Factions IIFE; called when Factions tab is activated
- `window._renderJournals` - set by the Journal IIFE; calls `loadJournal()` which just calls `renderJournals()` directly. `JOURNAL_DATA` is embedded at page load (no fetch).
- `window._renderShame` - set by the Shame IIFE; called when Pile of Shame tab is activated. Data is already in `SHAME_DATA` constant, no fetch needed.
- `window._renderForces` - set by the Forces IIFE; called when Forces & Rosters tab is activated. Data is already in `FORCES_DATA` constant, no fetch needed. Renders immediately on load (like Shame).
- `window._jumpToRecipe(rid)` - switches to Recipes tab, scrolls to that recipe, fires `highlight` pulse (used by recipe badges on scheme cards)
- `window._jumpToScheme(mid)` - switches to Paint Schemes tab, scrolls to that gallery entry (used by "Used in" links on recipe cards)
- `window._RECIPE_BY_ID` - Map of `id → recipe` exposed by the Recipes IIFE for `renderRecipeRefs()` to look up badge names (falls through silently when `$hasRecipes` is false)

### Tab order (landing page = Looted Knowledge / What's Inside)
Tabs are grouped thematically with a subtle vertical divider between groups (`.tab-btn.tab-group-start` = border-left + left-margin on the first tab of each group). The first visible tab of a group is tagged with `tab-group-start`; conditional opt-in tabs fall back to tagging the next-visible tab so the divider always anchors to the first button that actually renders.
1. **Looted Knowledge** (display name; internal id `contents`; default/active) - magazine-style index/contents page that jumps to other tabs
   - **The Work:**
2. **Recipes** (opt-in - only rendered if `$hasRecipes`)
3. **Paint Schemes**
4. **Factions** - per-faction rollup of schemes, recipes, bench, planned, deduped palette
5. **Pile of Shame** (opt-in - only rendered if `$hasShame`) - unbuilt box tracking with promote-to-Planned/Bench
6. **Planned**
7. **On the Bench** (opt-in - only rendered if `$hasBench`)
8. **Forces & Rosters** (opt-in - only rendered if `$hasForces`) - named rosters grouping gallery schemes into game-ready forces; readiness progress bar
   - **The Collection:**
9. **Paint Inventory**
10. **Brushes** (opt-in - only rendered if `$hasBrushes`)
   - **Reference Tools:**
11. **Equivalency**
12. **Colour Match** (display spelling with British "u"; internal tab id stays `match`, CSS classes stay `#tab-match` / `#match-*`)
    - **Reading & Inspiration:**
13. **White Dwarf** (opt-in - only rendered if `$hasWD`)
14. **Codices** (opt-in - only rendered if `$hasBooks`)
15. **Scrap Notes** (display name; internal id `journal`, JS internals all use `journal`; opt-in - only rendered if `$hasJournal`)

### Brushes tab
- Opt-in; only rendered when `$hasBrushes` (i.e. `data/brushes.json` exists)
- **Condition filter pills**: All / Prime / Workhorse / Retired - `brCondFilter` state variable, default `'all'`
- **Search box** filters across brand, series, size, material, use, and notes
- Each row shows: brand+series name, size+material line, primary use, condition badge, date started, notes (inline)
- Condition badge uses `.brush-cond-badge` with `.cond-prime` (gold), `.cond-workhorse` (amber), `.cond-retired` (grey) CSS classes
- Count display: "X of Y brushes"
- `renderBrushes()` handles filter+search+render; `window._renderBrushes` exposed for tab-activation hook

### On the Bench tab
- Opt-in; only rendered when `$hasBench` (i.e. `data/bench.json` exists)
- Layout: CSS grid 2 columns on desktop (`grid-template-columns: repeat(2, minmax(0, 1fr))`), 1 column on mobile (≤600px)
- **Stage filter pills**: All / Active / Done - `stageFilter` state variable, default `'all'`
- **Search box** filters across name, faction, notes
- Each card shows: project name, faction, stage badge with stage colour, "touched X" date, **Session Sheet button** (when paints are set), **Next Step preview line** (when a recipe is linked), gradient progress bar (built=0% → done=100%), WIP photo strip (clickable → lightbox), notes, paint queue pills (owned/low/missing flagged using `paintOwned` + `paintStock`), brush pills (looked up by ID via `BR_LOOKUP`), summary line
- **Pull list button** (`.pull-btn.planned-pull-btn`): appears in the card footer (`.planned-card-footer`) when the project has paints (direct colors or recipe paints). Opens the shared `populatePullSheet()` modal with the project name, current/next stage in the subtitle, and the full paint queue + recipe steps. Exposed as `window.openBenchPull(id)`. Paint pills and pull list both use `collectAllPaints(b.colors, b.recipes)` so recipe-only projects are fully supported.
- **Next Step preview** (`.bench-next-step`): italic line under the stage row showing the technique + paint name from step 1 of the first linked recipe. Hidden when no recipe is linked. Computed by `getBenchNextStep(b)` (also inside the bench IIFE)
- Stage badge uses `.bench-stage-label.stage-<stage>` CSS class for colour (built grey, primed light grey, basecoated/washed blue, highlighted gold, based brown, varnished cream, done green)
- Bench cards have `border-left` colour matching stage; "Done" cards get `opacity: .85`
- Count display: "X of Y projects"
- `renderBench()` handles filter+search+render; `window._renderBench` exposed for tab-activation hook
- WIP photos reuse the existing `openLightbox()` modal

### What's Inside tab (default landing)
- Magazine-style table of contents - first thing visitors see, replaces the previous default of jumping straight into the Paint Inventory table (which was visually overwhelming)
- **Hero bar**: sits between the mast and the contents grid. Left side is a `.hero-stats` card showing the **Pile of Shame** counter (box count + models flag) as a single featured stat - gated by `$hasShame`, omitted entirely when the file doesn't exist. Right side is a `.hero-bench` strip showing the most-recently-touched active bench project (photo, name, faction, stage badge, "touched N days ago"), computed server-side from `$benchData`. Collapses to single column on ≤720px. If `$hasBench` is false or no active projects, the bench strip is omitted.
- **Hero stats card**: flex column, centered. Contains a `.hero-shame-label` ("PILE OF SHAME" in small-caps gold) and a single `.hero-stat.hero-stat-shame` anchor (`data-jump="shame"`). Number rendered at 3rem via `.hero-stat-shame .hero-stat-num` override (base `.hero-stat-num` is 2rem). Models count shown as a red `.hero-stat-flag` when `$cnt_shame_units > 0`. Previously showed Recipes/Schemes/Bench/Planned/Ready Now - those were removed as they duplicate "The Pipeline" section below.
- **`$cnt_ready`**: computed server-side at page load - count of planned schemes where every paint in `colors` is owned and not out-of-stock. Uses the same owned/out logic as the JS `paintOwned`/`paintStock` checks. Variables used: `$ownedKeys` (built once from `$paints`, keyed as both 3-part `brand|name|layer` and legacy 2-part `brand|name`). Used by "The Pipeline" section, not the hero bar.
- Sections grouped logically: **The Handbook** (Recipes, featured full-width), then 2x2 of **The Work** / **The Collection** / **Reference Tools** / **Reading & Inspiration**
- Each entry: gold Cinzel name, italic Georgia blurb, uppercase Cinzel count line. Clicking switches to that tab via the shared `switchToTab(name)` helper (also scrolls to top)
- All counts computed server-side in PHP at the top of the panel (`$cnt_owned`, `$cnt_models`, `$cnt_recipes`, `$cnt_ready`, etc.)
- Opt-in sections (Recipes, Brushes, Bench, Forces, WD, Books) are guarded with `<?php if ($hasX): ?>` and only appear when the relevant data file exists
- Layout: `.contents-grid` is `repeat(2, 1fr)` fixed; Handbook spans both via `grid-column: 1 / -1`. Mobile collapses to single column
- `switchToTab(name)` is exposed for any other JS that wants to jump to a tab programmatically
- Click delegation for landing jumps uses the broad selector `[data-jump]` (not scoped to `.contents-entry`) so hero-stat and hero-bench anchors participate
- **Latest note strip** (`.hero-note-strip`): slim dark footer bar pinned to the bottom of `.hero-wrap`, rendered when `$hasJournal` is true and `$journalData` is non-empty. Left side shows "LATEST NOTE · YYYY-MM-DD" in small Cinzel (`.hero-note-label`); right side shows up to 90 chars of the latest entry body, italic, ellipsed (`.hero-note-body`). Clicking jumps to the Scrap Notes tab (`data-jump="journals"`). No dismiss button, no animation, no sessionStorage. Replaces the old sticky post-it scratch-note widget.

### Global search
- Client-side fuzzy/substring search across every data source already loaded as JS constants: PAINTS, MODELS, PLANNED, RECIPES_DATA, BENCH_DATA, BRUSHES_DATA, WD_DATA, BOOKS_DATA, FORCES_DATA. No network round-trip.
- **Triggers**: (a) floating gold-bordered magnifier button fixed top-right (`#gs-trigger`, z-index 150), (b) `Ctrl/Cmd+K` anywhere, (c) `/` when not already typing into an input
- **Modal**: dark backdrop + centered `.gs-modal` card, input at top, grouped results below, keyboard-hint footer. `Esc` or click on backdrop closes. `↑/↓` navigates the flat result list, `Enter` opens the highlighted result
- Results grouped in a fixed type order (`scheme, recipe, paint, planned, bench, brush, wd, book, force`) with type-tinted badges (`.gs-type-paint` amber, `.gs-type-scheme` blue, `.gs-type-recipe` purple, `.gs-type-force` dark green, etc.). Each type capped at 8 shown; count of hidden extras appended to the group heading
- Clicking a result dispatches to a type-specific jump:
  - paint: `switchToTab('inventory')` + prefill `#search` with the paint name (fires an `input` event)
  - scheme: `_jumpToScheme(id)`
  - recipe: `_jumpToRecipe(id)`
  - planned: `switchToTab('planned')` + scroll+pulse on `.planned-card[data-id="X"]`
  - bench: `switchToTab('bench')` + scroll+pulse on `.bench-card[data-id="X"]`
  - brush: `switchToTab('brushes')` + scroll+pulse on `.brush-entry[data-id="X"]`
  - wd: `goToWD(issue)`
  - book: `switchToTab('books')` + scroll+pulse on `.bl-row[data-id="X"]`
  - force: `switchToTab('forces')` + scroll+pulse on `.force-card[data-id="X"]`
- **Required `data-id` attributes added to card renderers** so the jumps find their target: `.planned-card`, `.bench-card`, `.brush-entry`, `.bl-row` (plus `data-issue` on `.wd-row`). `.model-card` already had one

### Paint Inventory tab
- Client-side filter/sort - brand, colour category, layer type, free-text search
- Sticky controls bar
- Swatch column uses `.swatch-<Color>` CSS classes
- Layer badge uses `.badge-<Layer>` CSS classes (spaces stripped from multi-word layer names)
- Brand row tinting via `tr.brand-<slug> td`
- **Used column**: rightmost column showing how many gallery schemes reference each paint; count > 1 renders in gold; sortable
- **Used-in modal**: clicking any inventory row opens a modal listing every scheme that uses that paint, with a "View →" button that switches to Paint Schemes tab and scrolls to that card
- **Stock badge**: `low` (orange), `out` (red), `wanted` (blue) badge shown inline in the paint name cell
- **Notes / Stars drawer**: pencil icon button (`.notes-btn`) on each row opens a slide-up drawer showing the paint's notes and quality rating (read-only). When `stars` is set, a star icon button (`.star-rate-btn.has-stars`) also appears and opens the same drawer. `openNotes(pid, brand, name, stars, notes)` populates the drawer; `drawerStarSet(n)` lights up `.nsp-star` elements visually (no click/write events). **This drawer is display-only on index.php - no writes permitted.**

### Paint Schemes tab (formerly Gallery)
- Cards: header (name + faction tag + date) → image grid → body (optional summary block → description + color pills)
- **Summary block** (`.model-summary`): 2-column label/value grid rendered above description when `m.summary` is set. Fields: Finish, Primary, Contrast, Technique. Labels in Cinzel small-caps at 8px (`#5a4a28`), values at 11px (`#9a8a6a`). Separated from description by a bottom border. Source: `summary` sub-object in models.json
- Grid: 2 cards per row on desktop, 1 column on mobile (≤600px); centered (`max-width: 960px`); capped at 12 entries with a "Show all N entries ↓" button
- Sorted by date descending
- **Sticky controls bar** (`#gallery-controls`): full-text search + active faction pill + Ready Only filter
- **Full-text search**: filters across name, faction, description, and colors
- **Faction filtering**: click a faction tag to filter; `[active faction ×]` pill in controls bar to dismiss
- Color pills: sorted by brand then paint name; switch to Paint Inventory tab and pre-filter on click
- Images clickable → lightbox (arrow keys, Escape, click-outside to close)
- **Direct link**: chain-link icon copies `?model=<id>` URL; loads with scroll + gold pulse highlight
- **Pull sheet**: "Pull list" button opens white modal - paints grouped by brand, checkbox per paint; missing flagged red, low/out flagged orange/red; Print + Copy buttons
- **Issue badge**: "Pull list N issues" when any scheme paint is missing or low/out

### Planned tab
- Layout: CSS grid 2 columns on desktop (`grid-template-columns: repeat(2, minmax(0, 1fr))`), 1 column on mobile (≤600px)
- Cards showing planned schemes with color pills - owned (green), low (amber), missing (red)
- **Search box** filters cards by name, faction, model/kit, or description
- **Readiness filter pills** (`.planned-rp`): All / Ready / Almost / Needs Work. State variable `plannedReadyFilter` (outer scope, like `plannedSystemFilter`). Readiness computed by `schemeReadiness(pl)` - returns `{ level, missing, missingNames }` where level is `'ready'` (0 missing), `'almost'` (1-2 missing), or `'needs'` (3+ missing). Missing = not in `paintOwned` OR stock is `'out'`.
- **Readiness badge** (`.ready-badge.ready/almost/needs`): shown in the card header. Green "Ready", amber "Almost", dark red "Needs Work".
- **Sort**: when `plannedReadyFilter` is empty (All), schemes are sorted READY first, then Almost, then Needs Work, then alphabetically within each group.
- **Shopping Impact line** (`.planned-shop-impact`): shown on ALMOST cards only - *"Buy: [Paint A], [Paint B] - then ready"* listing the 1-2 missing paint names.
- **WD source badge** - if `wd_source` is set, a gold `WD #NNN` button appears on the card; clicking calls `goToWD(issue)` which switches to the White Dwarf tab and pre-fills the search field
- `goToWD()` is gated by `<?php if ($hasWD): ?>` - a no-op stub is emitted when WD is inactive
- **Shopping List** button aggregates missing/low paints across all schemes into a printable/copyable modal
  - Two sections: "Must Buy" (missing/out) and "Consider Restocking" (low), grouped by brand
  - `body.print-shop` CSS class toggled before `window.print()` to distinguish from pull sheet printing

### Codices tab
- Opt-in; only rendered when `$hasBooks` (i.e. `data/books.json` exists); tab label is "Codices"
- No filter pills or status filtering - codex/supplement only, search only
- Layout: `#bl-list` is a 2-column CSS Grid (`grid-template-columns: repeat(2, minmax(0, 1fr))`), collapses to 1 column on mobile (≤600px)
- Each card (`.bl-row`) is a flex row: left spine label + right body
  - `.bl-type-spine`: `width: 22px`, vertical text via `writing-mode: vertical-rl; transform: rotate(180deg)`. Teal background for Codex (`#1a4a4a` / `#a0e8e8` text), green for Supplement (`#1a3a28` / `#80d8a8` text)
  - `.bl-body`: faction (large gold, `.bl-codex-faction`), title (smaller, `.bl-title`), edition from `series` field (`.bl-edition`), author/credit (`.bl-author`), notes (`.bl-notes`)
- `renderBookRow(b, q)` reads `b.type` (defaults to `'codex'`); emits spine label as `'Supplement'` or `'Codex'`
- `renderBooks()` filters by search query only; sorts by faction then title; outputs flat grid
- `window._renderBooks` exposed for tab-activation hook
- **`codex_source` badge** (`.codex-source-badge`, teal): shown on gallery, planned, and bench cards when set. Free-text label like "Codex: Death Guard p.42". Field added to all three admin forms and POST handlers

### Scrap Notes tab (internal: Hobby Journal)
- Opt-in; only rendered when `$hasJournal` (i.e. `data/journal.json` exists)
- Layout: `column-count: 2` on desktop, 1 column on mobile (≤600px); cards use `break-inside: avoid`
- **Data loading**: `JOURNAL_DATA` embedded as a JS constant at page load (same as `SHAME_DATA`); `window._renderJournals` calls `renderJournals()` directly - no fetch needed
- **Controls bar**: search input (`#jn-search`) + month navigator (`#jn-month-nav`) + count span (`#jn-count`); year-picker div (`#jn-year-picker`) sits below the controls bar
- **Month navigation**: tab shows one calendar month at a time. `jnCursor` (YYYY-MM string) tracks the current month; initialises to the current calendar month. Prev/next buttons (`#jn-prev`, `#jn-next`) step one month; next is disabled when cursor is at the current month; prev is disabled when cursor is at or before the earliest month with data (`jnMonthsWithData[0]`). Month label button (`#jn-month-label`) shows e.g. "May 2026"; clicking toggles the year picker
- **Year picker** (`#jn-year-picker`, `.jn-year-picker`): hidden by default; dynamically built from unique years in `jnMonthsWithData`. Clicking a year jumps to the latest month with data in that year. Active year button gets `.active` class
- **Search overrides month mode**: when `#jn-search` has a value, the month nav is hidden and all entries matching the query across all months are shown. Clearing search restores month view
- Each card shows: date (gold Cinzel), mood badge (great/good/okay/rough colour-coded), title (italic, if present), body (full text, `white-space:pre-wrap`)
- **@mention rendering**: `renderBody(raw)` splits body text on `@[type:id|Label]` tokens; plain text is `esc()`-escaped, tokens become `<span class="jn-mention jn-mention-{type}" data-mtype data-mid>` badges. Click dispatches via event delegation on `#jn-list`: scheme → `_jumpToScheme(id)`, recipe → `_jumpToRecipe(id)`, wd → `goToWD(id)`, bench → `switchToTab('bench')`. Orphaned IDs (deleted entity) render as plain badge text with no error
- Mention badge CSS: `.jn-mention` base + `.jn-mention-scheme` (blue), `.jn-mention-recipe` (purple), `.jn-mention-wd` (gold), `.jn-mention-bench` (green)
- Mood badge CSS classes: `.jn-mood-great`, `.jn-mood-good`, `.jn-mood-okay`, `.jn-mood-rough`
- Count display: "X entries" (month mode) or "X entries matching" (search mode)
- `renderJournals()` handles nav state, filter, and render; `window._renderJournals` exposed for tab-activation hook

### Pile of Shame tab
- Opt-in; only rendered when `$hasShame` (i.e. `data/shame.json` exists)
- Tab placed between Factions and Planned in "The Work" group
- `SHAME_DATA` embedded as a JS constant at page load (data is small; no lazy fetch)
- Layout: CSS grid 2 columns on desktop (`.shame-grid`), 1 column on mobile (≤600px)
- **Filter pills**: Active (no `promoted_to`) / Promoted / All - default Active
- **Search box** filters across name, faction, system, notes, acquired
- Each card shows: system badge (colour-coded inline), name (bold), faction, status badge, model count (if set), acquired date + "sitting X years/months" computed via `sittingSince(acq)`, notes, gold "Promoted → Planned/Bench" badge when promoted
- Count display: "X boxes" (Active/Promoted/All context)
- `renderShame()` handles filter+search+render; `window._renderShame` exposed for tab-activation hook
- Landing page "The Work" section shows a Pile of Shame entry with box count (gated by `$hasShame`)
- Global search includes shame entries (type `shame`, badge label "Shame Pile"); jump: `switchToTab('shame')` + scroll+pulse on `.shame-card[data-id="X"]`

### Wishlist tab
- Opt-in; only rendered when `$hasWishlist` (i.e. `data/wishlist.json` exists)
- `WISHLIST_DATA` embedded as a JS constant at page load (no lazy fetch)
- Layout: CSS grid 2 columns on desktop (`.wishlist-grid`), 1 column on mobile (≤600px)
- **Filter pills**: type pills (All / Paint / Model / Brush / Codex / WD - built dynamically from data) + priority pills (All / High / Medium / Low)
- **Search box** filters across name, brand, faction, system, notes, type
- **Copy / Print buttons** in controls bar export wishlist grouped by type as plain text
- Each card (`.wishlist-card`) uses a **vertical spine** (`.wish-spine`) as first flex child - same 22px pattern as Codices and Shame - showing the type label (PAINT / MODEL / BRUSH / CODEX / WD) rotated vertically. Card body (`.wish-body`) holds: priority badge (`.wpri-badge`), stock dot (paint type only), "Already owned" badge (WD type only), name in Cinzel, brand/faction/system meta line, notes, URL link, added date
- **Spine colors**: paint `#1a4a4a / #a0e8e8`, model `#1a3a1a / #80d880`, brush `#3a1a10 / #f09070`, codex `#2a1a4a / #b898e8`, wd `#3a2a08 / #d4a840`
- **Stock dot**: for paint-type items, uses `paintByKeyLC` to show owned/low/out/wanted/missing status dot
- **WD "Already owned" badge**: checks `WD_DATA` to show a green badge if the issue is already logged
- `renderWishlist()` handles filter+search+render; `window._renderWishlist` exposed for tab-activation hook; renders immediately on load

### Forces & Rosters tab
- Opt-in; only rendered when `$hasForces` (i.e. `data/forces.json` exists)
- `FORCES_DATA` embedded as a JS constant at page load (like SHAME_DATA; no lazy fetch)
- Layout: CSS grid 2 columns on desktop (`.forces-grid`), 1 column on mobile (≤600px)
- **Search box** (`#forces-search`) filters across name, system, notes
- Each card (`.force-card`) shows: system badge, force name, meta line (scheme count · painted vs target models), readiness progress bar (`.force-progress` / `.force-progress-fill`, painted/target_models %; capped at 100%), scheme thumbnail strip (`.force-thumbnails`), notes
- **Painted count**: sum of `count` fields on referenced gallery entries (`Math.max(1, parseInt(m.count || 1, 10))`), not just length of models array
- **Thumbnail strip**: first image of each scheme's `images` array, clickable via `_jumpToScheme(id)`; placeholder div when no image; up to all linked schemes shown
- `MODEL_BY_ID = new Map(MODELS.map(m => [m.id, m]))` - built at Forces IIFE load for O(1) lookups
- `renderForces()` handles search+render; `window._renderForces` exposed for tab-activation hook; renders immediately on load
- Landing page "The Work" section shows a Forces entry with roster count (gated by `$hasForces`)
- Global search includes forces entries (type `force`, badge `.gs-type-force` dark green); jump: `switchToTab('forces')` + scroll+pulse on `.force-card[data-id="X"]`
- **`SYS_COLORS` constant**: `{'40k':'#5a1a1a','30k / HH':'#4a3a0a','AoS':'#1a2a5a','Kill Team':'#0a3a3a','Blood Bowl':'#0a3a1a','Necromunda':'#3a0a5a','OPR':'#1a2a3a','Other':'#2a2a2a'}` - declared at gallery scope so it's accessible everywhere (Gallery, Planned, Bench, Forces IIFEs all inherit it)
- System filter dropdowns added to Gallery, Planned, and Bench controls bars; state variables `gallerySystemFilter` / `plannedSystemFilter` (outer scope) and `benchSystemFilter` (bench IIFE)
- System badge CSS: `.sys-game-badge` - colour driven by `SYS_COLORS[system]` as inline background; shown on gallery cards, planned cards, bench cards, and force cards

### Equivalency Search tab
- Displays all rows from `inventory/conversions.csv` as a three-brand table (Citadel | Vallejo | Pro Acryl | Match Quality)
- **Filter**: type any paint name or quality keyword to narrow rows instantly; count shows "X of Y equivalencies"
- **Status dots** on each paint name (8px circles with glow): green = owned, orange = low, red = out, blue = wanted, hollow ring = not in inventory; em-dash = no equivalent listed
- **Column tints**: each brand column has its own background colour matching the rest of the app (Citadel warm amber, Vallejo dark green, Pro Acryl dark blue)
- **Match quality badges**: near identical (green), usable (amber), avoid (dark red)
- On mobile (≤600px) Pro Acryl column hides; Citadel + Vallejo fill the space
- `CONVERSIONS_DATA` - flat array `{citadel, vallejo, proAcryl, quality}` per row (null for -)
- `paintByKeyLC` - lowercase Map used for O(1) status lookups

### Color Match tab
- Pure client-side palette extractor: drop a reference photo, get the dominant colours plus the closest paints in inventory
- **Disclaimer panel** at the top - gold-bordered "personal ballpark tool, ~90% with a clean white-balanced photo" - never remove or hide
- **Matchable count** in the controls bar: "N of M paints matchable" (only paints with a valid `hex` participate)
- File input uses `accept="image/*" capture="environment"` so mobile gets the camera prompt
- Algorithm:
  - `MATCHABLE = PAINTS.filter(p => /^#[0-9a-fA-F]{6}$/.test(p.hex))`; precomputes LAB for each
  - On image upload: downsample to ≤200×200 via canvas `drawImage`; sample every pixel; filter near-white (max>240, min>230), near-black (max<18), and transparent (alpha<200) to avoid background bias
  - K-means in LAB space, k=6 (or fewer if pixels scarce), max 8 iterations, k-means++ init
  - For each cluster centroid, find top-3 nearest paints by ΔE76
  - Cluster swatch is the average **RGB** of pixels in that cluster (not a LAB→RGB inverse) - visually faithful to what the photo actually contains
- Distance buckets: `<8` "very close" (green), `<20` "close" (amber), else "rough" (red)
- Suggestion rows show: paint hex swatch, paint name with stock dot (reusing `paintOwned`/`paintStock`), brand · layer meta, distance label
- `window._renderMatch` is a no-op (data prepared on script load); the hook exists for consistency
- Empty states handled: no matchable paints (asks user to add hex in admin), no usable pixels (image too uniform)

### Factions tab
- Layout: CSS grid 2 columns on desktop (`#factions-wrap`), collapses to 1 column at ≤768px (wider breakpoint than other tabs - faction cards are content-dense)
- Client-side aggregation: on load, iterates MODELS / PLANNED / BENCH_DATA / RECIPES_DATA once and groups by `faction` field into an `INDEX: Map<name, {schemes, planned, bench, recipes, palette}>`
- Each faction card shows (sections only render when non-empty):
  - Header: Cinzel faction name + italic summary line (scheme count, models painted if > schemes, in-progress, planned, recipes)
  - **Painted Work**: grid of mini scheme thumbnails (image + name), clicking calls `_jumpToScheme(id)` with highlight pulse
  - **Recipes**: pill list with purple "Recipe" badge, click calls `_jumpToRecipe(id)`
  - **In Flight**: combined bench + planned chips (amber "stage" badge for bench, green "Planned" badge for planned), click dispatches via `[data-bench-id]` / `[data-planned-id]` delegation to the right tab + scroll+pulse
  - **Paint Palette**: deduped across all sources for the faction, sorted by usage count desc. Each pill has paint hex swatch, owned/low/out/wanted/missing stock dot (via `paintOwned`/`paintStock`), name, and optional `×N` usage badge. Click jumps to Inventory and prefills the search input
- Search box filters factions by name (case-insensitive substring)
- No PHP opt-in gate; tab is always visible. If no entries have a `faction` set, an empty-state message points to admin
- Tab placement: between Paint Schemes and Equivalency
- Deep-link anchors: each card gets `id="faction-<slug>"` but nothing currently links to them directly - room to grow (e.g. faction tag on a scheme card could jump to the Factions tab)

### Recipes tab
- Opt-in; only rendered when `$hasRecipes` (i.e. `data/recipes.json` exists)
- Layout: `column-count: 2` masonry on desktop (`700px` breakpoint), single column on mobile
- Cards show: name, category badge, faction badge, description, ordered step list, notes, and "Used in" footer
- Each step row: technique badge (11 colour-coded variants), paint pill (owned/low/missing using `paintOwned`/`paintStock`), paint swatch (from paint `hex`), optional ratio/note/brush meta line
- **Search** filters across name, description, faction, notes, step paint names, step techniques, step notes
- **Category filter pills** are built dynamically from the categories actually used in the data (`All` + unique `r.category` values)
- **Used-in index** computed at render time: `USED_IN: Map<recipeId, [{kind, name, id}]>` by iterating MODELS/PLANNED/BENCH_DATA once; gallery entries link via `_jumpToScheme()`, planned/bench shown as plain text
- Helper `window.renderRecipeRefs(ids)` emits the "Uses: [badge][badge]" row on gallery/planned/bench cards; no-ops when `$hasRecipes` is false or the ids array is empty (keeps card code clean)
- `_jumpToRecipe(rid)` switches to Recipes tab, scrolls to the card, fires `highlightFade` pulse
- **Direct link**: chain-link icon on each recipe card copies `?recipe=<id>` URL; loads with auto-jump to the Recipes tab + gold pulse highlight (mirrors the gallery `?model=<id>` pattern)

## CSS / design notes
- Theme: 1980s Heavy Metal Magazine / early Warhammer grimdark
- Font: Cinzel (Google Fonts) for headings/labels
- Palette: near-black backgrounds (`#0e0d0a` warm brown-black for controls bars, `#0d0d1a` blue-black used sparingly), gold accents (`#c9a227`), muted brown text (`#c4b49a`)
- Logo: CSS `mask-image` (two intersecting linear gradients, `mask-composite: intersect`) fades edges; `filter: drop-shadow` adds gold glow - requires PNG alpha to work correctly (JPEG breaks both)
- Layer badges are intentionally muted/dark to match the grimdark feel
- Swatch colours are opinionated approximations, not exact brand matches
- **Controls bar convention** (all tabs): `[.tab-label link] | [.tab-search input] | [filter pills / dropdowns] | [count span] | [action buttons]`. The `.tab-label` is an `<a>` that calls `copyTabLink(event, 'tabkey')` to copy `?tab=X` to clipboard - no underline, pointer cursor, brief `✓` text feedback. The `.tab-search` shared CSS class styles all search inputs identically (`#130f08` bg, `4px` radius, `5px 10px` padding, `flex: 1 1 160px`)
- **Card title fonts** (all tabs): Cinzel, `.9rem`, `#c9a227`, `letter-spacing: .04em` - no weight boost, no glow, no uppercase. `.faction-name` and `.bl-codex-faction` follow this same standard
- **Content max-widths**: card-grid tabs (Schemes, Planned, Forces, Wishlist, Shame, Codices, Brushes) use `max-width: 960px`; masonry/dense tabs (Bench, Recipes, Scrap Notes, Looted Knowledge) use `max-width: 1100px`; Factions uses `1080px`

## Image handling gotchas
- Always use `__DIR__ . '/path'` for filesystem operations in PHP; use plain `'img/models/filename'` (web-relative) for `<img src>` and for values stored in JSON
- `move_uploaded_file` fails silently if the target directory doesn't exist - the admin auto-creates `data/` and `img/models/` on load
- Images in `model-images` grid use `overflow: hidden` + `flex-shrink: 0` + `min-height: 0` on `img` to prevent bleed into adjacent card sections

## Deployment / multi-instance notes
- Fully portable - all filesystem paths use `__DIR__`, all web paths are relative (no leading slash)
- To spin up a second instance (e.g. `paint.waaagh.co` for another user): copy files, change `ADMIN_PASSWORD` in admin.php, update footer in index.php, delete the JSON files in `data/` - the new user starts fresh and imports their own inventory from the shared CSVs
- No database, no environment config, no hardcoded domain references

## File-size / scaling notes
- Both `models.json` and `paints.json` are read-and-rewritten whole on every save - fine up to ~1000–2000 entries each
- All saves use `LOCK_EX` flag on `file_put_contents` to prevent concurrent-write corruption
- If entries ever hit the hundreds, SQLite would be a natural next step (no server needed, just a file)

## Analytics
- GA4 tag (`gtag.js`) added after `<head>` in both `index.php` and `admin.php`
- Tab switches in `index.php` fire a `gtag('event', 'tab_view', { tab_name: ... })` custom event on every tab click (inside the tab-click handler, after the fire-and-forget `track_tab` POST). Guard: `if (typeof gtag !== 'undefined')` so the call is safe if GA fails to load.

## Planned features (not yet built)
- *(none currently tracked)*
