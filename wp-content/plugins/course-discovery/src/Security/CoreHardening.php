<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Security;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Two small WordPress-core hardening measures this plugin doesn't need
 * any external "security plugin" for — both found via a manual audit of
 * the live deployment, not part of the brief's functional requirements:
 *
 * 1. WordPress's own REST API exposes every user's login username (as
 *    `slug`) at `GET /wp/v2/users` with no authentication required by
 *    default — a real aid to credential-stuffing/brute-force attempts
 *    against wp-login.php, since it removes the need to guess a
 *    username at all. Restricted to authenticated requests here, not
 *    removed outright, since logged-in admin screens (e.g. author
 *    dropdowns) legitimately depend on it.
 * 2. The `generator` meta tag (and equivalent output in feeds)
 *    advertises the exact WordPress version to anyone who requests the
 *    page — a fingerprinting aid for targeting version-specific
 *    exploits once one exists. Suppressed via the same `the_generator`
 *    filter core itself uses to build that output, rather than trying
 *    to strip it back out of already-rendered HTML.
 *
 * Neither touches this plugin's own `course-discovery/v1/*` REST routes
 * — those are deliberately public read-only search endpoints (see
 * REST\CourseSearchController/FilterOptionsController) and are
 * unaffected by either change.
 */
final class CoreHardening
{
    public function registerHooks(): void
    {
        add_filter('the_generator', '__return_empty_string');
        add_filter('rest_pre_dispatch', [$this, 'blockAnonymousUserEnumeration'], 10, 3);
    }

    /**
     * @param mixed $result Whatever a plugin already run earlier in this filter chain returned.
     * @return mixed
     */
    public function blockAnonymousUserEnumeration(mixed $result, WP_REST_Server $server, WP_REST_Request $request): mixed
    {
        if ($result instanceof WP_REST_Response || $result instanceof WP_Error) {
            return $result;
        }

        if (is_user_logged_in() || ! self::isUsersRoute($request->get_route())) {
            return $result;
        }

        return new WP_Error(
            'rest_forbidden',
            __('Sorry, you are not allowed to do that.', 'course-discovery'),
            ['status' => 401],
        );
    }

    /** Pure route-matching logic, unit-testable without a WordPress bootstrap. */
    public static function isUsersRoute(string $route): bool
    {
        return $route === '/wp/v2/users' || str_starts_with($route, '/wp/v2/users/');
    }
}
