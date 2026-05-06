# Waaagh! Paint

A personal Warhammer hobby paint collection manager. Flat-file PHP - no database, no framework,
no dependencies beyond a PHP host. Drop it on any shared host and go.

> **Work in progress.** This is a personal tool shared as-is with the Warhammer hobby community.
> Features are added when they're needed. Bugs may exist. No support is guaranteed.
> Use at your own risk.

---

## What it does

- **Paint Inventory** - track every paint you own with stock level (normal/low/out/wanted), hex
  color swatch, quality rating, and freeform notes
- **Paint Schemes** - gallery of finished models with photos, color tagging, and a pull-sheet
  checklist for repainting
- **Planned Schemes** - wishlist of future projects with readiness tracking (Ready / Almost /
  Needs Work) and a shopping list for missing paints
- **On the Bench** - active WIP projects with stage tracking, session logging, and WIP photos
- **Recipe Library** - reusable step-by-step painting techniques referenced from schemes and
  bench projects
- **Factions** - automatic per-army rollup of schemes, recipes, bench work, and paint palette
- **Forces & Rosters** - group painted schemes into named game rosters with readiness progress
- **Pile of Shame** - track unbuilt boxes; promote to Planned or Bench when ready
- **Hobby Wishlist** - paints, models, brushes, and books to buy; mark ordered/in transit
- **Brush Inventory** - track brush condition (Prime / Workhorse / Retired)
- **Equivalency Search** - Citadel vs Vallejo vs Pro Acryl conversion table with owned-paint dots
- **Codex Library** - optional army book and supplement tracker
- **Scrap Notes** - optional freeform hobby journal with @mention linking to schemes and recipes

All features beyond Paint Inventory and Schemes are **opt-in** - they only appear once you
create the relevant data file via the Admin panel. Start simple and grow into the rest.

---

## Requirements

- PHP 7.4 or newer (no extensions required beyond the default install)
- Write access to the `data/` and `img/` directories on the server
- That's it

---

## Getting PHP (if you don't have a host yet)

You have a few options depending on what you want to do:

**Local use (your own computer)**

