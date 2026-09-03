# RomsFun — WordPress Build

Rebuilding a romsfun.com-style retro game ROM library at **https://roms-fun.net/** on WordPress,
with SEO as a first-class architecture concern (not a plugin you install at the end).

## Repo purpose

This repo holds the build plan, the child theme, the custom plugin (post types, taxonomies,
schema, importers), and any scripts used to populate the catalogue.

## Documentation

| Doc | What it covers |
|---|---|
| [docs/00-roadmap.md](docs/00-roadmap.md) | The full 9-phase build order, one line each |
| [docs/01-foundation.md](docs/01-foundation.md) | **Step 1** — stack, hosting, theme, plugin shortlist |
| [docs/competitor-romsfun-com.md](docs/competitor-romsfun-com.md) | What romsfun.com actually runs, and where it is beatable |
| [docs/server-facts.md](docs/server-facts.md) | Live server reference — IP, specs, stack, access |
| [docs/01-server-setup-runbook.md](docs/01-server-setup-runbook.md) | **Step 1 runbook** — Hostinger KVM 2 → secured, tuned, empty WordPress |

## Status

- [x] Step 1a — Foundation & stack decisions (hosting: Hostinger VPS KVM 2)
- [x] Step 1b — Server provisioned and hardened
- [x] Step 1c — DNS, WordPress, SSL, tuning
- [ ] Step 2 — Content architecture
- [ ] Step 3 onward — see roadmap
