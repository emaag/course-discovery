<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Migration;

use OxfordInternational\CourseDiscovery\Migration\FilterIndexSync;
use OxfordInternational\CourseDiscovery\Tests\Support\CourseFactory;
use PHPUnit\Framework\TestCase;

final class FilterIndexSyncTest extends TestCase
{
    public function test_provider_rows_pairs_each_provider_with_its_location_slug(): void
    {
        $course = CourseFactory::make([
            'providerIds' => [5, 9],
            'locations' => ['India', 'United Kingdom'],
        ]);

        self::assertSame(
            [
                ['provider_id' => 5, 'location_slug' => 'india'],
                ['provider_id' => 9, 'location_slug' => 'united-kingdom'],
            ],
            FilterIndexSync::providerRows($course),
        );
    }

    public function test_provider_rows_is_empty_for_a_course_with_no_providers(): void
    {
        $course = CourseFactory::make(['providerIds' => []]);

        self::assertSame([], FilterIndexSync::providerRows($course));
    }

    public function test_start_date_rows_maps_each_date_to_its_storage_string(): void
    {
        $course = CourseFactory::make(['startDates' => ['09-2026', '01-2027']]);

        self::assertSame(
            [
                ['start_date' => '09-2026'],
                ['start_date' => '01-2027'],
            ],
            FilterIndexSync::startDateRows($course),
        );
    }
}
