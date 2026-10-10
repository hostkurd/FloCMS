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

## API (recommended: /api/v1)

Build APIs in `api/routes.php`; `public/api.php` serves them under `/api`
using `hostkurd/flocms-api`:

```php
return static function (Router $router): void {
    $router->get('/v1/health', static fn (): array => ['status' => 'ok']);
    $router->get('/v1/listings/{id}', [ListingsApiController::class, 'show']);
};
```

`GET /api/v1/health` returns `{"success":true,"data":{"status":"ok"}}`. Requests get JSON
errors, security headers, CORS for the origins in `API_CORS_ORIGINS`, and are
rate limited per IP (`API_RATE_LIMIT` per minute). The API path has no session
and no CSRF check: protect private routes with
`FloCMS\Api\Middleware\AuthenticateMiddleware`.

The old `/api/<controller>/<action>` route (methods prefixed `api_`) still
works, but it skips CSRF checks and has no authentication. It is deprecated;
move endpoints to `api/routes.php`.

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
   (`base64:YFHTnSHarB6...`), copy the new `flo` file and `support/KeyGenerator.php`,
   then run `php flo key:generate --force`. Nothing uses `APP_KEY` yet, so
   replacing it is safe.
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
