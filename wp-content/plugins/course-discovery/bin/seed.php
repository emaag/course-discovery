<?php

/**
 * Dummy data seeder for local/demo environments.
 *
 * Run from inside the WordPress container (needs the WP + ACF runtime):
 *
 *   docker compose exec wordpress php wp-content/plugins/course-discovery/bin/seed.php
 *
 * Regenerates a fixed, realistic Provider/Instructor/Category/Course
 * dataset. Safe to re-run: everything it previously created is tagged with
 * a `_course_discovery_seed` post meta flag and purged before reseeding, so
 * repeated runs don't pile up duplicates. Never touches content that
 * wasn't created by this script.
 *
 * This intentionally regenerates real WordPress content via wp_insert_post
 * rather than shipping a `.sql` dump, so the exact same dataset can be
 * reproduced on any environment (local, or eventually the public
 * deployment) without transferring a database file.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('This script must be run from the command line.' . PHP_EOL);
}

$wpLoad = dirname(__DIR__, 4) . '/wp-load.php';

if (! file_exists($wpLoad)) {
    exit(sprintf('Could not find wp-load.php at expected path: %s%s', $wpLoad, PHP_EOL));
}

require_once $wpLoad;
require_once dirname(__DIR__) . '/vendor/autoload.php';

use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;

if (! function_exists('acf_add_local_field_group')) {
    exit('Advanced Custom Fields must be installed and active before seeding.' . PHP_EOL);
}

const SEED_FLAG = '_course_discovery_seed';

/** @return list<string> */
function seeded_post_types(): array
{
    return ['course', 'instructor', 'provider'];
}

function purge_previous_seed(): void
{
    $query = new WP_Query([
        'post_type' => seeded_post_types(),
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => SEED_FLAG,
    ]);

    foreach ($query->posts as $postId) {
        wp_delete_post((int) $postId, true);
    }

    printf('Purged %d previously seeded post(s).%s', count($query->posts), PHP_EOL);
}

function find_or_create_term(string $name, string $taxonomy, ?int $parentId = 0): int
{
    $existing = get_term_by('name', $name, $taxonomy);

    if ($existing instanceof WP_Term) {
        return (int) $existing->term_id;
    }

    $result = wp_insert_term($name, $taxonomy, ['parent' => $parentId ?? 0]);

    if (is_wp_error($result)) {
        exit(sprintf('Failed to create term "%s": %s%s', $name, $result->get_error_message(), PHP_EOL));
    }

    return (int) $result['term_id'];
}

