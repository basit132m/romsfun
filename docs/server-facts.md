# Server Facts

Live reference for the production box. Update when anything changes.

## Host

| | |
|---|---|
| Provider | Hostinger VPS, KVM 2 |
| IP | `72.60.17.23` |
| Hostname | `srv1856283` |
| OS | Ubuntu 24.04.4 LTS |
| Panel | CloudPanel — `https://72.60.17.23:8443` (CLI: `clpctl`) |

## Resources (verified 2026-09-03)

| Resource | Actual |
|---|---|
| vCPU | 2 |
| RAM | 7.8 GB + 2 GB swap |
| Disk | 96 GB (`/dev/sda1`), 8% used |

## Stack — pre-installed by the CloudPanel image

`nginx`, `mysql`, `redis-server` all confirmed **active** at first boot. Redis does **not** need
installing; it only needs the `maxmemory` tuning in the runbook and the WordPress-side plugin.

## Hardening applied (2026-09-03)

| Control | State |
|---|---|
| Root SSH login | disabled (`/etc/ssh/sshd_config.d/00-romsfun-hardening.conf`) |
| Password auth | disabled — keys only |
| ufw | active; `deny incoming`; open: 22, 80, 443, 443/udp, 8443 |
| fail2ban | active, `sshd` jail with `backend = systemd` |
| Unattended upgrades | enabled (`20auto-upgrades` both set to `"1"`) |

> **Ubuntu 24.04 gotcha:** fail2ban's default file backend reads `/var/log/auth.log`, which 24.04
> no longer writes. Without `backend = systemd` in `/etc/fail2ban/jail.local` the jail loads but
> silently bans nothing. Verify with `fail2ban-client status sshd` — the "Journal matches" line
> confirms it is reading journald.

## Applied tuning

| Layer | Setting | Value | Notes |
|---|---|---|---|
| MySQL | `innodb_buffer_pool_size` | **3G** (was 512M) | `/etc/mysql/mysql.conf.d/mysqld.cnf`; backup at `.bak` |
| PHP | `memory_limit` | 512M | via CloudPanel PHP Settings |
| PHP | `max_input_vars` | 10000 | **critical** — ACF and FacetWP silently truncate at the 1000 default |
| PHP | `max_execution_time` / `max_input_time` | 300 | |
| PHP | upload / post max size | 128M | |
| WordPress | `WP_POST_REVISIONS` | 5 | uncapped revisions across 70k posts bloats the DB past the buffer pool |
| WordPress | `DISALLOW_FILE_EDIT` | true | removes the dashboard code editor |
| WordPress | `WP_REDIS_HOST` / `WP_CACHE_KEY_SALT` | 127.0.0.1 / `romsfun_` | |

The buffer pool is the highest-value setting on the box: `wp_postmeta` will exceed a million rows
at 70k ROMs and every faceted query joins against it. At 512M those index pages are evicted
constantly and each filter click hits disk.

## Database

Percona Server **8.4.11-11**. Root uses password auth, not socket auth — `sudo mysql` alone fails.
Credentials via `clpctl db:show:master-credentials`.

Config lives in `/etc/mysql/mysql.conf.d/mysqld.cnf` (not the Debian-standard location some guides
assume). `innodb_buffer_pool_instances` left at CloudPanel's 8.

## WordPress install

| | |
|---|---|
| Doc root | `/home/RomsFun/htdocs/roms-fun.net` |
| Site user | `RomsFun` (capitalised — note when globbing paths) |
| PHP | 8.3, `php8.3-redis` present |
| SSL | Let's Encrypt, issued while grey-clouded; Cloudflare now proxying at Full (strict) |

> A non-root admin cannot `ls` into `/home/<siteuser>/` — CloudPanel locks site home directories
> down. Use `sudo find` rather than a glob when locating site paths.

## Also on the image

Varnish, plus PHP-FPM 7.1 through 8.5. Varnish is a caching layer we can use in Phase 7 rather
than adding another one.

## Access

- SSH: key-only (ed25519, `romsfun-vps`), passphrase-protected
- Admin user: `romsadmin` (sudo). Root SSH login disabled.

## Domain

Registrar **Namecheap**, DNS on **Cloudflare** (free plan). Verified resolving to `72.60.17.23`
on 2026-09-03.

| Record | Value | Proxy |
|---|---|---|
| `roms-fun.net` A | `72.60.17.23` | DNS only |
| `www` CNAME | `roms-fun.net` | DNS only |

Grey-clouded deliberately so Let's Encrypt can validate. **Turn proxying on only after the
certificate is issued.**

### Prior host

The domain previously sat on a cPanel host (`104.207.79.4`, mail via `jellyfish.systems`). The
account was empty — the site served a bare `Index of /` directory listing, so there was no content
or ranking to preserve and no redirects were needed.

Deleted 14 dead cPanel records: `cpanel`, `cpcalendars`, `cpcontacts`, `webdisk`, `whm` (A),
`ftp` (CNAME), and the `_caldav*`/`_carddav*` SRV + TXT pairs.

### Mail records — retained pending confirmation

Kept until the owner confirms whether `@roms-fun.net` email is in use: 3x MX to
`jellyfish.systems`, `default._domainkey` (DKIM), `_dmarc`, the SPF TXT, `_autodiscover._tcp`,
and the `mail`/`webmail`/`autoconfig`/`autodiscover` A records.

> The SPF record still authorises the old host (`ip4:104.207.79.2`). Harmless while no mail is
> sent from the domain, but it must be corrected or removed before any mail is sent from this
> server. Cloudflare's importer also orange-clouds mail A records by default, which breaks
> delivery — all have been set back to DNS only.
