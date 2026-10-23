# Changelog

## 2.0.0 - unreleased

Requires Core 2.8.0 (`composer.lock` installs it). flocms-api is no longer
required. Design note: "FloCMS setups: website, admin, API".

### What changed for new projects
- **`composer create-project` asks what you are building** (`php flo setup`,
  `commands/SetupCommand.php`): website + admin panel (default), website
  only, admin panel only, API + admin panel, or API only. It adds the packages
  for that choice with `composer require`, runs their installers
  (`admin:install`, `api:install`), sets `home` and, for admin-only and API
  setups, hides the admin's View site link and removes the welcome page and
  the `dark` example template while they are unchanged. Without a terminal it
  changes nothing. `--as=…` skips the question, `--dry-run` prints the plan.
  Running it again only adds; it never removes a package.
- **A plain install is website only.** `hostkurd/flocms-api` moved from
  `require` to `suggest`, and `api/` and `public/api.php` left the skeleton:
  `php flo api:install` (flocms-api 1.3) writes them when you add the API.
  `composer.json` still loads `App\Api\` from `api/`, and `public/.htaccess`
  still sends `/api/v1` to `public/api.php`.
- **`Config::set('home', 'site')`** in `config/config.php`: `admin` sends `/`
  to `/admin`, `api` answers `{"name": …, "api": "…/api/v1"}`, `site` is the
  website as before (a Pages home page of flocms-admin still wins). Any other
  value means `site`.

### Changed
- The login throttle, the client IP and maintenance mode use Core 2.8's
  `FloCMS\Core\RateLimit\FileRateLimiter`, `RateLimiterInterface`,
  `FloCMS\Core\Security\ClientIpResolver` and `IpMatcher` instead of
  flocms-api's (`support/LoginThrottle.php`, `controllers/UsersController.php`,
  `includes/maintenance.php`).
- `post-create-project-cmd` ends with
  `Composer\Config::disableProcessTimeout` and `@php flo setup`.
- `APP_VERSION=2.0.0` in `.env.example`.

### Tests
- `tests/Feature/SetupCommandTest.php` (every setup, the question and its
  default, no terminal, dry run, changed example files kept, running again,
  a failing command), `tests/Feature/HomeSettingTest.php`.
- The API tests (`ApiEndpointTest`, `ApiFeaturesTest`, `ApiAuthTest`, the API
  part of `MaintenanceModeTest`) run on a copy where `php flo api:install`
  added the API, and are skipped without flocms-api. CI's new "With
  flocms-api" job adds it and runs them.

### Existing sites
Nothing to do: your `composer.json` keeps flocms-api, and flocms-api 1.3
keeps the old class names. To follow 2.0, see "Upgrading to 2.0" in the
README. To add the admin panel or the API to a site, copy
`commands/SetupCommand.php` and the `home` lines (`config/config.php`,
`PagesController::index()`), or run the packages' installers yourself.

## 1.8.8 - 2026-10-21

### Changed
- Requires Core 2.6.0 (`composer.lock` installs v2.6.0), whose fallback hook
  (`App::fallbackUsing()`) lets flocms-admin 0.30's Pages answer their own
  addresses.

### Added
- Ready for flocms-admin 0.30's **Pages** (Site -> Pages): the home controller
  (`PagesController::index()`) shows the page chosen as the home page when
  there is one, and both layouts print `page_meta()` (the page's title,
  description, languages and social tags) on those pages and
  `blocks_assets()` in the `<head>`. Without flocms-admin, or without pages,
  nothing changes.

Existing sites: copy the lines at the start of
`PagesController::index()` and the `<head>` changes in
`templates/*/layouts/default.html`, then
`composer require "hostkurd/flocms-core:^2.6" -W`.

## 1.8.7 - 2026-10-18

### Changed
- Requires Core 2.4.0, whose roles loader (`Auth::loadRolesUsing()`) lets a
  package supply roles at runtime; flocms-admin 0.21's Roles page uses it.
- The admin login asks `Auth::hasAdminAccess()` whether a role may open the
  admin area, and keeps its own `['1', '2', '3']` only when nothing is
  configured. A role added on the Roles page signs in at once; a role whose
  panel access was taken away does not.
- The `/admin` page shows the role name from `Auth::roles()`, so roles added
  at runtime have their names.

### Added
- `tests/Feature/RolesLoaderLoginTest.php` (MySQL/MariaDB).

Existing sites: `composer require "hostkurd/flocms-core:^2.4" -W`, then copy
the `admin_login` change from `controllers/UsersController.php` (the
`Auth::hasAdminAccess()` lines) and `Auth::roles()` in
`controllers/PagesController.php`. Without the copy everything still works:
with `auth.user_loader` (the skeleton sets it) FloCMS corrects admin access on
the next admin request.

## 1.8.6 - 2026-10-18

### Changed
- The Admin role (2) gets `activity.view`, so it can open **Site → Activity**,
  the activity log of flocms-admin 0.19+. Super Admin (`*`) already had it;
  Editors keep only the History of the records they can edit.

Existing sites: add `'activity.view'` to role 2 in `config/config.php` if your
Admins should see the activity log. No other change.

## 1.8.5 - 2026-10-17

### Added
- Migration `2026_10_17_000000_create_settings_table.php`: the `settings` table
  (`param`, `value`, `lang`, primary key `param` + `lang`) that flocms-core reads
  with `Config::getSetting()` (`offline_mode`, written by `php flo down`/`up`),
  that flocms-api's maintenance middleware checks, and that flocms-admin 0.14+
  uses for its settings pages (one row per key, empty `lang`, JSON value).
  New sites get it with `php flo migrate`; `CREATE TABLE IF NOT EXISTS` leaves an
  existing table alone.

Existing sites: copy the migration into `database/migrations/` and run
`php flo migrate` if the site has no `settings` table yet. Sites using
flocms-admin 0.14 already have it from `php flo admin:install` (the admin skips
its own migration when the site has this one). No other change.

## 1.8.4 - 2026-10-16

### Changed
- Framework packages are required with caret ranges from their tested minimum
  instead of exact versions: core `^2.3.2`, cli `^2.0.1`, uploader `^1.2.1`,
  captcha `^1.1.0`, api `^1.2.0`. `composer.lock` still fixes the exact versions,
  so new projects install the tested set and existing sites change only on
  `composer update`. Adding flocms-admin 0.10+ no longer fails with
  "conflicts with your root composer.json require (1.2.0)".
- The lock installs flocms-uploader 1.2.1, whose Imagick driver no longer pads
  resized images to a square.

Existing sites: edit the versions in `composer.json` to the ranges above (or run
`composer require "hostkurd/flocms-uploader:^1.2.1" -W`). No application-file changes.

## 1.8.3 - 2026-10-14

### Added
- Optional `auth.login_view` presentation callable in the admin login view. A nonempty string replaces the login markup; missing, disabled or empty renderers retain the standalone form.
- Login presentation regression tests for standalone fallback, CSRF rejection, failed/successful credentials, signed-in redirects and throttling on configured MySQL/MariaDB.

### Changed
- Admin layout language and direction follow the active language, including with JavaScript disabled.
- Authentication controllers, Core pins and standalone dependencies remain unchanged. No Admin or Reactive dependency is introduced.

Existing applications: Composer does not replace application views. Copy the hook at the top of `views/users/admin_login.html` and the dynamic `lang`/`dir` attributes from `templates/default/layouts/admin.html` if enabling the optional Admin 0.5.0 theme. See README for the renderer contract.

## 1.8.2 - 2026-10-13

### Changed
- Requires Core 2.3.2. Page and package controllers can return an explicit
  `FloCMS\Core\Http\Response` without template or layout rendering. HEAD
  responses omit the body, and malformed CSRF tokens return 419.
- Suggests `hostkurd/flocms-reactive` for optional interactive components.
  The standalone skeleton does not require or load Reactive.

Existing sites: run `composer require hostkurd/flocms-core:2.3.2 -W`.
No application-file changes are required for this upgrade from 1.8.1.

## 1.8.1 - Unreleased

### Fixed
- Requires Core 2.3.1 so authenticated users without admin access can log out
  and sign in with another account. Guests, other admin actions and POST
  CSRF protection remain gated.
- `admin.home` strings are always paths, even when they match a PHP function
  such as `mail`, `date` or `time`. Previously the function was called with
  no arguments. Use a closure or another non-string callable for a dynamic
  destination.
- Admin-home destinations that lead back to this dashboard or to an admin
  login action are ignored, preventing redirect loops. The skeleton's
  dashboard renders instead, including for hyphenated aliases of `pages`.

These changes are application files: copy the updated `adminHome()` method
from `controllers/PagesController.php` into an existing site. Also update the
Core dependency to 2.3.1 and run Composer after that patch release is published.

## 1.8.0 - 2026-10-12

The skeleton keeps signing in and the protected `/admin` area; the admin
screens come from a package, as with Laravel and Filament or Symfony and
EasyAdmin. flocms-core is unchanged (still 2.3.0).

### Added
- `/admin` follows the `admin.home` setting: a path under `/admin`
  (`'dashboard'`) or a callable returning one. `hostkurd/flocms-admin` 0.2+
  sets it, so after logging in you land on its dashboard. Set it in
  `config/config.php` to choose another page, or to `null` to keep the
  skeleton's page. Only lower-case paths under `/admin` are accepted.
- `composer.json` suggests `hostkurd/flocms-admin`.

### Removed
- The half-built user pages in `controllers/UsersController.php`:
  `admin_index` (a placeholder page), `admin_add`, `admin_edit` and
  `admin_profile` (their views did not exist), `admin_delete` (deleted on a
  GET request), `admin_verify` and `admin_suspend`, and the public `verify`
  page (its view did not exist; only `admin_add` sent verification emails).
  `views/users/admin_index.html` and the unused `Standalone_Pages` setting are
  removed too. Manage users with flocms-admin (`/admin/accounts`), the
  `php flo user:*` commands, or your own `admin_` actions.
- The Users link on the `/admin` page.

### Unchanged
- Login and logout, login throttling, session refresh, `UsersModel`, the users
  migration, roles and permissions, `php flo user:*`, and the API's
  `/api/v1/users`.

Existing sites: nothing changes unless you copy these files. To make `/admin`
open an admin package, copy `admin_index()` and `adminHome()` from
`controllers/PagesController.php`.

## 1.7.8 - 2026-10-12

### Fixed
- **The admin pages are styled again.** `templates/default/layouts/admin.html`
  linked its stylesheets, `config.js` and favicon under `/assets/`, which the
  skeleton does not have, so the login page and the dashboard showed plain
  HTML and the browser logged 404s. The layout now loads them with
  `template_asset()`, like `layouts/default.html`, from
  `public/themes/<template>/` (falling back to `default`).

Existing sites: copy the five changed lines of
`templates/default/layouts/admin.html` (favicon, `style.css`, `flo-core.css`,
`config.js`, `style.rtl.css`), or replace `<?=SITE_URI;?>/assets/...` and
`<?=jsPath;?>/config.js` with `<?= template_asset('...') ?>` yourself.

## 1.7.7 - 2026-10-12

### Added
- Requires `hostkurd/flocms-core` 2.3.0: packages can supply page
  controllers with `App::resolveControllersUsing()`. Your own controllers in
  `controllers/` always take precedence, and sites that register no
  resolvers behave exactly as before.

Existing sites:

```bash
composer require hostkurd/flocms-core:2.3.0
```

## 1.7.6 - 2026-10-12

### Fixed
- Requires `hostkurd/flocms-core` 2.2.3: the `max` validation rule no longer
  fails on a missing or empty value. An empty required field showed
  "must not exceed N characters" next to "is Required". `required` still
  reports the empty field, and `min` is unchanged.

Existing sites:

```bash
composer require hostkurd/flocms-core:2.2.3
```

## 1.7.5 - 2026-10-11

### Fixed
- Requires `hostkurd/flocms-core` 2.2.2:
  - **Multi-word page controllers work.** `/blog-post` runs
    `BlogPostController` and renders `views/blog-post/`, which is what
    `php flo make:route BlogPost` generates. In 2.2.1 these URLs answered 404.
  - **Template directives accept parentheses.** `@if(count($posts) > 0)`,
    `@foreach(array_slice($posts, 0, 3) as $post)` and
    `@lang('key', ['name' => strtoupper($name)])` compile correctly, and
    `@forelse ... @empty ... @endforelse` works. Existing templates compile to
    the same PHP as before; `views/cache/` is rebuilt automatically.

### Security
- Requires `hostkurd/flocms-captcha` 1.1.0, now pinned exactly like the other
  framework packages: captcha codes are single-use, so `verify()` removes the
  code on every check, right or wrong, and a code can no longer be guessed by
  retrying or reused by submitting the form again. Set `'single_use' => false`
  in the captcha config only if you must check the same code twice.

Existing sites:

```bash
composer require hostkurd/flocms-core:2.2.2 hostkurd/flocms-captcha:1.1.0
```

## 1.7.4 - 2026-10-11

### Fixed
- `APP_VERSION` in `.env.example` and the version pins in
  `tests/Unit/DependenciesTest.php` now match the release (1.7.3 shipped
  with `APP_VERSION=1.7.2` and a test still expecting flocms-core 2.2.0).
  
### Security
- Requires `hostkurd/flocms-core` 2.2.1: only public methods declared on your
  own controllers can be reached from a URL. Existing sites: run
  `composer require hostkurd/flocms-core:2.2.1`.
  
## 1.7.3 - 2026-10-11

### Fixed
- **Admin home.** Logging in (and logging out) redirects to `/admin/`, which
  answered 404 because `PagesController` had no `admin_index()`. It is now a
  small dashboard (`views/pages/admin_index.html`) with the signed-in user, their
  role, a link to user management for roles with `users.manage`, and log out.
  To fix an existing site, copy `admin_index()` from
  `controllers/PagesController.php`, the view, and the `roles` block from
  `config/config.php`.

### Changed
- Role display names live in `config/config.php` (`Config::set('roles', ...)`),
  next to the permissions. The dashboard and `UsersController` both read them;
  `UsersController` keeps its built-in names when the setting is missing.

## 1.7.2 - 2026-10-10

### Fixed
- Requires `hostkurd/flocms-cli` 2.0.1, which fixes the hidden password
  prompt on Windows (`php flo user:create`, `user:password`).

## 1.7.1 - 2026-10-10

### Changed
- **Welcome page.** New minimal design with the animated FloCMS logo
  (`templates/default/partials/logo.html`). Its styles are inline, so it
  still renders when a wrong `APP_URL` breaks theme asset links. The
  database card is now marked optional.

### Added
- **One-click APP_URL fix.** On an `APP_URL` mismatch the welcome page offers
  a button that writes the detected URL to `.env`. It is shown only when
  `APP_ENV=local`, the request comes from the loopback address and `.env` is
  writable, and it is protected by CSRF.
- Logo files `public/themes/default/img/logo.svg` and `logo-text.svg`.

## 1.7.0 - 2026-10-10

Requires `hostkurd/flocms-cli` 2.0.0 and `hostkurd/flocms-api` 1.2.0 (pinned
to exact versions, like core and uploader).

### Changed
- **Root launcher.** The `flo` file in the project root is a three-line
  launcher for the flocms-cli kernel. Every command now comes from
  `vendor/`, so `composer update` delivers new commands and fixes; sites no
  longer get "command not found" because of an old `flo` file:
  - `key:generate` moved into flocms-cli, and `support/KeyGenerator.php` is
    gone.
  - The `api:*` commands are registered by flocms-api itself, so nothing is
    forwarded.
- **Commands.** `php flo` lists every command, and `php flo help <command>`
  explains one. Unknown commands suggest the right name and exit with 1.
- **Application commands.** Classes in `commands/` (`App\Commands`, now
  autoloaded) are commands. `commands/LoginUnlockCommand.php` adds
  `php flo login:unlock <email|ip>` for locked-out admins, using
  `LoginThrottle::unlockIp()`, which is new.
- **Migrations and seeders.** `database/migrations` and `database/seeders`
  are used by `php flo make:migration`, `migrate` and `db:seed`. The users
  table and the API tables are migrations. On a new site, run
  `php flo migrate`, then `php flo user:create` for the first admin.
- **Scheduler.** `config/schedule.php` holds the scheduled tasks for
  `php flo schedule:run`. The built-in tasks are `api:gc`, log rotation and
  stale chunked uploads.
- **Maintenance mode.** `php flo down [--message=] [--retry=] [--allow=IP]`
  writes `storage/framework/down.json`. `public/index.php` then answers 503
  (`templates/default/errors/503.html`, with `Retry-After`), and so does
  `public/api.php` (JSON). Allowed IPs keep access, `/api/v1/health` stays
  up, and `php flo up` brings the site back (`includes/maintenance.php`).
- `storage/framework/` holds maintenance and scheduler state (git-ignored).

### Fixed
- `composer create-project` works on PHP 8.1 again. `composer.lock` is
  resolved for PHP 8.1 (`config.platform.php`), so it locks PHPUnit 10.5
  instead of 11, which needs PHP 8.2.

### Quality
- GitHub Actions: PHP 8.1–8.4 on Linux (all tests) and Windows (unit
  tests), plus MySQL 8.0 and MariaDB 10.11.

### Upgrading from 1.6
1. **Composer.** In `composer.json`, require `"hostkurd/flocms-cli": "2.0.0"`
   and `"hostkurd/flocms-api": "1.2.0"`, add `"App\\Commands\\": "commands/"`
   to `autoload.psr-4`, and run `composer update`.
2. **Launcher.** Replace the root `flo` file with this one, a one-time
   step:
   ```php
   #!/usr/bin/env php
   <?php
   require __DIR__ . '/vendor/autoload.php';
   exit(FloCMS\CLI\Kernel::handle(__DIR__, $argv));
   ```
   Then delete `support/KeyGenerator.php`.
3. **Maintenance mode.** Copy `includes/maintenance.php` and
   `templates/default/errors/503.html`, and the maintenance lines of
   `public/index.php` and `public/api.php`.
4. **Optional.**
   - Copy `commands/LoginUnlockCommand.php` and the `unlockIp()` method of
     `support/LoginThrottle.php`.
   - Copy `config/schedule.php` and add the cron job:
     `* * * * * php /path/to/site/flo schedule:run`.
5. **Check.** Run `php flo doctor` and `php flo migrate` (the API tables are
   only recorded when they already exist).

Sites created before 1.6 follow the same steps. Until the `flo` file is
replaced, `composer update` gives them flocms-cli 1.0.5. Its unknown-command
message points to these steps.

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
