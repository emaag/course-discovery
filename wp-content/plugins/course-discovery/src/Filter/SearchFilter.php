<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Filter;

use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;

/** Plain-text search across Course name, short description and long description. */
final class SearchFilter implements Filter
{
    public function key(): string
    {
        return 'search';
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $term = trim((string) $criteria->search());

        if ($term !== '') {
            $builder->addSearchTerm($term);
        }
    }
}
