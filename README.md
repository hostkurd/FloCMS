<p align="center">
<img src="https://github.com/user-attachments/assets/a3a90a19-a7d6-4709-99ab-0e60308ff6f5">
</p>
<p align="center">
<a href="https://packagist.org/packages/hostkurd/flocms"><img src="https://img.shields.io/packagist/dt/hostkurd/flocms" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/hostkurd/flocms"><img src="https://img.shields.io/packagist/v/hostkurd/flocms" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/hostkurd/flocms"><img src="https://img.shields.io/packagist/l/hostkurd/flocms" alt="License"></a>
</p>

## About FloCMS
FloCMS is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. FLoCMS takes the pain out of development by easing common tasks used in many web projects, such as:

**Table of Contents**
- [Features](#features)
- [Installation](#installation)
- [Command line (php flo)](#command-line-php-flo)
- [API](#api-recommended-apiv1)

## Features

- **Easy Routing**: FloCMS Offers easy and fast routing engine.
- **Caching**: Multi backends for session and cache storage.
- **Template Engine**: With Flo Template Engine you can use PHP Codes easy inside templates.
- **Multilingual**: Powerfull Multilingual skeleton gives you ability to create a multilingual website.

## Installation

To Create a new project, install it via Composer:

```bash
composer create-project hostkurd/flocms
```

### Package versions (1.8.4)

`composer.json` requires the framework packages with caret ranges from a tested
minimum (`"hostkurd/flocms-core": "^2.6.0"`, `"hostkurd/flocms-uploader": "^1.2.1"`, …),
and `composer.lock` records the exact versions. A new project installs exactly the
locked set; an existing site changes only when you run `composer update`, so each
site still upgrades when you choose. The ranges let a package you add, such as
flocms-admin, bring a newer release it needs.

Sites created before 1.8.4 have exact versions such as `"1.2.0"`. Change them to
ranges, or name both packages when adding the admin:

```bash
composer require "hostkurd/flocms-admin:^0.31" "hostkurd/flocms-uploader:^1.2.1" -W
```

### Upgrading to Core 2.6.0

```bash
composer require "hostkurd/flocms-core:^2.6" -W
```

Core 2.6 lets a package answer addresses no controller answers (`App::fallbackUsing()`),
which flocms-admin 0.30's Pages use. Nothing changes until something registers one.

### Upgrading to Core 2.4.0

```bash
composer require "hostkurd/flocms-core:^2.4" -W
```

Core 2.4 adds a roles loader (`Auth::loadRolesUsing()`) and role levels, used
by flocms-admin 0.21's Roles page. Nothing changes until a loader is
registered. See the 1.8.7 notes in CHANGELOG.md for the login change.

### Upgrading to Core 2.3.2

To update an existing project:

```bash
composer require "hostkurd/flocms-core:^2.3.2" -W
```

Page controllers can now return `FloCMS\Core\Http\Response` directly for
HTML fragments, JSON or other explicit responses. Existing template and
layout rendering continues to work. No application-file changes are needed
when upgrading from the 1.8.1 skeleton.

[flocms-reactive](https://github.com/hostkurd/flocms-reactive) is optional.
FloCMS runs independently of it. To add reactive components:

```bash
composer require hostkurd/flocms-reactive
```

Follow the package's README to register components and include its assets.

## Command line (php flo)

`php flo` runs [flocms-cli](https://github.com/hostkurd/flocms-cli). The `flo`
file in the project root only boots the CLI from `vendor/`, so
`composer update` brings new commands and fixes.

```bash
php flo                                   # every command, grouped
php flo help make:controller              # usage, arguments, options, examples
php flo doctor                            # check the installation (PHP, extensions, permissions, .env, database)
php flo make:route Blog                   # controller + model + view
php flo make:migration create_posts_table && php flo migrate
php flo user:create --role=super-admin    # create the first admin
php flo down --allow=203.0.113.7 && php flo up
php flo route:list                        # web and /api/v1 routes with permissions
```

**Commands by area:**

| Area | Commands |
|---|---|
| Generators | `make:controller`, `make:model`, `make:view`, `make:route`, `make:api-controller`, `make:resource`, `make:middleware`, `make:request`, `make:migration`, `make:seeder`, `make:command`, `make:test`, `make:module` (all with `--dry-run` and `--force`); `delete:*` |
| Database | `migrate`, `migrate:status`, `migrate:rollback`, `migrate:fresh`, `db:seed`, `db:show`, `db:check` |
| Application | `about`, `doctor`, `key:generate`, `env:check`, `serve`, `down` / `up` |
| Cache | `cache:clear`, `view:clear`, `optimize`, `optimize:clear` |
| Inspection | `route:list`, `permission:list` |
| Users | `user:create`, `user:password`, `user:role`, `user:suspend`, `user:unsuspend`, `user:list`, `login:unlock` |
| Translations | `lang:missing`, `lang:add` |
| Scheduler | `schedule:run`, `schedule:list` |
| Logs | `log:tail`, `log:clear`, `log:rotate`, `storage:check` |
| API | `api:*`, from flocms-api |
| Other | `completion` |

**Exit codes:** 0 success, 1 failure, 2 invalid usage. Errors go to STDERR,
so scripts and cron jobs can rely on them.

**Your own commands.** Classes in `commands/` (namespace `App\Commands`) are
commands too. `commands/LoginUnlockCommand.php` (`php flo login:unlock
<email|ip>`) is an example; `php flo make:command` creates a new one.

**Scheduler.** One cron job runs the tasks in `config/schedule.php`. In cPanel,
use Cron Jobs → Once Per Minute:

```
* * * * * php /home/USER/site/flo schedule:run >> /dev/null 2>&1
```

**Maintenance mode.** `php flo down` makes the site and the API answer 503
until `php flo up`. Pass `--allow=YOUR.IP` to keep access yourself.

**The settings table.** `php flo migrate` creates `settings` (`param`, `value`,
`lang`), the table core reads with `Config::getSetting()`: `php flo down`/`up`
also switch its `offline_mode` rows, and flocms-admin keeps its settings pages
there (one row per key, empty `lang`). Sites that already have the table keep it.

See the flocms-cli README for every option.

## API (recommended: /api/v1)

`public/api.php` serves the routes in `api/routes.php` under `/api` with
[hostkurd/flocms-api](https://github.com/hostkurd/flocms-api). Controllers
and resources live in `api/` (namespace `App\Api\`):

```php
return static function (Router $router): void {
    $router->version('v1', static function (Router $router): void {
        $router->get('/health', new HealthController())->name('health');

        $router->post('/contact', [ContactController::class, 'store'])
            ->middleware('throttle:forms', 'idempotent');

        $router->group('/users', static function (Router $router): void {
            $router->get('', [UsersController::class, 'index'])->name('index');
            $router->get('/{id:\d+}', [UsersController::class, 'show'])->name('show');
        }, ['auth:users.manage'], namePrefix: 'users.');
    });
};
```

Every response is JSON with security headers. CORS is enabled for the origins
in `API_CORS_ORIGINS` (`https://*.example.com` patterns allowed), the locale
comes from `?lang=` or `Accept-Language`, and requests are rate limited per
client (`API_RATE_LIMIT` per minute). The API path has no session and no CSRF
check by default. Route middleware:

| Middleware | Effect |
|---|---|
| `throttle:public`, `throttle:forms`, `throttle:auth` | named rate limits (60/min, 5/min, 10 per 15 min) |
| `auth`, `auth:users.manage` | a personal access token (`Authorization: Bearer ...`) or the admin session from same-origin JavaScript (with `X-CSRF-TOKEN`), optionally with permissions from `config/config.php` |
| `idempotent` | retries with the same `Idempotency-Key` header get the first response |

The examples show validation (`ContactController`), pagination with filters
and sorting, and a resource that keeps the password and token columns out of
responses (`UsersController`, `UserResource`).

Commands (`php flo list api`):

```bash
php flo api:install-schema                     # tables for tokens, API keys, idempotency
php flo api:token:create 1 "Mobile app"        # prints the token once
php flo api:routes                             # list routes
php flo api:docs --out=public/openapi.json     # OpenAPI 3.1 (also at /api/v1/openapi.json when APP_DEBUG=true)
php flo api:gc                                 # clean up rate-limit and idempotency files (cron)
```

See the flocms-api README for validation rules, resources, caching, uploads
and testing your API with `TestClient`.

The old `/api/<controller>/<action>` route (methods prefixed `api_`) skips
CSRF checks and has no authentication. It is off unless `LEGACY_API=true` is
set in `.env`; move endpoints to `api/routes.php`.

## The admin area

The skeleton gives you signing in and a protected area; the admin screens come from a package.

- **Included:** the login and logout pages (`/admin/users/login`, `/admin/users/logout`), the `users` table, roles and permissions in `config/config.php`, the `php flo user:*` commands, and a simple `/admin` page. Any controller method named `admin_...` is served under `/admin/...` and requires a logged-in user with admin access.
- **The admin panel:** `composer require hostkurd/flocms-admin` and `php flo admin:install` add a dashboard (which `/admin` then opens), users management, a profile page and pages for your own tables. See [flocms-admin](https://github.com/hostkurd/flocms-admin).
- **Your own admin instead:** add `admin_` actions to your controllers (`php flo make:controller Reports --admin`), protect them with `$actionPermissions`, and use `UsersModel` and `Auth` for users. The documentation's "Build your own admin" guide shows a complete users page.

`/admin` follows the `admin.home` setting when one is set: a path under `/admin` (`'dashboard'`), or a non-string callable returning one. flocms-admin sets it; set it in `config/config.php` to choose another page, or to `null` to keep the skeleton's page. Strings are always paths, including names such as `'mail'`. Use a closure for a dynamic destination. Paths to the skeleton's dashboard or an admin login action are ignored to prevent redirect loops.

## Permissions (flocms-core 2.1+)

Admin panel access is controlled by roles and permissions. `config/config.php` maps each role to its permissions:

| Role | Name        | Permissions                                              |
|------|-------------|----------------------------------------------------------|
| 0    | User        | none                                                     |
| 1    | Editor      | `content.*`                                              |
| 2    | Admin       | `content.*`, `users.manage`, `users.assign_role`, `settings.*`, `activity.view` |
| 3    | Super Admin | `*`                                                      |

`activity.view` opens the admin panel's **Site → Activity** page (flocms-admin 0.19+); without it, editors still see the History of the records they can edit.

Controllers declare the permission each action needs; actions not listed fall back to `'*'`, and `null` means no check:

```php
protected array $actionPermissions = [
    '*'           => 'users.manage',
    'admin_login' => null,
];
```

Inside an action, use `Auth::can('settings.edit')` or `Auth::authorize('settings.edit')` (throws 403).
For user management, `Auth::canAssignRole($role)` only allows roles up to your own, and `Auth::canManageUser($role)` only lower roles (Super Admins, with `users.manage_any`, can manage everyone).

### Upgrading an existing site

1. Require `hostkurd/flocms-core` `2.1.0`.
2. Copy the `permissions` block from `config/config.php`, adjusting it to your roles.
3. Add `$actionPermissions` to your admin controllers, and use `Auth::canAssignRole()` / `Auth::canManageUser()` where users are edited.
4. Copy `templates/default/errors/403.html` and the `page.forbidden` strings from `lang/*.php`.

Until step 2 is done, the site behaves exactly as before (any role with admin access can do everything).
Role changes take effect on the user's next login.

## Upgrading to 1.5 (flocms-core 2.2)

FloCMS 1.5 requires `hostkurd/flocms-core` `2.2.0` (1.5 pinned versions exactly;
since 1.8.4 the skeleton uses ranges and `composer.lock`, see Package versions). Copy the changes below into an existing
site as needed; see `CHANGELOG.md` for the details.

### Fresh install without a database
1. In `composer.json`, require `"hostkurd/flocms-core": "2.2.0"` and run `composer update hostkurd/flocms-core`.
2. Add `Config::set('db.port', Env::get('DB_PORT', 3306));` to `config/config.php`.
3. Copy `templates/default/errors/nodbserver.html` and `dberror.html`. They receive
   `$message`, `$errorCode` and, in debug mode only, `$detail`.
4. Optional: copy the lazy `model()` helper from `controllers/PagesController.php`
   and the database card from `views/pages/index.html`.

Models now connect on first query, so controllers that create a model in their
constructor no longer fail when the database is down until they actually query it.

### Session freshness and login throttling
1. Add `"FloCMS\\Support\\": "support/"` to `autoload.psr-4` in `composer.json`, copy
   `support/LoginThrottle.php`, and run `composer dump-autoload`.
2. In `controllers/UsersController.php` (`admin_login`), copy the `LoginThrottle` block,
   the `clientIp()` method, `$throttle->clear($email)` and `Session::set('user_id', ...)`.
3. Copy the `auth.user_loader`, `login_throttle` and `trusted_proxies` settings from
   `config/config.php`, and add `TRUSTED_PROXIES=` to `.env`.
4. Copy the `auth.*` strings from `lang/en.php`.
5. Make sure `storage/cache` is writable and ignored by git (see `.gitignore`).

Users who are logged in when you deploy this are logged out once (their
session has no `user_id` yet).

### Template cache
Templates are compiled to `views/cache/` automatically. Make sure the web
server can write to it, keep it out of git (copy `views/cache/.gitignore`), and
delete old files such as `views/cache/pages_index.php`. Set
`Config::set('view.cache_path', ...)` to use another directory.

### API, APP_KEY and CSRF
1. Copy `public/api.php` and `api/routes.php`, and add the `api/v1` rule from
   `public/.htaccess` above the `index.php` rule. Add `API_CORS_ORIGINS=` and
   `API_RATE_LIMIT=60` to `.env`.
2. If your `.env` still has the `APP_KEY` that older skeletons shipped
   (`base64:YFHTnSHarB6...`), run `php flo key:generate --force` (with the
   1.7 launcher, see the CHANGELOG). Nothing uses `APP_KEY` yet, so replacing
   it is safe.
3. Add `<meta name="csrf-token" ...>` and the `js/csrf.js` script from
   `templates/default/layouts/admin.html` to your admin layout, copy
   `public/themes/default/js/csrf.js`, and put `@csrf` in every POST form.

### Uploads (flocms-uploader 1.2)
Require `"hostkurd/flocms-uploader": "^1.2.1"` (1.2.1 fixes Imagick resizing). Video uploads
(`Uploader::video()`) and chunked uploads (`->chunked()`) are new and opt-in;
see the uploader README for the `video` and `chunks` config sections. Keep the
chunk directory outside `public/` (e.g. `storage/uploads/.chunks`).

## Running the tests

```bash
composer install
composer test
```

Tests that need MySQL/MariaDB are skipped unless `FLO_TEST_MYSQL_HOST` is set
(also `FLO_TEST_MYSQL_PORT`, `_USER`, `_PASS`, `_NAME`; default database
`flocms_test`).

# Security Vulnerabilities
If you discover a security vulnerability within FLoCMS, please send an e-mail to Dev Team via [dev@flocms.com](mailto:dev@flocms.com). All security vulnerabilities will be promptly addressed.

## License
The FloCMS framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


### Optional admin login presentation (1.8.3)

The skeleton owns authentication, CSRF, throttling and sessions. `views/users/admin_login.html` optionally calls `Config::get('auth.login_view')` with no arguments. A callable may return a trusted, nonempty HTML string for presentation only; null, false, missing or empty output leaves the standalone login form intact. A custom renderer must include the same credential names (`email`, `password`) and `Csrf::field()`, escape displayed values and never echo passwords.

Admin 0.5.0 registers this renderer automatically when installed and enables its theme through `'login_theme' => true` in `config/admin.php`. Without Admin the skeleton keeps its existing design and has no dependency on Admin or Reactive. Set `auth.login_view` to false to explicitly disable package rendering, or supply your own callable in application configuration.

Existing sites must copy the optional hook from the top of this version's `views/users/admin_login.html`; updating Composer does not replace project files. Retain your existing markup below the hook. Also copy the active-language `lang` and `dir` expressions from `templates/default/layouts/admin.html` for correct metadata without JavaScript. Clear compiled views after editing. No authentication-controller change or database migration is required.
