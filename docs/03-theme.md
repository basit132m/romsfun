# Step 4 — Theme

Custom theme at `theme/romsfun/`. No parent theme, no framework, no page builder — the same
posture as the competitor's bespoke build, but with the SEO layer done deliberately.

## Files

| File | Role |
|---|---|
| `functions.php` | Setup, asset loading, image sizes, LCP handling, plugin-dependency fallbacks |
| `header.php` / `footer.php` | Sticky brand header with pill nav; brand footer |
| `single-rom.php` | The ROM page — box art, facts row, spec table, download, description, checksums, related |
| `archive-rom.php` | ROM archive |
| `taxonomy.php` | Console / genre / collection / type / region hubs |
| `index.php` | Fallback |
| `inc/schema.php` | JSON-LD: VideoGame + SoftwareApplication, BreadcrumbList, WebSite |
| `inc/breadcrumbs.php` | Trail, shared by markup and schema |
| `inc/template-tags.php` | Cards, pills, stars, related-ROM query |
| `assets/css/main.css` | The whole design system, ~7KB |

## Where this beats the competitor

Their build is fast and clean. These are the gaps worth attacking:

**1. Structured data.** Every ROM emits `VideoGame` + `SoftwareApplication` with `gamePlatform`,
`genre`, `publisher`, `fileSize`, an `Offer`, and — where ratings exist — `AggregateRating`. Plus
`BreadcrumbList` on every page and a `WebSite` `SearchAction`. That is what produces star ratings
and breadcrumb trails in the SERP, and on listings that otherwise look identical, the rich result
wins the click.

`AggregateRating` is emitted **only** when both a rating value and a non-zero count exist. Emitting
an empty one is a structured-data error that costs the whole rich result — a common own goal.

**2. LCP handling.** Box art is the largest paint on every ROM page. WordPress lazy-loads images by
default, which delays exactly the element being measured. `romsfun_prioritise_lcp_image()` marks
the first in-content image on singular views `eager` + `fetchpriority="high"`, and every card image
carries explicit dimensions so nothing shifts as it loads.

**3. No external requests before first paint.** System font stack, one stylesheet, no jQuery on the
front end, no webfont. Each avoided round trip is real milliseconds on a mobile connection, which
is where search traffic lands.

**4. Checksums as content.** MD5/SHA1 are rendered on the page. Genuinely useful to the reader, and
unique text on a page that would otherwise be a spec table — the thin-content problem that caps
indexing at this scale.

**5. Related ROMs prefer the collection.** A franchise sibling is a better suggestion than a random
game on the same console, and it concentrates internal links inside a topical cluster instead of
scattering them.

**6. Accessibility.** Skip link, visible focus rings, `aria-label`ed landmarks, `role="img"` with a
text alternative on star ratings, real `<time datetime>`. Not a direct ranking factor, but it
overlaps heavily with the semantic markup crawlers read.

## Homepage

`front-page.php`, in order: full-bleed hero image, an overlapping search panel, announcement,
trending rows, latest grid. Every string, count and toggle is in **Customize → RomsFun Theme →
Homepage**.

### Search and filtering

`inc/search.php`. A plain GET form — **no JavaScript at all**. That is deliberate:

- It works before any script runs, so there is no interaction delay
- Filtered views are real URLs that can be linked, shared and crawled
- Zero JS cost against Core Web Vitals

Filters map to `console`, `genre`, `collection` and `rom_type`, plus sorting by newest, downloads,
rating, title or file size. Sorting by download count and file size works because those are stored
as numbers rather than display strings.

A hidden `post_type=rom` input scopes results to the catalogue — without it a search falls into
global WordPress search and returns pages and blog posts alongside ROMs.

### Crawl control — the part that matters

Filtered views get `noindex, follow` and a canonical pointing at the clean archive, and `?sort=`
is disallowed in robots.txt.

Five filters with dozens of values each generate millions of crawlable combinations. Left
indexable, Googlebot spends its crawl budget walking permutations and never reaches the ROM pages —
the standard way large catalogue sites fail. `follow` is retained so link equity still flows to the
ROMs being listed.

