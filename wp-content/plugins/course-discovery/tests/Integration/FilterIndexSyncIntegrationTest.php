<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use OxfordInternational\CourseDiscovery\Migration\FilterIndexTables;
use WP_UnitTestCase;

/**
 * Proves the migration actually created the tables and that saving/
 * deleting a real Course keeps them in sync via the real save_post_course
 * / before_delete_post hooks — not just the pure row-computation already
 * covered by tests/Unit/Migration/FilterIndexSyncTest.
 */
final class FilterIndexSyncIntegrationTest extends WP_UnitTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_the_migration_created_both_tables(): void
    {
        global $wpdb;

        $providersTable = FilterIndexTables::providers();
        $startDatesTable = FilterIndexTables::startDates();

        self::assertSame($providersTable, $wpdb->get_var("SHOW TABLES LIKE '{$providersTable}'"));
        self::assertSame($startDatesTable, $wpdb->get_var("SHOW TABLES LIKE '{$startDatesTable}'"));
    }

    public function test_publishing_a_course_populates_both_tables(): void
    {
        global $wpdb;

        $providerId = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'Provider']);
        update_field('location', 'Japan', $providerId);

        $courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'Synced Course',
            'post_status' => 'publish',
        ]);
        update_field('providers', [$providerId], $courseId);
        update_field('start_dates', [['start_date' => '09-2026']], $courseId);

        // update_field() alone doesn't fire save_post_course; simulate a real save.
        wp_update_post(['ID' => $courseId, 'post_title' => 'Synced Course']);

        $providerRows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM " . FilterIndexTables::providers() . ' WHERE course_id = %d', $courseId),
            ARRAY_A,
        );
        $startDateRows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM " . FilterIndexTables::startDates() . ' WHERE course_id = %d', $courseId),
            ARRAY_A,
        );

        self::assertCount(1, $providerRows);
        self::assertSame((string) $providerId, $providerRows[0]['provider_id']);
        self::assertSame('japan', $providerRows[0]['location_slug']);

        self::assertCount(1, $startDateRows);
        self::assertSame('09-2026', $startDateRows[0]['start_date']);
    }

    public function test_resaving_a_course_replaces_its_rows_rather_than_accumulating(): void
    {
        global $wpdb;

        $providerId = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'Provider']);
        update_field('location', 'Brazil', $providerId);

        $courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'Re-saved Course',
            'post_status' => 'publish',
        ]);
        update_field('providers', [$providerId], $courseId);
        update_field('start_dates', [['start_date' => '09-2026']], $courseId);
        wp_update_post(['ID' => $courseId, 'post_title' => 'Re-saved Course']);

        // Re-save with an additional start date.
        update_field('start_dates', [['start_date' => '09-2026'], ['start_date' => '01-2027']], $courseId);
        wp_update_post(['ID' => $courseId, 'post_title' => 'Re-saved Course v2']);

        $startDateRows = $wpdb->get_results(
            $wpdb->prepare("SELECT start_date FROM " . FilterIndexTables::startDates() . ' WHERE course_id = %d', $courseId),
            ARRAY_A,
        );

        self::assertCount(2, $startDateRows, 'Re-saving should replace rows, not accumulate duplicates.');
    }

    public function test_deleting_a_course_removes_its_rows(): void
    {
        global $wpdb;

        $courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'Doomed Course',
            'post_status' => 'publish',
        ]);
        update_field('start_dates', [['start_date' => '09-2026']], $courseId);
        wp_update_post(['ID' => $courseId, 'post_title' => 'Doomed Course']);

        wp_delete_post($courseId, true);

        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM " . FilterIndexTables::startDates() . ' WHERE course_id = %d', $courseId),
        );

        self::assertSame('0', $count);
    }

    public function test_unpublishing_a_course_removes_its_rows(): void
    {
        global $wpdb;

        $courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'Draft-Bound Course',
            'post_status' => 'publish',
        ]);
        update_field('start_dates', [['start_date' => '09-2026']], $courseId);
        wp_update_post(['ID' => $courseId, 'post_title' => 'Draft-Bound Course']);

        wp_update_post(['ID' => $courseId, 'post_status' => 'draft']);

        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM " . FilterIndexTables::startDates() . ' WHERE course_id = %d', $courseId),
        );

        self::assertSame('0', $count);
    }
}
