<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Filter;

use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;

/**
 * One implementation per filter, composed by FilterPipeline rather than
 * built as subclasses of a shared base — each filter only needs to know
 * how to contribute its own criteria to the query builder, nothing else
 * depends on its internals. A filter decides for itself whether its
 * criteria can be pushed down into WP_Query args (search, category) or
 * must be applied as an in-PHP predicate over hydrated Course objects
 * (provider, location, start date) — see CourseQueryBuilder's docblock.
 */
interface Filter
{
    public function key(): string;

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void;
}
