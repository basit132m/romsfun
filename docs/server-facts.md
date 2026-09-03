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

## Access

- SSH: key-only (ed25519, `romsfun-vps`), passphrase-protected
- Admin user: `romsadmin` (sudo). Root SSH login disabled.

## Domain

`roms-fun.net` — not yet pointed at this box.
