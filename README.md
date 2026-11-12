# japjapTV

**Your Channels. One Streaming Hub.**

japjapTV is a PHP 8 + MySQL live TV directory and player built for XAMPP/Apache. It combines a responsive public catalogue, an official-source watch page, a Philippine/international/sports/NBA discovery experience, and a protected admin console.

- **No visitor accounts.** The public site is browse-and-watch; only administrators sign in.
- **No pirated streams.** The player only embeds sources confirmed as official and authorized. Everything else links to the broadcaster's own destination.
- **No invented availability.** Channels are explicitly labelled `Live now`, `Official site`, `Unavailable`, or `Coming soon`. A broken or restricted player never silently turns into an unofficial feed.
- **No NBA restreams.** The NBA page uses ESPN's public scoreboard for schedule reference and points to League Pass, NBA TV, and NBA TV Philippines for official access.

The seeded catalogue contains **60 channels** across the Philippines and international markets. At the verification snapshot, **27 channels** have an official YouTube livestream source; the rest are official-destination or reference entries. See [CHANNEL_SOURCES.md](CHANNEL_SOURCES.md) for the source audit and update workflow.

---

## Requirements

| Component | Requirement |
|---|---|
| PHP | 8.1 or newer (tested with PHP 8.2.12) |
| PHP extensions | `pdo_mysql`, `curl`, `fileinfo`, `gd`, `mbstring`, `openssl`, `json` |
| Database | MySQL 8.0+ or MariaDB 10.4+ |
| Web server | Apache 2.4 with `mod_headers`, or the PHP built-in server for local development only |
| Optional | `mod_rewrite` is not required; no URL rewriting is used |
| Browser | Current Chrome, Edge, Firefox, or Safari |

XAMPP 8.2 includes everything needed. Confirm the extensions in `C:\xampp\php\php.ini` are enabled (remove the leading `;` if necessary):

```ini
extension=pdo_mysql
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
```

---

## Quick start: XAMPP on Windows

### 1. Place the application in the web root

Copy the `japjapTV` folder to:

```text
C:\xampp\htdocs\japjapTV
```

The folder name is part of the default URL. If you use a different name or a virtual host, the application detects its base URL automatically.

### 2. Start Apache and MySQL

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### 3. Import the database

The generated `database.sql` creates `japjaptv_db`, its tables, the seed catalogue, and the bootstrap administrator. It includes `CREATE DATABASE IF NOT EXISTS` and `USE japjaptv_db`, so you do not need to create the database first.

**Option A — command line (recommended):**

```powershell
cd C:\xampp\htdocs\japjapTV
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database.sql
```

