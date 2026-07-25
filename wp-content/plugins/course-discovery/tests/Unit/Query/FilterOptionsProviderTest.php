<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Query;

use OxfordInternational\CourseDiscovery\Query\FilterOptionsProvider;
use OxfordInternational\CourseDiscovery\Tests\Support\CourseFactory;
use PHPUnit\Framework\TestCase;

/**
 * Exercises compute()'s $courses parameter directly against fabricated
 * Course objects — no WP_Query/WordPress bootstrap needed, since passing
 * the list explicitly is exactly what lets a caller (the archive
 * template, when no filter is selected) reuse an already-fetched list
 * instead of triggering FilterOptionsProvider's own internal query.
 */
final class FilterOptionsProviderTest extends TestCase
{
    public function test_computes_options_from_the_given_course_list_without_querying(): void
    {
        $courses = [
            CourseFactory::make([
                'id' => 1,
                'providerIds' => [5],
                'locations' => ['India'],
                'startDates' => ['09-2026'],
                'categories' => [['id' => 1, 'name' => 'Design', 'slug' => 'design']],
            ]),
            CourseFactory::make([
                'id' => 2,
                'providerIds' => [9],
                'locations' => ['China'],
                'startDates' => ['01-2026'],
                'categories' => [['id' => 2, 'name' => 'Business', 'slug' => 'business']],
            ]),
        ];

        $options = (new FilterOptionsProvider())->compute($courses);

        self::assertSame(
            [5, 9],
            array_map(static fn (array $p): int => $p['id'], $options['providers']),
        );
        self::assertSame(
            ['india', 'china'],
            array_map(static fn (array $l): string => $l['slug'], $options['locations']),
        );
        self::assertSame(
            ['design', 'business'],
            array_map(static fn (array $c): string => $c['slug'], $options['categories']),
        );
        // Chronological, not selection order (Jan before Sep).
        self::assertSame(
            ['01-2026', '09-2026'],
            array_map(static fn (array $d): string => $d['value'], $options['start_dates']),
        );
    }

    public function test_an_empty_course_list_produces_empty_option_lists(): void
    {
        $options = (new FilterOptionsProvider())->compute([]);

        self::assertSame([], $options['providers']);
        self::assertSame([], $options['locations']);
        self::assertSame([], $options['categories']);
        self::assertSame([], $options['start_dates']);
    }
}
