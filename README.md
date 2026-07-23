# Course Discovery

## Overview

Course Discovery is a WordPress plugin (with a minimal companion theme) that
provides course search, filtering and discovery. This repository contains a
Dockerised local development environment plus the plugin and theme source.

This is a pre-interview technical exercise for Oxford International. The
full brief is reproduced below for reference, followed by a running log of
progress.

## Task Brief

> Reproduced verbatim from the exercise instructions.

### Overview

This assessment evaluates the ability to design and implement a scalable,
maintainable, and extensible Course Discovery system using WordPress, PHP,
SQL, JavaScript, HTML, and CSS.

The primary focus is software architecture, domain modelling, extensibility,
and filter composition, rather than delivering a feature-complete or highly
polished user interface.

Candidates are expected to demonstrate sound engineering practices,
including separation of concerns, strong typing, modularity, testability,
and consideration for future scalability.

### Submission Requirements

Submit:

- A publicly accessible deployment of the application.
- A public Git repository.
- A README containing: setup instructions, environment requirements,
  database setup, development commands, testing instructions, architectural
  decisions, and assumptions made during implementation.

Docker Compose may be used to configure the environment with well-defined
services.

### Context

The team is building an EdTech platform where students can discover and
enroll in courses. The task is to implement a Course Discovery system in
WordPress, without using external plugins except Advanced Custom Fields
(ACF).

The system must be designed with extensibility and future-proofing in mind,
so the platform can be easily extended as requirements evolve. The
architecture should include appropriate levels of abstraction and apply
relevant design patterns so that filters can be reused and manipulated
consistently across different parts of the system.

### Technical Expectations

**Architecture** — Define interfaces and abstractions to allow third-party
code to integrate via hooks and filters to modify behaviour, with
composition favoured over inheritance wherever applicable.

**Domain model** — Capture the business logic; avoid passing primitives
where richer domain concepts apply. Value objects and abstractions over
`WP_Query` are highly encouraged to model domain-specific problems.

**Type safety** — Prioritise strong typing; all public APIs must be typed.
Collections and generics should be documented wherever necessary.

**Extensibility** — New filters must be introducible without modifying
existing filter implementations, with behaviour extensible through a
hook/event pipeline. Examples: registering additional filters, altering
available filter options, modifying filter queries, transforming search
criteria, customising result ordering.

### Deliverables

- A frontend interface that allows users to search for and discover
  relevant courses.
- A WordPress admin dashboard for managing and administering courses.

### Functional Requirements

**Data requirements** — A Course can have:

- Name, Short description, Long description
- Price: a singular numeric value (note: can be extended to support a range
  or multiple price points)
- Instructors: link to one or more posts in the Instructor post type
- Providers: link to one or more posts in the Provider post type
- Locations: derived field from Provider
- Start dates: list of dates entered in `{month}-{year}` format
- Categories: list of one or more hierarchical category terms

**Frontend** — A responsive UI where users can filter courses by:

- Plain text search: matched against name, short description and long
  description
- Providers: multi-select
- Locations: multi-select, must be a dropdown combobox
- Start dates: multi-select, must be a dropdown combobox, options listed in
  chronological `{month}-{year}` order
- Categories: multi-select

**Accessibility** — Fully keyboard-operable (no pointing device required);
semantic markup and `aria-label`s wherever necessary.

**Backend** — Must support Instructor and Provider post types, plus a WP
admin dashboard for managing courses.

**Filter grouping** — Top-level filters combine using AND; multiple values
within the same filter combine using OR. Example:

```
(provider = uosd OR provider = dmu)
AND
(location = india OR location = china)
AND
(category = graphic design)
```

**Database** — Write necessary migrations and add additional database
tables where requirements can't be met within the regular WordPress
database structure.

### Testing

The project should be configured to support automated WordPress testing.
Document unit tests, integration tests, feature tests, and end-to-end tests
(where appropriate), with particular attention to filter behaviour.
Documentation should describe what should be tested, high-risk areas,
regression prevention strategy, and how new filters can be tested
consistently.

### Performance & Scalability

Large-scale optimisation is not required to be implemented. Instead,
document: expected performance bottlenecks, limitations of WordPress meta
queries, indexing considerations, query performance, caching opportunities,
pagination strategy, search optimisation, and how the system would evolve
to support hundreds of thousands (or millions) of courses — including when
to introduce dedicated lookup tables, denormalised data, or external search
technologies.

## Progress

- 2026-07-23 — Repository initialised; Docker Compose scaffolded
  (WordPress 6.7/PHP 8.2, MySQL 8.0, phpMyAdmin on ports 8080/8081/3306).
- 2026-07-23 — Plugin scaffold created at
  `wp-content/plugins/course-discovery`: `composer.json` with PSR-4
  autoloading (`OxfordInternational\CourseDiscovery\` → `src/`), main plugin
  file with WordPress headers, and a `Plugin.php` bootstrap class wiring
  `init` / `rest_api_init` / `plugins_loaded` hooks. Empty stub directories
  created for `Domain/ValueObject`, `Domain/Model`, `Query`, `Filter`,
  `PostType`, `Taxonomy`, `Migration`, `REST` — not yet implemented.
- 2026-07-23 — Minimal companion theme scaffolded at
  `wp-content/themes/course-discovery-theme` (`style.css`, `functions.php`,
  `header.php`, `footer.php`, `index.php`).
- 2026-07-23 — `db/` wired into the `db` service at
  `/docker-entrypoint-initdb.d` for local SQL dump/import; dump files
  gitignored.
- 2026-07-23 — Docker stack started locally for verification.
- **Not yet done:** Course/Instructor/Provider post types, ACF field groups,
  taxonomies, the filter pipeline and hook system, REST endpoints, frontend
  UI, migrations/custom DB tables, and the test suite (unit/integration/
  feature/e2e).

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
