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

## Visitor ratings

Ratings come from visitors, not from an editor typing a number.

That is a compliance point as much as a product one: Google's structured data policy prohibits
self-serving `AggregateRating` markup, and fabricated ratings put the rich result — and potentially
the site — at risk of a manual action. Real votes are the only version of this worth shipping.

### How it works

- The full 1–5 distribution is stored in `_rf_rating_dist`, so the histogram costs nothing to render
- The derived average and count are mirrored into `_rf_rating_value` / `_rf_rating_count` so
  `orderby => meta_value_num` sorting keeps working
- Star widget sits in the ROM hero; the breakdown sits below the description
- Schema reads the real numbers

### Deduplication

Logged-in users are tracked in user meta. Anonymous visitors are deduplicated by a hashed-IP
transient — hashed with the site's auth salt so nothing personally identifying is stored, and
expiring rather than accumulating, since keeping every voter's IP in post meta would grow unbounded
across 70,000 ROMs.

This stops casual double-voting, not a determined attacker. Vote manipulation on a public widget is
an unwinnable arms race; the goal is a signal that is broadly honest.

### Why the endpoint is public

The page HTML is served from Varnish and Cloudflare, so a nonce embedded in it would be stale for
most visitors and the widget would fail for exactly the people it exists for. Rating a public page
is not a privileged action — the worst outcome of a forged request is one skewed vote, which
deduplication already limits, and a nonce would not stop scripted manipulation anyway.

### Caching and staleness

Server-rendered numbers can be minutes stale behind the CDN. That is an accepted trade for a
cacheable page. When someone votes, the API response updates the DOM immediately so their own
action is always reflected.

## Comments

Enabled on the `rom` post type, rendered by `comments.php` at the end of the ROM page. Threaded,
paginated, styled to match. Remember they are switched off globally in
**Settings → Discussion** — enable them there, or per-ROM in the editor.

## Comment policy — no links

A ROM site attracts heavy automated link-drop spam. Links in user comments are both an SEO
liability (you vouch for whatever gets posted) and a safety one (visitors trust links on your
pages). Rather than moderate that stream forever, links are not permitted at all.

Enforced in four places, because any one alone leaves a gap:

1. **Rejected on submission** — the pattern catches `http://`, `www.`, raw `<a` tags, BBCode `[url`,
   and bare domains like `example.com`, which is how spam arrives once a naive `http://` check
   is in place
2. **The website field is removed** from the form — the best way to stop people filling it in is
   not to ask
3. **`make_clickable` is unhooked** so bare URLs in existing comments never become links
4. **Anchors are stripped on output**, covering anything already in the database

Editors and administrators are exempt, so they can link to a related ROM when answering someone.

## Download Emulator button

Sits beside Download ROM. The emulator is chosen per-ROM from a dropdown of **published emulator
posts** — a real relation, not free text, so the button always points somewhere that exists.

Left on **Auto**, it falls back to an emulator sharing the ROM's console. That matters at scale:
a bulk-imported catalogue still offers the right emulator without anyone setting the field on
70,000 entries.

The button links to the emulator's **page**, not straight to a file. It keeps the visitor on site
and it is an internal link into a page that needs the equity — emulator pages capture "how to play
X" intent and are worth ranking in their own right.

## Site verification & header code

**Settings → RomsFun SEO.** Fields for Google Search Console, Bing, Yandex and Pinterest, plus
header and footer code boxes for analytics and ad tags.

Two details that matter:

**It accepts the whole meta tag or just the token.** People paste
`<meta name="google-site-verification" content="TOKEN">` far more often than the bare token, and a
tag stored inside a tag fails verification silently with nothing to see in the page source. The
saved value is parsed either way.

**Saving purges the caches automatically.** The tag has to be visible to the crawler, and the page
it needs to appear on is almost certainly sitting in Varnish or Cloudflare at that moment. Without
the purge, verification fails and the reason is invisible.

The header/footer code boxes render only for users with `unfiltered_html` (administrators on a
single site), and their contents are output verbatim — that is the point of them, and it is why
they are gated.

### Prefer a Domain property

DNS is on Cloudflare, so Search Console's **Domain** property is the better route: add the TXT
record it gives you rather than using the meta tag.

A Domain property covers http and https, www and non-www, and every subdomain in one place —
otherwise the same site reports as up to four separate properties with the data split between them.
It also cannot be broken by a theme change or a caching layer serving a stale page.

## Meta descriptions and social tags

WordPress core emits no meta description, so without this the site had none at all.

**Settings → RomsFun SEO** holds the homepage description. Every other page derives one
automatically:

| Page | Source |
|---|---|
| Homepage | The field, falling back to the site tagline |
| ROM / post / page | Excerpt, falling back to trimmed content |
| Taxonomy hub | Term description, falling back to a generated line naming the term and its count |

Trimming breaks on a word boundary at 158 characters rather than mid-word.

Open Graph and Twitter tags are emitted alongside, since they share the same description and image.
`summary_large_image` is only claimed when an image actually exists — claiming it without one
produces a broken preview rather than a large one.

### It stands down for a real SEO plugin

If Rank Math, Yoast, AIOSEO, SEOPress or The SEO Framework is activated, this outputs nothing.

Two competing descriptions or two sets of Open Graph tags on a page is worse than having neither,
and it is exactly the kind of conflict that stays invisible until someone views source and wonders
why their social previews are wrong.

## Emulator pages

`single-emulator.php` mirrors the ROM page so the two read as one system, with the fields an
emulator actually has — version, developer, licence, last updated, official site — rather than
region and checksums.

### `platform` vs `console`

A second taxonomy, and the distinction matters: **`console` is what an emulator plays, `platform`
is what it runs on.** PPSSPP emulates PSP (console) and runs on Windows, macOS, Linux and Android
(platform). Conflating them makes both filters useless — you could no longer ask "which emulators
run on Android" or "which emulators play PSP games" separately.

Platform pills are styled green against the brand-pink console pill so the two families are
distinguishable at a glance, using tokens so they stay legible in dark mode.

### Popular ROMs for this emulator

Each emulator page lists the most-downloaded ROMs for the consoles it emulates. It is the most
useful thing on the page for a visitor who has just downloaded the emulator, and it feeds authority
from a guide page straight back into the catalogue.

### Schema

Emulators emit plain `SoftwareApplication` — **not** `VideoGame`. An emulator is not a game, and
claiming a type that does not match the page is how structured data gets ignored altogether.

Ratings, screenshots and comments all work on emulator pages, sharing the same code as ROMs.
