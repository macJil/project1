# SmartSlope Version 1.0 — Verification record

Review date: 17 October 2026. Source reviewed: macJil/project1, branch p6,
commit `dad5207a9c8ce9496845bacefbd5ac37fc9fc204`, plus this documentation/checker patch.

## Executed checks

| Check | Result | Scope |
| --- | --- | --- |
| HTTP and database regression | 721 assertions passed | Disposable MariaDB; actual PHP requests at root and /project1 URLs |
| Weather/risk unit checks | 38 assertions passed | Thresholds, window processing and freshness with deterministic inputs |
| Map/address interaction checks | 38 assertions passed | Mock browser/Leaflet APIs; selection, address races, dropdown fields, report-map logic |
| Shipped tools/verify.php without DB | 71 checks, zero failures | PHP syntax/extensions, bundled assets/tiles, sample boundary and risk cases |
| Shipped tools/verify.php --database | Passed inside both disposable test deployments | Read-only connection, expected tables and required columns |

The HTTP checks cover registration/login, role access, CRUD, selected-location
history, report privacy/snapshots, review filters, reading risk edits, failure
messages, CSV permissions/roundtrip/new rows/invalid-file rejection and admin
bulk deletion/deactivation. Database tests did not modify the user's database.

Weather and address integrations were exercised with deterministic provider
fixtures, including failure responses. This confirms application handling, not
current external-service availability or exact addresses in Irisan.

## Cleanup in this patch

- Replaced phase-history README with standalone Version 1.0 setup and usage.
- Added application/database reference, course alignment and local test guide.
- Added an optional CLI-only, read-only verification script.
- Removed three unused configuration helpers and the tracked generated patch.
- Updated historical wording in the methodology/comment without changing rules.
- No schema, credentials, risk thresholds, role permissions or UI workflow changes.

## Not certified by these results

- Actual Herd/XAMPP installation and browser rendering on the student's device.
- Live weather/address provider uptime, map address completeness or house accuracy.
- Production security; basic sessions omit CSRF tokens, ID regeneration and throttling.
- Landslide predictive accuracy, official warning authority or sensor measurements.
- Full course marks, jQuery compliance, team participation or startup deliverables.

Complete docs/TESTING.md and the instructor-specific items in REQUIREMENTS.md
before signing off the submission. This record is evidence of executed checks,
not a claim that every possible error has been eliminated.
