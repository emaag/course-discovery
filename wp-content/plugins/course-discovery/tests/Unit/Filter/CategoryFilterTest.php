<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Filter;

use OxfordInternational\CourseDiscovery\Filter\CategoryFilter;
use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use PHPUnit\Framework\TestCase;

final class CategoryFilterTest extends TestCase
{
    public function test_it_adds_an_in_tax_clause_for_the_selected_categories(): void
    {
        $builder = new CourseQueryBuilder();
        (new CategoryFilter())->apply($builder, FilterCriteria::fromArray(['categories' => [2, 3]]));

        self::assertSame([
            [
                'taxonomy' => 'course_category',
                'field' => 'term_id',
                'terms' => [2, 3],
                'operator' => 'IN',
            ],
        ], $builder->taxQuery());
    }

    public function test_no_category_criteria_leaves_the_builder_untouched(): void
    {
        $builder = new CourseQueryBuilder();
        (new CategoryFilter())->apply($builder, FilterCriteria::fromArray([]));

        self::assertSame([], $builder->taxQuery());
    }
}
