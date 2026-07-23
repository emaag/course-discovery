# Course Discovery

**A domain-modelled course search & filtering system, built as a WordPress plugin.**

![WordPress](https://img.shields.io/badge/WordPress-7.0.2-21759B?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![Status](https://img.shields.io/badge/status-in%20progress-yellow)

Course Discovery is a WordPress plugin (with a minimal companion theme) that
provides course search, filtering and discovery for an EdTech platform. It's
built around a typed domain model, a composable filter pipeline, and a
hook/event extension surface — see [Architectural Decisions](#architectural-decisions)
for the reasoning.

This is a pre-interview technical exercise for Oxford International. The
verbatim task brief is preserved below for reference, followed by the
project documentation and a running development log.

### Status at a glance

| Layer | State |
|-------|-------|
| Domain model (value objects + `Course`/`Instructor`/`Provider`) | ✅ Implemented, 30 unit tests passing |
| Post types, taxonomy, ACF field groups | ✅ Implemented, verified live |
| Dummy data seeder (`bin/seed.php`) | ✅ Implemented |
| Query builder, filter pipeline, REST endpoint | ⏳ Not started |
| Frontend filter UI | ⏳ Not started |
| Migrations / custom DB tables | ⏳ Not started |
| Integration / feature / e2e tests | ⏳ Not started |

Full detail is in [Architectural Decisions](#architectural-decisions) and the
[Development Log](#development-log) at the bottom of this file.

<details>
<summary><strong>Task Brief</strong> (reproduced verbatim from the exercise instructions — click to expand)</summary>

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

</details>

## Table of Contents

- [Setup Instructions](#setup-instructions)
- [Environment Requirements](#environment-requirements)
- [Database Setup](#database-setup)
- [Development Commands](#development-commands)
- [Testing Instructions](#testing-instructions)
- [Architectural Decisions](#architectural-decisions)
- [Performance & Scalability](#performance--scalability)
- [Assumptions Made](#assumptions-made)
- [Development Log](#development-log)

## Setup Instructions

1. Clone this repository.
2. Start the stack from the repository root:

   ```bash
   docker compose up -d
   ```

3. Visit **http://localhost:8080/wp-admin/install.php** and complete the
   WordPress install wizard (site title, admin username/password/email).
   **Choose and note down your own admin credentials here** — the official
   WordPress Docker image has no environment variable to pre-seed or later
   recover the admin password (unlike the database credentials, see
   [Retrieving credentials from Docker](#retrieving-credentials-from-docker)
   below), so this is the only point they're set. If you forget them, either
   use wp-admin's "Lost your password?" link (needs outbound email/SMTP,
   not configured by default in this stack) or just start over —
   `docker compose down -v && docker compose up -d` followed by this same
   install step and `bin/seed.php` (see
   [Development Commands](#development-commands)) reproduces the whole
   environment from scratch in a couple of minutes.
4. Log in to `/wp-admin/` and activate:
   - **Plugins → Course Discovery**
   - **Appearance → Themes → Course Discovery Theme**
5. Install and activate **Advanced Custom Fields** (free edition) via
   **Plugins → Add New → search "Advanced Custom Fields"** — this is the
   one external plugin the brief allows, and the Course/Provider field
   groups (registered in code by the plugin) only appear once it's active.
6. Visit **http://localhost:8080** to confirm the front end renders under
   the Course Discovery theme.

| Service    | URL                                              |
|------------|---------------------------------------------------|
| Site       | http://localhost:8080                              |
| WP Admin   | http://localhost:8080/wp-admin/                    |
| phpMyAdmin | http://localhost:8081                              |
| MySQL      | `localhost:3306` (from host) / `db:3306` (in-network) |

## Environment Requirements

| Requirement | Version | Notes |
|--------------|---------|-------|
| Docker & Docker Compose | — | Only host-side dependency needed to run the stack. |
| WordPress | 7.0.2 | Pinned via the `wordpress:7.0.2-php8.2-apache` image in `docker-compose.yml`. |
| PHP | 8.2+ | Matches the pinned image tag and the plugin's `composer.json` (`"php": ">=8.2"`). WordPress 7.0 itself recommends PHP 8.3+, but 8.2 remains supported — the image is pinned to 8.2 for now since that's what the plugin targets. |
| MySQL | 8.0 | Provisioned by the `db` service. |
| Composer | 2.x | Only needed on the host to install plugin dependencies and run tests (`wp-content/plugins/course-discovery/`) — not required inside the container. |

## Database Setup

The `db` service provisions a MySQL 8.0 database automatically on first run,
using the credentials below (development only — do not reuse in production):

| Setting  | Value                                     |
|----------|--------------------------------------------|
| Host     | `db` (or `localhost:3306` from the host)    |
| Database | `wordpress`                                 |
| User     | `wordpress`                                 |
| Password | `wordpress`                                 |
| Root pw  | `root`                                      |

phpMyAdmin is available at `http://localhost:8081` for inspecting the
database directly.

### Retrieving credentials from Docker

The values above are also set as plain environment variables on the running
containers (development only — never do this for real secrets), so they can
be read directly from Docker instead of trusting this table stays in sync
with `docker-compose.yml`:

```bash
docker compose exec wordpress printenv | grep WORDPRESS_DB_   # DB host/user/password/name, as WordPress sees them
docker compose exec db printenv | grep MYSQL_                # MySQL root/user/password/database, as the db container sees them
```

There's no equivalent lookup for the **WordPress admin** username/password —
see the note in [Setup Instructions](#setup-instructions) step 3, since the
official image doesn't expose those as environment variables.

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

## Development Commands

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

Seeding dummy data (run from the repository root, needs the WordPress/ACF
runtime, so it's run inside the container rather than via composer):

```bash
docker compose exec wordpress php wp-content/plugins/course-discovery/bin/seed.php
```

Creates a fixed, realistic set of Providers, Instructors, hierarchical
Categories and Courses (varied prices, multiple/overlapping start dates,
multi-provider courses to exercise the derived-Location logic). Safe to
re-run — everything it creates is tagged with a `_course_discovery_seed`
post meta flag and purged before reseeding, so it never touches other
content and never accumulates duplicates. Regenerating content this way
(rather than shipping a `.sql` dump) is intentional: the same command
reproduces the same dataset on any environment, including the eventual
public deployment, without transferring a database file.

## Testing Instructions

The plugin uses PHPUnit for automated tests, run via:

```bash
composer test
```

**Current coverage:** 30 unit tests over `Domain/ValueObject` (`PostId`,
`Price`, `StartDate`, `Location`, `CategoryTerm`) and `Domain/Model`'s
`Course::locations()` derivation logic — all run with no WordPress
bootstrap, since the value objects and the tested model logic have no WP
dependency. Integration, feature and e2e tests (below) land once the query
builder, filter pipeline and REST endpoint exist to test against.

### Strategy (planned)

- **Unit tests** — value objects (e.g. price, start date, slug wrappers) and
  individual `Filter` implementations tested in isolation, no WordPress
  bootstrap required. This is where filter *logic* correctness (AND/OR
  composition, edge cases like an empty selection or an unknown value) is
  covered cheaply and fast.
- **Integration tests** — filters and the query builder tested against a
  real WordPress test database (`WP_UnitTestCase` / wp-phpunit), asserting
  the actual `WP_Query`/SQL produced against seeded Course/Instructor/
  Provider fixtures. This is the layer that catches WordPress-specific
  surprises (meta query quirks, taxonomy joins) that unit tests can't see.
- **Feature tests** — exercise a full filter request end-to-end against the
  REST endpoint (multiple filters combined, pagination, ordering) and admin
  screens (post type registration, capability checks).
- **End-to-end tests** — where appropriate, browser-driven tests (e.g.
  Playwright) covering the frontend filter UI: keyboard-only operation,
  combobox behaviour for locations/start dates, and that selecting filters
  narrows results as expected.

**High-risk areas** — the filter AND/OR composition logic; start date
parsing/formatting and chronological ordering of the `{month}-{year}`
combobox; the derived Location-from-Provider relationship; and any custom
SQL in lookup tables (highest regression risk since it bypasses WP_Query's
own testing surface).

**Regression prevention** — each `Filter` implementation ships with a fixed
fixture set (known Courses/Providers/Locations) and a table of
input-selection → expected-result-IDs cases, run in CI on every change.
Query-shape assertions (not just result counts) are used for the filters
most likely to regress silently, since a wrong-but-similar SQL join can
still return plausible-looking results.

**Testing new filters** — because filters implement a shared
`Filter` contract (see Architectural Decisions below), a generic contract test
suite runs against every registered filter implementation, so a new filter
is exercised the same way as existing ones without hand-writing bespoke
plumbing each time; only its fixture data and expected cases need adding.

## Architectural Decisions

The plugin follows a namespaced, PSR-4 structure under
`OxfordInternational\CourseDiscovery`:

| Namespace           | Responsibility | Status |
|---------------------|----------------|--------|
| `Plugin.php`         | Bootstraps the plugin and wires up WordPress hooks. | ✅ Implemented |
| `Domain/Model`       | Domain entities (`Course`, `Instructor`, `Provider`) hydrated from `WP_Post` + ACF field data, not raw arrays. | ✅ Implemented |
| `Domain/ValueObject` | Immutable value objects (`Price`, `StartDate`, `PostId`, `Location`, `CategoryTerm`) so primitives never leak into the domain layer. | ✅ Implemented |
| `PostType`           | Custom post type registrations (`course`, `instructor`, `provider`), each behind a `PostTypeRegistrar` interface and filterable via `course_discovery_post_types`. | ✅ Implemented |
| `Taxonomy`           | Custom taxonomy registrations (hierarchical `course_category`), behind a `TaxonomyRegistrar` interface and filterable via `course_discovery_taxonomies`. | ✅ Implemented |
| `Field`              | ACF field groups registered in code (`acf_add_local_field_group`) for Course and Provider, behind a `FieldGroupRegistrar` interface and filterable via `course_discovery_field_groups`. | ✅ Implemented |
| `Query`              | A `WP_Query` abstraction (`CourseQuery`/`CourseQueryBuilder`) exposing a typed, fluent API instead of passing `WP_Query` arg arrays around directly. | ⏳ Planned |
| `Filter`             | One class per filter (search, provider, location, start date, category), each implementing a shared `Filter` interface with a method to contribute to the query builder given selected criteria. | ⏳ Planned |
| `Migration`          | Versioned schema/data migration runners for any custom tables (e.g. a course/provider/location lookup table). | ⏳ Planned |
| `REST`               | REST controllers exposing course search/filtering to the frontend. | ⏳ Planned |

ACF (Advanced Custom Fields, free edition) is installed and active as the
one external plugin the brief allows. Its field groups are defined in code
rather than left as UI-only config, so the schema versions alongside the
domain model that reads it — see the docblock on `Course::fromPost()` for
the exact field names each group must keep in sync with.

The theme (`course-discovery-theme`) is intentionally minimal and exists to
provide a rendering surface for the plugin during development.

### Design decisions

- **Composition over inheritance.** Filters are separate, independently
  testable classes composed by a `FilterPipeline`/registry rather than
  built as subclasses of a base "filter" class. Each filter only needs to
  know how to (a) describe its own available options and (b) contribute
  its criteria to a query builder — nothing else depends on its internals.
- **Specification-style composition for AND/OR grouping.** Selected filter
  values are modelled as a small composite of value-object criteria: values
  *within* one filter are combined with OR, and the filters themselves are
  combined with AND, mirroring the brief's example
  `(provider = A OR provider = B) AND (location = X OR location = Y)`. This
  composition lives in one place (the query builder) rather than being
  reimplemented per filter, so it can't drift between filter types.
- **Hook/event pipeline for extensibility.** Rather than a fixed filter
  list, filters register themselves against a `Filter` registry via a
  WordPress action (e.g. `course_discovery_register_filters`), and the
  query builder fires WordPress filters at key extension points — altering
  available filter options, mutating query args before execution,
  transforming raw search criteria, and customising result ordering. New
  filters are addable by third-party code hooking in, with no changes to
  existing filter classes.
- **`WP_Query` abstraction.** Domain code never builds raw `WP_Query` arg
  arrays inline; a query builder translates typed filter criteria into
  `WP_Query`/`WP_Meta_Query`/`WP_Tax_Query` arguments in one place, which is
  also what integration tests assert against.
- **Value objects over primitives.** e.g. price is a `Price` value object
  (not a bare float) so currency/formatting/future range support has one
  home; start dates are a `StartDate` value object that knows how to
  format/compare/sort chronologically, rather than passing month/year
  strings around and re-parsing them wherever ordering is needed.
- **Locations as derived, not stored.** Since Location is derived from
  Provider, it's computed/read from the Provider relationship rather than
  duplicated as its own Course meta field, avoiding a second source of
  truth that could drift.
- **ACF for field storage, domain layer for meaning.** ACF is used purely
  as the admin data-entry/storage mechanism (the only allowed external
  plugin); all business logic and typed access goes through the
  `Domain/Model` and `Domain/ValueObject` layer so the rest of the codebase
  never touches `get_field()` calls directly.

## Performance & Scalability

Not implemented for this exercise (explicitly out of scope per the brief),
but documented here as the intended evolution path.

- **Expected bottlenecks.** `WP_Query` with multiple `meta_query`/
  `tax_query` clauses generates multi-way `JOIN`s against `wp_postmeta`,
  which is an EAV-style table (`meta_key`/`meta_value` as `LONGTEXT`) — this
  degrades fast as course count and filter combinations grow, well before
  the low hundreds-of-thousands mark.
- **Meta query limitations.** `wp_postmeta.meta_value` isn't indexed for
  range/equality comparisons beyond a shared `meta_key` index; ACF
  relationship/repeater fields are stored as serialized/CSV-ish meta,
  meaning provider/instructor relationships often require `LIKE '%id%'`
  matching rather than a real indexed join — this is the single biggest
  scaling risk for the Provider/Instructor/Category filters.
- **Indexing considerations.** Beyond WordPress's default indexes, a
  dedicated lookup/pivot table (e.g. `course_filter_index` with proper
  foreign keys and composite indexes on `(provider_id)`, `(location_id)`,
  `(category_id)`, `(start_date)`) would let filtering happen via indexed
  `JOIN`s instead of meta-value scans.
- **Query performance.** Favour a small number of well-indexed joins over
  compounding `meta_query` clauses; keep the query builder's output
  inspectable/loggable so slow filter combinations are easy to spot in
  development.
- **Caching opportunities.** Filter *option lists* (available providers,
  locations, start dates, categories) change far less often than course
  data and are prime candidates for object cache/transient caching;
  popular/common filter-result sets can also be cached with an
  invalidation hook on course save/delete.
- **Pagination strategy.** Offset-based pagination (`WP_Query`'s default)
  is adequate at moderate scale; at high volume, cursor/keyset pagination
  (ordering by an indexed, unique column) avoids the increasing cost of
  large `OFFSET`s.
- **Search optimisation.** Plain-text search across name/short/long
  description via `WP_Query`'s default `s` parameter uses `LIKE` matching
  and doesn't scale or rank well. A MySQL `FULLTEXT` index on those columns
  (via a denormalised read table, since core WP post tables aren't set up
  for it) is a reasonable mid-scale step.
- **Evolution path.** Roughly: (1) current — `WP_Query` + meta/tax queries,
  fine at low volume; (2) introduce a denormalised `course_filter_index`
  lookup table kept in sync via save/delete hooks, once meta-query joins
  show up as slow; (3) add caching around option lists and common filter
  results; (4) once full-text relevance/ranking or facet counts at scale
  become necessary (hundreds of thousands to millions of courses), move
  search to an external engine (e.g. Elasticsearch/OpenSearch or Algolia),
  with WordPress remaining the system of record and the search index kept
  eventually consistent via the same save/delete hooks.

## Assumptions Made

- Local development only; the Docker Compose file and credentials in this
  repo are not intended for production use.
- The plugin targets PHP 8.2+ and WordPress 7.0+; no support for older
  versions is assumed.
- Domain logic lives in the plugin, not the theme; the theme is a thin
  presentation layer.

## Development Log

<details>
<summary>Dated progress log — click to expand</summary>

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
- 2026-07-23 — WordPress installed via the `/wp-admin/install.php` wizard;
  **Course Discovery** plugin and **Course Discovery Theme** activated;
  front end verified rendering under the new theme with no PHP errors in
  the container logs.
- 2026-07-23 — Domain layer implemented: `Domain/ValueObject` (`PostId`,
  `Price`, `StartDate`, `Location`, `CategoryTerm`) and `Domain/Model`
  (`Course`, `Instructor`, `Provider`), with `Course::locations()` deriving
  and de-duplicating locations from its Providers. 30 unit tests added,
  all passing with no WordPress bootstrap required.
- 2026-07-23 — `course`, `instructor` and `provider` post types and the
  hierarchical `course_category` taxonomy registered, each behind a
  `PostTypeRegistrar`/`TaxonomyRegistrar` interface and filterable
  (`course_discovery_post_types`/`course_discovery_taxonomies`) so new
  post types/taxonomies can be added by a third party without editing
  `Plugin.php`. Verified live: admin screens load, `post_type_exists()`/
  `taxonomy_exists()` all return true, no PHP errors.
- 2026-07-23 — Advanced Custom Fields (free edition) installed and
  activated. `Field` namespace added: `CourseFieldGroup` (short
  description, price, instructors, providers, start dates repeater) and
  `ProviderFieldGroup` (location), registered in code via
  `acf_add_local_field_group` on ACF's `acf/init` hook, filterable via
  `course_discovery_field_groups`. Verified live: both field groups render
  on the real Course/Provider "Add New" admin screens, no PHP errors.
- 2026-07-23 — Added `bin/seed.php`, a repeatable dummy-data seeder
  (6 Providers, 8 Instructors, 9 Categories in a two-level hierarchy, 16
  Courses with varied prices/dates/multi-provider locations), tagged and
  purge-before-reseed for idempotency. Verified end-to-end by reading
  seeded Courses back through `Domain\Model\Course::fromPost()` — derived
  multi-location logic, price formatting and chronological start dates all
  correct. Also discovered and fixed a pre-existing issue: permalinks were
  left on WordPress's default "Plain" structure from install, so the
  `course`/`instructor`/`provider` archive URLs 404'd; set
  `/%postname%/` and saved via Permalinks settings (flushing rewrite
  rules from CLI didn't write `.htaccess` reliably — doing it through the
  real admin form did).
- 2026-07-23 — Upgraded the stack from WordPress 6.7 to **7.0.2**
  (`wordpress:7.0.2-php8.2-apache`). Since the environment is fully
  reproducible (scripted install + `bin/seed.php`), the whole stack was
  reprovisioned from scratch (`docker compose down -v && up -d`) rather
  than attempting a live core upgrade — reinstalled WordPress, reactivated
  the plugin/theme/ACF, reseeded the dummy dataset. Verified: 30/30 tests
  still pass, the `course`/`instructor`/`provider` post types, ACF field
  groups and `/courses/` archive all work identically under 7.0.2, no PHP
  errors or deprecation notices in the container logs. Also hit the same
  "Plain" permalink default as the original install and fixed it the same
  way (see the previous entry).
- **Not yet done:** the query builder, the filter pipeline and hook
  system, REST endpoints, frontend UI, migrations/custom DB tables, and
  integration/feature/e2e tests.

</details>
