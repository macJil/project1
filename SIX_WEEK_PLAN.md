# SmartSlope — Five-week new-repository team plan

## Goal

Build a working copy of SmartSlope in a **new GitHub repository** over five
weeks. Use the current SmartSlope project as the source for the staged work;
this is not a request to change the current project during planning. By the end
of Week 5, the new repository should have a working, tested website with the
existing features: accounts and roles, dashboards, offline maps, weather
screening, resident reports, administration, CSV and bulk actions.

The README describes the project's intended three-layer architecture:
Presentation / Client, Application / Logic, and Data / Database. Keep browser
code away from the database, and keep authentication, validation and
authoritative application rules in PHP.

Replace **Member A–D** with the four students' names before the first week.
Each member keeps one personal branch for all five weeks. One agreed integrator
merges the four branches into `main` each week, only after review and testing.
Never commit directly to `main`.

## Before Week 1

### Prepare the new repository

1. One student creates a private or public GitHub repository, chooses `main`
   as its default branch, and invites the other three students.
2. Add a short initial README on GitHub (or make an initial commit locally) so
   the repository has a `main` branch before members clone it.
3. Keep the existing SmartSlope source project separate. Copy only files
   assigned for that week's milestone; **never copy the source project's
   `.git/` directory**.
4. Before the first push, inspect `config.php`. Commit only safe example/local
   defaults; do not push a real database password, private keys, production
   data, database backups, or real resident contact information. Each member
   configures their own local database credentials privately.
5. Agree on one integrator, one reviewer per pull request, and the PHP/MySQL
   setup and `BASE_URL` each member will test. The integrator is not the only
   person allowed to contribute or review.

### Create one persistent branch per member

Each student clones the new repository and creates their own branch **once**.
Use the matching name for your role; keep using that branch every week. Use
the same clone for your own work. Do not copy the source project's `.git/`
folder into the new repository.

```bash
git clone https://github.com/<GROUP>/<NEW-REPOSITORY>.git
cd <NEW-REPOSITORY>
git switch main
git pull --ff-only origin main
git switch -c member-a
git push -u origin member-a
```

Repeat the last two commands with `member-b`, `member-c` and `member-d` for the
other members. Confirm your branch with `git branch --show-current`. Do not
create a new branch named for each week; the four personal branches persist
through Week 5.

## Weekly responsibilities and exact commits

The paths below are relative to the **new repository root**. Copy or update
only the assigned paths from the current source project. Some PHP pages combine
HTML and request handling, so keep each milestone working as it grows: introduce
dependencies together, enable links only after their destination exists, and
do not hide missing features with suppressed errors or fake data.

### Week 1 — Repository foundation, accounts and role pages

| Member | Own this work | Stage these paths |
| --- | --- | --- |
| A | Login/registration flow, session checks, logout and role routing. | `includes/session.php index.php logout.php dashboard.php` |
| B | Shared page shell, responsive base styles and local Bootstrap assets. | `includes/header.php includes/footer.php assets/css/style.css assets/css/bootstrap.min.css assets/js/bootstrap.bundle.min.js` |
| C | Safe database setup, schema, PDO connection and user persistence. Sanitize database settings before staging. | `database/schema.sql class/db.php class/users.php config.php` |
| D | Resident/admin entry pages and a simple role-appropriate dashboard that does not require later-week features yet. | `users/admin/admin.php users/user/user.php includes/dashboard.php` |

**Exit check:** a new resident can register and log in; the seeded/test admin
can log in; each role reaches its own protected page; logout works; passwords
are hashed; and no page links to an unimplemented feature.

**Exact commit messages:** A — `Week 1: Add account and role routing`; B —
`Week 1: Add shared layout and visual foundation`; C — `Week 1: Add database
schema and account persistence`; D — `Week 1: Add role dashboards and verify
login flow`.

### Week 2 — Saved data, locations and offline maps

