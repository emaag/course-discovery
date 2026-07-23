<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Filter;

use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;

/**
 * Composes the registered filters against a query builder and criteria.
 * Top-level filters combine with AND: each Filter contributes either a
 * tax_query clause or an in-PHP predicate, and CourseQueryBuilder/
 * CourseResultAssembler require every one of them to match — while the
 * OR-within-a-filter behaviour lives inside each filter's own
 * contribution (an 'IN' tax_query operator, or a predicate that matches
 * on any of the selected values).
 */
final class FilterPipeline
{
    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        foreach ($this->filters() as $filter) {
            $filter->apply($builder, $criteria);
        }
    }

    /** @return list<Filter> */
    private function filters(): array
    {
        $defaults = [
            new SearchFilter(),
            new ProviderFilter(),
            new LocationFilter(),
            new CategoryFilter(),
            new StartDateFilter(),
        ];

        if (! function_exists('apply_filters')) {
            return $defaults;
        }

        /**
         * Register additional filters, or remove/replace a default one,
         * without modifying any existing Filter implementation.
         *
         * @param list<Filter> $filters
         */
        return apply_filters('course_discovery_filters', $defaults);
    }
}
