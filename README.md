# Course Discovery

## Overview

Course Discovery is a WordPress plugin (with a minimal companion theme) that
provides course search, filtering and discovery. This repository contains a
Dockerised local development environment plus the plugin and theme source.

## Setup

1. Clone this repository.
2. Copy any required environment files (see [Environment Requirements](#environment-requirements)).
3. Start the stack:

   ```bash
   docker compose up -d
   ```

4. Visit `http://localhost:8080` and complete the WordPress install wizard.
5. Activate the **Course Discovery** plugin and the **Course Discovery Theme**
   from the WordPress admin.

## Environment Requirements

- Docker and Docker Compose
- PHP 8.2+ (matches the `wordpress:6.7-php8.2-apache` image, and required by
  the plugin's `composer.json`)
- Composer (for installing plugin dependencies)
- WordPress 6.7
- MySQL 8.0

## Database Setup

The `db` service provisions a MySQL 8.0 database automatically on first run,
using the credentials below (development only — do not reuse in production):

| Setting  | Value       |
|----------|-------------|
| Host     | `db` (or `localhost:3306` from the host) |
| Database | `wordpress` |
| User     | `wordpress` |
| Password | `wordpress` |
| Root pw  | `root`      |

phpMyAdmin is available at `http://localhost:8081` for inspecting the
database directly.

### Importing a dump

Drop a `.sql` (or `.sql.gz`) file into `db/` and it will be imported
automatically the first time the `db` container initialises an empty data
volume (via MySQL's `/docker-entrypoint-initdb.d` mechanism). This only runs
once per fresh volume — if `db_data` already exists, remove it first
(`docker compose down -v`) to trigger a re-import.

To import into a database that's already running, instead run:

```bash
docker compose exec -T db mysql -uwordpress -pwordpress wordpress < db/dump.sql
```

Dump files in `db/` are gitignored and stay local.

## Dev Commands

Run these from `wp-content/plugins/course-discovery/`:

```bash
composer install       # install plugin dependencies
composer test          # run the PHPUnit test suite
```

Docker stack commands, run from the repository root:

```bash
docker compose up -d       # start WordPress, MySQL, phpMyAdmin
docker compose down        # stop the stack
docker compose logs -f     # tail logs
```

## Testing

The plugin uses PHPUnit for automated tests. Tests live in
`wp-content/plugins/course-discovery/tests/` and are run via:

```bash
composer test
```

## Architecture

The plugin follows a namespaced, PSR-4 structure under
`OxfordInternational\CourseDiscovery`:

- `Plugin.php` — bootstraps the plugin and wires up WordPress hooks.
- `Domain/Model` — domain entities.
- `Domain/ValueObject` — immutable value objects used by the domain model.
- `Query` — read-side query objects for retrieving courses.
- `Filter` — filtering logic applied to course queries.
- `PostType` — custom post type registrations.
- `Taxonomy` — custom taxonomy registrations.
- `Migration` — database/schema migration runners.
- `REST` — REST API controllers/routes.

The theme (`course-discovery-theme`) is intentionally minimal and exists to
provide a rendering surface for the plugin during development.

## Assumptions

- Local development only; the Docker Compose file and credentials in this
  repo are not intended for production use.
- The plugin targets PHP 8.2+ and WordPress 6.7+; no support for older
  versions is assumed.
- Domain logic lives in the plugin, not the theme; the theme is a thin
  presentation layer.
