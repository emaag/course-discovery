<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Migration;

/**
 * Creates two indexed lookup tables for the two filter dimensions that
 * currently can't be queried via WP_Query's meta_query at all — ACF
 * stores `providers` and `start_dates` as a single serialized value per
 * post (see Filter\ProviderFilter's docblock) — one row per course per
 * value, rather than one flat course_category-style tax_query.
 *
 * Deliberately two focused junction tables, not one wide table crossing
 * every dimension together: a course with 2 providers and 3 start dates
 * would need 6 cross-product rows for one flat table but only 5 rows
 * split across two (2 + 3) — and each table stays a simple, single-
 * purpose index.
 *
 * Categories aren't duplicated here — `course_category` is a real
 * WordPress taxonomy already backed by `wp_term_relationships`, an
 * existing indexed join, so there's nothing to denormalise for it.
 *
 * Not wired into CourseQueryBuilder yet: the current in-PHP-predicate
 * approach is simpler, already thoroughly tested, and correct at this
 * project's scale. These tables exist, and are kept live via
 * FilterIndexSync, as the documented Performance & Scalability
 * evolution path — ready to become the query source once meta/predicate
 * filtering stops being fast enough, without a risky "build the index
 * and cut over in the same change" step.
 */
final class CreateFilterIndexTables implements Migration
{
    public function version(): string
    {
        return '2026_07_23_001_create_filter_index_tables';
    }

    public function run(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();
        $providersTable = FilterIndexTables::providers();
        $startDatesTable = FilterIndexTables::startDates();

        dbDelta("CREATE TABLE {$providersTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            course_id BIGINT UNSIGNED NOT NULL,
            provider_id BIGINT UNSIGNED NOT NULL,
            location_slug VARCHAR(200) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY course_id (course_id),
            KEY provider_id (provider_id),
            KEY location_slug (location_slug)
        ) {$charsetCollate};");

        dbDelta("CREATE TABLE {$startDatesTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            course_id BIGINT UNSIGNED NOT NULL,
            start_date VARCHAR(7) NOT NULL,
            PRIMARY KEY  (id),
            KEY course_id (course_id),
            KEY start_date (start_date)
        ) {$charsetCollate};");
    }
}
