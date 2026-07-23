<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Migration;

/** Table names shared between the migration that creates them and the sync that keeps them populated. */
final class FilterIndexTables
{
    public static function providers(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'course_discovery_course_providers';
    }

    public static function startDates(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'course_discovery_course_start_dates';
    }
}
