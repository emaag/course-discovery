# Course Discovery

**A domain-modelled course search & filtering system, built as a WordPress plugin.**

![CI](https://github.com/emaag/course-discovery/actions/workflows/ci.yml/badge.svg)
![WordPress](https://img.shields.io/badge/WordPress-7.0.2-21759B?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![Status](https://img.shields.io/badge/status-in%20progress-yellow)

This is my submission for Oxford International's pre-interview technical
exercise: a WordPress plugin (plus a small companion theme) for course
search, filtering, and discovery on an EdTech platform. I built it around
a typed domain model, a composable filter pipeline, and a hook-based
extension surface, rather than the usual mesh of raw `WP_Post`/ACF arrays
and inline `WP_Query` calls. The reasoning behind each choice is in
[Architectural Decisions](#architectural-decisions).

The verbatim brief is below for reference, followed by the actual
documentation and a build log.

**Live deployment:** [courses.statichex.dev](https://courses.statichex.dev)

### Status at a glance

| Layer | State |
|-------|-------|
| Domain model (value objects + `Course`/`Instructor`/`Provider`) | ✅ Implemented |
| Post types, taxonomy, ACF field groups | ✅ Implemented, verified live |
| Dummy data seeder (`bin/seed.php`) | ✅ Implemented |
| Query builder + filter pipeline | ✅ Implemented, verified against live seeded data |
| REST endpoint (`course-discovery/v1/courses`, `/filters`) | ✅ Implemented, verified live |
| Frontend filter UI | ✅ Implemented, verified live |
| Migrations / custom DB tables | ✅ Implemented, verified live |
| Admin dashboard (Course list table columns) | ✅ Implemented, verified via integration tests |
| Static analysis (PHPStan, level 8) | ✅ Implemented, clean (`composer stan`) |
| Unit tests | ✅ 83 passing |
| Integration / feature tests (`WP_UnitTestCase`) | ✅ 41 passing |
| End-to-end (browser) tests | ✅ 19 passing |
| CI (`.github/workflows/ci.yml`) | ✅ quality/integration/e2e jobs on every push/PR |

Full detail is in [Architectural Decisions](#architectural-decisions) and
the [Development Log](#development-log) at the bottom of this file.

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

   ```console
   docker compose up -d
   ```

3. Visit **http://localhost:8080/wp-admin/install.php** and complete the
   WordPress install wizard (site title, admin username/password/email).
   **Note your own admin credentials here** — the official WordPress
   Docker image has no environment variable to pre-seed or recover the
   admin password later (unlike the database credentials — see
   [Retrieving credentials from Docker](#retrieving-credentials-from-docker)),
   so this install step is the only point they're set. If you forget them,
   either use wp-admin's "Lost your password?" link (needs outbound
   email/SMTP, not configured by default here) or just start over —
   `docker compose down -v && docker compose up -d`, then this same
   install step and `bin/seed.php` (see
   [Development Commands](#development-commands)), rebuilds the whole
   environment from scratch in a couple of minutes.
4. Log in to `/wp-admin/` and activate:
   - **Plugins → Course Discovery**
   - **Appearance → Themes → Course Discovery Theme**
5. Install and activate **Advanced Custom Fields** (free edition) via
   **Plugins → Add New → search "Advanced Custom Fields"**. This is the
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

Live at **[courses.statichex.dev](https://courses.statichex.dev)**, on
shared cPanel hosting rather than Docker — that environment has no
Docker/root access, so WordPress, the plugin, and ACF run directly under
PHP-FPM, against their own isolated MySQL database. Same codebase, same
plugin/theme, seeded with `bin/seed.php`.

**HTTPS** runs on a self-managed Let's Encrypt certificate
([acme.sh](https://github.com/acmesh-official/acme.sh), installed under
the account's home directory, no root needed) rather than cPanel's
built-in AutoSSL. AutoSSL is disabled at the hosting-package level on this
account (`uapi SSL get_autossl_problems` → `"You do not have the feature
'autossl'"`), and re-disables itself even after Namecheap support
manually re-enables it — so I stopped relying on it. `acme.sh` issues the
cert via HTTP-01 webroot validation and installs it through `uapi SSL
install_ssl`, a separate API from the gated AutoSSL feature, so it works
regardless. A reload hook re-installs the renewed cert into cPanel
automatically after every renewal, so this shouldn't need manual
attention going forward. This matters more than it might for a
non-`.dev` domain: `.dev` is on every major browser's hardcoded
HSTS-preload list, so a broken cert makes the site completely unreachable
even over plain HTTP — there's no insecure fallback a real visitor's
browser will allow.

## Environment Requirements

| Requirement | Version | Notes |
|--------------|---------|-------|
| Docker & Docker Compose | — | Only host-side dependency needed to run the stack. |
| WordPress | 7.0.2 | Pinned via the `wordpress:7.0.2-php8.2-apache` image in `docker-compose.yml`. |
| PHP | 8.2+ | Matches the pinned image tag and the plugin's `composer.json` (`"php": ">=8.2"`). WordPress 7.0 recommends PHP 8.3+, but 8.2 is still supported and is what the plugin targets. |
| MySQL | 8.0 | Provisioned by the `db` service. |
| Composer | 2.x | Only needed on the host, to install plugin dependencies and run tests (`wp-content/plugins/course-discovery/`) — not required inside the container. |

**WordPress 7.0.2 is a security release** (17 July 2026), patching a
critical pre-authentication RCE chain (dubbed "wp2shell": CVE-2026-63030,
a route-confusion issue in WordPress core's built-in REST batch endpoint
chained into a SQL injection in `WP_Query`'s `author__not_in` parameter,
CVE-2026-60137). This plugin's own REST endpoints aren't exposed to that
specific vector regardless of core version — both are read-only
(`WP_REST_Server::READABLE`/`GET` only), take typed and sanitised input,
never accept or forward an `author__not_in` parameter, and aren't
consumed through core's batch-request mechanism. I still pinned to 7.0.2
because it patches the vulnerability at the core level either way — this
isn't a claim that the plugin's own endpoints were ever independently
vulnerable, just standard due diligence.

## Database Setup

The `db` service provisions a MySQL 8.0 database automatically on first
run, using the credentials below (development only — don't reuse these
in production):

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
data, kept separate from `wordpress` so running tests never touches dev
content. See [Testing Instructions](#testing-instructions) for the
one-time creation command.

### Retrieving credentials from Docker

The values above are also set as plain environment variables on the
running containers (development only — never do this for real secrets),
so you can read them straight from Docker instead of trusting this table
stays in sync with `docker-compose.yml`:

```console
docker compose exec wordpress printenv | grep WORDPRESS_DB_   # DB host/user/password/name, as WordPress sees them
docker compose exec db printenv | grep MYSQL_                # MySQL root/user/password/database, as the db container sees them
```

There's no equivalent lookup for the **WordPress admin** username/password
— see the note in [Setup Instructions](#setup-instructions) step 3, since
the official image doesn't expose those as environment variables.

### Importing a dump

Drop a `.sql` (or `.sql.gz`) file into `db/` and it's imported
automatically the first time the `db` container initialises an empty data
volume (via MySQL's `/docker-entrypoint-initdb.d` mechanism). This only
runs once per fresh volume — if `db_data` already exists, remove it first
(`docker compose down -v`) to trigger a re-import.

To import into a database that's already running, run this instead:

```console
docker compose exec -T db mysql -uwordpress -pwordpress wordpress < db/dump.sql
```

Dump files in `db/` are gitignored and stay local.

## Development Commands

Run these from `wp-content/plugins/course-discovery/`:

```console
composer install    # install plugin dependencies
composer test       # run the PHPUnit test suite
composer stan       # run PHPStan (level 8) against src/, using WordPress/ACF Pro stubs
```

Docker stack commands, run from the repository root:

```console
docker compose up -d       # start WordPress, MySQL, phpMyAdmin
docker compose down        # stop the stack
docker compose logs -f     # tail logs
```

Seeding dummy data (run from the repository root, needs the WordPress/ACF
runtime, so it runs inside the container rather than via composer):

```console
docker compose exec wordpress php wp-content/plugins/course-discovery/bin/seed.php
```

Creates a fixed set of Providers, Instructors, hierarchical Categories,
and Courses (varied prices, multiple/overlapping start dates,
multi-provider courses to exercise the derived-Location logic). Safe to
re-run — everything it creates is tagged with a `_course_discovery_seed`
post meta flag and purged before reseeding, so it never touches other
content and never duplicates. I regenerate content this way rather than
shipping a `.sql` dump on purpose: the same command reproduces the same
dataset on any environment, including the live deployment, without
transferring a database file around.

The specific Provider/Instructor/Course names, descriptions, prices, and
dates hard-coded in `bin/seed.php` are AI-generated placeholder demo
content — none of it represents real institutions, people, or courses.
Only the script's structure (tagging, purge-before-reseed, validating
dates through the `StartDate` value object) is functional code worth
reviewing.

## Testing Instructions

Three separate suites, because each needs a different environment.

**Unit tests** — pure PHP, no WordPress, run from the host:

```console
composer test
```

**Integration tests** — need a real WordPress bootstrap (`WP_UnitTestCase`,
real `WP_Query`/ACF/REST dispatch), so they run inside the container
against a dedicated test database:

```console
docker compose exec wordpress php wp-content/plugins/course-discovery/vendor/bin/phpunit \
  -c wp-content/plugins/course-discovery/phpunit-integration.xml.dist
```

(`composer test:integration` runs the same command, but only works from
inside the container, for the same ABSPATH/DB-host reasons.) The test
database (`wordpress_test` on the same `db` service, separate from the
dev `wordpress` database) needs creating once:

```console
docker compose exec -T db mysql -uroot -proot -e \
  "CREATE DATABASE IF NOT EXISTS wordpress_test; GRANT ALL PRIVILEGES ON wordpress_test.* TO 'wordpress'@'%';"
```

**End-to-end tests** — a real browser (Playwright) against a running
instance of the site, so they run from the host, outside Docker:

```console
cd wp-content/plugins/course-discovery/tests/e2e
npm install
npx playwright install chromium  # first time only
npm run test:e2e
```

Assumes the local stack is up and seeded (`bin/seed.php`) — the tests
assert against that exact dataset (16 courses, 3 tagged "Graphic
Design"). Point elsewhere with `COURSE_DISCOVERY_BASE_URL=https://...`.

**Current coverage:** 83 unit tests covering `Domain/ValueObject`
(`PostId`, `Price`, `StartDate`, `Location`, `CategoryTerm`),
`Domain/Model`'s `Course::locations()` derivation logic,
`Filter\FilterCriteria` parsing and its `isEmpty()` shared-fetch signal,
every concrete `Filter`'s contribution to the query builder, the
`FilterPipeline`'s end-to-end AND/OR composition,
`Query\CourseResultAssembler`'s filter/pagination math,
`Query\FilterOptionsProvider`'s option derivation given a pre-fetched
Course list, `REST\CourseTransformer`'s Course→JSON conversion,
`Migration\FilterIndexSync`'s row-computation logic,
`Field\CourseFieldGroup`'s start-date validation, and
`Security\CoreHardening`'s REST route-matching logic — all with no
WordPress bootstrap, since predicates and serialisation are tested
against fabricated `Course` objects. `composer stan` (PHPStan, level 8,
against the WordPress/ACF Pro stubs) runs alongside these as a second,
static gate — see Architectural Decisions.

Plus **41 integration tests** (`wp-phpunit` + `yoast/phpunit-polyfills`,
against the plugin's own real WordPress install) across nine suites:
`FilterPipelineIntegrationTest` (the brief's AND/OR composition against
real posts/ACF data — the highest-risk area), `CourseQueryBuilderIntegrationTest`
(search across all three text fields, real `tax_query` behaviour
including child-term inclusion, pagination math against a real result
set, ACF hydration end to end), `RestEndpointIntegrationTest` (the actual
registered routes dispatched through `WP_REST_Server`, not just direct
PHP calls), `StartDateFilterIntegrationTest` (chronological ordering and
single/multi start-date filtering), `FilterIndexSyncIntegrationTest`
(the migration creates both lookup tables, and saving/deleting/
unpublishing a real Course keeps them in sync via the real
`save_post_course`/`before_delete_post` hooks),
`ExtensibilityHooksIntegrationTest` (each of the five extensibility
examples the brief names — registering a new filter, modifying query
args, customising ordering, transforming raw criteria, altering filter
options — proven with a real third-party `add_filter()` callback that
never touches an existing class), `MalformedStartDateIntegrationTest` (a
start date written directly to postmeta, bypassing ACF's own validation
entirely, is skipped rather than crashing every page that lists courses),
`CourseListTableIntegrationTest` (the wp-admin Course list table's
Price/Providers/Locations/Start Dates columns render correctly against
real `WP_Post`/ACF data, including the numeric — not lexical — price
sort), and `CoreHardeningIntegrationTest` (an anonymous request to core's
`/wp/v2/users` is rejected, a logged-in administrator can still use it,
this plugin's own public routes are unaffected, and the `generator` tag
is actually suppressed).

### Strategy

- **Unit tests** — value objects (price, start date, slug wrappers) and
  individual `Filter` implementations, tested in isolation, no WordPress
  bootstrap required. This is where filter *logic* correctness (AND/OR
  composition, edge cases like an empty selection or an unknown value)
  gets covered cheaply and fast.
- **Integration tests** — filters and the query builder tested against a
  real WordPress test database (`WP_UnitTestCase`/wp-phpunit), asserting
  actual results from real `WP_Query`/`tax_query`/ACF data, each test
  creating its own fixtures rather than relying on `bin/seed.php`'s data.
  This is the layer that catches WordPress-specific surprises — meta
  query quirks, taxonomy joins — that unit tests structurally can't see.
  It's already caught one for real: see the `CourseSearchClause` incident
  in the Development Log below.
- **Feature tests** — folded into the integration suite.
  `RestEndpointIntegrationTest` exercises full filter requests end to end
  through the real REST server (`WP_REST_Server::dispatch()`), combined
  filters, pagination, and response shape. Admin-screen capability checks
  aren't covered yet.
- **End-to-end tests** —
  `wp-content/plugins/course-discovery/tests/e2e/` (Playwright): 19 tests
  across `keyboard-operability.spec.js` (every interaction via
  `page.keyboard`, never a mouse, against the plain `<details>`/
  `<summary>` Categories filter — see Architectural Decisions),
  `combobox-filters.spec.js` (the JS-enhanced Locations/Start Dates
  `role="combobox"`/`role="listbox"` widget: correct ARIA wiring,
  `aria-expanded`/`aria-activedescendant` tracking, arrow-key/Space/
  Escape keyboard behaviour, chronological start-date ordering, and that
  the hidden native checkbox — not just the visible widget — actually
  carries the selection into a form submission),
  `filter-narrows-results.spec.js` (a category filter narrows to the
  right courses, combining a checkbox-based Categories filter with a
  combobox-based Locations filter is AND not OR, Reset restores the full
  set), and `card-rendering-parity.spec.js` (the server-rendered card and
  the JS-re-rendered card, after a JS-driven filter, show the same fields
  in the same order — see Architectural Decisions). Run via
  `npm test:e2e` from that directory against a running instance
  (`COURSE_DISCOVERY_BASE_URL` to point at something other than
  `localhost:8080`).

**High-risk areas** — the filter AND/OR composition logic; start date
parsing/formatting and chronological ordering of the `{month}-{year}`
combobox; the derived Location-from-Provider relationship; any custom SQL
in the lookup tables (highest regression risk, since it bypasses
`WP_Query`'s own testing surface).

**Regression prevention** — each `Filter` implementation ships with a
fixed fixture set (known Courses/Providers/Locations) and a table of
input-selection → expected-result-IDs cases (`tests/Unit/Filter/*Test.php`),
run via `composer test`. Query-*shape* assertions, not just result
counts, are used for the filters most likely to regress silently —
`CategoryFilterTest`, for example, asserts the exact `tax_query` array
produced, not just which courses come back, since a wrong-but-similar SQL
join can still return plausible-looking results.
`.github/workflows/ci.yml` runs unit + PHPStan, the integration suite,
and the full Playwright suite in three separate jobs on every push/PR —
see Architectural Decisions for how it gets a real WordPress core and ACF
into the integration/e2e jobs without either depending on a live install.

**Testing new filters** — every filter implements the same `Filter`
contract (see Architectural Decisions), so each has its own focused unit
test following the same pattern: apply the filter to a
`CourseQueryBuilder` with given criteria, then inspect the builder's
state via its `taxQuery()`/`searchTerm()`/`postFilterPredicates()`
getters (for a tax_query- or search-based filter) or invoke the
predicate it registered against fabricated `Course` objects (for an
in-PHP one) — see `tests/Unit/Filter/*Test.php` for the existing five. A
new filter follows the same template rather than needing bespoke test
plumbing invented from scratch. There isn't a single generic contract
test that runs automatically against every registered filter — that
would be a reasonable follow-up (a shared test trait/base asserting "no
criteria ⇒ no contribution" and similar invariants) rather than something
I built for this exercise.

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
| `Field`              | ACF field groups registered in code (`acf_add_local_field_group`) for Course and Provider, behind a `FieldGroupRegistrar` interface and filterable via `course_discovery_field_groups`. `CourseFieldGroup` also validates the start date sub-field at admin data-entry time (`acf/validate_value`), reusing `StartDate`'s own parser. | ✅ Implemented |
| `Query`              | `CourseQueryBuilder` (a typed, fluent `WP_Query` abstraction), `CourseResultAssembler` (pure filter/pagination logic), `CourseSearchClause` (widens search to the `short_description` ACF field) and `FilterOptionsProvider` (available filter option lists). | ✅ Implemented |
| `Filter`             | `FilterCriteria` plus one class per filter (search, provider, location, start date, category), each implementing a shared `Filter` interface, composed by `FilterPipeline`. | ✅ Implemented |
| `Migration`          | `MigrationRunner` (tracks applied migrations via an option) running `CreateFilterIndexTables` (two lookup tables — see Design Decisions), kept in sync by `FilterIndexSync` on save/delete. | ✅ Implemented |
| `REST`               | `CourseSearchController` (`GET /courses` — filtered, paginated search) and `FilterOptionsController` (`GET /filters` — available option lists), plus `CourseTransformer` for Course→JSON serialisation, behind a `RestController` interface filterable via `course_discovery_rest_controllers`. | ✅ Implemented |
| `Frontend`           | `CourseArchiveTemplate` (serves the plugin's own course-listing template at the site's front page `/`, 301-redirecting the Course post type's own `/courses/` archive URL there) and `FilterFieldRenderer` (the multi-select filter disclosures — see `assets/js/combobox.js` for the Locations/Start Dates ARIA combobox enhancement layered on top). | ✅ Implemented |
| `Admin`              | `CourseListTable` — adds Price/Providers/Locations/Start Dates columns (with a numeric price sort) to the wp-admin Courses screen, reusing `Course::fromPost()` so it can't drift from the REST API/frontend. | ✅ Implemented |
| `Security`           | `CoreHardening` — restricts core's `GET /wp/v2/users` REST route to authenticated requests and suppresses the `generator` version-disclosure meta tag; found via a manual security audit, not part of the brief. | ✅ Implemented |

ACF (Advanced Custom Fields, free edition) is installed and active as the
one external plugin the brief allows. Its field groups are defined in
code rather than left as UI-only config, so the schema stays versioned
alongside the domain model that reads it — see the docblock on
`Course::fromPost()` for the exact field names each group has to keep in
sync with.

The theme (`course-discovery-theme`) is deliberately minimal — it exists
to give the plugin a rendering surface, not to carry any logic itself.

### Design decisions

Grouped by concern rather than one long flat list — jump to whichever you
care about.

#### Domain modelling

- **Value objects over primitives.** Price is a `Price` value object, not
  a bare float, so currency/formatting/future range support has one
  home. Start dates are a `StartDate` value object that knows how to
  format, compare, and sort chronologically, rather than passing
  month/year strings around and re-parsing them wherever ordering is
  needed.
- **Locations as derived, not stored.** Location comes from Provider, so
  it's computed/read from the Provider relationship rather than
  duplicated as its own Course meta field — that avoids a second source
  of truth that could drift out of sync.
- **ACF for field storage, domain layer for meaning.** ACF is purely the
  admin data-entry/storage mechanism (the only allowed external plugin);
  all business logic and typed access goes through `Domain/Model` and
  `Domain/ValueObject`, so nothing outside that layer touches
  `get_field()` directly.
- **Malformed start dates: prevented at entry, tolerated at read time —
  two layers, not one.** `Field\CourseFieldGroup` validates the
  `start_date` sub-field via `acf/validate_value`, rejecting anything
  that doesn't parse as `StartDate` before it reaches the database. But
  `acf/validate_value` only runs for the real wp-admin form — ACF's own
  `update_field()` API, a row written before validation existed, or a
  future import script all bypass it. So `Course::hydrateStartDates()`
  also catches a malformed value and skips it (logging why), rather than
  letting one bad row throw an uncaught exception that takes down every
  page listing courses. I found this gap and fixed both layers together
  — see `MalformedStartDateIntegrationTest`.

#### Filtering & querying

- **Composition over inheritance.** Filters are separate, independently
  testable classes composed by `FilterPipeline`, not subclasses of a base
  "filter" class. Each one only needs to know how to contribute its own
  criteria to a `CourseQueryBuilder` — nothing else depends on its
  internals.
- **Specification-style composition for AND/OR grouping.**
  `FilterCriteria` holds the full selection as typed lists. Each `Filter`
  combines *its own* selected values with OR (an `IN` tax_query operator,
  or a predicate matching any selected value), and
  `FilterPipeline`/`CourseQueryBuilder`/`CourseResultAssembler` require
  every filter to match — AND across filters — mirroring the brief's
  example `(provider = A OR provider = B) AND (location = X OR location = Y)`.
  The AND-across-filters composition lives in exactly one place, the
  assembler, rather than being reimplemented per filter, so it can't
  drift between filter types.
- **SQL-native filtering where it's reliable, in-PHP where it isn't.**
  `CategoryFilter` pushes down into a real `tax_query` clause, since
  categories are an indexed WordPress taxonomy relationship.
  `Provider`, `Location`, and `StartDate` filter as in-PHP predicates
  over already-hydrated `Course` objects instead: ACF stores those
  fields as a single serialized value per post, and a `meta_query`
  `LIKE`/`IN` match against that value risks false positives against the
  array's own index tokens, not just its stored values — exactly the
  "wrong-but-similar SQL join" the Testing Instructions flag as highest
  regression risk. Matching against typed, already-parsed domain objects
  removes that ambiguity entirely, at the cost of fetching the full
  candidate set before pagination (see `CourseQueryBuilder`'s docblock,
  and Performance & Scalability for the evolution path).
- **`WP_Query` abstraction.** Domain code never builds raw `WP_Query` arg
  arrays inline — a query builder translates typed filter criteria into
  `WP_Query`/`WP_Meta_Query`/`WP_Tax_Query` arguments in one place, which
  is also what integration tests assert against.
- **The archive template shares one course fetch instead of running two
  — but only where that's actually valid.** Options must always reflect
  *every* published Course regardless of the current selection, while
  results must reflect only Courses matching it — genuinely different
  queries whenever any filter is active, so nothing changes there. But
  when `FilterCriteria::isEmpty()` (no filter/search selected — the
  common first-page-view case), both queries are the *same* query: every
  published Course, in default order. `templates/archive-course.php`
  detects that case and fetches/hydrates once via
  `CourseQueryBuilder::executeAll()`, deriving both `$result` (through
  `CourseResultAssembler::assemble()` directly) and `$options`
  (`FilterOptionsProvider::compute()` takes an optional pre-fetched
  `list<Course>`, only running its own query when one isn't given —
  unaffected for the REST `/filters` endpoint's own standalone calls)
  from that one result, instead of two independent `WP_Query` +
  full-Course-hydration passes on every unfiltered page view.
- **Two focused lookup tables, not one wide cross-product table.**
  `CreateFilterIndexTables` creates `course_discovery_course_providers`
  (course_id, provider_id, location_slug) and
  `course_discovery_course_start_dates` (course_id, start_date), kept
  live by `FilterIndexSync` on every Course save/delete. A course with 2
  providers and 3 start dates needs 5 rows split across two
  single-purpose tables rather than 6 in one table crossing every
  dimension together, and each table stays independently indexable.
  Categories aren't duplicated here — `course_category` is a real
  taxonomy already backed by an indexed join (`wp_term_relationships`).
  **Not yet wired into `CourseQueryBuilder`**: the existing in-PHP
  predicate filters are simpler, already thoroughly tested, and correct
  at this project's scale. These tables exist and stay accurate as the
  documented Performance & Scalability evolution path — ready to become
  the query source without a risky "build the index and cut over in the
  same change" step.

#### Extensibility

- **Hook/event pipeline for extensibility.** Filters register themselves
  via `course_discovery_filters`. `CourseQueryBuilder` fires
  `course_discovery_query_args` (modify `WP_Query` args before execution)
  and `course_discovery_order_courses` (customise result ordering, over
  the hydrated `Course` list, not just `WP_Query`'s `orderby`).
  `FilterCriteria::fromArray()` fires `course_discovery_transform_criteria`
  (rewrite raw search criteria before it's typed). `FilterOptionsController`
  fires `course_discovery_filter_options` (alter the available option
  lists returned to the frontend). New filters, altered query args,
  custom ordering, altered filter options — all addable by third-party
  code hooking in, with no changes to any existing filter/controller
  class. Proven, not just asserted: `ExtensibilityHooksIntegrationTest`
  registers a real `add_filter()` callback against each of these five
  hooks and checks the behaviour actually changes.
- **Filter options derived from live data, not configuration.** `GET
  /filters` computes its option lists by walking every currently
  published Course rather than listing all Providers/Categories that
  exist, so an option that wouldn't return anything (a Provider with no
  Course assigned yet, say) never appears as a selectable filter value.

#### Frontend

- **Progressive enhancement, not a JS-only app.**
  `templates/archive-course.php` reads filter selections straight from
  `$_GET` and renders through the exact same `FilterCriteria`/
  `FilterPipeline`/`CourseQueryBuilder` the REST API uses, so the page
  filters correctly via a normal form submission with JavaScript
  disabled. `assets/js/frontend.js` only replaces that full-page reload
  with a `fetch` against `course-discovery/v1/courses` and an in-place
  DOM update — it never introduces filtering logic that doesn't already
  exist server-side, so the two can't drift out of sync.
- **Multi-select combobox: a working native `<details>`/`<summary>`
  disclosure as the base, upgraded to a real `role="combobox"`/
  `role="listbox"` widget by JS for exactly the two filters the brief
  requires it for.** `FilterFieldRenderer` renders all four multi-selects
  (Providers, Locations, Categories, Start Dates) as the same
  `<details>`/checkbox disclosure — closed by default, correct
  open/close keyboard behaviour from the browser for free, works with
  JavaScript entirely disabled. For Locations and Start Dates
  specifically (the two the brief names as needing a "dropdown
  combobox"), `assets/js/combobox.js` progressively enhances that markup
  into an ARIA 1.2 combobox: the `<summary>` becomes the
  `role="combobox"` trigger, a generated `role="listbox"` replaces the
  visible checkbox rows, and keyboard support (arrow keys, Home/End,
  typeahead, Space to toggle the active option without closing — it's
  multi-select, so it shouldn't close on select — Escape to close and
  return focus to the trigger) tracks the active option via
  `aria-activedescendant` rather than moving real DOM focus into the
  list, which is the more robust of the two documented ARIA combobox
  focus-management models. Providers and Categories stay the plain
  disclosure, since the brief only requires "selection of multiple
  values" for those two, not a combobox. The original checkboxes are
  never removed, only hidden — a hidden form control still submits, so
  this JS layer only changes what's *seen and interacted with*, never
  what's actually sent. The page degrades to the plain checkbox
  disclosure with JavaScript disabled or failed to load — still
  semantically correct, keyboard-operable, and satisfying "selection of
  multiple values" on its own. See the class's own docblock,
  `assets/js/combobox.js`'s docblock, and Assumptions Made below.
- **Course cards: one field list, two renderers that can't be merged
  into one — so a test enforces they can't drift instead.**
  `templates/partials/course-card.php` (server-rendered) and
  `assets/js/frontend.js`'s `courseCardHtml()` (client-rendered after a
  JS-driven filter/paginate) render the same fields in the same order,
  but can't literally share one template across the PHP/JS boundary the
  way `FilterOptionsProvider` is shared between REST and the template.
  `tests/e2e/specs/card-rendering-parity.spec.js` asserts both paths
  render the same field set in the same canonical order, so a field
  added to only one renderer fails a real test instead of silently
  drifting. (Providers/Instructors, added here, are a case in point —
  both were already in `CourseTransformer`'s REST payload and the domain
  model, just never surfaced on the card.)

#### Testing & tooling

- **PHPStan (level 8) as a second, static gate alongside the test
  suite.** `phpstan.neon.dist` runs against `src/` using
  `wordpress-stubs` and `acf-pro-stubs` (via `szepeviktor/phpstan-wordpress`),
  so type errors involving WordPress/ACF's own loosely-typed APIs
  (`get_field()` returning `mixed`, `WP_Query::$posts` being
  `WP_Post[]|int[]` depending on the `fields` arg) get caught, not just
  this plugin's own code. It's a genuinely useful complement to the test
  suite, not a checkbox — it directly caught two real gaps: an
  `array_map()` over an associative array silently losing its `list<>`
  shape without `array_values()`, and `CourseQueryBuilder` assuming
  `WP_Query::$posts` is always `WP_Post[]`, which isn't true once the
  `course_discovery_query_args` hook lets a third party set
  `fields => 'ids'`. `composer stan` runs clean.
- **CI in three jobs, matched to what each layer actually needs — not
  one monolithic job.** `.github/workflows/ci.yml`:
  - `quality` — `composer test` + `composer stan`. Pure PHP, no
    WordPress, so it's the fast gate the other two jobs wait on
    (`needs: quality`) rather than burning minutes on integration/e2e
    against code that's already known-broken.
  - `integration` — real `WP_UnitTestCase` against a real WordPress
    core. Rather than spinning up the full Docker stack just for this,
    `wp-tests-config-integration.php`'s bootstrap only actually needs two
    things: WordPress core class/function *files* on disk (ABSPATH), and
    ACF's `acf.php` on disk at a predictable path — wp-phpunit installs
    its own test database and tables, and this plugin's own bootstrap
    `require`s ACF directly (see `tests/bootstrap-integration.php`)
    rather than depending on a real "activation." So the CI job
    downloads WordPress core + ACF as a plain tarball/zip and symlinks
    this repo's plugin directory in — no install wizard, no admin user,
    no running site — against a `mysql:8.0` service container.
    `ABSPATH`/`DB_*` were already read from env vars with the Docker
    Compose values as defaults (`WP_TESTS_ABSPATH` was the one addition
    — previously hardcoded to `/var/www/html/`), so the exact same suite
    runs unmodified in both places.
  - `e2e` — the one job that does need a real running, installed site
    (to actually click through in a browser), so it's the one that runs
    the real `docker-compose.yml` as-is — the same stack I run locally,
    not a parallel CI-only setup. WP-CLI (fetched fresh into the
    container, not baked into the image) replaces the manual "click
    through the install wizard" step from Setup Instructions with `wp
    core install`/`wp plugin install --activate`/`wp theme activate`,
    then `bin/seed.php` runs unmodified to produce the exact dataset the
    specs assert against.

#### Security

- **Two WordPress-core hardening measures, found via a manual security
  audit of the live deployment — not part of the brief.**
  `Security\CoreHardening` restricts core's `GET /wp/v2/users` REST route
  to authenticated requests (it exposes every user's login username with
  no auth required by default — real ammunition for a credential-stuffing
  attempt against `wp-login.php`, blocked here rather than removed
  outright since logged-in admin screens legitimately depend on it) and
  suppresses the `generator` meta tag (which advertises the exact
  WordPress version to any anonymous visitor). Neither touches this
  plugin's own public `course-discovery/v1/*` routes —
  `CoreHardeningIntegrationTest` proves both the restriction and that it
  doesn't overreach into blocking them.

## Performance & Scalability

Not implemented for this exercise — explicitly out of scope per the
brief — but documented here as the intended evolution path.

- **Expected bottlenecks.** `WP_Query` with multiple `meta_query`/
  `tax_query` clauses generates multi-way `JOIN`s against `wp_postmeta`,
  an EAV-style table (`meta_key`/`meta_value` as `LONGTEXT`). That
  degrades fast as course count and filter combinations grow, well
  before the low hundreds-of-thousands mark.
- **Meta query limitations.** `wp_postmeta.meta_value` isn't indexed for
  range/equality comparisons beyond a shared `meta_key` index; ACF
  relationship/repeater fields are stored as serialized/CSV-ish meta,
  meaning provider/instructor relationships often need `LIKE '%id%'`
  matching rather than a real indexed join. This is the single biggest
  scaling risk for the Provider/Instructor/Category filters.
- **Indexing considerations.** Beyond WordPress's default indexes, a
  dedicated lookup/pivot table (e.g. `course_filter_index`, with proper
  foreign keys and composite indexes on `(provider_id)`, `(location_id)`,
  `(category_id)`, `(start_date)`) would let filtering happen via
  indexed `JOIN`s instead of meta-value scans.
- **Query performance.** Favour a small number of well-indexed joins
  over compounding `meta_query` clauses; keep the query builder's output
  inspectable/loggable so slow filter combinations are easy to spot
  during development.
- **Caching opportunities.** Filter *option lists* (available providers,
  locations, start dates, categories) change far less often than course
  data, and are prime candidates for object cache/transient caching;
  popular filter-result sets could also be cached with an invalidation
  hook on course save/delete.
- **Pagination strategy.** Offset-based pagination (`WP_Query`'s
  default) is fine at moderate scale; at high volume, cursor/keyset
  pagination (ordering by an indexed, unique column) avoids the growing
  cost of large `OFFSET`s.
- **Search optimisation.** Plain-text search across name/short/long
  description via `WP_Query`'s default `s` parameter uses `LIKE`
  matching and doesn't scale or rank well. A MySQL `FULLTEXT` index on
  those columns (via a denormalised read table, since core WP post
  tables aren't set up for it) is a reasonable mid-scale step.
- **Evolution path.** Roughly: (1) current state — `tax_query` for
  categories, in-PHP predicates over the full matching set for
  provider/location/start date, fine at low volume; (2) **already
  built**: two denormalised lookup tables
  (`course_discovery_course_providers`,
  `course_discovery_course_start_dates`, see Architectural Decisions),
  kept in sync via `FilterIndexSync` on every save/delete — not yet
  wired into `CourseQueryBuilder` as the query source, so the next step
  is cutting the relevant filters over to indexed `JOIN`s against these
  tables once in-PHP filtering stops being fast enough, with the sync
  itself already proven correct by `FilterIndexSyncIntegrationTest`;
  (3) add caching around option lists and common filter results;
  (4) once full-text relevance/ranking or facet counts at scale become
  necessary (hundreds of thousands to millions of courses), move search
  to an external engine (Elasticsearch/OpenSearch or Algolia, say), with
  WordPress staying the system of record and the search index kept
  eventually consistent via the same save/delete hooks.

## Assumptions Made

- Local development only — the Docker Compose file and credentials in
  this repo aren't intended for production use.
- The plugin targets PHP 8.2+ and WordPress 7.0+; I'm not supporting
  older versions.
- Domain logic lives in the plugin, not the theme — the theme is a thin
  presentation layer.
- "Dropdown combobox" (for Locations/Start Dates) is implemented as a
  `role="combobox"` trigger with a `role="listbox"` popup — not the
  WAI-ARIA "editable combobox with autocomplete" variant (a text input
  you type into to filter options), since neither field's option list is
  large enough to need text filtering. This is the "select-only"
  combobox shape, extended to multi-select — a JS-enhanced upgrade of a
  working `<details>`/checkbox disclosure rather than a hand-built
  widget from scratch (see the Design Decisions note above for why that
  split was worth it). Typeahead-by-typing is a bonus — jumps to the
  next option starting with the typed character — even though the brief
  doesn't ask for it, since it falls out of the same keyboard handling
  arrow-key navigation already needed.
- Visual styling (`assets/css/frontend.css`) follows an explicit
  direction: light/white background, one accent colour (Oxford blue
  `#002147`), system fonts only (no external font/CDN dependency, so the
  page renders identically offline) — a professional "university
  website" look rather than a developer-tool aesthetic.
- All course prices are assumed to be a single currency (GBP). There's
  no per-course or per-provider currency field anywhere in the data
  model — `Price` (see Domain modelling under Design Decisions) already
  carries a `currency` property/getter and formats accordingly, but
  nothing ever passes a value other than its constructor default, so
  every course is implicitly GBP today. Real multi-currency support
  (most naturally a currency field on Provider, since that's usually
  where it actually varies) would mean adding that ACF field and passing
  it through `Course::fromPost()`, not changing `Price` itself. This is
  a different axis from the brief's own price note ("designed to be
  extended to a range or multiple price points") — that's one course,
  several prices; this is one price, which currency.

## Development Log

<details>
<summary>Condensed build history — click to expand</summary>

### Scaffolding

Docker Compose (WordPress, MySQL, phpMyAdmin), plugin skeleton (PSR-4
under `OxfordInternational\CourseDiscovery\`), and a minimal companion
theme. WordPress installed, plugin/theme activated, ACF installed as the
one allowed external plugin.

### Domain layer

`Domain/ValueObject` (`PostId`, `Price`, `StartDate`, `Location`,
`CategoryTerm`) and `Domain/Model` (`Course`, `Instructor`, `Provider`),
with `Course::locations()` deriving and de-duplicating locations from
Providers rather than storing them independently. `course`/`instructor`/
`provider` post types and the hierarchical `course_category` taxonomy
registered behind filterable registrar interfaces
(`course_discovery_post_types`/`course_discovery_taxonomies`). ACF field
groups (`CourseFieldGroup`, `ProviderFieldGroup`) registered in code, not
left as UI-only config. `bin/seed.php` added as a repeatable, idempotent
dummy-data generator (tag-and-purge-before-reseed) rather than a shipped
SQL dump, so the same dataset reproduces on any environment.

### Query, filter pipeline, REST, frontend

`Filter\FilterCriteria` plus one class per filter (search, provider,
location, category, start date) behind a shared `Filter` interface,
composed by `FilterPipeline`. Categories push down into a real
`tax_query` (an indexed taxonomy join); Provider/Location/StartDate
filter as in-PHP predicates over hydrated `Course` objects, since ACF's
serialized storage for those fields isn't reliably `meta_query`-matchable
— matching against typed, already-parsed domain objects removes that
ambiguity. `CourseQueryBuilder` abstracts `WP_Query` entirely; a REST
layer (`GET /courses`, `GET /filters`) and a server-rendered,
progressively-enhanced frontend (`$_GET` → same filter pipeline → same
results the REST API returns, with `assets/js/frontend.js` only
replacing the full-page reload with a `fetch`) both consume it, so
there's one source of truth for "what matches." Native
`<details>`/checkbox disclosures for all four multi-selects, keyboard-
operable and JS-optional by construction.

Deployed to production at `courses.statichex.dev` (shared cPanel
hosting, no Docker) — same codebase, plugin/theme, and seeder.

### Testing infrastructure

A `WP_UnitTestCase` integration suite (`wp-phpunit` +
`yoast/phpunit-polyfills`, against the plugin's real WordPress install)
alongside the WordPress-independent unit suite, migrations/custom DB
tables (`MigrationRunner`, two denormalised lookup tables kept in sync by
`FilterIndexSync` on save/delete — not yet wired into the query builder,
documented as the scale-evolution path), and a Playwright e2e suite
(keyboard operability, combobox behaviour, filter narrowing).

**Real bugs this layer caught, not just inspection:** a "register once"
static guard on `CourseSearchClause`'s hooks silently broke under
`WP_UnitTestCase`'s hooks-table reset between tests (removed the guard —
`add_filter()` already dedupes); ACF's `update_field()` bypasses
`save_post_course` entirely, so the seeder's courses never reached the
lookup-table sync until a `wp_update_post()` call was added; getting a
real browser running in a constrained sandbox needed a manual
`libnspr4`/`libnss3` workaround, and `--single-process` Chromium flags
made `.click()` unreliable there specifically (confirmed as an
environment artifact, not a site bug, by swapping in
`.dispatchEvent('click')` as a diagnostic) — the committed specs use
idiomatic Playwright throughout regardless.

### Brief-compliance review

A deliberate line-by-line pass of the actual implementation against the
brief — not just re-reading prose — found and fixed three real issues:
two false claims that had crept into this README, **none of the five
named extensibility hook examples had a test actually proving a third
party could use them** (added `ExtensibilityHooksIntegrationTest`, a
real `add_filter()` against each), and **a real production bug** — the
`start_dates` ACF field had no format enforcement, so a typo (or any
non-admin-form write) would fatal-error every page listing courses.
Fixed with two layers: `acf/validate_value` rejects malformed input at
entry, and `Course::fromPost()` also catches and skips a malformed value
already in the database, logging why, rather than taking the whole site
down.

### Tooling, admin dashboard, accessibility rework

PHPStan (level 8, WordPress/ACF Pro stubs) added as a second static
gate, catching two real type gaps (`array_map()` silently losing
`list<>` shape without `array_values()`, and `CourseQueryBuilder`
assuming `WP_Query::$posts` is always `WP_Post[]`, not true once a third
party's `course_discovery_query_args` hook sets `fields => 'ids'`). The
wp-admin Courses list table gained Price/Providers/Locations/Start Dates
columns (reusing `Course::fromPost()`, so it can't drift from the REST
API/frontend), with a numeric, not lexical, price sort.

Locations and Start Dates (the two filters the brief specifically names
as needing a "dropdown combobox") were reworked from a plain `<details>`
disclosure into a real ARIA `role="combobox"`/`role="listbox"` widget —
arrow keys, Home/End, typeahead, `aria-activedescendant` tracking — as a
progressive JS enhancement over markup that still works with JavaScript
disabled. Providers/Categories deliberately kept as the plain
disclosure, since the brief only requires multi-select for those two.

### CI pipeline

Three jobs (`quality`: unit + PHPStan, pure PHP; `integration`: real
`WP_UnitTestCase` against a plain downloaded WordPress core + ACF zip, no
live install needed; `e2e`: the actual `docker-compose.yml` stack,
provisioned via WP-CLI, seeded, driven by Playwright). Pushed and
watched three real runs rather than trusting the YAML on inspection —
caught two genuine bugs this way: the `e2e` job wasn't running `composer
install` before starting the stack, so the bind-mounted `vendor/` didn't
exist and seeding fatal-errored; and a **real, previously-undiscovered
production bug** in `frontend.js` — its filter/pagination fetch built a
URL assuming `rest_url()` never carries its own query string, which is
false under WordPress's default "Plain" permalink structure (every
environment this suite had run against before had already changed away
from that default), silently 404ing every filtered request. Reproduced
on a disposable second Docker stack before fixing it with a proper
`URL`/`URLSearchParams` merge.

### Architecture polish

Closed two findings from a full architecture self-review: the archive
template ran two independent full course queries per page view (one for
results, one for filter options) even though they're identical whenever
no filter is selected — `FilterCriteria::isEmpty()` now lets the
template fetch once and derive both from it, only in that specific case.
Course cards (server-rendered PHP partial vs. client-rendered JS
template) never showed Providers/Instructors despite both already being
in the REST payload — added to both renderers, plus an e2e test
asserting the two field lists can't silently drift apart again.

### Production sync, SSL, and a security audit

Deployed the accumulated commits to production and removed a third
active plugin (`litespeed-cache`) found there — the brief requires no
external plugins besides ACF. Found HTTPS broken again: cPanel's
AutoSSL feature is disabled at the hosting-package level, and even a
prior support-assisted fix hadn't lasted. Fixed it durably with a
self-managed Let's Encrypt certificate (`acme.sh`, HTTP-01 webroot
validation, installed via a separate cPanel API unaffected by the
AutoSSL restriction) plus an automatic reload hook and renewal cron —
verified against the live site over real HTTPS with the actual
Playwright suite, not just curl. (`.dev` domains are hardcoded into
every major browser's HSTS-preload list, so a broken cert there means
the site is unreachable by any real visitor, not just "HTTPS down, HTTP
still works.")

A manual security audit — not part of the brief — of both the plugin
code and the live deployment's real-world exposure came back clean on
the plugin side (parameterised SQL throughout, consistent output
escaping, sanitised input, no `eval`/`exec`/`unserialize`, `composer
audit` clean), but found two real WordPress-core-default issues on the
live site: `GET /wp/v2/users` exposes every login username with no
authentication required, and the `generator` meta tag discloses the
exact WordPress version. Fixed both (`Security\CoreHardening`), verified
the fix doesn't overreach into blocking this plugin's own public
routes, and deployed to production the same way.

**Current totals:** 83 unit + 41 integration + 19 e2e tests, PHPStan
level 8 clean, three-job CI green on every push.

</details>