| Member | Own this work | Stage these paths |
| --- | --- | --- |
| A | Admin-only location management and location permissions. | `users/admin/admin_panel.php` |
| B | Saved-data dashboard and readings list; keep later weather/report controls disabled until their milestone. | `includes/dashboard.php readings.php includes/readings-table.php includes/header.php assets/css/style.css` |
| C | Location and event persistence; address and Irisan boundary helpers/data. | `class/locations.php class/events.php includes/address.php includes/map-boundary.php assets/map/irisan.geojson` |
| D | Location map, authenticated address selection, Leaflet assets and all local map tiles. Preserve each `zoom/x/y.png` path. | `includes/location-map.php api/address.php assets/js/location-map.js assets/vendor/leaflet/ assets/map-tiles/` |

**Exit check:** both roles can see the appropriate saved locations/readings;
admin location changes are protected; the map and boundary load; local tiles
work offline; selecting a point gives coordinates and a usable address result.
Test a fresh database and empty tables.

**Exact commit messages:** A — `Week 2: Add protected location management`;
B — `Week 2: Add saved-data dashboards and readings`; C — `Week 2: Add
location and event data access`; D — `Week 2: Add offline location maps and
address selection`.

### Week 3 — Weather requests and rainfall assessment

| Member | Own this work | Stage these paths |
| --- | --- | --- |
| A | Authenticated weather/readings API, request validation and explicit error responses. | `api/readings.php` |
| B | Assessment display, dashboard wiring, navigation and methodology/limitations. | `includes/assessment.php includes/dashboard.php includes/header.php methodology.php assets/css/style.css` |
| C | Weather data retrieval/storage and rainfall assessment rules. Preserve the prototype's units, thresholds and freshness limits. | `class/RiskAnalyzer.php class/weather_readings.php includes/weather.php` |
| D | Browser map-to-weather interaction, loading/error states and saved-reading refresh behavior. | `assets/js/weather.js assets/js/location-map.js` |

**Exit check:** a user-triggered dashboard selection/refresh can save and show
a valid reading and assessment; normal dashboard reloads do not save readings;
incomplete/stale results are not described as current; provider failure leaves
previous saved data available and shows a clear error. Keep the rainfall result
described as an academic prototype, not an official warning.

**Exact commit messages:** A — `Week 3: Add authenticated weather API`; B —
`Week 3: Add rainfall assessment to dashboards`; C — `Week 3: Add weather
storage and rainfall rules`; D — `Week 3: Connect map selection to weather
refresh`.

### Week 4 — Resident reports and administrator review

| Member | Own this work | Stage these paths |
| --- | --- | --- |
| A | Admin report-review and reading-edit routes; maintain role and record-type checks. | `users/admin/reports.php users/admin/edit_reading.php` |
| B | Navigation, dashboard/report tables and responsive presentation for the completed workflows. | `includes/header.php includes/dashboard.php includes/readings-table.php assets/css/style.css` |
| C | Report persistence, resident ownership, original location/address snapshot and review status updates. | `class/reports.php` |
| D | Resident report form/history and report-map behavior, including the admin report map. | `users/user/submit_report.php assets/js/report-maps.js` |

**Exit check:** residents submit reports and see only their own history;
administrators can review the original reported point and update review status;
residents cannot access admin report/contact details; report status does not
change rainfall risk. Admin reading edits preserve observation time and saved
risk behavior.

**Exact commit messages:** A — `Week 4: Add admin report review and reading
edits`; B — `Week 4: Add report navigation and responsive tables`; C —
`Week 4: Add report persistence and ownership checks`; D — `Week 4: Add
resident reports and report maps`.

### Week 5 — CSV, bulk actions and full acceptance

| Member | Own this work | Stage these paths |
| --- | --- | --- |
| A | Admin bulk-action endpoint, authorization and record-type protections. | `users/admin/bulk.php` |
| B | CSV/bulk controls, selection behavior and final page wiring. Do not add individual delete buttons where the UI uses selected-row deletion. | `includes/csv-tools.php includes/bulk-tools.php assets/js/bulk-select.js readings.php users/admin/admin_panel.php users/admin/reports.php` |
| C | CSV import/export and bulk data operations, with validation and duplicate/linked-location handling. | `class/csv.php class/bulk.php csv.php` |
| D | Run the full README test guide, record actual results/issues, update the README and this plan if names or evidence changed, and coordinate fixes with their owners. | `README.md SIX_WEEK_PLAN.md` |