If the MySQL root account has a password:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -p --default-character-set=utf8mb4 < database.sql
```

**Option B — phpMyAdmin:**

1. Open <http://localhost/phpmyadmin/>.
2. Select **Import**.
3. Choose `database.sql` and import with UTF-8 (`utf8mb4`).
4. Confirm that the `japjaptv_db` database appears.

> **Destructive:** importing `database.sql` drops and recreates the japjapTV tables. Do not import it over a database containing data you want to keep. Back up first with `mysqldump`.

### 4. Configure the connection

The defaults in `config/config.php` match XAMPP:

```php
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = japjaptv_db
DB_USER = root
DB_PASS = (blank)
```

Edit those defaults, or set environment variables before Apache starts:

```text
JAPJAP_DB_HOST=127.0.0.1
JAPJAP_DB_PORT=3306
JAPJAP_DB_NAME=japjaptv_db
JAPJAP_DB_USER=your_database_user
JAPJAP_DB_PASS=your_database_password
JAPJAP_BASE_URL=http://localhost/japjapTV
JAPJAP_ENV=production
JAPJAP_DEBUG=0
```

`JAPJAP_BASE_URL` is optional when the app is served from a normal XAMPP subfolder. Set it when a reverse proxy or unusual document root prevents automatic detection.

For Apache, environment variables can be supplied with `SetEnv` in the virtual host. On Windows, do not rely on shell variables being inherited by the Apache service.

### 5. Ensure Apache can read `.htaccess`

XAMPP's default `C:\xampp\apache\conf\httpd.conf` already contains `AllowOverride All` for `C:/xampp/htdocs`. Confirm it:

```apache
<Directory "C:/xampp/htdocs">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require all granted
</Directory>
```

Restart Apache after changing `httpd.conf`. The `.htaccess` rules protect:

- `config/` (database credentials)
- `includes/` and `admin/includes/`
- `tools/`, `bin/`, `logs/`, and `storage/`
- `uploads/` (uploads are served as images only; script execution is blocked)
- `database.sql`, `.env`, logs, backups, Markdown documentation, and source-control metadata
- directory listings

Verify from a browser after installation. These should return **403 Forbidden**, not file contents:

```text
http://localhost/japjapTV/config/database.php
http://localhost/japjapTV/database.sql
http://localhost/japjapTV/includes/functions.php
```

If they return 200, Apache is ignoring `.htaccess`; fix `AllowOverride` before putting the site online.

### 6. Open the site and sign in

- Public site: <http://localhost/japjapTV/>
- Admin login: <http://localhost/japjapTV/admin/login.php>

### 7. Refresh verification status

The seeded status is a snapshot taken on **12 November 2026 (UTC)**. After importing, refresh it against the live internet:

```powershell
C:\xampp\php\php.exe bin\check_sources.php
```

Or sign in as an administrator and use **Sources → Check all sources**. See [Updating sources](#updating-sources) for the checker's behaviour.

---

## Default administrator credentials

```text
URL:      http://localhost/japjapTV/admin/login.php
Username: admin
Password: JapJapTV@2026!
```

The account is seeded with `must_change_password = 1`. You will be required to choose a new password immediately after the first login, before any other admin page can be opened.

- Change the database password and administrator password before deploying publicly.
- Do not commit a production `config/config.php` containing real credentials.
- Re-importing `database.sql` resets the administrator to the bootstrap credentials above and discards all content changes.

If you are locked out and have server access, reset the password to a new hash with PHP:

```powershell
C:\xampp\php\php.exe -r "echo password_hash('YourNewStrongPassword', PASSWORD_DEFAULT), PHP_EOL;"
```

Then update `admins.password_hash` (and set `must_change_password = 0` if you do not want the forced-change screen) in phpMyAdmin. Never use unhashed passwords.

---

## Public features

| Page | Purpose |
|---|---|
| `index.php` | Featured channel strip, catalogue highlights, categories, stats, and NBA entry points |
| `channels.php` | Full catalogue with search, country, category, access, availability, region, and sort filters |
| `watch.php?id=channel-slug` | Integrated official player, channel metadata, source selector, official-link fallback, previous/next navigation |
| `philippines.php` | Philippine channels and regional quick filters |
| `international.php` | International channels grouped by market |
| `sports.php` | Sports channels and official sports destinations |
| `nba.php` | ESPN-backed NBA scoreboard for a selected Eastern-Time game day, PHT/ET tip-off times, and an official viewing guide |
| `search.php` | Full-catalogue search with the shared filter panel |
| `about.php` | Project purpose, source policy, and accessibility notes |
| `contact.php` | CSRF-protected, rate-limited correction/report form |
| `privacy.php` / `terms.php` | Privacy and use policies |
| `404.php` | Friendly not-found page |

### Watch page and player

- Official YouTube live embeds use `https://www.youtube.com/embed/live_stream?channel=…` and native YouTube playback.
- Fixed official YouTube videos use the official embed URL.
- Approved HLS manifests use `hls.js` where the browser needs it; the native video element is used where possible.
- Approved iframe URLs are limited to the configured iframe allowlist.
- The custom control bar provides play/pause, mute, volume, fullscreen, status text, loading feedback, error/offline fallback, and retry.
- A `LIVE` badge is shown only for a configured live source; a fixed video is labelled `VIDEO`.
- External-only or unavailable channels never receive a fake player. They show a clear explanation and an **Open official destination** button.
- Previous/next links move through the filtered catalogue without making a channel look playable.
- All player links are validated as safe public HTTP(S) destinations before output.

### NBA page

