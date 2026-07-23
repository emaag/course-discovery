<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Filter;

use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;

/**
 * Filters by the hierarchical `course_category` taxonomy — a real,
 * indexed WordPress taxonomy relationship, so this pushes straight down
 * into a tax_query clause rather than an in-PHP predicate. Selecting a
 * parent category matches its child terms too (WP_Tax_Query's default
 * `include_children` behaviour).
 */
final class CategoryFilter implements Filter
{
    public function key(): string
    {
        return 'category';
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $ids = $criteria->categoryIds();

        if ($ids === []) {
            return;
        }

        $builder->addTaxClause([
            'taxonomy' => 'course_category',
            'field' => 'term_id',
            'terms' => $ids,
            'operator' => 'IN', // values within this filter combine with OR
        ]);
    }
}
