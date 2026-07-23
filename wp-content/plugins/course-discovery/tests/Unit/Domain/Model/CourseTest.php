<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Domain\Model;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Domain\Model\Provider;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Location;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\PostId;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Price;
use PHPUnit\Framework\TestCase;

final class CourseTest extends TestCase
{
    public function test_locations_are_derived_from_providers_and_deduplicated(): void
    {
        $providerInIndia = new Provider(new PostId(1), 'University of Somewhere', new Location('India'));
        $providerAlsoInIndia = new Provider(new PostId(2), 'Another Institute', new Location('india'));
        $providerInChina = new Provider(new PostId(3), 'Beijing College', new Location('China'));

        $course = new Course(
            new PostId(10),
            'Graphic Design',
            'Short description',
            'Long description',
            new Price(500),
            [],
            [$providerInIndia, $providerAlsoInIndia, $providerInChina],
            [],
            [],
        );

        $locationNames = array_map(
            static fn (Location $location): string => $location->name(),
            $course->locations(),
        );

        self::assertSame(['India', 'China'], $locationNames);
    }

    public function test_a_course_with_no_providers_has_no_locations(): void
    {
        $course = new Course(
            new PostId(11),
            'Untethered Course',
            '',
            '',
            new Price(0),
            [],
            [],
            [],
            [],
        );

        self::assertSame([], $course->locations());
    }
}
