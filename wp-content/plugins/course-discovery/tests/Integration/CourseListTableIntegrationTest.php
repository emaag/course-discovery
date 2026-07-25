<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use OxfordInternational\CourseDiscovery\Admin\CourseListTable;
use WP_Query;
use WP_UnitTestCase;

/**
 * Exercises the wp-admin Course list table columns against real WP_Post/ACF
 * data — the exact gap flagged in review: the admin dashboard previously
 * showed only title/date, nothing an admin would need to identify a course
 * at a glance (price, providers, locations, start dates).
 */
final class CourseListTableIntegrationTest extends WP_UnitTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_columns_adds_the_new_columns_and_keeps_date_last(): void
    {
        $columns = (new CourseListTable())->columns([
            'cb' => '<input type="checkbox" />',
            'title' => 'Title',
            'date' => 'Date',
        ]);

        $keys = array_keys($columns);

        self::assertSame(
            ['cb', 'title', 'course_price', 'course_providers', 'course_locations', 'course_start_dates', 'date'],
            $keys,
            'New columns should sit between Title and Date, not push Date out of its usual trailing position.',
        );
    }

    public function test_render_column_outputs_price_providers_locations_and_start_dates(): void
    {
        $providerId = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'Test Provider']);
        update_field('location', 'Canada', $providerId);

        $courseId = $this->create_course('List Table Course');
        update_field('price', 249.5, $courseId);
        update_field('providers', [$providerId], $courseId);
        update_field('start_dates', [['start_date' => '09-2026'], ['start_date' => '01-2026']], $courseId);

        $listTable = new CourseListTable();

        self::assertSame('GBP 249.50', $this->render($listTable, 'course_price', $courseId));
        self::assertSame('Test Provider', $this->render($listTable, 'course_providers', $courseId));
        self::assertSame('Canada', $this->render($listTable, 'course_locations', $courseId));
        self::assertSame(
            'Jan 2026, Sep 2026',
            $this->render($listTable, 'course_start_dates', $courseId),
            'Start dates must render chronologically regardless of entry order.',
        );
    }

    public function test_render_column_shows_a_placeholder_when_a_course_has_no_providers(): void
    {
        $courseId = $this->create_course('No Providers Course');

        self::assertSame('—', $this->render(new CourseListTable(), 'course_providers', $courseId));
        self::assertSame('—', $this->render(new CourseListTable(), 'course_locations', $courseId));
        self::assertSame('—', $this->render(new CourseListTable(), 'course_start_dates', $courseId));
    }

    public function test_sortable_columns_registers_course_price(): void
    {
        $columns = (new CourseListTable())->sortableColumns([]);

        self::assertSame(['course_price' => 'course_price'], $columns);
    }

    public function test_sort_by_price_orders_courses_numerically_not_lexically(): void
    {
        require_once ABSPATH . 'wp-admin/includes/screen.php';
        set_current_screen('edit-course');

        (new CourseListTable())->registerHooks();

        $cheap = $this->create_course('Cheap Course');
        update_field('price', 9.0, $cheap);

        $expensive = $this->create_course('Expensive Course');
        update_field('price', 10.0, $expensive);

        $query = new WP_Query([
            'post_type' => 'course',
            'orderby' => 'course_price',
            'order' => 'ASC',
        ]);

        self::assertSame(
            [$cheap, $expensive],
            wp_list_pluck($query->posts, 'ID'),
            'A numeric meta sort must not order "9" after "10" as it would with a plain string sort.',
        );
    }

    private function render(CourseListTable $listTable, string $column, int $postId): string
    {
        ob_start();
        $listTable->renderColumn($column, $postId);

        return ob_get_clean();
    }

    private function create_course(string $title): int
    {
        return $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => $title,
            'post_status' => 'publish',
        ]);
    }
}
