<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\REST;

use OxfordInternational\CourseDiscovery\Query\FilterOptionsProvider;
use WP_REST_Response;

/**
 * GET /wp-json/course-discovery/v1/filters — available option lists for
 * each filter. Thin wrapper around FilterOptionsProvider, which is also
 * used directly by the server-rendered archive template so both agree.
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
        return new WP_REST_Response((new FilterOptionsProvider())->compute(), 200);
    }
}
