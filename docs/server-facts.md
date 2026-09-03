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

## Also on the image

Varnish, plus PHP-FPM 7.1 through 8.5. Varnish is a caching layer we can use in Phase 7 rather
than adding another one.

## Access

- SSH: key-only (ed25519, `romsfun-vps`), passphrase-protected
- Admin user: `romsadmin` (sudo). Root SSH login disabled.

## Domain

`roms-fun.net` — registered at **Namecheap**. Not yet pointed at this box; going via Cloudflare DNS.
