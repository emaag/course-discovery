<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Filter;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;

/**
 * Filters by start date. Applied as an in-PHP predicate for the same
 * reason as ProviderFilter: `start_dates` is an ACF repeater, stored as
 * serialized meta that isn't reliably meta_query-matchable, so this
 * matches against the already-hydrated, correctly-parsed StartDate
 * value objects instead.
 */
final class StartDateFilter implements Filter
{
    public function key(): string
    {
        return 'start_date';
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $selected = $criteria->startDates();

        if ($selected === []) {
            return;
        }

        $builder->addPostFilterPredicate(static function (Course $course) use ($selected): bool {
            foreach ($course->startDates() as $date) {
                if (in_array($date->toStorageString(), $selected, true)) {
                    return true; // values within this filter combine with OR
                }
            }

            return false;
        });
    }
}