WordPress's own `rel_canonical` is unhooked whenever we emit a filtered canonical, so a page never
carries two competing canonical tags — Google resolves that conflict by ignoring both.

### Hero image and LCP

The hero is a real `<img>` with `fetchpriority="high"`, not a CSS background. Background images are
discovered late — only once the stylesheet has parsed — and cannot be prioritised, which directly
costs LCP on the most important page on the site.

## Deliberately not included yet

- **Collections carousel** on the homepage (the "Best ROM collections" strip)
- **Comments and ratings UI** — the schema is ready; the interface is not built
- **Emulator template**
- **Download mirrors** — one `download_url` for now; a repeater comes with the import work

## Install

Upload `romsfun-theme.zip` via **Appearance → Themes → Add New → Upload Theme**, activate, then:

1. **Appearance → Menus** — create a menu, assign it to **Primary Menu**
2. **Settings → Permalinks → Save Changes**
3. Publish a ROM with box art, a console, and a few fields filled in

### Verify

- Single ROM renders with box art, specs and pills
- `/console/<slug>/` lists ROMs with a heading and count
- View source: one `<script type="application/ld+json">` containing `VideoGame` and `BreadcrumbList`
- Paste the URL into Google's Rich Results Test — expect no errors

## Theme settings

Everything visual is editable from **Appearance → Customize** — no file editing. Colours preview
live as you drag the picker.

| Section | Controls |
|---|---|
| Site Identity | Logo upload, logo max width, site title, favicon |
| RomsFun Theme → Brand Colours | Brand/accent, header background, header text, footer background, footer text |
| RomsFun Theme → Page Colours | Page background, card background, borders, body text, muted text, colour scheme |
| RomsFun Theme → Layout & Typography | Content width, corner radius, base font size, font family, sticky header |
| RomsFun Theme → ROM Pages | Show checksums, show related, related count, download button text |
| RomsFun Theme → Footer | Tagline, copyright text |

### How it works

`main.css` defines every value as a CSS custom property. The Customizer emits an inline `:root`
block overriding those properties. The stylesheet stays the single source of truth for *how* things
are styled; settings only change *what values* it uses. Adding a setting means one entry in
`romsfun_settings()`.

### Two implementation details worth knowing

**Light-mode overrides are wrapped in a `prefers-color-scheme: light` query, not applied to bare
`:root`.** Inline styles load after the stylesheet, so an unguarded `:root` block would also win
inside the dark-mode media query and silently break dark mode. Brand colours apply to both schemes;
only the page palette is light-scoped.

**The font picker defaults to System for a reason.** Selecting Inter, Poppins or Rubik adds a
Google Fonts request that blocks text painting, which costs page-speed score. The control says so
in its own description. No request is made on the default.

### Colour scheme

`auto` (default) respects each visitor's device preference. `light` or `dark` stamps `data-theme`
on `<html>` and forces one.

## Cache purging

Four independent caches sit in front of this site: the browser, Cloudflare's edge, Varnish on the
server, and Redis via the object cache. A change that "didn't work" is very often just being served
from one of them, and chasing that through four separate interfaces wastes real time.

**Tools → RomsFun Cache** (and a **Purge Cache** button in the admin toolbar) clears Redis, Varnish
and Cloudflare in one action, reporting each layer separately so you can see what actually ran.

The browser layer is handled at the source rather than by purging: theme assets are versioned by
file modification time, so an edited stylesheet always gets a fresh URL.

### Cloudflare credentials

Zone ID and an API token, stored in options. Use a token scoped to **Zone → Cache Purge** on that
zone only — never a Global API Key, which would grant full account access if the database were ever
exposed. Saving with the token field blank keeps the existing token rather than clearing it.

### Automatic purge on save

Off by default, and deliberately so: a bulk ROM import would fire thousands of purges and exhaust
the Cloudflare API rate limit. Worth enabling once the catalogue is stable and edits are occasional.
