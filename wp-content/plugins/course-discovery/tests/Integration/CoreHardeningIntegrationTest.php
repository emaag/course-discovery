<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Proves the two hardening measures from Security\CoreHardening actually
 * change real behaviour through the real REST server and wp_head output —
 * not just that the class's own logic is internally consistent (see
 * tests/Unit/Security/CoreHardeningTest.php for that) — and, just as
 * importantly, that neither one accidentally overreaches into blocking
 * this plugin's own public REST routes.
 */
final class CoreHardeningIntegrationTest extends WP_UnitTestCase
{
    public function tearDown(): void
    {
        wp_set_current_user(0);
        parent::tearDown();
    }

    public function test_an_anonymous_request_to_wp_users_is_rejected(): void
    {
        $request = new WP_REST_Request('GET', '/wp/v2/users');
        $response = rest_get_server()->dispatch($request);

        self::assertSame(401, $response->get_status());
    }

    public function test_a_logged_in_administrator_can_still_list_users(): void
    {
        $adminId = $this->factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($adminId);

        $request = new WP_REST_Request('GET', '/wp/v2/users');
        $response = rest_get_server()->dispatch($request);

        self::assertSame(200, $response->get_status());
    }

    public function test_this_plugins_own_public_routes_are_unaffected(): void
    {
        $request = new WP_REST_Request('GET', '/course-discovery/v1/courses');
        $response = rest_get_server()->dispatch($request);

        self::assertSame(200, $response->get_status());

        $filtersRequest = new WP_REST_Request('GET', '/course-discovery/v1/filters');
        self::assertSame(200, rest_get_server()->dispatch($filtersRequest)->get_status());
    }

    public function test_the_generator_tag_is_suppressed(): void
    {
        self::assertSame('', apply_filters('the_generator', '<meta name="generator" content="WordPress 7.0.2">', 'html'));
    }
}
