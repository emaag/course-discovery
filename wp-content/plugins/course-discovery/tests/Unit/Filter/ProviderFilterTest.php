<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Filter;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\ProviderFilter;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use OxfordInternational\CourseDiscovery\Tests\Support\CourseFactory;
use PHPUnit\Framework\TestCase;

final class ProviderFilterTest extends TestCase
{
    public function test_no_criteria_adds_no_predicate(): void
    {
        $builder = new CourseQueryBuilder();
        (new ProviderFilter())->apply($builder, FilterCriteria::fromArray([]));

        self::assertSame([], $builder->postFilterPredicates());
    }

    public function test_a_course_matches_if_any_of_its_providers_is_selected(): void
    {
        $builder = new CourseQueryBuilder();
        (new ProviderFilter())->apply($builder, FilterCriteria::fromArray(['providers' => [5, 9]]));

        $predicate = $builder->postFilterPredicates()[0];

        self::assertTrue($predicate(CourseFactory::make(['providerIds' => [9]])));
        self::assertTrue($predicate(CourseFactory::make(['providerIds' => [3, 5]])));
        self::assertFalse($predicate(CourseFactory::make(['providerIds' => [7]])));
    }
}
