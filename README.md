# SmartSlope — Phase 4

Separate administrator and resident dashboards with a shared green-and-brown
Bootstrap theme. Login, registration, logout, and role checks from Phase 1 remain
in place. No database schema or local configuration changes are required.

## Dashboard screens

- Admins open `users/admin/admin.php`: active-location totals, saved reading
  counts, recent weather readings, and a private pending-report list.
- Residents open `users/user/user.php`: community readings and their own report
  count. Other residents' report/contact details are not sent to the browser.
- `dashboard.php` redirects to the appropriate dashboard and preserves valid
  `dialog_id` reading-detail bookmarks.
- `readings.php` lists all non-archived saved readings, newest first, with working
  detail links. Dashboards show the five most recent readings.
- Shared PHP sections in `includes/` keep dashboard and reading-table HTML in one
  place. Navigation supports Bootstrap's mobile offcanvas menu.
- Login and registration stay together in one column with floating labels.
- Phase 3 adds offline map selection and admin location management. Phase 4 adds weather fetching and rainfall assessment.
  Report submission/review, reading changes, and CSV remain later phases.
- Displayed dates are explicitly labeled UTC; high/medium totals count saved
  observations and must not be interpreted as current location risk.

Phase 2 checks: repeat Phase 1 authentication checks below, open both dashboards,
open a reading detail and close it, follow View all, and try the mobile navigation.
Verify empty tables and multiple saved readings. Confirm a resident sees only
his or her own report count and no administrator report table.

## Phase 3: locations and offline map

Both dashboards now show the bundled Irisan boundary and active location markers.
Click a marker or a point inside Irisan to fetch and save weather, then view that
location’s recent readings. **View saved readings here** opens history without
requesting new weather and is useful offline. A new coordinate pair gets a simple
coordinate-based name after weather succeeds; admins can rename it later. Saved names, puroks, and landmarks work offline; reverse address lookup
is not available offline.

Admins can add/edit locations in **Management**, using map clicks or coordinate
inputs. Coordinates are checked against the same Irisan polygon in PHP and JS.
New locations use the existing schema's `unknown` susceptibility default; existing
susceptibility values are retained. Deactivate/reactivate changes `active` only.
We do not delete locations because the existing foreign key would cascade-delete
their readings and reports. Residents cannot access these management actions.

### Copy the offline image tiles

The patch includes local Leaflet (including images and LICENSE), the Irisan
GeoJSON, and the new map code. The larger tile images are copied separately.
Use `macJil/smartslope`, branch **sf2**, commit
`a27af9280de02c061eee2f147edcecd33cbe98a4` as the source:

| Copy from SmartSlope sf2 | Copy to this project |
| --- | --- |
| `assets/map-tiles/12/` | `assets/map-tiles/12/` |
| `assets/map-tiles/13/` | `assets/map-tiles/13/` |
| `assets/map-tiles/14/` | `assets/map-tiles/14/` |
| `assets/map-tiles/15/` | `assets/map-tiles/15/` |
| `assets/map-tiles/README.md` | `assets/map-tiles/README.md` |

Simplest: copy the **entire `assets/map-tiles` folder**, with all 110 PNG images
(about 7.7 MiB). Keep the nested `{zoom}/{x}/{y}.png` names unchanged; for example,
`assets/map-tiles/15/27356/14867.png`. Do not copy old dashboard.php, config.php,
JavaScript controllers, or database files over this rebuild.

If your local SmartSlope checkout is on sf2 and the folders are siblings, run
this from the project1 root:

```bash
cp -R ../smartslope/assets/map-tiles ./assets/
```

No new tiles need downloading. The map displays zooms 12–16 and uses native
zoom 15 images enlarged at zoom 16. Tile requests are limited to the Irisan
boundary's bounding box; there is no online tile fallback or CDN. Missing images
show a clear message. Retain attribution and check the original image provider's
redistribution terms before public hosting (the source tile README also notes this).

Offline here means **without internet while PHP and MySQL still run** through
Herd or XAMPP. Opening a PHP file directly from Finder will not run the website.
The map, saved records, and location management work offline; fresh weather
and external address services require internet.

### Check Phase 3 locally

1. Copy the tile folder, start the local web/database servers, and log in.
2. Confirm both dashboards show the map, boundary, and initial pilot marker.
3. Turn off Wi-Fi and reload. Pan/zoom through levels 12–16; tiles should remain.
4. Select a saved marker and open its readings; an empty location has an empty list.
5. As admin, add a named point within Irisan, edit it, deactivate and reactivate it.
6. Confirm duplicate coordinates and outside-boundary coordinates show a message.
7. As resident, verify direct GET and POST to Management are denied.
8. Confirm deactivation preserves linked events and omits the point from the
   resident's map. Check layout and controls on a narrow/mobile viewport.

## Phase 4: weather and assessment

- The dashboard calls `api/readings.php` with POST when a marker/point is clicked
  or **Refresh weather** is pressed. It reloads the saved location after success.
  A normal page load, reading-detail link, or GET API request never saves data.
- Both roles may fetch weather. Only admins can edit location names or activation.
  Existing inactive coordinate pairs cannot be reactivated through the weather API.
- `includes/weather.php` makes a server-side HTTPS request to Open-Meteo with
  cURL (5-second connection timeout, 12-second total timeout, TLS verification on).
  PHP cURL must be enabled in your active Herd/XAMPP PHP installation. No API key,
  `.env`, new URL configuration, or database import is required.
- Provider timestamps are UTC Unix seconds. We request 73 past hourly steps and
  one current hourly step. The last completed-hour timestamp anchors the 1/24/72h
  precipitation sums; future steps never enter these totals. No missing values
  are replaced with zero. Invalid times/units/values or incomplete history reject
  the response before saving a reading.
