<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

/** Exercises the registered REST routes through the real WP_REST_Server dispatch, not just direct PHP calls. */
final class RestEndpointIntegrationTest extends WP_UnitTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_courses_endpoint_returns_200_with_the_expected_json_shape(): void
    {
        $this->create_course('Shape Check Course');

        $request = new WP_REST_Request('GET', '/course-discovery/v1/courses');
        $response = rest_get_server()->dispatch($request);

        self::assertSame(200, $response->get_status());
        $data = $response->get_data();

        self::assertArrayHasKey('courses', $data);
        self::assertArrayHasKey('pagination', $data);
        self::assertArrayHasKey('total', $data['pagination']);
        self::assertArrayHasKey('total_pages', $data['pagination']);
        self::assertArrayHasKey('page', $data['pagination']);
        self::assertArrayHasKey('per_page', $data['pagination']);

        $course = $data['courses'][0];
        foreach (['id', 'name', 'short_description', 'long_description', 'price', 'instructors', 'providers', 'locations', 'start_dates', 'categories'] as $key) {
            self::assertArrayHasKey($key, $course);
        }
    }

    public function test_courses_endpoint_paginates_real_results(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->create_course("Course {$i}");
        }

        $request = new WP_REST_Request('GET', '/course-discovery/v1/courses');
        $request->set_param('per_page', 2);
        $response = rest_get_server()->dispatch($request);

        self::assertSame(200, $response->get_status());
        $data = $response->get_data();
        self::assertSame(3, $data['pagination']['total']);
        self::assertSame(2, $data['pagination']['total_pages']);
        self::assertCount(2, $data['courses']);

        $request2 = new WP_REST_Request('GET', '/course-discovery/v1/courses');
        $request2->set_param('per_page', 2);
        $request2->set_param('page', 2);
        $data2 = rest_get_server()->dispatch($request2)->get_data();

        self::assertCount(1, $data2['courses']);
        self::assertSame(2, $data2['pagination']['page']);
    }

    public function test_courses_endpoint_applies_category_filter(): void
    {
        $term = wp_insert_term('Finance', 'course_category');
        $matchingId = $this->create_course('Finance Course');
        wp_set_object_terms($matchingId, [$term['term_id']], 'course_category');

        $this->create_course('Unrelated Course');

        $request = new WP_REST_Request('GET', '/course-discovery/v1/courses');
        $request->set_param('categories', [$term['term_id']]);
        $response = rest_get_server()->dispatch($request);
        $data = $response->get_data();

        self::assertSame(1, $data['pagination']['total']);
        self::assertSame('Finance Course', $data['courses'][0]['name']);
    }

    public function test_filters_endpoint_returns_option_lists_from_real_data(): void
    {
        $providerId = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'A Provider']);
        update_field('location', 'Germany', $providerId);

        $courseId = $this->create_course('Course');
        update_field('providers', [$providerId], $courseId);

        $request = new WP_REST_Request('GET', '/course-discovery/v1/filters');
        $response = rest_get_server()->dispatch($request);

        self::assertSame(200, $response->get_status());
        $data = $response->get_data();
        foreach (['providers', 'locations', 'categories', 'start_dates'] as $key) {
            self::assertArrayHasKey($key, $data);
        }
        self::assertSame(['germany'], array_map(static fn (array $l): string => $l['slug'], $data['locations']));
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
