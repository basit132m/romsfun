# Build Roadmap

Nine phases. We do one at a time and do not move on until the previous one is live and verified.
The order is deliberate: every phase depends on the one before it, and doing them out of order
(especially building the design before the data model) is the single most common way these
projects end up needing a rebuild.

| # | Phase | Why it comes here |
|---|---|---|
| 1 | **Foundation & stack** | Hosting, domain, WordPress install, theme + plugin decisions. Everything else sits on this. |
| 2 | **Content architecture** | Custom post types, taxonomies, custom fields, URL structure. This is the SEO skeleton — changing it later means mass redirects. |
| 3 | **Data import** | Bulk-load the ROM catalogue from CSV/API. Done before design so we style against real data, not lorem ipsum. |
| 4 | **Theme & templates** | Child theme, single-ROM template, console/genre/collection archives, homepage. |
| 5 | **Search & faceted filtering** | The hero search + Console/Genre/Collection/Type/Sort filters. Needs indexing infrastructure, not `WP_Query`. |
| 6 | **Technical SEO** | Titles, metas, canonicals, schema, sitemaps, crawl budget, faceted-URL control, Core Web Vitals. |
| 7 | **Performance & scale** | Object cache, page cache, CDN, image pipeline, DB indexes. A 70k-post site behaves nothing like a blog. |
| 8 | **Content SEO & internal linking** | Thin-content mitigation, hub pages, related ROMs, blog cluster strategy. |
| 9 | **Launch & monitoring** | Search Console, analytics, index coverage, rank tracking, backup/security hardening. |

## The one thing that makes or breaks this site

It is a **programmatic SEO** site: ~70,000 near-identical pages generated from a database.
Google handles these badly by default. Two failure modes kill sites like this:

1. **Thin/duplicate content** — 70k pages that differ only by title get partially indexed, or
   trigger a site-wide quality demotion. Phase 8 exists entirely to prevent this.
2. **Crawl traps from faceted URLs** — 5 filters × dozens of values each generates millions of
   crawlable URL combinations. Googlebot burns its entire crawl budget on them and never reaches
   your actual ROM pages. Phase 6 handles this, but Phase 5 has to be *built* with it in mind.

Design and plugins are the easy part. These two are the project.

## One architecture constraint worth naming up front

Distributing commercial game ROMs is copyright infringement in most jurisdictions, and it shapes
real technical decisions in Phase 1 — mainstream managed hosts, Cloudflare, and most registrars
act on DMCA notices, and a takedown that hits your WordPress host takes the whole site down, not
just a file. The standard architecture response is to keep the **WordPress site and the ROM files
completely separate**: WordPress holds metadata, descriptions, and links only; files live on
independent storage under a different provider. That separation is worth building in from day one
whichever way you go, because it also happens to be the right call for bandwidth and cost.