- The schedule is read from ESPN's public scoreboard API and cached for five minutes (`NBA_CACHE_TTL`).
- Dates follow the NBA's Eastern Time game day, while tip-off times are displayed in both Philippine Time and Eastern Time.
- If ESPN is unreachable, saved rows in `nba_games` can provide a fallback schedule.
- The page never hosts, embeds, or implies a free NBA full-game stream. Official options are linked and paid access is labelled as a subscription:
  - NBA League Pass
  - NBA TV
  - NBA TV Philippines through Cignal Play
  - NBA's official YouTube channel for highlights, not live full games
- Blackout and territory rules are controlled by the NBA and its providers.

---

## Admin console

Sign in at `/admin/login.php`. Every page is protected by the shared admin bootstrap, session checks, inactivity timeout, and CSRF verification.

| Area | What you can do |
|---|---|
| Dashboard | Catalogue counts, source health, recent messages, quick actions |
| Channels | Create/edit/delete channels, upload/replace/remove logos, set access and availability, feature channels, control display order, view public page |
| Categories | Create/edit/delete categories, icons, colours, ordering; deletion is blocked while a category has channels |
| Countries | Create/edit/delete countries, ISO code, flag emoji, region, ordering; deletion is blocked while a country has channels |
| Sources | Add/edit/delete official sources, set priority, active/authorized flags, run one-source or all-source checks, inspect verification history |
| Featured picks | Add/remove homepage features and reorder them independently |
| NBA games | Manage optional local fallback games used only when ESPN is unavailable; times are entered in PHT and stored in UTC |
| Messages | Read visitor corrections, mark read/replied/archived, delete, and reply through the visitor's email client via `mailto:` |
| Account | Update display name/email, change password, review security notes |

### Logo uploads

- Accepted formats: PNG, JPEG, and WebP.
- Maximum file size: 2 MB.
- Maximum dimensions: 4000 × 4000 pixels.
- Files are verified with `finfo` and `getimagesize()`; the extension alone is never trusted.
- SVG and executable/script content are rejected.
- Files are stored under `uploads/channel_logos/` with randomized names.
- `uploads/.htaccess` disables PHP execution and denies script extensions.
- If no upload exists, an original generated text-mark placeholder is used. No official broadcaster logo is copied into the project.

### Contact messages

The contact form is public but protected by:

- a CSRF token,
- a hidden honeypot field,
- server-side validation,
- per-IP rate limiting,
- HTML escaping on output.

The application stores messages in `contact_messages`. It does **not** send email. When an administrator clicks reply, the visitor's email client opens with a pre-filled `mailto:` message. The administrator can mark a message read, replied, or archived, and delete it with a CSRF-protected POST action.

---

## Source policy and availability model

japjapTV only lists sources that are official and authorized.

### Allowed

- A broadcaster's own YouTube live channel, where the broadcaster permits embedding.
- A broadcaster's official YouTube video, official website live player, or official HLS manifest that you are authorized to use.
- An approved iframe destination explicitly added to `ALLOWED_IFRAME_HOSTS` after confirming the broadcaster permits embedding.
- An official external viewing page used as the fallback when embedding is not permitted.

### Not allowed

- Pirated IPTV playlists or restreams.
- Scraped or leaked player URLs.
- DRM circumvention, paywall bypass, or geo-restriction bypass.
- Paid feeds added without authorization.
- Any attempt to make an unavailable channel look like a working free stream.

### Availability values

| Value | Public label | Meaning |
|---|---|---|
| `available` | Live now | At least one active, authorized source is configured; the checker promotes this state when it confirms a live, embeddable YouTube source |
| `external_only` | Official site | No authorized embed is configured or embedding is not permitted; the official destination is the honest fallback |
| `unavailable` | Unavailable | The channel has ceased, is no longer carried, or has no legitimate current destination |
| `coming_soon` | Coming soon | A planned entry with no playable source yet |

Access values are `free`, `subscription`, and `unknown`. They describe the broadcaster's access model, not a promise that a stream is free or currently live.

### How checks work

`includes/sources.php` is deliberately conservative:

- **YouTube live:** confirms the channel's public `/live` page reports a live video, an OK playability status, `playableInEmbed = true`, and a video ID. An HTTP 200 page alone is not enough.
- **YouTube fixed video:** uses the public oEmbed endpoint; being reachable does not prove it is a live stream.
- **HLS:** checks the HTTPS manifest and segments.
- **Iframe/external:** cannot be proven automatically; they remain `unverified` until an administrator confirms them manually.

