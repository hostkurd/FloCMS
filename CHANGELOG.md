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

### Added
- PHPUnit test suite (`composer test`). Feature tests run the app under PHP's
  built-in server with a fresh `.env` made from `.env.example`.

## 1.4.0

- Restrict user management to administrators and cap role assignment at the
  current user's own role (requires flocms-core 2.1.0).