function create_seeded_post(string $postType, string $title, string $content): int
{
    $postId = wp_insert_post([
        'post_type' => $postType,
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($postId)) {
        exit(sprintf('Failed to create %s "%s": %s%s', $postType, $title, $postId->get_error_message(), PHP_EOL));
    }

    update_post_meta($postId, SEED_FLAG, 1);

    return (int) $postId;
}

/** @param list<string> $dates {month}-{year} strings, validated via the domain value object before storage. */
function set_start_dates(int $courseId, array $dates): void
{
    $rows = array_map(static function (string $date): array {
        StartDate::fromString($date); // throws if malformed — fail loudly rather than seed bad data

        return ['start_date' => $date];
    }, $dates);

    update_field('start_dates', $rows, $courseId);
}

// --- Categories (hierarchical) ---------------------------------------------

$categoryTree = [
    'Design' => ['Graphic Design', 'UX Design'],
    'Technology' => ['Web Development', 'Data Science'],
    'Business' => ['Marketing', 'Finance'],
];

purge_previous_seed();

$categoryIds = [];

foreach ($categoryTree as $parentName => $children) {
    $parentId = find_or_create_term($parentName, 'course_category');
    $categoryIds[$parentName] = $parentId;

    foreach ($children as $childName) {
        $categoryIds[$childName] = find_or_create_term($childName, 'course_category', $parentId);
    }
}

// --- Providers ---------------------------------------------------------------

$providers = [
    'University of Oxford Digital' => 'United Kingdom',
    'Dubai Management School' => 'United Arab Emirates',
    'Shanghai Institute of Technology' => 'China',
    'Mumbai School of Design' => 'India',
    'Toronto Business Academy' => 'Canada',
    'Berlin Tech Academy' => 'Germany',
];

$providerIds = [];

foreach ($providers as $name => $location) {
    $id = create_seeded_post('provider', $name, sprintf('%s is a course provider based in %s.', $name, $location));
    update_field('location', $location, $id);
    $providerIds[$name] = $id;
}

// --- Instructors ---------------------------------------------------------------

$instructors = [
    'Dr. Amara Okafor' => 'Specialist in accessible and inclusive design practice.',
    'James Whitfield' => 'User experience researcher with a focus on enterprise software.',
    'Priya Sharma' => 'Award-winning graphic designer and brand strategist.',
    'Wei Zhang' => 'Full-stack engineer and cloud infrastructure consultant.',
    'Sofia Rossi' => 'Data scientist working on applied machine learning.',
    'Liam O\'Connor' => 'Chartered accountant and corporate finance lecturer.',
    'Fatima Al-Sayed' => 'Digital marketing strategist for global brands.',
    'Noah Kim' => 'Typography and editorial design practitioner.',
];

$instructorIds = [];

foreach ($instructors as $name => $bio) {
    $instructorIds[$name] = create_seeded_post('instructor', $name, $bio);
}

// --- Courses ---------------------------------------------------------------

/**
 * @var list<array{
 *     name: string,
 *     short: string,
 *     long: string,
 *     price: float,
 *     categories: list<string>,
 *     providers: list<string>,
 *     instructors: list<string>,
 *     dates: list<string>,
 * }>
 */
$courses = [
    [
        'name' => 'Foundations of Graphic Design',
        'short' => 'Core principles of visual design for print and digital media.',
        'long' => 'A hands-on introduction to layout, colour theory and typography, building the foundation for a career in graphic design.',
        'price' => 350.00,
        'categories' => ['Graphic Design'],
        'providers' => ['Mumbai School of Design'],
        'instructors' => ['Priya Sharma'],
        'dates' => ['09-2026', '01-2027'],
    ],
    [
        'name' => 'Advanced UX Research Methods',
        'short' => 'Rigorous qualitative and quantitative research techniques for UX teams.',
        'long' => 'Covers usability testing, contextual inquiry and survey design, with an emphasis on translating findings into product decisions.',
        'price' => 799.00,
        'categories' => ['UX Design'],
        'providers' => ['University of Oxford Digital'],
        'instructors' => ['James Whitfield'],
        'dates' => ['10-2026'],
    ],
    [
        'name' => 'Full-Stack Web Development Bootcamp',
        'short' => 'An intensive bootcamp covering modern frontend and backend development.',
        'long' => 'Students build and deploy full-stack applications using contemporary JavaScript tooling, REST APIs and cloud hosting.',
        'price' => 1499.00,
        'categories' => ['Web Development'],
        'providers' => ['Berlin Tech Academy'],
        'instructors' => ['Wei Zhang'],
        'dates' => ['09-2026', '03-2027'],
    ],
    [
        'name' => 'Data Science with Python',
        'short' => 'Practical data analysis, visualisation and modelling with Python.',
        'long' => 'From pandas and data cleaning through to predictive modelling, taught jointly across two partner institutions.',
        'price' => 1299.00,
        'categories' => ['Data Science'],
        'providers' => ['Shanghai Institute of Technology', 'Berlin Tech Academy'],
        'instructors' => ['Wei Zhang', 'Sofia Rossi'],
        'dates' => ['10-2026', '06-2027'],
    ],
    [
        'name' => 'Digital Marketing Strategy',
        'short' => 'Building and measuring multi-channel digital marketing campaigns.',
        'long' => 'Covers SEO, paid acquisition, content strategy and campaign analytics for modern marketing teams.',
        'price' => 599.00,
        'categories' => ['Marketing'],
        'providers' => ['Dubai Management School'],
        'instructors' => ['Fatima Al-Sayed'],
        'dates' => ['09-2026'],
    ],
    [
        'name' => 'Corporate Finance Essentials',
        'short' => 'Financial statement analysis and capital budgeting fundamentals.',
        'long' => 'An essentials course in corporate finance for professionals moving into finance-adjacent roles.',
        'price' => 899.00,
        'categories' => ['Finance'],
        'providers' => ['Toronto Business Academy'],
        'instructors' => ['Liam O\'Connor'],
        'dates' => ['01-2027'],
    ],
    [
        'name' => 'Brand Identity & Visual Systems',
        'short' => 'Designing cohesive brand identity systems across touchpoints.',
        'long' => 'A studio-style course building a complete brand identity system, from logo design through to a full style guide.',
        'price' => 450.00,
        'categories' => ['Graphic Design'],
        'providers' => ['Mumbai School of Design', 'University of Oxford Digital'],
        'instructors' => ['Priya Sharma', 'James Whitfield'],
        'dates' => ['10-2026', '01-2027'],
    ],
    [
        'name' => 'User Interface Prototyping',
        'short' => 'Rapid prototyping techniques for high-fidelity interface design.',
        'long' => 'Focuses on interactive prototyping tools and techniques for communicating design intent to engineering teams.',
        'price' => 650.00,
        'categories' => ['UX Design'],
        'providers' => ['University of Oxford Digital'],
        'instructors' => ['James Whitfield'],
        'dates' => ['03-2027'],
    ],
    [
        'name' => 'Modern JavaScript Frameworks',
        'short' => 'A comparative deep dive into current frontend frameworks.',
        'long' => 'Covers component architecture, state management and performance optimisation across modern JavaScript frameworks.',
        'price' => 999.00,
        'categories' => ['Web Development'],
        'providers' => ['Berlin Tech Academy'],
        'instructors' => ['Wei Zhang'],
        'dates' => ['09-2026'],
    ],
    [
        'name' => 'Machine Learning Fundamentals',
        'short' => 'An introduction to supervised and unsupervised learning.',
        'long' => 'Builds practical intuition for core machine learning algorithms and their real-world applications.',
        'price' => 1599.00,
        'categories' => ['Data Science'],
        'providers' => ['Shanghai Institute of Technology'],
        'instructors' => ['Sofia Rossi'],
        'dates' => ['06-2027'],
    ],
    [
        'name' => 'Social Media Marketing Mastery',
        'short' => 'Platform-specific strategy for organic and paid social media.',
        'long' => 'A practical course on building and measuring social media campaigns across major platforms.',
        'price' => 399.00,
        'categories' => ['Marketing'],
        'providers' => ['Dubai Management School'],
        'instructors' => ['Fatima Al-Sayed'],
        'dates' => ['10-2026', '03-2027'],
    ],
    [
        'name' => 'Investment Analysis & Portfolio Management',
        'short' => 'Valuation techniques and portfolio construction principles.',
        'long' => 'Covers equity valuation, risk management and portfolio construction for aspiring investment professionals.',
        'price' => 1099.00,
        'categories' => ['Finance'],
        'providers' => ['Toronto Business Academy'],
        'instructors' => ['Liam O\'Connor'],
        'dates' => ['01-2027'],
    ],
    [
        'name' => 'Typography & Layout Design',
        'short' => 'Typographic craft and editorial layout for print and screen.',
        'long' => 'An in-depth exploration of type systems, grids and layout composition for editorial and digital design.',
        'price' => 299.00,
        'categories' => ['Graphic Design'],
        'providers' => ['Mumbai School of Design'],
        'instructors' => ['Noah Kim'],
        'dates' => ['09-2026'],
    ],
    [
        'name' => 'Accessible Design Principles',
        'short' => 'Designing inclusive digital experiences for all users.',
        'long' => 'Covers WCAG guidelines, assistive technology and inclusive design patterns for real-world products.',
        'price' => 549.00,
        'categories' => ['UX Design'],
        'providers' => ['University of Oxford Digital'],
        'instructors' => ['Dr. Amara Okafor'],
        'dates' => ['10-2026'],
    ],
    [
        'name' => 'Cloud Infrastructure & DevOps',
        'short' => 'Provisioning, deploying and scaling cloud infrastructure.',
        'long' => 'A practical course in infrastructure-as-code, CI/CD pipelines and container orchestration.',
        'price' => 1799.00,
        'categories' => ['Web Development'],
        'providers' => ['Berlin Tech Academy', 'Shanghai Institute of Technology'],
        'instructors' => ['Wei Zhang'],
        'dates' => ['03-2027', '06-2027'],
    ],
    [
        'name' => 'Entrepreneurial Finance for Startups',
        'short' => 'Financial planning and fundraising for early-stage startups.',
        'long' => 'Covers financial modelling, fundraising strategy and cash flow management for founders and early finance hires.',
        'price' => 749.00,
        'categories' => ['Finance'],
        'providers' => ['Toronto Business Academy', 'Dubai Management School'],
        'instructors' => ['Liam O\'Connor', 'Fatima Al-Sayed'],
        'dates' => ['09-2026'],
    ],
];

foreach ($courses as $course) {
    $courseId = create_seeded_post('course', $course['name'], $course['long']);

    update_field('short_description', $course['short'], $courseId);
    update_field('price', $course['price'], $courseId);

    update_field('instructors', array_map(
        static fn (string $name): int => $instructorIds[$name],
        $course['instructors'],
    ), $courseId);

    update_field('providers', array_map(
        static fn (string $name): int => $providerIds[$name],
        $course['providers'],
    ), $courseId);

    set_start_dates($courseId, $course['dates']);

    wp_set_object_terms($courseId, array_map(
        static fn (string $name): int => $categoryIds[$name],
        $course['categories'],
    ), 'course_category');
}

printf(
    'Seeded %d categor%s, %d provider(s), %d instructor(s), %d course(s).%s',
    count($categoryIds),
    count($categoryIds) === 1 ? 'y' : 'ies',
    count($providerIds),
    count($instructorIds),
    count($courses),
    PHP_EOL,
);