**Exit check:** residents can download permitted reading CSVs; admins can use
the authorized import/export and bulk actions; invalid imports are rejected
without partial writes; linked locations are protected; all roles, routes,
maps, weather, reports and responsive pages pass the README test guide. Fix
blockers before calling the website complete. Record the final tested commit,
environment and any remaining limitations; do not mark unrun tests as passed.

**Exact commit messages:** A — `Week 5: Add protected admin bulk actions`; B —
`Week 5: Add CSV and bulk controls to admin pages`; C — `Week 5: Add validated
CSV and bulk data operations`; D — `Week 5: Complete acceptance testing and
project documentation`.

## Exact copy, commit and merge steps

Follow the members in the order shown for each week. Do not work on the four
branches in parallel: each member must start from the `main` that includes the
previous member's merged work. The member should copy only the paths assigned
to them in that week's table above. Copy from the existing SmartSlope source
project into the new repository, keeping every path the same. For a folder
assignment, copy the whole folder and keep its subfolder structure.

### Each member's steps

For every weekly assignment, the member does these steps in order:

1. Open a terminal in your new repository. Substitute your own branch name in
   the commands below. Check that you have no uncommitted changes:

   ```bash
   git switch member-a
   git status --short
   git fetch origin
   git merge origin/main
   git branch --show-current
   ```

   Use `member-b`, `member-c` or `member-d` instead of `member-a` as
   appropriate. If the status shows old work, commit or otherwise safely
   resolve it before continuing. If bringing in `main` causes conflicts, use
   **If a merge conflicts** below.
2. In Finder/File Explorer, open the existing source project and the new
   repository. Copy only your assigned files/folders from the weekly table
   into the same relative paths in the new repository. Create any destination
   folders that do not exist. Replace a destination file if it already exists.
   Never copy `.git/`. Before copying `config.php`, remove real passwords or
   other private values; use only safe example/local defaults.
3. From the terminal in the new repository, stage exactly your assigned paths.
   Do not use `git add .`:

   ```bash
   git add <your exact paths from this week's table>
   git diff --cached --check
   git diff --cached
   ```

   Confirm that the staged changes contain only your work and no secrets.
   Then use the exact commit message from the weekly table:

   ```bash
   git commit -m "<your exact commit message from this week's table>"
   git push origin member-a
   ```

   Replace `member-a` with your own branch name.
4. Tell the integrator your branch is ready. Do not merge your branch into
   `main` yourself.
5. Wait until the integrator merges your branch and pushes `main`. The next
   member in that week's order then follows these same steps and first merges
   the updated `origin/main` into their own branch.

### Integrator's steps after each member

Use one integrator for the whole project. After each member pushes, the
integrator reviews that member's branch and merges it to `main` before the next
member starts. In the integrator's clone, run:

```bash
git switch main
git pull --ff-only origin main
git fetch origin
git merge --no-ff origin/member-a -m "Merge member-a Week 1 work"
git push origin main
```

Replace the member branch and week number with the current assignment. Review
the merge before pushing. The `main` branch is built up one member at a time;
do not deploy or call a week complete until all four members' work for that
week is merged and the week's exit check above passes. If a merge conflicts,
resolve it as described below, then review and test before pushing.

### Required order for every week

For each numbered week below, do Member A, merge and push A; then Member B,
merge and push B; then Member C, merge and push C; then Member D, merge and
push D. The branch and merge-message names for each step are:

| Week | First: member branch → merge message | Second: member branch → merge message | Third: member branch → merge message | Fourth: member branch → merge message |
| --- | --- | --- | --- | --- |
| 1 | `member-a` → `Merge member-a Week 1 work` | `member-b` → `Merge member-b Week 1 work` | `member-c` → `Merge member-c Week 1 work` | `member-d` → `Merge member-d Week 1 work` |
| 2 | `member-a` → `Merge member-a Week 2 work` | `member-b` → `Merge member-b Week 2 work` | `member-c` → `Merge member-c Week 2 work` | `member-d` → `Merge member-d Week 2 work` |
| 3 | `member-a` → `Merge member-a Week 3 work` | `member-b` → `Merge member-b Week 3 work` | `member-c` → `Merge member-c Week 3 work` | `member-d` → `Merge member-d Week 3 work` |
| 4 | `member-a` → `Merge member-a Week 4 work` | `member-b` → `Merge member-b Week 4 work` | `member-c` → `Merge member-c Week 4 work` | `member-d` → `Merge member-d Week 4 work` |
| 5 | `member-a` → `Merge member-a Week 5 work` | `member-b` → `Merge member-b Week 5 work` | `member-c` → `Merge member-c Week 5 work` | `member-d` → `Merge member-d Week 5 work` |

### Week 1 example: exact order and files

Use the common member and integrator steps above. Follow this list exactly;
each integrator merge and push happens before the next member begins:

1. **Member A:** copy `includes/session.php`, `index.php`, `logout.php` and
   `dashboard.php` from the source project into the same paths in the new
   repository. Stage those paths, commit `Week 1: Add account and role
   routing`, and push `member-a`. The integrator merges `origin/member-a` with
   message `Merge member-a Week 1 work` and pushes `main`.
2. **Member B:** first merge the updated `origin/main` into `member-b`. Copy
   `includes/header.php`, `includes/footer.php`, `assets/css/style.css`,
   `assets/css/bootstrap.min.css` and `assets/js/bootstrap.bundle.min.js`.
   Stage those paths, commit `Week 1: Add shared layout and visual
   foundation`, and push `member-b`. The integrator merges `origin/member-b`
   with message `Merge member-b Week 1 work` and pushes `main`.
3. **Member C:** first merge the updated `origin/main` into `member-c`. Copy
   `database/schema.sql`, `class/db.php`, `class/users.php` and the sanitized
   `config.php`. Stage those paths, commit `Week 1: Add database schema and
   account persistence`, and push `member-c`. The integrator merges
   `origin/member-c` with message `Merge member-c Week 1 work` and pushes
   `main`.
4. **Member D:** first merge the updated `origin/main` into `member-d`. Copy
   `users/admin/admin.php`, `users/user/user.php` and `includes/dashboard.php`.
   Stage those paths, commit `Week 1: Add role dashboards and verify login
   flow`, and push `member-d`. The integrator merges `origin/member-d` with
   message `Merge member-d Week 1 work` and pushes `main`.

For each step, stage only the named files, inspect the staged diff, and follow
the commands in **Each member's steps** and **Integrator's steps after each
member**. Weeks 2–5 use the same sequence and steps, with the exact paths and
commit messages in that week's table above.

## If a merge conflicts

1. Stop and read the full `git status`. Do not stage or commit unrelated files.
2. Open each conflicted file, compare both versions, remove only the conflict
   markers (`<<<<<<<`, `=======`, `>>>>>>>`), and preserve both intended changes.
   Ask the file owners to agree on behavior when edits overlap.
3. Stage only the resolved paths and inspect the staged result:

   ```bash
   git add <resolved-paths>
   git diff --cached --check
   git diff --cached
   ```

4. Complete the merge with `git commit` (or `git merge --continue` if Git asks),
   then run the milestone tests again. Push only after the combined result
   works.
5. If the resolution is unclear, safely cancel the in-progress merge and ask
   the integrator/owners to coordinate:

   ```bash
   git merge --abort
   git status --short
   ```

Never use `git push --force` to make a merge appear successful. Avoid conflicts
by assigning one owner per file for each week, merging one member branch at a
time, and syncing with `origin/main` before the next week's edits.

## Final handoff

- Run the README's full manual test guide against the new repository's `main`,
  including both roles, fresh and populated data, mobile layout, offline tiles,
  provider failures, report privacy/maps, CSV validation and bulk actions.
- Record the final Git commit and real test results. Keep credentials and
  personal data out of screenshots, commits and the repository.
- Ask each member to explain their contribution and review one other member's
  work. Record accurate contribution dates; do not claim copied code as newly
  invented work.
- Tag a release only after the integrated website passes acceptance and the
  group has resolved or documented remaining issues.
