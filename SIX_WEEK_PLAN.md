# SmartSlope Version 1.0 — Six-week team plan

## Purpose

Move the approved project into the official submission repository in working,
reviewable stages. Login, registration, both dashboards and their visual design
must be ready by the end of Week 2.

This is a migration, adaptation and integration plan using an existing reference.
Commit each member's actual work with real dates and preserve relevant attribution.
The plan does not describe work as newly invented when it was copied/adapted.

Use Member A–D below until real names are assigned.

## Responsibilities

| Member | Primary responsibility | Secondary responsibility |
| --- | --- | --- |
| A | Repository integration, PHP setup and authentication | Permissions and integration review |
| B | Bootstrap layout, shared views and dashboards | Responsive design and browser testing |
| C | PDO/database, weather and assessment | CSV and data integrity |
| D | Leaflet, location selection and resident reports | Test evidence and documentation |

Every member reviews another member's work. A owns integration decisions, not
all commits or all implementation.

## Before Week 1

1. Keep the reference project intact in a separate folder.
2. Apply the approved cleanup to the reference copy and record its commit.
3. Create the official GitHub repository and invite the other three members.
4. Each member clones the official repository using their own Git identity.
5. Agree on the served folder name and matching BASE_URL.
6. Use a separate development database; import the unchanged three-table schema
   once for that new database. Do not share private credentials or real reports.
7. Create six milestone issues and assign the tasks below.
8. Keep the full reference code outside the official repository until its
   scheduled integration. Do not copy the reference .git directory.
9. Preserve Bootstrap/Leaflet licenses, map attribution and required local assets.

## Weekly Git routine

1. Start from the latest reviewed main branch.
2. Create a branch such as week2/member-b-dashboard.
3. Copy only the assigned files from the reference folder, preserving paths.
4. Adapt the copied code to the dependencies available in that week's build.
5. Test the assigned feature and inspect git diff.
6. Commit specific files with an accurate message.
7. Push the branch and open a pull request.
8. A different member reviews and tests it.
9. Merge only when the week's integrated build works.
10. Record actual test evidence and tag the accepted milestone, such as week-2.

Example:

```bash
git switch main
git pull --ff-only
git switch -c week2/member-b-dashboard
# Copy/adapt assigned files, then test.
git add includes/header.php includes/footer.php assets/css/style.css
git commit -m "Integrate shared dashboard layout"
git push -u origin week2/member-b-dashboard
```

Do not blindly copy a final page that includes modules scheduled for later weeks.
Stage the page's includes, links and sections so every weekly checkpoint runs.
Do not create fake readings, fake success messages or empty classes just to hide
missing dependencies.

## Week 1 — Foundation and authentication

### Member A

1. Set up the official repository and folder structure.
2. Integrate config.php and includes/session.php.
3. Integrate index.php and logout.php with direct __DIR__ includes.
4. Ensure successful login has a working role-specific landing page.

### Member B

1. Integrate assets/css/bootstrap.min.css, assets/css/style.css and the needed
   Bootstrap bundle.
2. Finish the one-column login/registration forms and green/brown styling.
3. Create the initial shared header/footer and simple authenticated landing pages.
4. Temporarily omit links to pages not yet implemented.

### Member C

1. Integrate database/schema.sql and class/db.php.
2. Integrate class/users.php.
3. Verify registration, prepared SQL, password hashing and resident role assignment.
4. Check duplicate-account and incorrect-password behavior.

### Member D

1. Test registration/login/logout in a resident and admin browser session.
2. Check direct access to the opposite role's landing page.
3. Record setup steps, actual issues and each member's contribution.
4. Confirm Herd/root and XAMPP/subfolder path settings.

### Acceptance

- New resident registration and both role logins work.
- Login redirects never lead to a missing page.
- Logout works and protected pages require authentication.
- Passwords are stored as hashes.
- The schema remains users, locations and events.

## Week 2 — Dashboards and design complete

### Member A

1. Integrate dashboard.php and the two role entry pages:
   users/admin/admin.php and users/user/user.php.
2. Check permissions and session-based navigation.
3. Review the dashboard's includes before merging.

### Member B

1. Complete includes/header.php, includes/footer.php and dashboard visual design.
2. Adapt includes/dashboard.php into a working saved-data dashboard.
3. Integrate the read-only portions of readings.php and includes/readings-table.php.
4. Complete responsive cards, tables, navigation and the reading detail view.
5. Keep future management links, edit/delete buttons and CSV controls hidden
   until their endpoints are integrated.

### Member C

1. Integrate class/events.php for dashboard counts and saved-reading queries.
2. Include its dependencies: includes/address.php, includes/map-boundary.php
   and assets/map/irisan.geojson.
3. Verify that empty databases display honest empty states.
4. Verify dashboard counts against SQL data.

### Member D

1. Test both dashboards, saved-reading details and mobile navigation.
2. Check that residents do not receive the admin report/contact table.
3. Record screenshots and path checks in both URL layouts.
4. Review labels, spacing, contrast and table overflow.

### Dependency rule

The final dashboard currently references map, weather, assessment and bulk
modules. In this week's copy, integrate its saved-data/layout sections only.
Do not yet load location-map.js, weather.js or bulk-select.js, require the
assessment view, or show links to unfinished management/report actions.

### Acceptance

Login, registration, logout, separate admin/resident dashboards, saved-data
display and the dashboard design are functional. Map/weather/report management
are scheduled additions, not required to pretend complete in Week 2.

