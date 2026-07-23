<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Filter;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use PHPUnit\Framework\TestCase;

final class FilterCriteriaTest extends TestCase
{
    public function test_defaults_are_empty_when_given_an_empty_array(): void
    {
        $criteria = FilterCriteria::fromArray([]);

        self::assertNull($criteria->search());
        self::assertSame([], $criteria->providerIds());
        self::assertSame([], $criteria->locationSlugs());
        self::assertSame([], $criteria->startDates());
        self::assertSame([], $criteria->categoryIds());
    }

    public function test_it_parses_a_full_raw_payload(): void
    {
        $criteria = FilterCriteria::fromArray([
            'search' => 'design',
            'providers' => ['5', 9],
            'locations' => ['india', 'china'],
            'start_dates' => ['09-2026', '01-2027'],
            'categories' => ['2', 3],
        ]);

        self::assertSame('design', $criteria->search());
        self::assertSame([5, 9], $criteria->providerIds());
        self::assertSame(['india', 'china'], $criteria->locationSlugs());
        self::assertSame(['09-2026', '01-2027'], $criteria->startDates());
        self::assertSame([2, 3], $criteria->categoryIds());
    }

    public function test_an_empty_search_string_is_treated_as_no_search(): void
    {
        self::assertNull(FilterCriteria::fromArray(['search' => ''])->search());
    }

    public function test_non_array_list_values_are_ignored_rather_than_erroring(): void
    {
        $criteria = FilterCriteria::fromArray(['providers' => 'not-an-array']);

        self::assertSame([], $criteria->providerIds());
    }
}