A channel is promoted to `available` only when a source is confirmed live. It is demoted to `external_only` only after **three consecutive definitive failures on every active, authorized source**. Uncertain results leave the channel state unchanged.

---

## Updating sources

### Through the admin console (recommended for day-to-day changes)

1. Sign in and open **Sources**.
2. Choose **Add source** (optionally from a specific channel).
3. Select the official channel, source type, and a clear label.
4. Enter the official identifier/URL:
   - YouTube live: channel ID (`UC…`), `/channel/UC…` URL, or an `@handle` for an official broadcaster.
   - YouTube video: watch URL or 11-character video ID.
   - HLS: HTTPS `.m3u8` URL.
   - Iframe: only an approved host.
   - External: the official viewing page.
5. Keep **Active** enabled for usable sources.
6. Only enable **Authorized** after confirming the broadcaster permits this use.
7. Leave **Verify after saving** enabled for YouTube/HLS sources.
8. Check the public watch page and the **Official destination** link.
9. Use **Sources → Check all sources** for a full refresh, or run the CLI checker.

### CLI checker

```powershell
# List what would be checked, without network calls or database writes
C:\xampp\php\php.exe bin\check_sources.php --dry-run

# Check every active, authorized source
C:\xampp\php\php.exe bin\check_sources.php

# Check one source or all sources for one channel
C:\xampp\php\php.exe bin\check_sources.php --source=12
C:\xampp\php\php.exe bin\check_sources.php --channel=2

# Machine-readable output / quieter cron output
C:\xampp\php\php.exe bin\check_sources.php --json
C:\xampp\php\php.exe bin\check_sources.php --quiet
```

Exit codes:

- `0` — no definitive source failures
- `1` — at least one source returned offline/error
- `2` — invalid invocation, database problem, or another checker holds the lock

The checker writes a lock file under `logs/` so overlapping cron runs do not race. A Windows Task Scheduler action can run:

```text
Program:   C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\japjapTV\bin\check_sources.php --quiet
Start in:  C:\xampp\htdocs\japjapTV
```

A Linux cron equivalent:

```cron
0 * * * * /usr/bin/php /var/www/japjaptv/bin/check_sources.php --quiet
```

### Updating the seed catalogue

`tools/seed-data.php` is the authoritative catalogue definition. If you add or change seeded channels/sources:

```powershell
# Edit tools/seed-data.php, then regenerate the import file
C:\xampp\php\php.exe tools/build_database_sql.php

# If you added channels, regenerate the original placeholder artwork
C:\xampp\php\php.exe tools/generate_logos.php
```

`database.sql` is generated. Do not hand-edit it and expect the changes to survive the next build. The build is destructive when imported: it drops the japjapTV tables and recreates them from the seed.

After importing a new seed, sign in and change the admin password, then run the source checker.

### Updating a channel's official link

- Open **Channels → Edit**.
- Update **Official destination URL** to the broadcaster's own page.
- Never change an official link to a third-party restream.
- Use **Availability** to reflect what is actually available; do not mark a channel `available` unless it has an active, authorized source.

---

## Data, time zones, and caching

- Database timestamps are stored in **UTC** (`SET time_zone = '+00:00'` on every PDO connection).
- The public site displays times in **Asia/Manila** (PHT). NBA game days follow **America/New_York**.
- NBA schedule cache: five minutes; source-status caches and YouTube live lookups are short-lived.
- Runtime cache files are written outside the web root by default (`CACHE_DIR` in the system temp folder). `storage/` and `logs/` remain blocked by `.htaccess`.
- Sessions use a dedicated cookie name, HttpOnly, SameSite=Lax, strict mode, and server-side session data. Admin sessions expire after two hours of inactivity.

---

## Security notes

This is a small deployment-friendly app, not a hardened multi-tenant platform. Before public deployment:

