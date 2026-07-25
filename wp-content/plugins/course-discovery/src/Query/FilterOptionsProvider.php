<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Query;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;

/**
 * Computes available filter option lists from currently published
 * Courses — shared by the REST /filters endpoint and the server-rendered
 * archive template, so both present exactly the same options without
 * duplicating the derivation logic. An option with no matching course
 * never appears.
 */
final class FilterOptionsProvider
{
    /**
     * @param list<Course>|null $courses Every published Course to derive
     *     options from. When omitted, fetches them itself — the correct
     *     default for a standalone call (e.g. the REST /filters
     *     endpoint). Callers that already hold this exact list for
     *     another reason in the same request (the archive template,
     *     when no filter is selected — see FilterCriteria::isEmpty())
     *     should pass it directly instead, to avoid running the same
     *     WP_Query and Course hydration twice.
     * @return array<string, list<array<string, mixed>>>
     */
    public function compute(?array $courses = null): array
    {
        $courses ??= (new CourseQueryBuilder())->executeAll();

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
                $startDates,
            ),
        ];

        /**
         * Alter the available filter options before they're returned —
         * e.g. reorder Providers, hide a Category, inject an option list
         * for a third-party filter — without editing this class.
         *
         * @param array<string, list<array<string, mixed>>> $options
         */
        return function_exists('apply_filters')
            ? apply_filters('course_discovery_filter_options', $options)
            : $options;
    }
}
