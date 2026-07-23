<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Query;

use WP_Query;

/**
 * Widens WP_Query's search to also match the `short_description` ACF
 * field, not just post_title/post_content — WP's own `s` query var only
 * ever checks title/content, which would silently miss the brief's
 * "matched against name, short description and long description"
 * requirement. Scoped entirely to queries that explicitly opt in via the
 * `course_discovery_search_term` query var, so this never affects normal
 * WordPress search.
 */
final class CourseSearchClause
{
    private static bool $registered = false;

    public static function registerHooks(): void
    {
        if (self::$registered || ! function_exists('add_filter')) {
            return;
        }

        add_filter('posts_join', [self::class, 'join'], 10, 2);
        add_filter('posts_where', [self::class, 'where'], 10, 2);

        self::$registered = true;
    }

    public static function join(string $join, WP_Query $query): string
    {
        $term = $query->get('course_discovery_search_term');

        if (! is_string($term) || $term === '') {
            return $join;
        }

        global $wpdb;

        return $join . " LEFT JOIN {$wpdb->postmeta} AS course_discovery_short_desc"
            . " ON ({$wpdb->posts}.ID = course_discovery_short_desc.post_id"
            . " AND course_discovery_short_desc.meta_key = 'short_description')";
    }

    public static function where(string $where, WP_Query $query): string
    {
        $term = $query->get('course_discovery_search_term');

        if (! is_string($term) || $term === '') {
            return $where;
        }

        global $wpdb;

        $like = '%' . $wpdb->esc_like($term) . '%';

        return $where . $wpdb->prepare(
            " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR course_discovery_short_desc.meta_value LIKE %s)",
            $like,
            $like,
            $like,
        );
    }
}
