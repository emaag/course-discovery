<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Filter;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\StartDateFilter;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use OxfordInternational\CourseDiscovery\Tests\Support\CourseFactory;
use PHPUnit\Framework\TestCase;

final class StartDateFilterTest extends TestCase
{
    public function test_no_criteria_adds_no_predicate(): void
    {
        $builder = new CourseQueryBuilder();
        (new StartDateFilter())->apply($builder, FilterCriteria::fromArray([]));

        self::assertSame([], $builder->postFilterPredicates());
    }

    public function test_a_course_matches_if_any_of_its_start_dates_is_selected(): void
    {
        $builder = new CourseQueryBuilder();
        (new StartDateFilter())->apply($builder, FilterCriteria::fromArray(['start_dates' => ['09-2026', '01-2027']]));

        $predicate = $builder->postFilterPredicates()[0];

        self::assertTrue($predicate(CourseFactory::make(['startDates' => ['09-2026']])));
        self::assertTrue($predicate(CourseFactory::make(['startDates' => ['06-2026', '01-2027']])));
        self::assertFalse($predicate(CourseFactory::make(['startDates' => ['06-2026']])));
    }
}
