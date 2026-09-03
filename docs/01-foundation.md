# Step 1 — Foundation & Stack

Goal of this step: a running, empty, fast WordPress install on the right hosting with the right
theme and a locked plugin list. No content, no design yet.

---

## 1.1 Hosting

A ROM library is **not** a blog. Sizing assumptions:

- ~70,000 `rom` posts, each with 10–20 custom field rows → ~1M+ rows in `wp_postmeta`
- Faceted filtering = expensive multi-`JOIN` queries on that table
- Heavy anonymous traffic, high image count (box art), long-tail search entry

Shared hosting (Hostinger/Bluehost/GoDaddy) will fall over on the filtered archive queries.
What you actually need:

| Requirement | Why |
|---|---|
| 4+ GB RAM VPS or managed cloud | Faceted queries + 70k index |
| **Redis or Memcached object cache** | Non-negotiable. Without it every filter click hits MySQL raw. |
| PHP 8.2+, MySQL 8 / MariaDB 10.6+ | Query performance, and MySQL 8 handles the meta joins far better |
| NVMe storage | Index reads |
| Server-level page cache (NGINX FastCGI / Varnish) | Serves anonymous traffic without booting PHP |

**Chosen: Hostinger VPS KVM 2** — 2 vCPU, 8 GB RAM, 100 GB NVMe, 8 TB bandwidth.

Assessment against the requirements above:

| Resource | KVM 2 | Verdict |
|---|---|---|
| RAM | 8 GB | Comfortable. Leaves room for a 3 GB InnoDB buffer pool + Redis + PHP-FPM. |
| vCPU | 2 | The real constraint. Fine **provided** full-page caching is working, since cached hits barely touch PHP. Uncached filter queries are what will hurt. |
| Storage | 100 GB NVMe | Plenty for metadata + box art. Would be nothing if ROM files lived here. |
| Bandwidth | 8 TB | Generous for a metadata site. A single popular ROM file would eat it in days. |

The CPU count makes Phase 7 (caching) load-bearing rather than optional, and the
bandwidth/storage figures are the practical argument for keeping ROM files off this box.

See [01-server-setup-runbook.md](01-server-setup-runbook.md) for the provisioning steps.

## 1.2 Theme

Skip ThemeForest multipurpose themes (Newspaper, JNews, Rehub). They ship page builders and 40
features you won't use, and they will cost you Core Web Vitals — which is a ranking factor and
matters a lot on a site competing on thousands of long-tail queries.

**Recommended: GeneratePress Premium ($59/yr, or $249 lifetime) + a custom child theme.**

Why GeneratePress specifically:

- ~10KB base CSS, consistently top of Core Web Vitals benchmarks
- First-class custom post type support — its Block Element system lets us build the single-ROM
  template and the console archive template as real templates, hooked by taxonomy
- No page builder dependency; templates stay in PHP/blocks we control in this repo
- Stable, maintained since 2014, no lock-in

**Runner-up: Blocksy Pro** — more design features out of the box, slightly heavier. Fine if you
want more visual control and less code.

We will write a **child theme** either way, committed to this repo, so all customisation is version
controlled and survives theme updates.

## 1.3 Plugins

The rule: every plugin is a performance and security liability. This list is deliberately short,
and each entry earns its place.

### Essential — the site does not work without these

| Plugin | Role | Cost |
|---|---|---|
| **Rank Math Pro** | SEO. Chosen over Yoast for CPT/taxonomy schema control, better bulk title/meta templating, and free-tier redirects + 404 monitor. Critical for Phase 6. | ~$79/yr |
| **Advanced Custom Fields Pro** | ROM metadata (file size, region, version, download links, emulator). Repeater fields handle multi-link downloads. | $49/yr |
| **FacetWP** | The hero filter bar (Console/Genre/Collection/Type/Sort). Builds its own index table so filtering doesn't hammer `wp_postmeta`. This is the piece that makes 70k records filterable. | $99/yr |
| **WP All Import Pro** | Bulk-import the catalogue from CSV/XML in Phase 3, and re-sync it later. Doing 70k records by hand is not a plan. | $199 one-time |
| **Redis Object Cache** | Wires WordPress to the Redis instance. Free. | Free |

### Strongly recommended

| Plugin | Role | Cost |
|---|---|---|
| **Perfmatters** | Disable unused WP features per-page, defer JS, lazy-load. Cheaper and lighter than WP Rocket if the host already does page caching. | $25/yr |
| **SearchWP** | Real relevance-ranked search for the hero search box. Native WP search is genuinely bad at 70k records. Can be deferred to Phase 5. | $99/yr |
| **Wordfence** or **Solid Security** | Login hardening, firewall. | Free tier fine |
| **UpdraftPlus** | Offsite backups. Non-negotiable once the catalogue is imported. | Free tier fine |

### Explicitly avoid

- **Elementor / WPBakery / Divi** — page builders on a 70k-post site are a Core Web Vitals disaster
  and make templating harder, not easier. Our templates are code.
- **Jetpack** — bloat, and its stats module is a performance cost for something GA4 does free.
- **Any "all-in-one SEO + cache + security" bundle** — they conflict and none of them do any of
  the three well.
- **Broken Link Checker (on-site version)** — it will crawl 70k posts and destroy your server.

### Running total

Year one: roughly **$550–700** in software, plus ~$400–700/yr hosting.

The paid picks are FacetWP, ACF Pro, WP All Import and Rank Math. There are free alternatives
(Facetious, Meta Box Lite/CMB2, WP Ultimate CSV Importer, Rank Math free) — they work, but each
costs meaningful development time and two of them will not scale cleanly to 70k records. Worth
deciding consciously rather than by default.

## 1.4 Cloudflare

Set up in front of the site from day one:

- Free plan is sufficient to start
- CDN for images and static assets
- **Cache Rules** to cache HTML for anonymous visitors
- WAF for basic bot filtering

## 1.5 Definition of done for Step 1

- [ ] Hosting provisioned, PHP 8.2+, Redis running
- [ ] `roms-fun.net` pointed at it, SSL live, `https://` forced
- [ ] WordPress installed, admin user is **not** named `admin`
- [ ] Permalinks set to `/%postname%/`
- [ ] Default plugins (Hello Dolly, Akismet) deleted, default themes except one deleted
- [ ] GeneratePress + child theme installed and active
- [ ] Plugin list above installed and licensed
- [ ] Redis Object Cache enabled and confirmed connected
- [ ] Cloudflare proxying, HTML cache rule active
- [ ] Staging environment created — we never build on production
