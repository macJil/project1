# SmartSlope Version 1.0 — Verification guide

## 1. Prepare the test installation

1. Use a disposable database or back up your current database. Bulk deletion is
   permanent. CSV is not a full database backup.
2. Follow the README setup. Start PHP/web server and MySQL. Confirm `BASE_URL`
   and database credentials. Do not re-import schema.sql into your working data.
3. Use two browser profiles or a regular/private window: one admin and one
   resident. Create a second resident for privacy checks. Use fictional contact
   details and clearly label test observations. Do not submit real emergency reports.
4. Record the date, browser, PHP version (`php -v`), database version, commit
   (`git rev-parse HEAD`) and URL in the result record below.
5. Run `php tools/verify.php --database`. Investigate every FAIL. SKIP means the
   relevant check has not run. These checks never change database rows.
6. If Node is installed, check the app JavaScript:

```bash
node --check assets/js/location-map.js
node --check assets/js/weather.js
node --check assets/js/report-maps.js
node --check assets/js/bulk-select.js
```

7. Open the browser Developer Tools Console and Network tabs. Clear old output.
   Keep them open while testing. Note the HTTP response when an action fails;
   distinguish an expected validation/provider error from a PHP crash.

## 2. Authentication and permissions

| Test | Steps | Expected result |
| --- | --- | --- |
| A1 | Register a new resident with all fields. Log in. | Success message; user dashboard; role is user in DB even if a request supplies `role=admin`. |
| A2 | Repeat a username/email/phone, try malformed email, short password or blank fields. | Helpful message; no duplicate account or raw SQL/stack trace. |
| A3 | Try a wrong password, then the correct one. | Wrong password rejected; correct login succeeds. |
| A4 | Log in as admin in the other window. | Admin dashboard and Management links appear. |
| A5 | As resident, open `/users/admin/admin_panel.php`, `/users/admin/reports.php`, `/users/admin/edit_reading.php?id=1`. | Access denied, HTTP 403. Hiding buttons alone is not the check. |
| A6 | Log out, then reload a protected dashboard. Open `/api/readings.php?location_id=1` logged out. | Dashboard redirects to login; API returns 401. Logout requires POST. |
| A7 | Inspect a newly registered user's password column. | A password hash, never the typed plaintext. Do not screenshot passwords/hashes in public evidence. |

## 3. Maps, addresses and weather

1. Open both dashboards. Check the brown Irisan boundary, local tiles, zoom,
   active markers, legend and layout. Inspect Network: tiles should be local
   `assets/map-tiles/...png`, not an online tile CDN.
2. Click a point clearly inside Irisan. The selected coordinates should match
   the point, and a mapped address should appear when the provider has data.
   Nearby addresses can be approximate; no house number is guaranteed.
3. With internet, let weather finish. A successful request saves a reading and
   reloads the selected location. Verify rainfall, UTC time, source and assessment.
4. Press Refresh once. Verify one additional reading after a successful request.
   Reload the page without clicking: it must not save another reading.
5. Use View saved readings here. Confirm the selected location's history, then
   View all to see the full list. Details should show that reading's weather/source.
6. Click outside the boundary. Confirm rejection and no new database reading.
7. Click point A then B rapidly during address lookup. B must remain selected
   even if A's address response arrives later. No A address should overwrite B.
8. Disconnect internet while leaving PHP/MySQL running. Reload the page and
   open saved history: local tiles and saved data remain visible. Refresh should
   report provider failure without deleting old data. A new unmapped address
   may be unavailable; coordinates still identify the selected point.
9. Reconnect. Repeat a successful refresh. Do not treat a timeout as proof that
   no write occurred: reload saved history before retrying.
10. On an admin-edited latest reading, choose low, normal, medium and high in
    turn. Reload the map after each save: green, blue, amber and red respectively.
    The saved category must match; the calculated category is displayed separately
    when an assessment is available. Editing an older row does not override a newer row.
11. The CLI script checks threshold and three-hour expiry boundaries. Also inspect
    an old reading in the browser: history can retain its colored saved category,
    but the current assessment must say outdated/incomplete rather than fresh.

## 4. Location management

1. In Management choose an active saved location. Verify name, optional house
   information, coordinates and selected address.
2. Click a new point inside the map. This starts an Add location form. Save it
   with a unique test name and optional house information. There is no editable
   full-address input; a resolved address is saved automatically when available.
3. Select the saved row again and edit its name/house information. Confirm the
   existing ID is updated rather than a duplicate inserted.
4. Try blank name, outside coordinates and an existing coordinate pair. Confirm
   validation/duplicate feedback; no fatal error or unintended overwrite.
5. Deactivate the test location. Reload both dashboards, Management's map and
   the resident report picker: it must disappear from active maps/dropdowns,
   but remain in the management table and historical reports/readings.
6. Reactivate it. Confirm it returns. Select two test locations and bulk
   deactivate/reactivate. Check Select all applies only to displayed rows.
7. Select an unlinked test location and a location with a reading/report. Choose
   bulk Delete: the unlinked one disappears; the linked one is skipped. Its
   events remain. Deactivate the linked location if it should be hidden.

## 5. Resident reports and admin review