The easiest way is [XAMPP](https://www.apachefriends.org/) - a free, one-click installer that
gives you Apache + PHP on Windows, Mac, or Linux. Install it, drop the Waaagh! Paint files into
the `htdocs` folder, and open `http://localhost/waaagh-paint/` in your browser. No internet
connection required once installed.

**Shared web hosting (accessible anywhere)**

Any budget shared host that supports PHP 7.4+ will work - this covers virtually all of them
(Namecheap, DreamHost, Hostinger, SiteGround, etc.). Upload the files via FTP or cPanel File
Manager. No database or special server configuration needed.

**Self-hosted VPS or home server**

Install PHP via your package manager (e.g. `apt install php libapache2-mod-php` on Ubuntu) and
point a virtual host at the folder. The app has no special requirements beyond PHP itself.

---

## Quick Start

1. Upload all files to your PHP-enabled web host
2. Copy `config.example.php` to `config.php`
3. Edit `config.php` - set a strong admin password, your site name/domain, and optionally a
   GA4 Measurement ID
4. Navigate to `admin.php` in your browser and log in
5. Scroll to **Paint Inventory** and click **Import from CSVs** to get started - a sample Two
   Thin Coats entry is included. Add your own paints manually, or build a CSV first (see below).
6. Visit `index.php` to see the front-end collection view

---

## Building Your Paint List from a CSV

The fastest way to bulk-load a collection is to create a pipe-delimited CSV and drop it in the
`inventory/` folder, then import from admin.

**Format** - five pipe-delimited fields, one paint per line:

```
Brand | Name | Color | Hue | Layer
```

- **Brand** - your paint brand name (e.g. `Citadel`, `Vallejo`, `Army Painter`)
- **Name** - the paint name exactly as printed on the pot
- **Color** - broad colour category: `White`, `Grey`, `Black`, `Brown`, `Red`, `Orange`,
  `Yellow`, `Green`, `Blue`, `Purple`, `Pink`, `Metallic`, `Wash`, `Shade`, `Contrast`,
  `Ink`, `Effect`, `Medium`, `Texture`, `Primer`, `Pigment`, `Fluid`, `Utility`,
  `Fluorescent`, `Special`, or `Transparent`
- **Hue** - a short descriptive variant, e.g. `Pure White` or `Warm Earth`
- **Layer** - paint type or product line, e.g. `Base`, `Contrast`, `Shade`, `Air`,
  `Technical`, `Metallic`, `Model Color`, `Speedpaint`

**Example:**

```
Citadel | Mephiston Red | Red | Base Red | Base
Citadel | Agrax Earthshade | Brown | Warm Brown | Shade
Vallejo | Sunny Skin Tone | Pink | Warm Skin | Model Color
Army Painter | Speed Primer Black | Black | Pure Black | Primer
```

Blank lines between colour groups are fine - the parser skips rows with fewer than five
non-empty fields. Name your file anything ending in `.csv` and place it in `inventory/`.
You will need to register it in `admin.php` inside `loadPaintsFromCsvs()` (a one-line
addition) before it appears in the import.

---

## Documentation

A full user guide is bundled with the app at `guide.php`. It covers every tab and every admin
section in detail - how each feature works, what the fields mean, and tips for getting the most
out of it. Open it from the **User Guide** link in the admin quicknav bar (you need to be logged
in to access it).

---

## Configuration

All instance-specific settings live in `config.php` (which you create from the example and is
never committed to git):

| Setting | Description |
|---|---|
| `SITE_TITLE` | App name shown in the browser title |
| `SITE_DOMAIN` | Display domain shown in the footer |
| `SITE_AUTHOR` | Your name shown in the footer |
| `SITE_EMAIL` | Contact email shown in the footer |
| `SITE_URL` | Full URL with trailing slash (used for Open Graph meta tags) |
| `ADMIN_FILENAME` | Filename of the admin panel (default: `admin.php`). Rename the file and set this to match for security. |
| `ADMIN_PASSWORD` | Password for the admin panel - change this before deploying |
| `GA4_ID` | Google Analytics 4 Measurement ID (e.g. `G-XXXXXXXXXX`); leave empty to disable |

---

## Data Files

All data lives in flat JSON files under `data/`. These are **not** included in the repository
(they contain your personal hobby data). The app creates them automatically via the Admin panel.

| File | Created by |
|---|---|
| `data/paints.json` | Import from CSVs or Add Paint in admin |
| `data/models.json` | Gallery entries in admin |
| `data/planned.json` | Planned Schemes in admin |
| `data/brushes.json` | Start Brush Inventory button |
| `data/bench.json` | Start Workbench button |
| `data/recipes.json` | Start Recipe Library button |
| `data/books.json` | Start Codex Library button |
| `data/journal.json` | Start Scrap Notes button |
| `data/shame.json` | Start Pile of Shame button |
| `data/wishlist.json` | Start Hobby Wishlist button |
| `data/forces.json` | Start Forces & Rosters button |

Model photos go in `img/models/`; bench WIP photos go in `img/bench/`. Both are excluded from
git and created automatically on first use.

---

## Multiple Instances

The app is fully portable - all paths use `__DIR__`, all web paths are relative, no hardcoded
domain anywhere in PHP. To run a second instance for another user: copy the files, change
`ADMIN_PASSWORD` in their `config.php`, delete the `data/` JSON files, and they start fresh
with their own inventory.

---

## License

**Polyform Noncommercial License 1.0.0**

Free for personal and community use. **Commercial use of this software is not permitted.**

This means you can:
- Use it for your own hobby tracking
- Share it with friends and the community
- Fork it and make changes for personal use
- Self-host it for free for yourself or a small group

This means you cannot:
- Sell the software or a hosted version of it
- Use it as part of a commercial product or service
- Use it to generate revenue

See [LICENSE](LICENSE) for the full legal text.

---

## Contributing

Bug reports and pull requests are welcome. By submitting a contribution you agree your changes
are released under the same Polyform Noncommercial License.

Please keep the spirit of the project: flat-file, dependency-free, easy to self-host.

---

## Author

**Waaagh! Paint** by Ray Larose  
nob@waaagh.co  
Version 0.9.0-rc.1 - 2026

---

## Disclaimer

This project is not affiliated with or endorsed by Games Workshop. Warhammer, Warhammer 40,000,
and all associated marks are trademarks of Games Workshop Ltd. Paint brand names (Citadel,
Vallejo, Pro Acryl, Two Thin Coats, Army Painter, etc.) belong to their respective owners.
