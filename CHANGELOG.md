# Changelog

## 1.5.0 - Unreleased

Requires `hostkurd/flocms-core` 2.2.0.

### Fixed
- A fresh install (`composer create-project`) shows the welcome page instead
  of HTTP 500 "Database connection failed" when no database is set up (#0).
  - `.env.example` ships with empty `DB_NAME` / `DB_USERNAME`.
  - `PagesController` creates its model only when an action needs it.
  - The welcome page shows a "Database: not configured / not reachable" card
    with setup hints (and the driver message when `APP_DEBUG=true`).
  - `templates/default/errors/nodbserver.html` and `dberror.html` are shown for
    database connection errors, with setup hints.
  - `DB_PORT` from `.env` is now used (`Config::set('db.port', ...)`).
- `.gitignore` used `/Storage/...` (wrong case on Linux), so `storage/cache`
  and `storage/logs` contents were not ignored.

### Security
- Suspended, deleted or demoted users lose admin access on their next request
  instead of at logout (#2). Login stores `user_id` in the session and
  `config/config.php` sets `auth.user_loader`, so flocms-core reloads role and
  status on every admin request.
- Admin login is throttled per IP (20) and per email (5) per 15 minutes using
  `FloCMS\Api\RateLimit\FileRateLimiter` (state in `storage/cache/login-throttle`).
  Blocked attempts get HTTP 429. Configure with `login_throttle`; set
  `TRUSTED_PROXIES` when running behind a reverse proxy.

### Changed
- The login throttle message uses `Lang::get()` placeholders (`:minutes`),
  new in flocms-core 2.2 (#4).

### Added
- `views/users/admin_login.html`: minimal admin login form (the skeleton had none).
- `support/` directory (`FloCMS\Support\` namespace) with `LoginThrottle`.
- PHPUnit test suite (`composer test`). Feature tests run the app under PHP's
  built-in server with a fresh `.env` made from `.env.example`.

## 1.4.0

- Restrict user management to administrators and cap role assignment at the
  current user's own role (requires flocms-core 2.1.0).
