# Competitor Intel — romsfun.com

Gathered 2026-09-03 via WPThemeDetector. Not independently verified from this environment
(outbound requests to the domain are blocked by the agent proxy), so treat as detector-reported.

## Stack

| | |
|---|---|
| CMS | **WordPress** |
| Theme | `ROMSFUN-SYSTEM` v1.2 — fully custom, not a marketplace theme |
| Theme tags | TailwindCSS, php8 |
| License | MIT |
| Author / provider | "Trump" / `t.me/moocdit` (Telegram) |
| Downloadable | No |
| Plugins detected | 2 |

## What this tells us

**1. WordPress is validated at this scale.** The market leader runs 70,000+ ROM pages on
WordPress. Whatever doubts existed about the platform handling this catalogue size are settled —
the question is only whether it is configured correctly.

**2. They run a bespoke theme, not a page builder.** No Elementor, no Divi, no ThemeForest
multipurpose theme. This directly confirms the Step 1 decision to skip marketplace themes and
build our own templates. Tailwind + PHP 8 is essentially the same posture as a lean base theme
plus a custom child theme.

**3. Only two plugins detected.** They run extremely lean. Detection is imperfect — server-side
plugins like caching and SEO often leave no front-end fingerprint — but it is a strong signal that
they are not carrying plugin bloat. Reinforces our deliberately short plugin list.

**4. The theme is not obtainable.** Marked "Not Downloadable". The plan is to reproduce the
*user experience and information architecture* — faceted catalogue, console/collection hubs,
download pages — with our own implementation. That is the part that carries SEO value anyway;
their CSS does not rank for them.

## Where they are actually beatable

Their advantage is content depth and domain age, not technology. Their weaknesses, in likely order
of exploitability:

| Opportunity | Why it is winnable |
|---|---|
| **Per-ROM content depth** | Programmatic catalogues are usually thin — title, size, download button. Genuinely useful per-ROM content (compatibility notes, emulator setup, regional differences, screenshots) outranks a database dump. |
| **Faceted crawl control** | Almost every large catalogue leaks millions of filter URLs into the index. Getting this right is a direct crawl-budget advantage. |
| **Core Web Vitals** | Tailwind + custom PHP is fast in principle, but image-heavy catalogue pages usually are not. Measurable, and we can target it. |
| **Schema coverage** | `VideoGame` / `SoftwareApplication` / `BreadcrumbList` markup done thoroughly wins rich results they may not hold. |
| **Internal linking** | Hub pages and related-ROM linking distribute authority to long-tail pages. Rarely done well on catalogue sites. |

## Platform decision

Staying on WordPress. See `docs/01-foundation.md`. Rationale recorded here so it is not re-litigated:
no CMS ranks better than another — Google indexes HTML. What ranks is content, crawl efficiency,
speed and links. WordPress additionally supplies mature SEO tooling (schema, sitemap sharding,
redirect management, bulk meta templating) that would otherwise have to be hand-built.
