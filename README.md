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

**Live deployment:** [courses.statichex.dev](http://courses.statichex.dev)

### Status at a glance

| Layer | State |
|-------|-------|
| Domain model (value objects + `Course`/`Instructor`/`Provider`) | ✅ Implemented, 30 unit tests passing |
| Post types, taxonomy, ACF field groups | ✅ Implemented, verified live |
| Dummy data seeder (`bin/seed.php`) | ✅ Implemented |
| Query builder + filter pipeline | ✅ Implemented, verified against live seeded data, 25 unit tests passing |
| REST endpoint (`course-discovery/v1/courses`, `/filters`) | ✅ Implemented, verified live |
| Frontend filter UI | ✅ Implemented, verified live |
| Migrations / custom DB tables | ✅ Implemented, verified live |
| Integration / feature tests (`WP_UnitTestCase`) | ✅ Implemented — 20 tests |
| End-to-end (browser) tests | ⏳ Not started |

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
- [Production Deployment](#production-deployment)
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

## Production Deployment

The live deployment is at **[courses.statichex.dev](http://courses.statichex.dev)**,
on shared cPanel hosting rather than Docker — that environment has no
Docker/root access, so it runs WordPress, the plugin and ACF directly
under PHP-FPM, with its own isolated MySQL database. Same codebase, same
plugin/theme, seeded with `bin/seed.php`. HTTPS is available but currently
shows a certificate mismatch (AutoSSL for the subdomain hasn't run yet) —
use the `http://` URL above in the meantime.

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

A second database, `wordpress_test`, holds the integration test suite's
data — kept separate from `wordpress` so running tests never touches dev
content. See [Testing Instructions](#testing-instructions) for the
one-time creation command.

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

The specific Provider/Instructor/Course names, descriptions, prices and
dates hard-coded in `bin/seed.php` were AI-generated as placeholder demo
content — none of it represents real institutions, people, courses or
data; only the script's structure (tagging, purge-before-reseed,
validating dates through the `StartDate` value object) is functional code
to review.

## Testing Instructions

Two separate suites, because they need different environments.

**Unit tests** — pure PHP, no WordPress, run from the host:

```bash
composer test
```

**Integration tests** — need a real WordPress bootstrap (`WP_UnitTestCase`,
real `WP_Query`/ACF/REST dispatch), so they run inside the container
against a dedicated test database:

```bash
docker compose exec wordpress php wp-content/plugins/course-discovery/vendor/bin/phpunit \
  -c wp-content/plugins/course-discovery/phpunit-integration.xml.dist
```

(`composer test:integration` runs the same command, but only works when
run from inside the container for the same ABSPATH/DB-host reasons.) The
test database (`wordpress_test` on the same `db` service, separate from
the dev `wordpress` database) needs creating once:

```bash
docker compose exec -T db mysql -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS wordpress_test; GRANT ALL PRIVILEGES ON wordpress_test.* TO 'wordpress'@'%';"
```

**Current coverage:** 62 unit tests — `Domain/ValueObject` (`PostId`,
`Price`, `StartDate`, `Location`, `CategoryTerm`), `Domain/Model`'s
`Course::locations()` derivation logic, `Filter\FilterCriteria` parsing,
every concrete `Filter`'s contribution to the query builder, the
`FilterPipeline`'s end-to-end AND/OR composition,
`Query\CourseResultAssembler`'s filter/pagination math,
`REST\CourseTransformer`'s Course→JSON conversion, and
`Migration\FilterIndexSync`'s row-computation logic — all with no
WordPress bootstrap, since predicates and serialisation are tested
against fabricated `Course` objects.

Plus **25 integration tests** (`wp-phpunit` + `yoast/phpunit-polyfills`,
against the plugin's own real WordPress install) across five suites:
`FilterPipelineIntegrationTest` (the brief's AND/OR composition against
real posts/ACF data — the highest-risk area), `CourseQueryBuilderIntegrationTest`
(search across all three text fields, real `tax_query` behaviour
including child-term inclusion, pagination math against a real result
set, ACF hydration end to end), `RestEndpointIntegrationTest` (the actual
registered routes dispatched through `WP_REST_Server`, not just direct
PHP calls), `StartDateFilterIntegrationTest` (chronological ordering and
single/multi start-date filtering), and `FilterIndexSyncIntegrationTest`
(the migration actually created both lookup tables, and saving/deleting/
unpublishing a real Course keeps them in sync via the real
`save_post_course`/`before_delete_post` hooks).

### Strategy

- **Unit tests** — value objects (e.g. price, start date, slug wrappers) and
  individual `Filter` implementations tested in isolation, no WordPress
  bootstrap required. This is where filter *logic* correctness (AND/OR
  composition, edge cases like an empty selection or an unknown value) is
  covered cheaply and fast.
- **Integration tests** (implemented) — filters and the query builder
  tested against a real WordPress test database (`WP_UnitTestCase` /
  wp-phpunit), asserting actual results from real `WP_Query`/`tax_query`/
  ACF data, each test creating its own fixtures rather than relying on
  `bin/seed.php`'s data. This is the layer that catches WordPress-specific
  surprises (meta query quirks, taxonomy joins) unit tests can't see — and
  it already has: see the `CourseSearchClause` incident in the
  Development Log below, found by exactly this suite.
- **Feature tests** (implemented, folded into the integration suite) —
  `RestEndpointIntegrationTest` exercises full filter requests end-to-end
  through the real REST server (`WP_REST_Server::dispatch()`), combined
  filters, pagination and response shape. Admin-screen capability checks
  are not yet covered.
- **End-to-end tests** (not yet implemented) — where appropriate,
  browser-driven tests (e.g. Playwright) covering the frontend filter UI:
  keyboard-only operation, combobox behaviour for locations/start dates,
  and that selecting filters narrows results as expected.

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
| `Query`              | `CourseQueryBuilder` (a typed, fluent `WP_Query` abstraction), `CourseResultAssembler` (pure filter/pagination logic), `CourseSearchClause` (widens search to the `short_description` ACF field) and `FilterOptionsProvider` (available filter option lists). | ✅ Implemented |
| `Filter`             | `FilterCriteria` plus one class per filter (search, provider, location, start date, category), each implementing a shared `Filter` interface, composed by `FilterPipeline`. | ✅ Implemented |
| `Migration`          | `MigrationRunner` (tracks applied migrations via an option) running `CreateFilterIndexTables` (two lookup tables — see Design Decisions), kept in sync by `FilterIndexSync` on save/delete. | ✅ Implemented |
| `REST`               | `CourseSearchController` (`GET /courses` — filtered, paginated search) and `FilterOptionsController` (`GET /filters` — available option lists), plus `CourseTransformer` for Course→JSON serialisation, behind a `RestController` interface filterable via `course_discovery_rest_controllers`. | ✅ Implemented |
| `Frontend`           | `CourseArchiveTemplate` (serves the plugin's own course-listing template at the site's front page `/`, 301-redirecting the Course post type's own `/courses/` archive URL there) and `FilterFieldRenderer` (the multi-select filter disclosures). | ✅ Implemented |

ACF (Advanced Custom Fields, free edition) is installed and active as the
one external plugin the brief allows. Its field groups are defined in code
rather than left as UI-only config, so the schema versions alongside the
domain model that reads it — see the docblock on `Course::fromPost()` for
the exact field names each group must keep in sync with.

The theme (`course-discovery-theme`) is intentionally minimal and exists to
provide a rendering surface for the plugin during development.

### Design decisions

- **Composition over inheritance.** Filters are separate, independently
  testable classes composed by `FilterPipeline` rather than built as
  subclasses of a base "filter" class. Each filter only needs to know how
  to contribute its own criteria to a `CourseQueryBuilder` — nothing else
  depends on its internals.
- **Specification-style composition for AND/OR grouping.** `FilterCriteria`
  holds the full selection as typed lists; each `Filter` combines *its own*
  selected values with OR (an `IN` tax_query operator, or a predicate
  matching any selected value), and `FilterPipeline`/`CourseQueryBuilder`/
  `CourseResultAssembler` require every filter to match, i.e. AND across
  filters — mirroring the brief's example
  `(provider = A OR provider = B) AND (location = X OR location = Y)`. The
  AND-across-filters composition lives in one place (the assembler) rather
  than being reimplemented per filter, so it can't drift between filter
  types.
- **SQL-native filtering where it's reliable, in-PHP where it isn't.**
  `CategoryFilter` pushes down into a real `tax_query` clause, since
  categories are an indexed WordPress taxonomy relationship. `Provider`,
  `Location` and `StartDate` filter as in-PHP predicates over already-
  hydrated `Course` objects instead: ACF stores those fields as a single
  serialized value per post, and a `meta_query` `LIKE`/`IN` match against
  that serialized value risks false positives against the array's own
  index tokens, not just its stored values — exactly the "wrong-but-
  similar SQL join" the Testing Instructions flag as highest regression
  risk. Matching against typed, already-parsed domain objects removes that
  ambiguity entirely, at the cost of fetching the full candidate set
  before pagination (see `CourseQueryBuilder`'s docblock, and Performance &
  Scalability for the evolution path).
- **Hook/event pipeline for extensibility.** Filters register themselves
  via `course_discovery_filters`; `CourseQueryBuilder` fires
  `course_discovery_query_args` (modify `WP_Query` args before execution)
  and `course_discovery_order_courses` (customise result ordering, over
  the hydrated `Course` list rather than just `WP_Query`'s `orderby`);
  `FilterCriteria::fromArray()` fires `course_discovery_transform_criteria`
  (rewrite raw search criteria before it's typed); `FilterOptionsController`
  fires `course_discovery_filter_options` (alter the available Provider/
  Location/Category/StartDate option lists returned to the frontend). New
  filters, altered query args, custom ordering, or altered filter options
  are all addable by third-party code hooking in, with no changes to any
  existing filter/controller class.
- **Filter options derived from live data, not configuration.** `GET
  /filters` computes its option lists by walking every currently published
  Course rather than listing all Providers/Categories that exist — so an
  option that wouldn't return anything (e.g. a Provider with no Course
  assigned yet) never appears as a selectable filter value.
- **Two focused lookup tables, not one wide cross-product table.**
  `CreateFilterIndexTables` creates `course_discovery_course_providers`
  (course_id, provider_id, location_slug) and
  `course_discovery_course_start_dates` (course_id, start_date) — kept
  live by `FilterIndexSync` on every Course save/delete. A course with 2
  providers and 3 start dates needs 5 rows split across two single-
  purpose tables rather than 6 in one table crossing every dimension
  together, and each table stays independently indexable. Categories
  aren't duplicated here — `course_category` is a real taxonomy already
  backed by an indexed join (`wp_term_relationships`). **Not yet wired
  into `CourseQueryBuilder`**: the existing in-PHP-predicate filters are
  simpler, already thoroughly tested, and correct at this project's
  scale — these tables exist and stay accurate as the documented
  Performance & Scalability evolution path, ready to become the query
  source without a risky "build the index and cut over in the same
  change" step.
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
- **Frontend as progressive enhancement, not a JS-only app.**
  `templates/archive-course.php` reads filter selections straight from
  `$_GET` and renders through the exact same `FilterCriteria`/
  `FilterPipeline`/`CourseQueryBuilder` the REST API uses — so the page
  filters correctly via a normal form submission with JavaScript
  disabled. `assets/js/frontend.js` only replaces that full-page reload
  with a `fetch` against `course-discovery/v1/courses` and an in-place
  DOM update; it never introduces filtering logic that doesn't already
  exist server-side, so the two can't drift out of sync with each other.
- **Multi-select "combobox" as a native `<details>`/`<summary>`
  disclosure, not a hand-built ARIA `role="combobox"` widget.** The
  brief requires Locations and Start Dates to be a dropdown combobox;
  `FilterFieldRenderer` implements all four multi-selects (Providers,
  Locations, Categories, Start Dates) this way for consistency. A native
  disclosure widget gets correct open/close keyboard behaviour from the
  browser for free and works with JavaScript entirely disabled, which a
  custom combobox's hand-rolled keyboard handling can't guarantee — see
  the class's own docblock, and Assumptions Made below.

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
- **Evolution path.** Roughly: (1) current — `tax_query` for categories,
  in-PHP predicates over the full matching set for provider/location/
  start date, fine at low volume; (2) **already built**: two denormalised
  lookup tables (`course_discovery_course_providers`,
  `course_discovery_course_start_dates`, see Architectural Decisions),
  kept in sync via `FilterIndexSync` on every save/delete — not yet
  wired into `CourseQueryBuilder` as the query source, so the next step
  is cutting the relevant filters over to indexed `JOIN`s against these
  tables once in-PHP filtering stops being fast enough, with the sync
  itself already proven correct by
  `FilterIndexSyncIntegrationTest`; (3) add caching around option lists
  and common filter results; (4) once full-text relevance/ranking or
  facet counts at scale become necessary (hundreds of thousands to
  millions of courses), move search to an external engine (e.g.
  Elasticsearch/OpenSearch or Algolia), with WordPress remaining the
  system of record and the search index kept eventually consistent via
  the same save/delete hooks.

## Assumptions Made

- Local development only; the Docker Compose file and credentials in this
  repo are not intended for production use.
- The plugin targets PHP 8.2+ and WordPress 7.0+; no support for older
  versions is assumed.
- Domain logic lives in the plugin, not the theme; the theme is a thin
  presentation layer.
- "Dropdown combobox" (for Locations/Start Dates) is interpreted as a
  closed-by-default control that expands into a list of options on
  click or keyboard activation — not specifically the WAI-ARIA
  `role="combobox"` pattern (a text input with inline autocomplete).
  Implemented as a native `<details>`/`<summary>` disclosure instead;
  see the Design Decisions note above for why. If a stricter ARIA
  combobox (with typeahead filtering) is expected instead, that would
  be a follow-up to `FilterFieldRenderer`, not a change to the
  underlying Filter/Query layer.
- Visual styling (`assets/css/frontend.css`) follows an explicit
  direction: light/white background, one accent colour (Oxford blue
  `#002147`), system fonts only (no external font/CDN dependency, so the
  page renders identically offline) — a professional "university
  website" look rather than a developer-tool aesthetic.

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
- 2026-07-23 — Implemented the query builder and filter pipeline:
  `Filter\FilterCriteria`, the `Filter` interface, five concrete filters
  (search, provider, location, category, start date), `FilterPipeline`,
  `Query\CourseQueryBuilder`, `CourseResultAssembler` and
  `CourseSearchClause`. Category filtering pushes into a real `tax_query`;
  Provider/Location/StartDate filter as in-PHP predicates over hydrated
  `Course` objects instead of a `meta_query`, since ACF's serialized
  storage for those fields isn't reliably `LIKE`/`IN`-matchable (see
  Architectural Decisions). Added 25 unit tests (55/55 total, no
  WordPress bootstrap needed even for the filter/AND-OR logic, since it's
  tested against fabricated `Course` objects). Verified live against the
  real seeded data: unfiltered listing paginates correctly (16 courses,
  2 pages); category-only, combined AND-across-filters, and a
  deliberately contradictory combination (expecting zero results) all
  returned exactly the expected courses; confirmed the widened search
  matches a term that exists *only* in a course's `short_description`
  field (not title/content), proving `CourseSearchClause`'s join actually
  extends WordPress's default search rather than just coincidentally
  overlapping with it. No PHP errors in the container logs.
- 2026-07-23 — Added the REST layer: `GET /wp-json/course-discovery/v1/
  courses` (filtered, paginated search) and `.../filters` (available
  option lists, derived from currently published courses, wiring up the
  brief's "altering available filter options" extension point via
  `course_discovery_filter_options` — the one hook missing after the
  previous milestone). Added `CourseTransformer` for Course→JSON
  serialisation and refactored `CourseQueryBuilder`/`CourseResultAssembler`
  to share fetch logic between the paginated `execute()` and a new
  `executeAll()`. 4 new unit tests (59/59 total). Verified live: an
  unfiltered request paginates correctly; combined category+location
  AND-filtering and a deliberately contradictory combination (expecting
  zero) both behave correctly through the actual HTTP endpoint, not just
  in-process; search still matches `short_description`-only text over
  REST; `/filters` returns correct counts and chronologically-ordered
  start dates. No PHP errors in the container logs.
- 2026-07-23 — Built the frontend filter UI: `Frontend\
  CourseArchiveTemplate` serves the plugin's own `templates/archive-
  course.php` for `/courses/` via `template_include`, independent of the
  active theme. The page reads filter selections from `$_GET` and
  renders through the same `FilterCriteria`/`FilterPipeline`/
  `CourseQueryBuilder` the REST API uses, so it filters correctly with
  JavaScript entirely disabled; `assets/js/frontend.js` progressively
  enhances it to fetch `course-discovery/v1/courses` and update the DOM
  in place instead of reloading the page. `FilterFieldRenderer` renders
  Providers/Locations/Categories/Start Dates as native `<details>`/
  `<summary>` multi-select disclosures (see Design Decisions/Assumptions
  for why, not a hand-built ARIA combobox). Extracted `FilterOptions
  Provider` out of `FilterOptionsController` so the REST endpoint and the
  server-rendered template compute option lists identically. Also took a
  design pass on `assets/css/frontend.css`: a small catalog/card-index
  visual motif (serif headings, monospace price/date "stamps", tab-
  styled filter disclosures), system fonts only, dark-mode and
  reduced-motion aware. Verified live: unfiltered and filtered page
  loads both render correctly via plain GET (no-JS baseline), selected
  filters persist as checked checkboxes and badge counts across a
  request, CSS/JS assets load with no errors, full test suite still
  59/59. Could not capture an automated screenshot in this environment
  (Playwright's Chromium needs `libnspr4`, which needs root to install
  and this environment has no passwordless sudo) — visual review was
  done by request rather than by an automated check.
- 2026-07-23 — Restyled the frontend on explicit direction: replaced the
  earlier serif/monospace catalog motif with a light/white, professional
  "university website" look — Oxford blue (`#002147`) as the single
  accent colour, system fonts throughout (no monospace, no serif
  display), white cards with a subtle border and hover shadow-lift,
  filter dropdowns styled as plain form controls rather than coloured
  pills, an explicit 3/2/1-column responsive grid, and a CSS-only search
  icon (an inline SVG data URI as the input's `background-image`, so no
  HTML changed). Also added a matching light-theme base (body font,
  background, link colour) to `course-discovery-theme/style.css` for
  whatever non-plugin pages the theme renders. No PHP, JS or HTML
  structure changed — CSS only, per the request; verified no errors,
  correct palette served, full suite still 59/59.
- 2026-07-23 — Provisioned production server on my account at
  courses.statichex.dev and deployed the site there. Verified live:
  front end, admin, seeded data, REST endpoints and combined filtering
  all match local behaviour.
- 2026-07-23 — Moved the course listing to the site's front page (`/`)
  instead of the Course post type's own archive URL (`/courses/`), which
  now 301-redirects to `/` so there's one canonical URL rather than a
  duplicate. Verified locally: `/` renders the listing, `/courses/`
  redirects correctly, filtering still works at the new URL, full suite
  still 59/59.
- 2026-07-23 — Added the formal integration test suite: `wp-phpunit` +
  `yoast/phpunit-polyfills`, bootstrapped against the plugin's own real
  WordPress install (inside the container) and a dedicated
  `wordpress_test` database. 20 tests across `FilterPipelineIntegrationTest`,
  `CourseQueryBuilderIntegrationTest`, `RestEndpointIntegrationTest` and
  `StartDateFilterIntegrationTest`, each creating its own fixtures.
  Downgraded PHPUnit from 10 to 9.6 project-wide — `wp-phpunit` 7.0.2
  calls a `PHPUnit\Util\Test` method PHPUnit 10 removed, and 9.6 is what
  the wp-phpunit/polyfills combination actually supports; the existing
  59 unit tests needed no changes and still pass.
  **Found and fixed a real bug in the process:** `CourseSearchClause`
  guarded its `add_filter()` calls with a "register once" static flag,
  correct for a normal request but wrong under `WP_UnitTestCase`, which
  snapshots and restores the hooks table between every test — after the
  first test registered the filter, the guard silently skipped
  re-registering it once the table was reset, so the search-widening
  filter quietly stopped working for every test after the first. Removed
  the guard (WordPress's `add_filter()` already dedupes identical
  callback+priority registrations, so there's no double-execution risk).
  Full suite: 59 unit + 20 integration, all passing.
- 2026-07-23 — Added migrations/custom DB tables: `MigrationRunner` +
  `CreateFilterIndexTables` (two lookup tables — `course_discovery_
  course_providers`, `course_discovery_course_start_dates` — deliberately
  two focused junction tables rather than one wide cross-product table;
  see Architectural Decisions), kept in sync via `FilterIndexSync` on
  `save_post_course`/`before_delete_post`. Not yet wired into
  `CourseQueryBuilder` as a query source — see Performance & Scalability's
  evolution path for why. Added 3 unit tests (pure row-computation) and 5
  integration tests (table creation, sync on publish/re-save/delete/
  unpublish). Found a real gap while verifying live: ACF's `update_field()`
  writes postmeta directly and never fires `save_post_course`, so
  `bin/seed.php`'s courses never reached the sync — fixed by adding a
  `wp_update_post()` call after each course's fields are set. Verified
  live: both tables populate correctly (20 provider rows, 22 start-date
  rows across 16 reseeded courses), no errors. Full suite: 62 unit + 25
  integration, all passing.
- **Not yet done:** end-to-end (browser-driven) tests.

</details>
