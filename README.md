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

## Permissions (flocms-core 2.1+)

Admin panel access is controlled by roles and permissions. `config/config.php` maps each role to its permissions:

| Role | Name        | Permissions                                              |
|------|-------------|----------------------------------------------------------|
| 0    | User        | none                                                     |
| 1    | Editor      | `content.*`                                              |
| 2    | Admin       | `content.*`, `users.manage`, `users.assign_role`, `settings.*` |
| 3    | Super Admin | `*`                                                      |

Controllers declare the permission each action needs; actions not listed fall back to `'*'`, and `null` means no check:

```php
protected array $actionPermissions = [
    '*'           => 'users.manage',
    'admin_login' => null,
];
```

Inside an action, use `Auth::can('settings.edit')` or `Auth::authorize('settings.edit')` (throws 403).
User management only lets you assign roles up to your own, and only edit, delete or suspend users of a lower role (Super Admins can manage everyone).

### Upgrading an existing site

1. Require `hostkurd/flocms-core` `2.1.0`.
2. Copy the `permissions` block from `config/config.php`, adjusting it to your roles.
3. Add `$actionPermissions` to your admin controllers, and copy the `Auth` checks from `controllers/UsersController.php` (`admin_add`, `admin_edit`, `admin_delete`, `admin_suspend`).
4. Copy `templates/default/errors/403.html` and the `page.forbidden` strings from `lang/*.php`.

Until step 2 is done, the site behaves exactly as before (any role with admin access can do everything).
Role changes take effect on the user's next login.

## Upgrading to 1.5 (flocms-core 2.2)

FloCMS 1.5 requires `hostkurd/flocms-core` `2.2.0`. Versions are pinned exactly,
so each site upgrades when you choose. Copy the changes below into an existing
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
Require `"hostkurd/flocms-uploader": "1.2.0"`. Video uploads
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
