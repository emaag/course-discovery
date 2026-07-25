<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Admin;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Domain\Model\Provider;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Location;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;
use WP_Post;
use WP_Query;

/**
 * Adds Price/Providers/Locations/Start Dates to the wp-admin Course list
 * table, reusing Course::fromPost() rather than reading ACF fields ad hoc —
 * so this view can't drift out of sync with what the REST API and frontend
 * show for the same course.
 */
final class CourseListTable
{
    private const SORTABLE_COLUMN = 'course_price';

    public function registerHooks(): void
    {
        add_filter('manage_edit-course_columns', [$this, 'columns']);
        add_action('manage_course_posts_custom_column', [$this, 'renderColumn'], 10, 2);
        add_filter('manage_edit-course_sortable_columns', [$this, 'sortableColumns']);
        add_action('pre_get_posts', [$this, 'sortByPrice']);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function columns(array $columns): array
    {
        $date = $columns['date'] ?? __('Date', 'course-discovery');
        unset($columns['date']);

        return array_merge($columns, [
            'course_price' => __('Price', 'course-discovery'),
            'course_providers' => __('Providers', 'course-discovery'),
            'course_locations' => __('Locations', 'course-discovery'),
            'course_start_dates' => __('Start Dates', 'course-discovery'),
            'date' => $date,
        ]);
    }

    public function renderColumn(string $column, int $postId): void
    {
        $post = get_post($postId);

        if (! $post instanceof WP_Post || $post->post_type !== 'course') {
            return;
        }

        $course = Course::fromPost($post);

        $output = match ($column) {
            'course_price' => $course->price()->format(),
            'course_providers' => $this->providerNames($course->providers()),
            'course_locations' => $this->locationNames($course->locations()),
            'course_start_dates' => $this->startDateList($course->startDates()),
            default => null,
        };

        if ($output !== null) {
            echo esc_html($output);
        }
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function sortableColumns(array $columns): array
    {
        $columns[self::SORTABLE_COLUMN] = self::SORTABLE_COLUMN;

        return $columns;
    }

    /**
     * `price` is a plain ACF number field stored in postmeta, so sorting by
     * it needs an explicit numeric meta sort — WordPress won't infer that
     * from the column key alone, and a non-numeric meta sort would order
     * "9" after "10" as strings.
     */
    public function sortByPrice(WP_Query $query): void
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        if ($query->get('orderby') !== self::SORTABLE_COLUMN) {
            return;
        }

        $query->set('meta_key', 'price');
        $query->set('orderby', 'meta_value_num');
    }

    /** @param list<Provider> $providers */
    private function providerNames(array $providers): string
    {
        if ($providers === []) {
            return '—';
        }

        return implode(', ', array_map(static fn (Provider $provider): string => $provider->name(), $providers));
    }

    /** @param list<Location> $locations */
    private function locationNames(array $locations): string
    {
        if ($locations === []) {
            return '—';
        }

        return implode(', ', array_map(static fn (Location $location): string => $location->name(), $locations));
    }

    /** @param list<StartDate> $startDates */
    private function startDateList(array $startDates): string
    {
        if ($startDates === []) {
            return '—';
        }

        return implode(', ', array_map(static fn (StartDate $date): string => $date->format('M Y'), $startDates));
    }
}
