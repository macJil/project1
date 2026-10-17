# SmartSlope — Version 1.0

SmartSlope is an academic landslide-awareness web application for Barangay
Irisan, Baguio City. It combines an offline map, API weather records, simple
rainfall screening, and resident observations. It is not an official warning
service or a validated landslide prediction system.

## Features

- Registration and username/password login; separate resident/admin dashboards.
- Offline Leaflet map, Irisan boundary, active locations, and saved risk colors.
- Click-to-select weather refresh, saved readings, timestamps, and freshness checks.
- Automatic approximate addresses when online; saved addresses work offline.
- Resident reports with selected address/coordinates, required phone and email.
- Admin report review, original report-point map, and pending/reviewed/resolved filters.
- Admin location management and reading edits, risk overrides, and deletion.
- Reading CSV download for both roles; admin reading/location import and location export.
- Admin row selection and bulk actions. Linked locations are deactivated rather
  than deleted; only unlinked locations can be permanently deleted.

## Technology and structure

PHP 8+, PDO/MySQL, small PHP classes and functions, Bootstrap 5, local Leaflet,
and plain JavaScript Fetch for asynchronous JSON requests. No framework,
Composer, Node build, URL rewriting, or environment-file loader is required.
Node is optional for JavaScript syntax checks, not for running the website.

| Location | Responsibility |
| --- | --- |
| `config.php` | Database settings and browser `BASE_URL` |
| `class/` | PDO connection, users, locations, events, reports, readings, risk, CSV and bulk SQL |
| `includes/` | Session start, reusable views, boundary/address/weather functions |
| `users/admin/` | Admin pages and actions |
| `users/user/` | Resident dashboard and report form |
| `api/` | Authenticated saved-reading/weather and address endpoints |
| `assets/` | Local styles, JavaScript, Leaflet, boundary and PNG tiles |
| `database/schema.sql` | Three-table installation schema |
| `tools/verify.php` | Optional read-only terminal checks |
| `docs/` | Application/database reference, test guide, course alignment |

PHP includes use `__DIR__`; browser links use `BASE_URL`. These serve different
purposes: a filesystem path cannot be used as a browser URL.

## Install locally

1. Use PHP 8.0+ with `pdo_mysql`, `curl`, JSON and sessions, and MySQL/MariaDB.
2. Put the repository in the web server's document folder. For XAMPP, use
   `htdocs/project1`; start Apache and MySQL. For Herd, serve/link the repository
   as `project1.test` and start a separate MySQL server (Herd alone is not MySQL).
3. For a **new database only**, import `database/schema.sql` in phpMyAdmin or
   your MySQL client. It creates `smartslope_mvp`, the three tables, an admin and
   an Irisan pilot point. Existing installations do not need a schema import.
4. Edit the five database constants in `config.php` to match your server. The
   checked-in port/password may not match yours. Typical XAMPP defaults are
   port `3306`, user `root`, empty password; verify your actual installation.
   Herd's database port/user/password come from your separately installed server.
5. Set `BASE_URL = '/project1'` for `http://localhost/project1`, or `BASE_URL = ''`
   for `http://project1.test`. Use no trailing slash. A custom port belongs in
   the address bar, not in `BASE_URL`.
6. Open `index.php` through that URL. On a fresh installation, the seed login is
   `admin` / `admin123`. This is a demonstration credential: change its hash in
   the database before sharing the installation. There is no password-change UI.
   Generate a replacement with `password_hash()`; do not store plaintext.
7. Register a resident account, then follow [the test guide](docs/TESTING.md).

The offline tile set is already included: 110 PNGs under zoom directories
12–15. Keep the `zoom/x/y.png` names. Zoom 16 enlarges zoom 15 images. Do not
open PHP files directly in Finder: PHP and MySQL must be running even offline.

## Verification

From the project root:

```bash
php tools/verify.php
php tools/verify.php --database
```

Use the PHP executable from your installation if `php` is not on PATH.
XAMPP Windows example: `C:\xampp\php\php.exe tools\verify.php --database`.
The script performs read-only checks; it does not create/delete test records.
A pass is not proof that browser interactions work. Complete the step-by-step
[acceptance tests](docs/TESTING.md) and record actual results before submission.

## Documentation

- [Application, API and database reference](docs/APPLICATION.md)
- [Step-by-step tests and sign-off record](docs/TESTING.md)
- [Course alignment and submission checklist](docs/REQUIREMENTS.md)
- [Executed verification record](docs/VERIFICATION.md)

## Important limits

Rainfall rules are prototype rules, not calibrated Irisan landslide thresholds.
“Low” and “normal” do not establish safety. There are no physical sensors,
SMS alerts, payment integration, AI, or automated evacuation instructions.
Weather comes from Open-Meteo; addresses come from Photon/OpenStreetMap and
may identify a nearby mapped feature rather than the exact house.

The session model is intentionally basic. Password hashing, prepared statements,
output escaping, role checks and input checks are present. CSRF tokens, login
session-ID regeneration and login throttling are not implemented. This is a
local academic prototype, not a production-security certification. Keep SQL
exports, credentials and reports private; a public deployment needs additional
security and a tile-source/redistribution review.
