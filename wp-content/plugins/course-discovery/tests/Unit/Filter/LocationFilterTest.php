<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Filter;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\LocationFilter;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use OxfordInternational\CourseDiscovery\Tests\Support\CourseFactory;
use PHPUnit\Framework\TestCase;

final class LocationFilterTest extends TestCase
{
    public function test_no_criteria_adds_no_predicate(): void
    {
        $builder = new CourseQueryBuilder();
        (new LocationFilter())->apply($builder, FilterCriteria::fromArray([]));

        self::assertSame([], $builder->postFilterPredicates());
    }

    public function test_a_course_matches_if_any_derived_location_is_selected(): void
    {
        $builder = new CourseQueryBuilder();
        (new LocationFilter())->apply($builder, FilterCriteria::fromArray(['locations' => ['india', 'china']]));

        $predicate = $builder->postFilterPredicates()[0];

        self::assertTrue($predicate(CourseFactory::make(['providerIds' => [1], 'locations' => ['India']])));
        self::assertTrue($predicate(CourseFactory::make(['providerIds' => [1, 2], 'locations' => ['Canada', 'China']])));
        self::assertFalse($predicate(CourseFactory::make(['providerIds' => [1], 'locations' => ['Germany']])));
    }

    public function test_matching_is_case_insensitive_via_slug(): void
    {
        $builder = new CourseQueryBuilder();
        (new LocationFilter())->apply($builder, FilterCriteria::fromArray(['locations' => ['united-kingdom']]));

        $predicate = $builder->postFilterPredicates()[0];

        self::assertTrue($predicate(CourseFactory::make(['providerIds' => [1], 'locations' => ['United Kingdom']])));
    }
}