1. As resident A, choose a saved location in Submit report. Visible read-only
   latitude, longitude and address fields must fill automatically.
2. Click another map point. Fields and selection must switch to that point.
   Select a saved option again: the marker and fields must agree with that option.
3. Leave phone/email blank or enter invalid email. Submission must fail without
   creating a report. The chosen point and written message should remain.
4. Submit valid required details, observation type and Manila observation time.
   Verify a success message and a new entry in Your reports. The stored/displayed
   observation time is UTC (Manila time minus eight hours).
5. Log in as resident B. A's private report history/contact details must not
   appear. Shared weather/location records remain visible by design.
6. As admin, open Pending reports and View the submitted report. Check contact,
   description and the map at the original submitted coordinates. Close/reopen
   the modal; the map must stay correctly sized. Repeat on a narrow viewport.
7. Edit/move/deactivate the linked location. Reopen the report. New reports retain
   their submission point/address; older reports lacking a snapshot use the linked
   location and are labeled accordingly.
8. Change Pending to Reviewed to Resolved. Check each filter and dashboard count.
   Review status does not change rainfall risk or certify safety.
9. Delete one test report via its dialog, then select two different test reports
   and Delete selected. Verify only those reports disappear. Test empty selection:
   it must show a message and delete nothing.

## 6. Reading management and CSV

1. Edit a test reading as admin. Change rainfall/weather within valid ranges and
   choose a saved risk. Save and reopen. Verify stored values, manual source and
   preserved observation time. Invalid values must not be saved.
2. Delete one test reading, then two with checkboxes. Verify only selected
   reading IDs disappear. Report records must remain.
3. As resident, download the readings CSV. As admin, download readings and
   locations. Headers and rows must appear without HTML, warnings or login pages.
4. Import each unchanged download as admin. Existing records should be skipped,
   not duplicated, renamed or reactivated. Residents must have no import controls;
   a direct resident POST to `csv.php` must return 403.
5. To test new location import, copy a locations CSV, retain its exact header and
   format marker, and add a row with a unique coordinate inside Irisan and a test
   name. Import it. Verify the location appears. Use straight CSV quotes; do not
   change the header names or sheet-save it as XLSX.
6. For a new reading, copy a readings CSV row and use a different valid UTC
   `observed_at_utc`, e.g. `2025-01-02 03:04:05` if unused at that location.
   Import. Verify its weather values and saved risk, source manual, and historical
   rather than fresh assessment. Repeat: duplicate is skipped.
7. Try a wrong header, invalid risk, outside coordinates and a valid row followed
   by an invalid row. The whole file must be rejected with no partial import.
   Try an empty/header-only file and a non-CSV file: clear error, no database changes.
8. Confirm quoted commas and Unicode location names survive export/import. Text
   cells have a leading apostrophe for spreadsheet handling; import removes that
   prefix. Export/import is not a full backup of provider payloads/edit history.
9. Limits: upload at most 2 MB and 2000 data rows; select at most 500 bulk rows.
   Larger downloads must be split into valid files before import. Readings match
   by location coordinates plus UTC observation time, including archived rows.
   Inactive locations' reading imports are skipped. Invalid files are rejected.

## 7. Layout, deployment and error checks

- Test desktop and a roughly 375px-wide viewport. Check navigation, readable
  form labels, horizontal table scrolling, dialogs, map controls and buttons.
- Inspect Console for uncaught JavaScript errors and Network for missing CSS,
  JavaScript, GeoJSON and PNG files. Investigate unexpected HTTP 500 responses.
- Test once at Herd root URL and once at XAMPP's `/project1` subfolder. Match
  BASE_URL each time. The CLI PHP version can differ from the web server's PHP.
- In an isolated test installation, stop MySQL and open a DB-dependent page:
  expect the application's safe error, no credentials/SQL stack trace. Restart it.
- Inspect your PHP/web-server logs after tests for warnings/fatals. Do not edit
  production/server configuration to reproduce a failure in your only installation.
- Refresh an open second browser tab after changing risk or active status: maps
  are not pushed live across tabs. Check blank tables after deleting all test rows.

## 8. Result record and submission gate

Make a copy of this table for each local environment. Do not pre-mark PASS.
Attach screenshots with fictional data and relevant log extracts; avoid passwords.

| Item | Actual result / evidence |
| --- | --- |
| Tester, date, commit | Not yet recorded |
| URL, OS, PHP, database, browser | Not yet recorded |
| CLI check output / skipped checks | Not yet recorded |
| Authentication/permissions A1–A7 | Not yet recorded |
| Maps, addresses, weather/freshness | Not yet recorded |
| Location CRUD and bulk operations | Not yet recorded |
| Reports, privacy, modal map | Not yet recorded |
| Reading edits, CSV, bulk deletion | Not yet recorded |
| Offline and provider failure | Not yet recorded |
| Desktop/mobile and both URL layouts | Not yet recorded |
| Remaining issues and owner | Not yet recorded |
| Final local acceptance | Pending |

The project is ready for a classroom demonstration when these local checks pass
and the remaining course deliverables in REQUIREMENTS.md are prepared. Automated
checks alone do not certify production security or landslide prediction accuracy.
