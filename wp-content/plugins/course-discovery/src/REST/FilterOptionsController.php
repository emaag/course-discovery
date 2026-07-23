<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\REST;

use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use WP_REST_Response;

/**
 * GET /wp-json/course-discovery/v1/filters — available option lists for
 * each filter, derived from currently published Courses so an option with
 * no matching course never appears. This is the brief's "altering
 * available filter options" extension point: third parties hook
 * `course_discovery_filter_options` rather than editing this class.
 */
final class FilterOptionsController implements RestController
{
    public function register(): void
    {
        register_rest_route('course-discovery/v1', '/filters', [
            'methods' => 'GET',
            'callback' => [$this, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle(): WP_REST_Response
    {
        $courses = (new CourseQueryBuilder())->executeAll();

        $providers = [];
        $locations = [];
        $categories = [];
        /** @var array<string, StartDate> $startDates */
        $startDates = [];

        foreach ($courses as $course) {
            foreach ($course->providers() as $provider) {
                $providers[$provider->id()->toInt()] = [
                    'id' => $provider->id()->toInt(),
                    'name' => $provider->name(),
                ];
            }

            foreach ($course->locations() as $location) {
                $locations[$location->slug()] = [
                    'slug' => $location->slug(),
                    'name' => $location->name(),
                ];
            }

            foreach ($course->categories() as $category) {
                $categories[$category->id()] = [
                    'id' => $category->id(),
                    'name' => $category->name(),
                    'slug' => $category->slug(),
                    'parent_id' => $category->parentId(),
                ];
            }

            foreach ($course->startDates() as $date) {
                $startDates[$date->toStorageString()] = $date;
            }
        }

        usort($startDates, static fn (StartDate $a, StartDate $b): int => $a->compareTo($b));

        $options = [
            'providers' => array_values($providers),
            'locations' => array_values($locations),
            'categories' => array_values($categories),
            'start_dates' => array_map(
                static fn (StartDate $date): array => [
                    'value' => $date->toStorageString(),
                    'label' => $date->format('F Y'),
                ],
                array_values($startDates),
            ),
        ];

        /**
         * Alter the available filter options before they're returned —
         * e.g. reorder Providers, hide a Category, inject an option list
         * for a third-party filter — without editing this controller.
         *
         * @param array<string, list<array<string, mixed>>> $options
         */
        $options = apply_filters('course_discovery_filter_options', $options);

        return new WP_REST_Response($options, 200);
    }
}
