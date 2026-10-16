# SmartSlope — Phase 5

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
  Phase 5 adds report submission/review and admin reading edits/deletion. CSV remains Phase 6.
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
retrying. CSV remains Phase 6.

## Phase 5: reports and management

### Resident ground reports

Open **Submit report**, choose a saved location or click within Irisan, and enter
an observation type, description, and observation time. The time input is in
**Asia/Manila** and is stored/displayed as UTC. Phone and email are required; nearby landmark is optional; contact fields initially use the resident's account details.

A successful submission creates a `report` event with the signed-in resident's
ID and `pending` status. Submitted user IDs, roles, or status fields cannot
change that assignment. A new map point creates a coordinate-based location only
when the report is submitted; it does not call the weather provider. Existing
inactive points are rejected. Reports work without internet while PHP/MySQL run.

**Your reports** shows only the signed-in resident's history and current review
status. Other residents' reports/contact details are not exposed. Reports are
user observations, not verified hazard data or an emergency response channel.

### Administrator report review

Use **Management → Review reports** or the pending-report dashboard link.
`users/admin/reports.php` provides All / Pending / Reviewed / Resolved filters.
**View** opens a Bootstrap dialog with the report, contact details, and review
status form. Save the selected status there. Reviewed/resolved records store the
reviewing admin and review time; returning to pending clears those review fields.
Resolved is an administrative workflow label, not a statement of physical safety.

Delete report permanently removes that report after the browser confirmation.
Only POST requests perform updates/deletions. Guests and resident accounts cannot
access these pages or actions. No report action can modify a reading event.

### Administrator reading management

Admins see **Edit** and **Delete** beside saved readings on the dashboard and
readings page. Edit opens `users/admin/edit_reading.php`, with rainfall totals,
temperature, humidity, and wind. Rainfall totals must be nonnegative and consistent
(1h ≤ 24h ≤ 72h); the rainfall category is calculated separately from the administrator-selected risk status. Other weather values
may be left blank when unavailable.

Editing preserves the location, original observation timestamp, and original
provider JSON. It labels the source `manual` and stores the most recent editor
and edit time in `adjustment_log`. The displayed assessment identifies these as
administrator-edited values. Editing does not make old observations fresh.
Delete reading permanently removes that reading after the browser confirmation.
Reading actions cannot modify report rows. Residents remain read-only here.

The database connection sets its session timezone to UTC so `TIMESTAMP` fields
match the UTC labels even if MySQL's server timezone is Asia/Manila. No schema,
credentials, table, or existing-record migration is needed.

### Phase 5 checks

1. As resident, submit a report using a saved point, then another using a new map
   point. Confirm the success message and your own history; reload without
   submitting again to confirm there is no duplicate submission.
2. Try a missing description, future observation time, invalid contact details,
   and an outside-Irisan point. Expect a readable message and no report saved.
3. As admin, open each status tab. View the new report, set Reviewed, then Resolved,
   and confirm it moves to the correct tab. Verify the resident sees the new status.
4. Log in as a different resident and confirm the first resident's history is absent.
5. Edit a test reading. Confirm its category is recalculated, source is manual,
   and timestamp remains unchanged. An old reading must stay outdated.
6. Delete a disposable report and reading. Confirm each disappears, while the
   corresponding location remains. Cancel the browser confirmation once as well.
7. As resident, directly visit the admin review/edit URLs and try POST actions:
   each must be denied. Check both desktop and mobile layouts and the View dialog.

## p4 refinements: addresses, risk status, and report maps

Map clicks on both dashboards, the location editor, and the report form now look
up the nearest mapped address using Photon (OpenStreetMap data). Coordinates stay
visible. House numbers, street names, puroks/localities, and city information are
included only when available. A nearest mapped feature is an **approximate
address**, not proof of the exact clicked house. Check/correct suggested details.

