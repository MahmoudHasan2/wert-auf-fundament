# Wert auf Fundament GmbH – Website & CMS

🇬🇧 English · [🇩🇪 Deutsch](README.de.md)

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-utf8mb4-4479A1?logo=mysql&logoColor=white)
![No Composer](https://img.shields.io/badge/no%20Composer%20%C2%B7%20no%20Node-plain%20PHP-2ea44f)
![Hostinger](https://img.shields.io/badge/hosting-Hostinger%20shared-673DE6?logo=hostinger&logoColor=white)

Multi-page company website with a full content management system (German UI), built for **Hostinger shared hosting**.
Plain PHP 8.2+ and MySQL/MariaDB – **no Composer, Node.js, SSH or cron required**.

- Public site: home, service overview, 4 service categories with sub-service pages, references, company, contact, Impressum, Datenschutz, any number of additional CMS pages.
- Admin (`/admin`): pages & content blocks, homepage, services, references/galleries, media library, navigation, FAQ, reusable blocks, contact enquiries, company settings, legal pages with version history, users & roles, audit log, backups, system status, pre-launch checklist.

---

## 1. Requirements

| | |
|---|---|
| PHP | 8.2 or newer (8.3 recommended) |
| PHP extensions | `pdo_mysql`, `mbstring`, `gd` (with WebP), `fileinfo`, `dom`, `openssl`; optional: `zip` (media backups), `exif` (auto-rotate phone photos), `zlib` (compressed backups) |
| Database | MySQL 5.7+/8.x or MariaDB 10.4+ (utf8mb4) |
| Web server | Apache or LiteSpeed with `.htaccess` + `mod_rewrite` (Hostinger default) |

All of these are standard on Hostinger. Check under **hPanel → Advanced → PHP Configuration**.

## 2. Directory layout

```
app/            application code (controllers, services, views)     – private
bin/            command-line installer                               – private
config/         app.php (defaults), env.example.php (template)      – private
database/       migrations (SQL) + seed content and seed images      – private
docs/           additional documentation                              – private
public/         THE ONLY PUBLIC DIRECTORY (index.php, assets, uploads)
storage/        logs, sessions, backups, installed.lock               – private
tests/          automated tests                                       – private
```

Every private directory contains a `Deny all` `.htaccess`. `public/uploads/.htaccess` only allows generated image files and disables script execution.

## 3. Deployment on Hostinger (without SSH)

### Quick start (tested with Apache, the same `.htaccess` rules apply on Hostinger's LiteSpeed)
1. hPanel → PHP Configuration: choose **PHP 8.3**. hPanel → Databases: create a MySQL database + user.
2. Zip the whole project folder, upload the ZIP into `public_html` with the File Manager and extract it there (delete Hostinger's `default.php` if present). Make sure the hidden `.htaccess` files are included.
3. Open `https://your-domain/admin/install` **immediately**:
   - Step 1: enter the database details → the installer tests the connection and writes `env.php` automatically to `domains/your-domain/waf-private/` (outside `public_html`).
   - Step 2: create your administrator account → tables, content and photos are installed (≈ 30 s).
4. Log in at `/admin` and work through the checklist on the dashboard.

The detailed/manual variant follows below.

### 3.1 Create the database
hPanel → **Databases → MySQL Databases**: create a database and user (e.g. `u123456789_waf`), note name, user and password. The user needs SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES on this database only.

### 3.2 Upload files – recommended structure
Upload the project (via File Manager as ZIP, or FTP) **next to** `public_html`, not into it:

```
/home/u123456789/domains/wertauffundament.de/
├── waf/                 ← whole project (app, config, public, storage …)
├── waf-private/
│   └── env.php          ← configuration with secrets (outside every web root)
└── public_html/         ← document root
```

Then make the document root point to `waf/public`:
- **Option A (preferred):** hPanel → Websites → *your domain* → **Advanced → Change website root / document root** (where available) → `domains/wertauffundament.de/waf/public`.
- **Option B:** if the document root cannot be changed, upload the **whole project into `public_html`**. The root `.htaccess` included in the project routes every request into `/public` and blocks `app/`, `config/`, `storage/` etc. In this case put `env.php` into `domains/wertauffundament.de/waf-private/env.php` (one level above `public_html`) – the app looks there automatically.

Do **not** upload `tests/` and `docs/` to production if you don't need them (they are protected anyway).

### 3.3 Configuration (`env.php`)
Copy `config/env.example.php` to `../waf-private/env.php` (relative to the project folder) and fill in:

- `app.url` – e.g. `https://www.wertauffundament.de` (no trailing slash)
- `app.key` – 64 random hex characters. Generate locally: `php -r "echo bin2hex(random_bytes(32));"` or use any password generator (letters/digits, ≥ 32 characters)
- `db.*` – values from 3.1 (`host` is `localhost` on Hostinger)
- `install.token` – a long random string, only needed once for the installer
- `mail.*` – see section 5 (can stay `none` for now)

Lookup order: environment variable `WAF_ENV_FILE` → `../waf-private/env.php` → `config/env.php`. Never commit `env.php`.

### 3.4 File permissions
Directories `755`, files `644`. These must be writable by PHP (default on Hostinger): `storage/`, `storage/logs`, `storage/cache`, `storage/backups`, `public/uploads`.

### 3.5 HTTPS
hPanel → **Security → SSL**: install the free certificate. Then set `app.url` to `https://…`, `app.force_https => true`. Optionally uncomment the HTTPS redirect in `public/.htaccess`. Enable `app.hsts` only once HTTPS works reliably for all sub-domains.

### 3.6 Run the installer
Open `https://your-domain/admin/install`, enter the install token, name, e-mail and a password (≥ 12 characters, must not contain your name/e-mail). The installer:
1. creates all tables (`database/migrations/*.sql`),
2. imports the initial German content and 24 licensed placeholder photos,
3. creates the first administrator,
4. writes `storage/installed.lock` – **the installer is disabled permanently afterwards**.

Then **remove `install.token` from env.php**. There is no default password and no public registration.

Alternative with SSH: `php bin/install.php --name="Max Mustermann" --email=admin@…` (password is prompted).

## 4. First steps after installation

The dashboard shows a **pre-launch checklist**. Work through it:

1. **Unternehmenseinstellungen:** add managing directors, commercial register court + number (mandatory Impressum data, currently empty), hosting provider, office hours.
2. **Datenschutzerklärung / Impressum:** replace all `[BITTE PRÜFEN …]` markers; have both reviewed by the company or a German privacy/legal professional, then delete the draft notice block.
3. **Confirm claims** listed in the checklist (hidden blocks: own cranes, garden maintenance, verified figures; services with notes: garden sub-services, Baugrundverbesserung methods, Altlastensanierung scope/qualifications).
4. **Photos:** replace the 24 Wikimedia Commons placeholders (marked "Platzhalter") with own photos via *Medien → Bild → Bilddatei ersetzen*. Until then keep *Bildnachweise anzeigen* enabled (attribution is a licence requirement, CC BY-SA).
5. **References:** add real projects only (*Referenzen*). The site shows a neutral text while none exist.
6. **E-mail:** configure SMTP (section 5) and send a test mail (*Systemstatus*).
7. **Go live:** *Unternehmenseinstellungen → SEO → „Suchmaschinen dürfen die Website indexieren“*. Until then all pages are `noindex` and robots.txt disallows crawling.

## 5. E-mail / SMTP

Without mail configuration, enquiries are **stored safely** in the dashboard; no notification is sent and "password forgotten" is disabled.

Hostinger: create a mailbox (e.g. `website@wertauffundament.de`) under hPanel → Emails, then in env.php:

```php
'mail' => [
    'transport' => 'smtp', 'host' => 'smtp.hostinger.com', 'port' => 465, 'encryption' => 'ssl',
    'username' => 'website@wertauffundament.de', 'password' => '…',
    'from_address' => 'website@wertauffundament.de', 'from_name' => 'Website Wert auf Fundament',
],
```
Test via *Systemstatus → Testnachricht senden*. Recipient of enquiry notifications: *Unternehmenseinstellungen → Kontaktformular*.

## 6. Backups

- **In the admin (Backups):** database dump (`.sql.gz`) and ZIP of all images; stored in `storage/backups` (not web-accessible), downloadable only by administrators. Restore requires your password + typing `WIEDERHERSTELLEN`; a safety backup of the current state is created first.
- **Hostinger:** hPanel → Files → Backups (automatic, daily/weekly depending on plan).
- **Recommended routine (monthly and before every update):** create DB backup + media archive in the admin, download both, keep them off the server; additionally keep a copy of `env.php`.
- **Manual (without the admin):** export the database in phpMyAdmin (hPanel → Databases → phpMyAdmin → Export) and download `public/uploads` via File Manager.
- Shared hosting cannot run scheduled jobs reliably without cron, therefore in-app backups are manual by design.

## 7. Updating the application
1. Create a DB backup + media archive and download them.
2. Upload the new code (overwrite `app/`, `public/assets/`, `database/`, `config/app.php`; **never** overwrite `env.php`, `storage/` or `public/uploads/`).
3. Open *Systemstatus*: if database updates are pending, click *Jetzt aktualisieren* (runs new files from `database/migrations`).

## 8. Security summary
- PDO prepared statements everywhere; context-aware escaping in all templates; allow-list HTML sanitizer for rich text.
- Central enforcement (`App\Core\Kernel`): every admin route declares a permission; every non-GET admin request needs a CSRF token.
- Roles: *Administrator* (everything) and *Redakteur* (content, media, enquiries – no legal pages, settings, users, backups, system).
- Sessions only in `/admin` (HttpOnly, SameSite=Lax, Secure on HTTPS, strict mode, ID regeneration on login, idle/absolute timeouts; invalidated on password change/deactivation).
- Login throttling (5 attempts per account+IP / 20 per IP in 15 min), generic error messages, bcrypt via `password_hash()`.
- Uploads: extension + MIME + `getimagesize` + full GD re-encode (metadata stripped), random filenames, no SVG, no script execution in `uploads/`.
- Public site sets **no cookies**; the contact form uses an HMAC-signed token (min. fill time), honeypot, IP rate limit (stored only as HMAC hash).
- Security headers: strict CSP (no inline scripts/styles), `nosniff`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, optional HSTS. Admin pages: `noindex`, `no-store`.
- Errors are logged to `storage/logs`; visitors never see stack traces (keep `app.debug = false`).
- Audit log for logins, content changes, publication, deletions, settings, users and backups (IP shortened, no passwords or enquiry contents).

## 9. Privacy notes (implementation facts for the Datenschutzerklärung)
- No analytics, tracking, ads, external fonts, CDNs, maps or video embeds. Fonts/icons are self-hosted.
- Cookie: only `waf_admin` (session, admin area only). No consent banner is needed for the current implementation; if tracking/embeds are added later, a consent solution must be implemented first.
- Contact form data is stored in the `enquiries` table; deletion via *Kontaktanfragen* (single or bulk after the retention period configured in settings, default 365 days).
- Rate-limit records contain only HMAC hashes of IPs and are cleaned up automatically.

## 10. Testing

```bash
php tests/run.php                       # unit tests
# integration tests against a FRESH test installation (creates/deletes data – never production):
WAF_ENV_FILE=/path/test-env.php WAF_TEST_BASE=http://127.0.0.1:8001 \
WAF_TEST_EMAIL=… WAF_TEST_PASSWORD=… php tests/run.php
```
Local development: `php -S 127.0.0.1:8000 -t public public/index.php` with `WAF_ENV_FILE` pointing to a dev env.php.

## 11. Troubleshooting
| Problem | Solution |
|---|---|
| "Unsere Website wird gerade eingerichtet" | Not installed yet → open `/admin/install`. |
| Installer: "Voraussetzungen nicht erfüllt" | Read the listed items: env.php location/values, DB credentials, writable `storage/`, PHP extensions. |
| 500 error / blank page | Check `storage/logs/app-YYYY-MM-DD.log` (File Manager). Temporarily set `app.debug => true` only if necessary, then back to false. |
| 404 for every page except home | `mod_rewrite`/`.htaccess` not active, or the document root does not point to `public/` (see 3.2). |
| Styles/images missing | Wrong `app.url` (must match the domain incl. https and optional sub-folder). |
| Upload fails "zu groß" | Raise `upload_max_filesize`/`post_max_size` in hPanel → PHP Configuration (app limit: 10 MB, `uploads.max_bytes`). |
| No enquiry e-mails | Mail not configured or SMTP data wrong → *Systemstatus → Testnachricht*; log contains the SMTP response. |
| Locked out after failed logins | Wait 15 minutes. Another administrator can reset the password under *Benutzer*. |
| Lost all admin passwords | In phpMyAdmin set `password_hash` of an admin to the output of `php -r "echo password_hash('NEW-PASSWORD', PASSWORD_DEFAULT);"`. |
