<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Security;

use OxfordInternational\CourseDiscovery\Security\CoreHardening;
use PHPUnit\Framework\TestCase;

final class CoreHardeningTest extends TestCase
{
    public function test_matches_the_users_collection_route(): void
    {
        self::assertTrue(CoreHardening::isUsersRoute('/wp/v2/users'));
    }

    public function test_matches_a_single_user_route(): void
    {
        self::assertTrue(CoreHardening::isUsersRoute('/wp/v2/users/1'));
    }

    /** @return iterable<string, array{string}> */
    public static function nonUsersRouteProvider(): iterable
    {
        yield 'this plugin\'s own courses route' => ['/course-discovery/v1/courses'];
        yield 'this plugin\'s own filters route' => ['/course-discovery/v1/filters'];
        yield 'a route that merely contains "users" elsewhere' => ['/wp/v2/course-discovery-users'];
        yield 'a different core collection' => ['/wp/v2/posts'];
    }

    /** @dataProvider nonUsersRouteProvider */
    public function test_does_not_match_other_routes(string $route): void
    {
        self::assertFalse(CoreHardening::isUsersRoute($route));
    }
}
