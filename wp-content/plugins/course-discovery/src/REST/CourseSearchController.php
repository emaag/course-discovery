<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\REST;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\FilterPipeline;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use WP_REST_Request;
use WP_REST_Response;

/** GET /wp-json/course-discovery/v1/courses — filtered, paginated course search. */
final class CourseSearchController implements RestController
{
    public function register(): void
    {
        register_rest_route('course-discovery/v1', '/courses', [
            'methods' => 'GET',
            'callback' => [$this, 'handle'],
            'permission_callback' => '__return_true',
            'args' => [
                'search' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'providers' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                ],
                'locations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'start_dates' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'categories' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
        ]);
    }

    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $criteria = FilterCriteria::fromArray([
            'search' => $request->get_param('search'),
            'providers' => $request->get_param('providers') ?? [],
            'locations' => $request->get_param('locations') ?? [],
            'start_dates' => $request->get_param('start_dates') ?? [],
            'categories' => $request->get_param('categories') ?? [],
        ]);

        $builder = (new CourseQueryBuilder())
            ->setPage((int) ($request->get_param('page') ?? 1))
            ->setPerPage((int) ($request->get_param('per_page') ?? 10));

        (new FilterPipeline())->apply($builder, $criteria);

        $result = $builder->execute();
        $transformer = new CourseTransformer();

        return new WP_REST_Response([
            'courses' => array_map($transformer->toArray(...), $result->courses()),
            'pagination' => [
                'total' => $result->total(),
                'total_pages' => $result->totalPages(),
                'page' => $result->page(),
                'per_page' => $result->perPage(),
            ],
        ], 200);
    }
}
