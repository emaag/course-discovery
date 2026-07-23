<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Filter;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\SearchFilter;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use PHPUnit\Framework\TestCase;

final class SearchFilterTest extends TestCase
{
    public function test_it_sets_the_search_term_on_the_builder(): void
    {
        $builder = new CourseQueryBuilder();
        (new SearchFilter())->apply($builder, FilterCriteria::fromArray(['search' => 'graphic design']));

        self::assertSame('graphic design', $builder->searchTerm());
    }

    public function test_it_trims_whitespace(): void
    {
        $builder = new CourseQueryBuilder();
        (new SearchFilter())->apply($builder, FilterCriteria::fromArray(['search' => '  design  ']));

        self::assertSame('design', $builder->searchTerm());
    }

    public function test_no_search_criteria_leaves_the_builder_untouched(): void
    {
        $builder = new CourseQueryBuilder();
        (new SearchFilter())->apply($builder, FilterCriteria::fromArray([]));

        self::assertNull($builder->searchTerm());
    }
}