## Week 3 — Locations, offline map and addresses

### Member A

1. Integrate users/admin/admin_panel.php.
2. Check admin-only create/edit/activate/deactivate actions.
3. Leave CSV/bulk controls out until Week 6.

### Member B

1. Integrate includes/location-map.php and dashboard map placement.
2. Add location labels, selected coordinates and the active-location dropdown.
3. Check responsive map/form layout.

### Member C

1. Integrate class/locations.php and its address/boundary dependencies.
2. Check unique coordinates and active/inactive behavior.
3. Keep the schema unchanged.

### Member D

1. Copy assets/vendor/leaflet, assets/map and the entire assets/map-tiles folder.
2. Integrate assets/js/location-map.js and api/address.php.
3. Add the local Leaflet tags to the header when a page requests a map.
4. Test clicks, boundary rejection, addresses and offline tiles.

### Dependency rule

During this week, dashboard selection may open saved location history.
Do not call the weather refresh endpoint or reference missing weather controls
until Week 4. Adapt those sections of the reference map script explicitly.

### Acceptance

Map works without internet while PHP/MySQL run. Active locations can be
selected, edited and deactivated. Addresses are shown when available online.
No nonexistent weather endpoint is called.

## Week 4 — Weather and rainfall assessment

### Member A

1. Integrate api/readings.php and check GET/POST permissions.
2. Review errors and confirm normal page loads do not write readings.

### Member B

1. Integrate includes/assessment.php into the completed dashboard design.
2. Add Refresh, saved-reading feedback and source/freshness labels.
3. Integrate methodology.php.

### Member C

1. Integrate class/RiskAnalyzer.php, class/weather_readings.php and includes/weather.php.
2. Verify rainfall windows, threshold boundaries and freshness.
3. Check that provider failure preserves saved readings.

### Member D

1. Integrate assets/js/weather.js.
2. Restore the map-to-weather event flow in location-map.js.
3. Test repeated clicks, refresh, offline failure and selected-location history.

### Acceptance

A successful click/refresh saves weather and displays its assessment.
No reading is created simply by loading a page. Incomplete or outdated data is
not presented as a current warning. No physical sensor data is claimed.

## Week 5 — Reports and reading management

### Member A

1. Integrate users/admin/reports.php and its review filters/status actions.
2. Integrate users/admin/edit_reading.php.
3. Check administrator permissions and saved-risk editing.

### Member B

1. Complete report modal styling and reading edit/detail layouts.
2. Keep View/Edit/status controls; do not reintroduce individual Delete buttons.
3. Finish navigation to the newly available pages.

### Member C

1. Integrate class/reports.php.
2. Check report ownership, original-point snapshots and review updates.
3. Check reading edits preserve observation time and identify manual changes.

### Member D

1. Integrate users/user/submit_report.php and assets/js/report-maps.js.
2. Verify map-click and saved-location selection populate address/coordinates.
3. Test required contacts, own-report privacy and the modal map.

### Dependency rule

Remove or defer bulk-tools includes and checkbox actions until Week 6.
Report review and reading editing must work independently.

### Acceptance

Residents can submit and view their own reports. Administrators can review
reports and edit readings. Report maps show submitted locations and saved-risk
colors agree with the latest saved reading.

## Week 6 — CSV, bulk actions and official submission

### Member A

1. Integrate users/admin/bulk.php and final action permissions.
2. Restore the completed reference navigation and remaining active controls.
3. Review every final route and direct __DIR__ include.

### Member B

1. Integrate includes/csv-tools.php and includes/bulk-tools.php.
2. Add checkbox columns to readings, reports, locations and dashboard tables.
3. Integrate assets/js/bulk-select.js and its header script.
4. Confirm individual Delete buttons are absent.

### Member C

1. Integrate class/csv.php, class/bulk.php and csv.php.
2. Test export/import, duplicates, invalid files and manual historical readings.
3. Test multi-row actions and linked-location deletion protection.
4. Confirm the three-table schema is still unchanged.

### Member D

1. Integrate the consolidated README.md and finish the actual test record.
2. Test the complete workflow at root and subfolder URLs.
3. Check offline use, provider failure, report maps and narrow-screen layout.
4. Assemble screenshots and demonstration evidence using fictional data.

### Final integration and copying check

1. Compare the approved reference and submission file lists.
2. Confirm root runtime files are present: config.php, index.php, dashboard.php,
   readings.php, csv.php, logout.php and methodology.php.
3. Compare every file under class, includes, api, users, database and assets.
4. Reconcile every temporary Week 2/3/5 adaptation with the final implementation.
5. Preserve only the intended local config differences.
6. Keep dependency licenses and tile attribution.
7. Do not copy /docs, /tools, old generated patches, server logs, database
   backups containing personal data, or the reference repository's .git folder.
8. Keep README.md as the full project documentation.
9. Keep SIX_WEEK_PLAN.md as the team plan if requested for submission.
10. Run the README syntax checks and all manual acceptance tests.
11. Resolve teacher requirements about jQuery/security and prepare the required
    presentation, contribution evidence and startup artifacts.
12. Merge the reviewed release and tag v1.0.0 after actual acceptance.

## Weekly evidence

For each week record the real dates, member tasks, reviewed pull requests,
screenshots, test results, defects and next actions. Every member must explain
their assigned PHP/SQL/JavaScript and review at least one other member's work.

A tag records an accepted checkpoint. It is not proof of six weeks of original
development when the work was an adaptation/import; describe the work accurately.