- Serve over HTTPS. HSTS is emitted only when HTTPS is detected.
- Set `APP_ENV=production` and `APP_DEBUG=0`.
- Use a dedicated MySQL user with only the privileges japjapTV needs; do not use root in production.
- Change the bootstrap admin password immediately.
- Keep the `AllowOverride All` / `.htaccess` protections enabled, or reproduce equivalent rules in the virtual host.
- Restrict access to `bin/` and `tools/` on the server if you do not need CLI checks there.
- Back up the database and `uploads/channel_logos/` together.
- Review the source authorization checklist whenever adding a stream.
- Do not add analytics, embeds, or scripts that you have not reviewed for privacy and CSP compatibility.

Implemented protections include:

- PDO prepared statements with native prepares (`ATTR_EMULATE_PREPARES = false`).
- `password_hash()` / `password_verify()` for admin credentials.
- CSRF tokens on all state-changing admin and public forms.
- Output escaping via the shared `e()` helper; no raw request data is rendered unescaped.
- Secure session cookie settings and session regeneration.
- Login rate limiting and a generic invalid-credentials response.
- Server-side validation of channel, category, country, source, game, and contact input.
- MIME and image-dimension checks on uploads; uploads stored outside executable paths with a hardened `.htaccess`.
- URL safety checks for external and server-fetched URLs, including private-IP/SSRF rejection.
- CSP, frame restrictions, MIME sniffing prevention, referrer policy, and permissions policy.
- No admin credentials or database configuration are exposed through public pages.

---

## Directory layout

```text
japjapTV/
├─ index.php                 Public homepage
├─ channels.php             Channel directory and filters
├─ watch.php                Watch page and player
├─ philippines.php          Philippine hub
├─ international.php        International hub
├─ sports.php               Sports hub
├─ nba.php                  NBA scoreboard and official viewing guide
├─ search.php               Catalogue search
├─ about.php                About/source policy
├─ contact.php              Visitor correction form
├─ privacy.php              Privacy policy
├─ terms.php                Terms of use
├─ 404.php                  Not-found page
├─ admin/                   Protected admin console
│  ├─ includes/             Bootstrap, auth, layout, admin helpers
│  ├─ login.php             Admin sign-in
│  ├─ index.php             Dashboard
│  ├─ channels.php          Channel list/delete
│  ├─ channel-edit.php      Channel create/edit, logo upload
│  ├─ categories.php        Category CRUD
│  ├─ countries.php         Country CRUD
│  ├─ sources.php           Source list/check/delete
│  ├─ source-edit.php       Source create/edit/history
│  ├─ featured.php          Featured picks and ordering
│  ├─ nba-games.php         Fallback NBA game list
│  ├─ nba-game-edit.php     Fallback NBA game create/edit
│  ├─ messages.php          Visitor inbox
│  ├─ message.php           Message detail/actions
│  └─ account.php           Profile and password
├─ config/                  DB connection and app configuration
├─ includes/                Shared bootstrap, queries, layout, player, NBA, sources
├─ assets/
│  ├─ css/                  Custom dark UI and responsive styles
│  ├─ js/                   Navigation, filter, player, and admin scripts
│  └─ images/               Generated marks, placeholders, favicon, OG image
├─ uploads/channel_logos/   Validated uploaded logos (blocked from scripts)
├─ tools/                   Seed catalogue, SQL builder, logo generator
├─ bin/check_sources.php    CLI source checker
├─ logs/                    PHP error log and checker lock (blocked)
├─ storage/                 Private runtime storage (blocked)
├─ database.sql             Generated schema, seed data, bootstrap admin
├─ CHANNEL_SOURCES.md       Source audit and update workflow
└─ README.md                This guide
```

---

## Maintenance

### Backups

```powershell
# Database
C:\xampp\mysql\bin\mysqldump.exe -u root --default-character-set=utf8mb4 --routines --triggers japjaptv_db > japjaptv-backup.sql

# Uploaded logos (PowerShell)
Compress-Archive -Path uploads\channel_logos\* -DestinationPath logos-backup.zip
```