- Each successful refresh inserts a reading (even when the provider observation
  time is unchanged). Existing records are retained. Repeated coordinates reuse
  their existing location. New unnamed points use an Irisan coordinate label;
  no online address lookup is included.
- `class/RiskAnalyzer.php` keeps the original `prototype-1` thresholds:

| Category | 1h mm | 24h mm | 72h mm |
| --- | --- | --- | --- |
| High | 50 | 100 | 150 |
| Medium | 25 | 50 | 100 |
| Normal | 10 | 25 | 50 |

The highest category with **any** threshold reached applies; below all thresholds
is low. These are academic screening rules, not validated Irisan landslide
thresholds. A low/normal category does not establish safety. Open-Meteo provides
weather-model precipitation, not readings from physical sensors at the map pin.
See the on-site Methodology page and https://open-meteo.com/en/docs.

A current assessment needs consistent totals, a supported rule version, valid
observation/window timestamps, and a rainfall window less than 3 hours old.
Outdated or incomplete/legacy data stays visible as history without a current
category badge. The badge also expires while the page remains open. The database
`stale` column is not used as a clock. Historical count cards keep their labels.

The existing event columns store totals, temperature, humidity, wind, weather
code, observation/window times, rule version, and the original provider JSON.
Forecast/soil-moisture fields stay untouched and are not used by this phase.

### Weather checks on your computer

1. Log in as each role and click the pilot marker while connected to the internet.
2. Confirm a new reading, summary, calculated category, and UTC times appear.
3. Press Refresh and confirm a new saved reading without a duplicate location.
4. Click a new point within Irisan; confirm its location and weather are saved.
5. Disconnect Wi-Fi and refresh weather: a readable error appears, old data stays
   in the database, and **View saved readings here** still works locally.
6. Confirm an old reading is marked outdated, never as a current warning.
7. Check `api/readings.php?location_id=YOUR_ID` while logged in: GET returns saved
   JSON. A logged-out request returns 401 JSON. POST is required to fetch/save.

On a browser timeout the server may still finish saving: reload history before
retrying. Report review, reading edits/deletion, and CSV remain subsequent phases.

## Database

Use the existing `smartslope_mvp` database with `users`, `locations`, and `events`.
There are no schema changes or automatic migrations.

`database/schema.sql` is an unchanged copy from SmartSlope `sf2`, commit
`a27af9280de02c061eee2f147edcecd33cbe98a4`. Import it **only for a new installation**.
Do not reimport it over your existing database. The fresh-install seed contains
an administrator account; change its password hash before using that account
beyond a local demo. Existing accounts and their password hashes continue to work.

Registration writes the contact field to `users.phone` and always assigns `user`.
The session keeps only the account ID, username, full name, and role. Existing
sessions from the starter version must log in again.

## Local setup

Use PHP 8 with PDO MySQL and a running MySQL/MariaDB server. Configure these
constants in `config.php` to match your installation:

| Constant | XAMPP example | Herd example |
| --- | --- | --- |
| `BASE_URL` | `'/project1'` | `''` when the project is served at `project1.test` |
| `DB_HOST` | `'127.0.0.1'` | `'127.0.0.1'` |
| `DB_PORT` | `3306` | Your MySQL service's TCP port |
| `DB_NAME` | `'smartslope_mvp'` | `'smartslope_mvp'` |
| `DB_USER`, `DB_PASS` | Your local credentials | Your local credentials |

XAMPP: put the project in `htdocs/project1`, start Apache and MySQL, then open
`http://localhost/project1/` (include your Apache port if different).

Herd: park/link the project folder in Herd, start your separate MySQL service,
set `BASE_URL` to an empty string, then open your configured Herd domain.

Use no trailing slash in `BASE_URL`. All PHP includes use `__DIR__`; browser
links and redirects use `BASE_URL`. Neither a `.env` file nor rewrite rules
are required. Do not copy the MAMP FastCGI workaround into this project.

## Authentication behavior

- Successful login redirects to `users/admin/admin.php` or `users/user/user.php`.
- Registration checks required fields, email, contact format, field lengths,
  and duplicate username/email/phone. Passwords are hashed with `password_hash()`.
- Login checks passwords with `password_verify()` and uses a generic failure message.
- Guest requests stop before protected content. Regular users receive HTTP 403
  when requesting admin pages, including POST requests.
- Log Out submits POST to `logout.php`, clears session data and the session cookie,
  then redirects to login. GET does not log out a user.
- Report/contact details are not sent in regular users' dashboard HTML.
- Database errors are logged; pages display a short message without SQL details.

As requested, this phase uses simple sessions without CSRF tokens or session ID
regeneration. This is an academic local-development baseline, not a hardened
public deployment. Basic HTML escaping remains where account/report text is shown.

## Verify after applying

1. Set `config.php` for your local server and database.
2. Register an account, log in, and confirm the user home opens.
3. Try empty fields, an invalid email, and duplicate account details. Confirm a
   readable message and no duplicate row.
4. Log in with an existing administrator and confirm the admin home opens.
5. While logged in as a user, open `users/admin/admin.php` and
   `users/admin/admin_panel.php` directly: both must deny access.
6. Log out, then revisit protected pages: each must return to login.
7. Open the existing shared dashboard and confirm saved readings load. Reports
   must not appear as readings; reading details must select the reading's ID.
8. Check navigation and Bootstrap assets under both your XAMPP subfolder and
   Herd root URL after adjusting `BASE_URL`.

Syntax check from the project directory:

```bash
find . -name '*.php' -not -path './.git/*' -exec php -l {} \;
```

Automated verification for this patch uses a disposable MariaDB database and
real PHP HTTP requests at root and subfolder URLs. This does not replace a
final check in your actual Herd and XAMPP installations.