The local Leaflet tiles still work offline. Only a user-triggered address lookup
requires internet. Saved labels remain available offline, and lookup failure does
not block weather requests or report submission. Old coordinates-only locations
gain a saved address after lookup plus a successful weather refresh/report or an
admin save. Lists do not perform background/bulk geocoding.

`api/address.php` is authenticated and Irisan-only. It uses a short timeout and
caches up to 100 coordinate results in the session, with at most one uncached
request per second per session. The Photon public demo permits moderate use but
may throttle requests and has no availability guarantee. No key is needed for
this local academic setup. Before larger-scale deployment use an appropriate
hosted/private provider. Only coordinates are sent; report messages and contact
details are not. Attribution is displayed on the map.
Provider documentation: https://github.com/komoot/photon and its `docs/api-v1.md`.

No new database columns are needed: short names remain in `locations.name`,
resolved full addresses use `locations.landmark` (up to 255 characters), and
`purok` stores the optional house number / extra location information. The address helper formats existing
partial labels with Irisan/Baguio context. Existing manually entered landmarks
are not silently overwritten. In Management, a map click displays the resolved address below the map and fills
the name. Press Save to store it; there is no editable full-address input.

Saved reading tables now say **Risk status**. Admin Edit reading includes Low,
Normal, Medium, and High. The selected status is stored in `risk_level`; the
calculated rainfall category is kept separately in the edit metadata and shown
on the assessment panel. Overrides are identified as administrator-selected,
not as automatic rainfall calculations. Observation times and freshness rules
remain unchanged. Invalid status values are rejected server-side.

Report View dialogs now contain an offline map. New reports save their point
and address in the existing report event's `provider_payload` field, separately
from weather-event payloads. This snapshot stays fixed if a location is later
moved. Older reports without snapshots show their linked location and say so.
Missing coordinates/tiles produce a message instead of breaking the dialog.

Report phone and email are required both in HTML and PHP, and are still prefilled
from the resident's account. No public contact disclosure or new roles are added.

Check: click points on both dashboards, Management, and Submit report; try with
Wi-Fi off; save and reopen readings to check their address labels; save each risk
status; try blank contact fields; open/close/reopen report dialogs on desktop and
mobile. No schema import, tile recopy, or credential change is needed.

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


## p5 map and location corrections

- Report dialogs have an explicit map height and initialize after opening. The
  saved report point and local tiles load independently of the boundary overlay.
  Reopening resizes the same map. Missing tiles or coordinates have a visible message.
- Map colors use the latest unarchived saved reading: low green, normal blue,
  medium amber, high red, and no reading grey. Editing that reading changes the
  marker on the next page load. Historical reading edits do not replace a newer
  reading. Report review status (pending/reviewed/resolved) is separate from risk.
- Management offers an active saved-location dropdown. Choosing a marker or a
  saved option edits that location. Clicking a new point starts a new location;
  it does not silently move the previously selected location.
- Full addresses appear automatically when the address service returns one;
  the editable full-address input is removed. The optional house/details input
  reuses `purok`. Existing addresses are retained when coordinates stay the same;
  moving coordinates clears an old address unless a new lookup is available.
- Inactive locations stay in the management table for reactivation but are
  excluded from selection maps and dropdowns. Historical report maps retain the
  original reported point, even when its linked location is later deactivated.
- Residents can choose active saved locations or click a new point. Slow address
  responses cannot replace a newer selection. Saving waits for the lookup to finish.

No database import, migration, credentials change, or replacement tile download
is needed. Detailed address lookup still needs internet and available mapped data;
it cannot guarantee a house-level address for every point. Offline saved labels
and coordinates remain usable.

Verification: PHP syntax, JavaScript syntax, disposable MariaDB/HTTP tests at root
and `/project1` URLs, and mocked map interaction tests. Check the View dialog,
selection, and marker colors in your actual browser after applying the patch;
these automated checks do not constitute a browser rendering test.
