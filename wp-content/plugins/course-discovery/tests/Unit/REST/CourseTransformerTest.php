<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\REST;

use OxfordInternational\CourseDiscovery\REST\CourseTransformer;
use OxfordInternational\CourseDiscovery\Tests\Support\CourseFactory;
use PHPUnit\Framework\TestCase;

final class CourseTransformerTest extends TestCase
{
    public function test_it_converts_a_course_to_a_rest_friendly_array(): void
    {
        $course = CourseFactory::make([
            'id' => 42,
            'name' => 'Test Course',
            'price' => 199.99,
            'providerIds' => [5],
            'locations' => ['India'],
            'startDates' => ['09-2026'],
            'categories' => [['id' => 2, 'name' => 'Graphic Design', 'slug' => 'graphic-design']],
        ]);

        $array = (new CourseTransformer())->toArray($course);

        self::assertSame(42, $array['id']);
        self::assertSame('Test Course', $array['name']);
        self::assertSame('Short description', $array['short_description']);
        self::assertSame('Long description', $array['long_description']);
        self::assertSame(
            ['amount' => 199.99, 'currency' => 'GBP', 'formatted' => 'GBP 199.99'],
            $array['price'],
        );
        self::assertSame([['id' => 5, 'name' => 'Provider 5']], $array['providers']);
        self::assertSame([['slug' => 'india', 'name' => 'India']], $array['locations']);
        self::assertSame([['value' => '09-2026', 'label' => 'September 2026']], $array['start_dates']);
        self::assertSame(
            [['id' => 2, 'name' => 'Graphic Design', 'slug' => 'graphic-design', 'parent_id' => null]],
            $array['categories'],
        );
        self::assertSame([['id' => 1, 'name' => 'Sample Instructor']], $array['instructors']);
    }

    public function test_a_course_with_multiple_providers_lists_all_of_them(): void
    {
        $course = CourseFactory::make(['providerIds' => [5, 9], 'locations' => ['India', 'China']]);

        $array = (new CourseTransformer())->toArray($course);

        self::assertCount(2, $array['providers']);
        self::assertCount(2, $array['locations']);
    }
}