Restore the database with the same import command used during installation, or restore a dump with:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 japjaptv_db < japjaptv-backup.sql
```

### Routine checks

- Run `bin/check_sources.php` on a schedule (hourly or daily, depending on the catalogue).
- Review the admin **Sources** page for repeated failures.
- Re-check official destination links periodically; broadcasters change URLs.
- Remove or mark `unavailable` channels that have ceased.
- Review visitor messages and correct stale labels.
- Keep PHP, Apache, MySQL, and the YouTube/HLS client dependencies up to date.

### Logs

PHP errors are written to `logs/php-error.log`. The checker lock file is `logs/check_sources.lock`. Both are protected by `.htaccess` and should be excluded from backups you publish.

---

## Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| “The channel database is unavailable.” | MySQL is stopped, credentials are wrong, or the database was not imported. Check `config/config.php` and logs. |
| 403 for every page | Apache directory permissions / `Require all denied` inherited incorrectly, or a security module is blocking access. Check `httpd.conf` and Apache error log. |
| `.htaccess` files seem ignored | `AllowOverride` is not `All` for the project directory, or the app is served by a vhost that does not allow overrides. This is expected with `php -S`; use Apache. |
| `database.sql`, `config/`, or `includes/` open directly | The `.htaccess` protections are not active. Fix before deploying. |
| Uploads fail | `uploads/channel_logos/` is not writable by the Apache/PHP user, or the file exceeds 2 MB / 4000 px, or it is not a real PNG/JPEG/WebP. |
| Admin login loops | Check that cookies are enabled, the session path matches the subfolder, and `BASE_URL` is correct. Clear old `japjaptvsess` cookies. |
| “Password change required” cannot be bypassed | This is intentional for the bootstrap account. Change the password on the account page. |
| YouTube player is blank | The broadcaster may be offline, region-restricted, or refusing embedding. Use **Official channel**; japjapTV does not proxy or bypass restrictions. |
| Every source fails | Check outbound HTTPS/DNS from the server, curl CA certificates, and the system clock. Run the checker with `--json` for details. |
| NBA page shows a fallback warning | ESPN's public scoreboard is temporarily unavailable; the page uses saved `nba_games` rows or shows an empty state. Confirm with NBA.com. |
| Contact form says too many requests | The per-IP rate limit has been reached. Wait and retry, or review the rate-limit logic in `contact.php` / helpers. |

---

## Local development with the PHP built-in server

For quick UI work without Apache:

```powershell
cd C:\xampp\htdocs\japjapTV
C:\xampp\php\php.exe -S 127.0.0.1:8080
```

Then open <http://127.0.0.1:8080/>.

The PHP built-in server **does not read `.htaccess`**. It is suitable only for local development. Use Apache/XAMPP for any test of configuration protection, uploads hardening, or deployment behaviour.

---

## Verification performed on this build

- All PHP files pass `php -l`.
- Public smoke tests: required pages return 200; invalid channel returns 404; live, external-only, and unavailable watch pages render.
- Admin flow: login, forced password change, all admin pages, profile update, contact form, inbox detail/status/archive/delete, logout.
- Admin CRUD: category, country, channel, source, featured picks, NBA fallback game, valid and invalid logo uploads, source checks, channel demotion after the last source is removed.
- Source checker: `--dry-run` lists all 27 active authorized sources; `--help` and option validation work.
- Apache: `.htaccess` blocks configuration, includes, SQL, logs, storage, tools, and executable uploads; uploaded images remain servable; directory listings are disabled; security headers are present.
- Responsive layout: checked at desktop, tablet, and mobile widths with no horizontal overflow.

This verification does not guarantee that a third-party live stream will remain online. Broadcaster availability changes constantly; the admin console and checker exist to keep the catalogue honest.

---

## Legal and attribution

japjapTV is an independent directory and does not claim affiliation with any broadcaster, the NBA, ESPN, Cignal, YouTube, or Google.

- Channel names, programme names, and trademarks belong to their respective owners.
- Generated text-mark artwork is original and is not an official broadcaster logo.
- Embedded players remain governed by the source platform's terms, privacy policy, territorial restrictions, and playback rules.
- The NBA page uses ESPN's public scoreboard data for reference and links to official NBA and provider destinations. It does not host or restream NBA games.
- If you believe a listing is inaccurate or unauthorized, use the contact form with the channel name and details.

See [CHANNEL_SOURCES.md](CHANNEL_SOURCES.md) for the current source audit.
