# SmartSlope — Phase 2

Separate administrator and resident dashboards with a shared green-and-brown
Bootstrap theme. Login, registration, logout, and role checks from Phase 1 remain
in place. No database schema or local configuration changes are required.

## Phase 2 screens

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
- Map selection, live risk assessment, management actions, report submission,
  and CSV are still later phases. The screens clearly identify unavailable
  components instead of showing buttons that pretend to perform those actions.
- Displayed dates are explicitly labeled UTC; high/medium totals count saved
  observations and must not be interpreted as current location risk.

Phase 2 checks: repeat Phase 1 authentication checks below, open both dashboards,
open a reading detail and close it, follow View all, and try the mobile navigation.
Verify empty tables and multiple saved readings. Confirm a resident sees only
his or her own report count and no administrator report table.

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
