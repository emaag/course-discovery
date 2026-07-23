<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Filter;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;

/**
 * Filters by Location — derived from a Course's Providers, so there's no
 * `location` meta on Course to query at all. Applied as an in-PHP
 * predicate against Course::locations(), the same derivation used
 * everywhere else Location is read.
 */
final class LocationFilter implements Filter
{
    public function key(): string
    {
        return 'location';
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $selected = $criteria->locationSlugs();

        if ($selected === []) {
            return;
        }

        $builder->addPostFilterPredicate(static function (Course $course) use ($selected): bool {
            foreach ($course->locations() as $location) {
                if (in_array($location->slug(), $selected, true)) {
                    return true; // values within this filter combine with OR
                }
            }

            return false;
        });
    }
}
