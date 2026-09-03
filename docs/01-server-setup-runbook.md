# Step 1 Runbook — Hostinger KVM 2 → running WordPress

Takes you from a freshly purchased VPS to a secured, tuned, empty WordPress install on
`roms-fun.net`. No theme, no content yet — that is Step 2 onward.

Work through these in order. Do not skip 1 and 2; a VPS with password SSH exposed to the
internet gets brute-forced within hours, and a compromised box means starting over.

---

## 0. Choose the control panel

You are managing this yourself, so you want a panel rather than hand-rolling NGINX.

**Recommended: CloudPanel.** Free, NGINX + PHP-FPM, Redis and MySQL built in, clean security
record, and Hostinger offers it as a one-click OS template. It is the low-drama choice.

**Alternative: CyberPanel (OpenLiteSpeed).** Genuinely faster for WordPress because the free
LiteSpeed Cache plugin does page cache, Redis object cache, critical CSS and image optimisation in
one integrated stack. The catch is CyberPanel's security track record — it had a serious
unauthenticated RCE in late 2024. Viable if you commit to patching it promptly and firewalling the
panel port; otherwise take CloudPanel.

The rest of this runbook assumes **CloudPanel on Ubuntu 24.04**.

## 1. Provision the OS

In Hostinger hPanel → VPS → your server → **Operating System**:

1. Choose **Ubuntu 24.04 with CloudPanel** from the application templates.
2. Let it rebuild. This wipes the server — do it now, before anything is on it.
3. Note the panel URL (`https://<your-ip>:8443`) and the root password Hostinger shows you.
4. Open the panel, create your admin user immediately.

## 2. Secure the box

SSH in as root with the password Hostinger gave you, then:

```bash
# --- create a non-root admin user ---
adduser romsadmin
usermod -aG sudo romsadmin
```

From **your own machine**, push your SSH key up:

```bash
ssh-keygen -t ed25519 -C "romsfun-vps"        # skip if you already have a key
ssh-copy-id romsadmin@<your-vps-ip>
```

Confirm `ssh romsadmin@<your-vps-ip>` works **without a password** before continuing. Then back on
the server:

```bash
# --- disable password + root SSH login ---
sudo sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
sudo sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo systemctl restart ssh

# --- firewall ---
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow from <your-home-ip> to any port 8443 proto tcp   # panel: your IP only
sudo ufw enable
sudo ufw status verbose

# --- brute-force protection + unattended security updates ---
sudo apt update && sudo apt install -y fail2ban unattended-upgrades
sudo systemctl enable --now fail2ban
sudo dpkg-reconfigure -plow unattended-upgrades
```

> If your home IP is dynamic, use a VPN's static IP for the 8443 rule, or accept `ufw allow
> 8443/tcp` and rely on the panel's own 2FA. Do not leave 8443 open *and* unprotected.

**Checkpoint:** `ssh root@ip` must fail, `ssh romsadmin@ip` must succeed with a key, and
`https://ip:8443` must be reachable only from your IP.

## 3. DNS via Cloudflare

1. Add `roms-fun.net` to Cloudflare (free plan), change the nameservers at your registrar.
2. Add records — **grey cloud / DNS-only for now**, so Let's Encrypt can validate:

   | Type | Name | Content | Proxy |
   |---|---|---|---|
   | A | `@` | `<your-vps-ip>` | DNS only |
   | A | `www` | `<your-vps-ip>` | DNS only |

3. Wait for propagation: `dig +short roms-fun.net` should return your VPS IP.

We turn the orange cloud on in step 8, after SSL exists.

## 4. Create the site

In CloudPanel → **Add Site** → *Create a WordPress Site*:

- Domain: `roms-fun.net`
- PHP version: **8.3**
- Set a strong site user password; save the DB credentials it generates

Then **SSL/TLS → New Let's Encrypt Certificate** for `roms-fun.net` and `www.roms-fun.net`.
This only works while Cloudflare is grey-clouded.

## 5. Tune PHP

CloudPanel → site → **Settings → PHP**. These defaults will break WP All Import and ACF later, so
set them now:

```ini
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
max_input_vars = 10000
upload_max_filesize = 128M
post_max_size = 128M

; opcache sized for a large plugin/theme surface
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 30000
opcache.revalidate_freq = 60
```

`max_input_vars = 10000` matters more than it looks — ACF field groups and FacetWP's settings
screens silently truncate on the default of 1000, and the bug is miserable to diagnose.

