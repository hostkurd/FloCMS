# Changelog

## 1.6.0 - Unreleased

Requires `hostkurd/flocms-api` 1.1.0 (pinned to the exact version, like
core and uploader) and PHP 8.1.

### Changed
- `public/api.php` uses flocms-api 1.1 (D3):
  - Middleware order: security headers, errors, CORS, maintenance mode,
    locale, rate limit, body limits. Error responses (404, 405, 422, 429,
    500) now carry security and CORS headers, and CORS preflights are no
    longer rate limited.
  - Named rate limits (`throttle:forms`), and `auth` and `idempotent` route
    middleware.
  - Strict routing in debug mode; the OpenAPI document at
    `/api/v1/openapi.json` in debug mode only.
  - API errors and route warnings are logged to `storage/logs/api.log`.
- `api/routes.php` has example routes: `UsersController` (authentication
  with a permission, pagination, filters and sorting, `UserResource`) and
  `ContactController` (validation, rate limit, idempotency). Controllers
  autoload from `api/` as `App\Api\`.
- `GET /api/v1/health` reports the database: `{"status":"ok","db":"ok"}`
  (or `"not_configured"`), 503 when the database is down.
- `php flo api:*` runs the flocms-api commands (tokens, API keys, schema,
  routes, OpenAPI, cleanup).
- `composer.json` requires PHP `^8.1` (core already did).

### Security
- The legacy `/api/<controller>/<action>` route, which skips CSRF checks and
  has no authentication, is now off by default. Set `LEGACY_API=true` in
  `.env` to keep using `api_` controller methods.

### Upgrading from 1.5
1. Require `hostkurd/flocms-api` `1.1.0` and `"php": "^8.1"`, add
   `"App\\Api\\": "api/"` to `autoload.psr-4`, and run `composer update`.
2. Copy `public/api.php`, `flo` and the `routes` block of `config/config.php`.
   If you use `api_` controller methods, set `LEGACY_API=true` in `.env`.
3. Add the new `.env` keys from `.env.example` (`API_MAINTENANCE_ALLOWED_IPS`,
   `LEGACY_API`).
4. For tokens, API keys or the database idempotency store, run
   `php flo api:install-schema`.
5. Optionally add `php flo api:gc` to cron.


## 1.5.0 - Unreleased

Requires `hostkurd/flocms-core` 2.2.0 and `hostkurd/flocms-uploader` 1.2.0
(both pinned to exact versions).

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
- `.env.example` no longer ships a fixed `APP_KEY` that every site shared (#6).
- Suspended, deleted or demoted users lose admin access on their next request
  instead of at logout (#2). Login stores `user_id` in the session and
  `config/config.php` sets `auth.user_loader`, so flocms-core reloads role and
  status on every admin request.
- Admin login is throttled per IP (20) and per email (5) per 15 minutes using
  `FloCMS\Api\RateLimit\FileRateLimiter` (state in `storage/cache/login-throttle`).
  Blocked attempts get HTTP 429. Configure with `login_throttle`; set
  `TRUSTED_PROXIES` when running behind a reverse proxy.

### Changed
- Views, layouts and partials are compiled once to `views/cache/` and
  included (flocms-core 2.2), so OPcache can cache them (#5). The stale
  `views/cache/pages_index.php` is no longer tracked; `views/cache/` is kept
  with its own `.gitignore`.
- Forms use the `@csrf` directive (flocms-core 2.2); a test checks that
  every POST form in `views/` and `templates/` has a CSRF field (#6).
- The login throttle message uses `Lang::get()` placeholders (`:minutes`),
  new in flocms-core 2.2 (#4).

### Added
- `hostkurd/flocms-uploader` 1.2.0: `Uploader::video()` (mp4/webm, optional
  mov and poster image) and chunked, resumable uploads (`->chunked()`) for
  files above `upload_max_filesize` (#7).
- `public/api.php` with `api/routes.php`: the recommended API path, built on
  `FloCMS\Api\Kernel` (flocms-api), with JSON errors, security headers, CORS
  (`API_CORS_ORIGINS`) and per-IP rate limiting (`API_RATE_LIMIT`, default 60
  per minute). `public/.htaccess` sends `/api/v1/*` there (#6). The legacy
  `/api/<controller>/<action>` route (`api_` methods, no CSRF check, no
  authentication) still works but is deprecated.
- `php flo key:generate [--force]`, also run by `post-create-project-cmd`, so
  every install gets its own `APP_KEY` (#6).
- Admin layout: `<meta name="csrf-token">` and `js/csrf.js`, which sends
  `X-CSRF-TOKEN` with same-origin POST/PUT/PATCH/DELETE requests made with
  `fetch()` or jQuery (#6).
- `views/users/admin_login.html`: minimal admin login form (the skeleton had none).
- `support/` directory (`FloCMS\Support\` namespace) with `LoginThrottle`.
- PHPUnit test suite (`composer test`). Feature tests run the app under PHP's
  built-in server with a fresh `.env` made from `.env.example`.

## 1.4.0

- Restrict user management to administrators and cap role assignment at the
  current user's own role (requires flocms-core 2.1.0).
