# Step 2 — Content Architecture

The SEO skeleton. Everything from here builds on it, and it is the hardest layer to change once
content exists — altering a URL pattern after 70,000 ROMs are published means 70,000 redirects.

Implemented as a plugin in `plugin/romsfun-core/`, deliberately **not** in the theme: the
catalogue has to survive a redesign. A theme holding 70,000 posts hostage is the most common
reason these sites can never be rebuilt.

---

## Content types

| Type | Purpose | Archive |
|---|---|---|
| `rom` | One downloadable ROM. The money pages — ~70,000 of them. | `/roms/` |
| `emulator` | One emulator, per platform. Captures "how to play X" intent. | `/emulators/` |
| `post` (built-in) | News and guides. Topical authority + internal links into the catalogue. | `/blog/` |
| `page` (built-in) | About, contact, DMCA, FAQ. | — |

## Taxonomies

All attached to `rom`. Each is both a **filter facet** and an **indexable hub page** — that dual
role is exactly why they are taxonomies rather than custom fields. A custom field can filter; only
a taxonomy gives you an archive URL that can rank.

| Taxonomy | Slug | Hierarchical | Example terms |
|---|---|---|---|
| `console` | `/console/` | ✅ | PS2, PSP, GBA, NES, Windows |
| `genre` | `/genre/` | ✅ | Action, RPG, Racing, Fighting |
| `collection` | `/collection/` | ❌ | God of War, Call of Duty, Arcade |
| `rom_type` | `/type/` | ❌ | Fan Translation, Hack, Repack, Homebrew |
| `region` | `/region/` | ❌ | USA, Europe, Japan, World |

`console` and `genre` are hierarchical so platform and genre families group naturally
(Nintendo → GBA). That gives breadcrumbs and parent hub pages that can rank for broader terms.

## URL structure

| Content | URL |
|---|---|
| ROM | `/roms/{console}/{rom-slug}/` |
| ROM archive | `/roms/` |
| Console hub | `/console/{console}/` |
| Genre hub | `/genre/{genre}/` |
| Collection hub | `/collection/{collection}/` |
| Type hub | `/type/{type}/` |
| Emulator | `/emulators/{slug}/` |
| Blog post | `/blog/{post-slug}/` |

### Why the console sits in the ROM URL

Searchers type the platform alongside the title — "god of war psp rom", not "god of war rom". A
keyword in the path is worth more than a tidier flat URL, and the same title on three consoles is
three genuinely different files that deserve three pages.

The cost is that a ROM's URL depends on its console term. Two mitigations are built in:

1. An explicit `_primary_console` meta value pins the URL, so it never shifts because an editor
   reordered terms.
2. The rewrite rule **ignores the console segment when resolving the post** — the slug alone
   identifies it. A ROM whose console changes still resolves at its old URL rather than 404ing,
   and the canonical tag points crawlers at the current one.

## Custom fields on `rom`

Via ACF Pro (or CMB2/Meta Box on the free path — the field names below are what matters, and they
stay identical either way).

| Field | Type | Notes |
|---|---|---|
| `file_size` | number (bytes) | Store bytes, format on output. Sortable and filterable; "1.2 GB" as text is neither. |
| `download_links` | repeater | label, url, mirror, size. Multiple mirrors per ROM. |
| `version` | text | e.g. `v1.02`, `Rev A` |
| `release_year` | number | Filter facet and schema field |
| `developer` / `publisher` | text | Schema `author` / `publisher` |
| `languages` | multi-select | |
| `md5` / `sha1` | text | Redump/No-Intro verification — real differentiating content |
| `download_count` | number | Sort facet; social proof |
| `view_count` | number | |
| `recommended_emulator` | relationship → `emulator` | Powers cross-linking between catalogue and guides |
| `unique_description` | wysiwyg | **Required.** See thin content below. |
| `screenshots` | gallery | |

Box art uses the built-in featured image.

---

## The two decisions that decide whether this ranks

### 1. Faceted URLs — crawl budget

Five filters with dozens of values each generate millions of crawlable combinations. Left alone,
Googlebot spends its entire crawl budget on `?genre=action&sort=downloads&region=usa` permutations
and never reaches your actual ROM pages. This is the single most common way large catalogue sites
fail.

The rules:

| URL shape | Treatment |
|---|---|
| `/console/psp/` — single taxonomy term | **Index, follow.** Canonical to self. These are hub pages that rank. |
| `/console/psp/page/2/` — pagination | **Index, follow.** Self-canonical. Never canonical to page 1 — that de-indexes the deeper items. |
| `?genre=`, `?type=`, `?region=` — filters | **`noindex, follow`**, canonical to the unfiltered archive. |
| `?sort=`, `?order=`, `?view=` — presentation | **`Disallow`** in robots.txt. These never change the result set, only its order. |
| Deliberate high-value combinations | Promote to real indexable pages (see below). |

**The exception that wins traffic:** a handful of two-facet combinations have genuine search demand
— "PSP RPG ROMs", "GBA fan translations". Those get promoted to real, statically-defined landing
pages with their own copy and self-canonical, rather than being left as filtered query strings.
Curated, in the tens; never generated in the thousands.

### 2. Thin content — the 70,000-page problem

70,000 pages differing only by title get partially indexed or trigger a site-wide quality
demotion. `unique_description` is marked required for exactly this reason.

Every ROM page must carry something no database dump provides:

- What the ROM actually is, in prose — not a spec table restated
- Which emulator runs it best, and the settings that matter
- Regional differences (censorship, translation, exclusive content)
- Known issues on common emulators
- File verification (`md5`/`sha1`) — genuinely useful, and almost nobody publishes it
- Related ROMs from the same collection

The competitor's likely weakness is precisely here. Programmatic catalogues are almost always
thin, and out-researching them page by page is slower than scraping but is the durable advantage.

---

## Installing the plugin

```bash
# From the repo, upload plugin/romsfun-core/ to the site:
sudo cp -r plugin/romsfun-core /home/RomsFun/htdocs/roms-fun.net/wp-content/plugins/
sudo chown -R RomsFun:RomsFun /home/RomsFun/htdocs/roms-fun.net/wp-content/plugins/romsfun-core
```

Then activate it in **Plugins → Installed Plugins**, and visit
**Settings → Permalinks → Save Changes** once to flush rewrite rules.

### Verify

- `/roms/` lists ROMs
- A published ROM with a console term resolves at `/roms/{console}/{slug}/`
- `/console/`, `/genre/`, `/collection/`, `/type/`, `/region/` archives all load
- Changing a ROM's console does not 404 the old URL