## 6. Tune MySQL

The default InnoDB buffer pool is far too small to hold a 70k-post index. Edit
`/etc/mysql/mariadb.conf.d/50-server.cnf` (or `/etc/mysql/my.cnf`):

```ini
[mysqld]
innodb_buffer_pool_size      = 3G
innodb_buffer_pool_instances = 3
innodb_log_file_size         = 512M
innodb_flush_method          = O_DIRECT
innodb_flush_log_at_trx_commit = 2
max_connections              = 100
tmp_table_size               = 128M
max_heap_table_size          = 128M
```

```bash
sudo systemctl restart mysql   # or mariadb
```

The 3 GB buffer pool is the single highest-value line in this runbook. It lets the whole
`wp_postmeta` index live in RAM instead of hitting disk on every filter query.

## 7. Redis object cache

```bash
sudo apt install -y redis-server
sudo sed -i 's/^# *maxmemory .*/maxmemory 512mb/' /etc/redis/redis.conf
sudo sed -i 's/^# *maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
sudo systemctl enable --now redis-server
redis-cli ping     # expect: PONG
```

Then in WordPress: install the **Redis Object Cache** plugin → Settings → Enable. It must report
*Status: Connected*. If it doesn't, the PHP redis extension is missing:
`sudo apt install php8.3-redis && sudo systemctl restart php8.3-fpm`.

## 8. Cloudflare proxy on

Back in Cloudflare, now that SSL is issued:

1. Flip both A records to **orange cloud (Proxied)**.
2. **SSL/TLS → Overview → Full (strict)**. Anything less is either broken or insecure.
3. **SSL/TLS → Edge Certificates →** enable *Always Use HTTPS* and *Automatic HTTPS Rewrites*.
4. **Speed → Optimization →** enable Brotli.
5. Leave Auto Minify and Rocket Loader **off** — Rocket Loader breaks jQuery-dependent admin and
   filter scripts, and we will handle minification at the WordPress layer.

## 9. WordPress hardening + cleanup

In WP Admin:

- Delete the default plugins (Hello Dolly, Akismet) and all but one default theme
- Users → confirm the admin account is **not** called `admin`; delete any that is
- Settings → Permalinks → **Post name** (`/%postname%/`)
- Settings → Discussion → uncheck *Allow people to submit comments on new posts* (we enable
  comments on blog posts only, later)
- Settings → General → uncheck *Anyone can register* until we actually build accounts

Add to `wp-config.php`, above the `/* That's all, stop editing! */` line:

```php
define( 'DISALLOW_FILE_EDIT', true );   // no plugin/theme editing from the dashboard
define( 'WP_POST_REVISIONS', 5 );       // 70k posts × unlimited revisions = a bloated DB
define( 'EMPTY_TRASH_DAYS', 7 );
define( 'WP_MEMORY_LIMIT', '512M' );
define( 'WP_MAX_MEMORY_LIMIT', '512M' );
```

`WP_POST_REVISIONS` matters at this scale — uncapped revisions on 70k posts is one of the most
common causes of a WordPress database ballooning past what the buffer pool can hold.

## 10. Staging

CloudPanel → **Clone Site** → `staging.roms-fun.net`. Then either password-protect it in the panel
or add a `Disallow: /` robots rule — a crawlable staging copy is duplicate content pointed straight
at your own site.

**We build on staging and push to production.** Not the other way round.

---

## Definition of done

- [ ] Ubuntu 24.04 + CloudPanel installed
- [ ] Root SSH and password auth disabled; key auth working; `ufw` + `fail2ban` active
- [ ] Panel port 8443 restricted
- [ ] `roms-fun.net` resolves to the VPS; SSL live; HTTPS forced
- [ ] PHP tuned (`max_input_vars = 10000` especially)
- [ ] `innodb_buffer_pool_size = 3G` applied and MySQL restarted
- [ ] Redis Object Cache reports **Connected**
- [ ] Cloudflare proxied, Full (strict), Rocket Loader off
- [ ] WordPress cleaned up, permalinks set, `wp-config.php` constants added
- [ ] Staging site exists and is not indexable

## Verification one-liners

```bash
sudo ufw status                                   # firewall rules
redis-cli info stats | grep keyspace              # Redis is being written to
mysql -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"   # expect 3221225472
curl -sI https://roms-fun.net | head -20          # 200 + HSTS + cf-ray header
```
