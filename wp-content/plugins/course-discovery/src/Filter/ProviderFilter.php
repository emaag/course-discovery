<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Filter;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;

/**
 * Filters by Provider. Applied as an in-PHP predicate rather than a
 * meta_query clause: ACF stores the `providers` relationship field as a
 * single serialized array in one postmeta row, which WP_Query's
 * meta_query can't reliably IN-match against without risking false
 * positives (a LIKE match on a serialized value can collide with the
 * array's own index tokens, not just its stored values) — exactly the
 * "wrong-but-similar SQL join" risk called out in the README's testing
 * strategy. Matching against the already-hydrated, correctly-typed
 * Provider IDs is unambiguous instead.
 */
final class ProviderFilter implements Filter
{
    public function key(): string
    {
        return 'provider';
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $selected = $criteria->providerIds();

        if ($selected === []) {
            return;
        }

        $builder->addPostFilterPredicate(static function (Course $course) use ($selected): bool {
            foreach ($course->providers() as $provider) {
                if (in_array($provider->id()->toInt(), $selected, true)) {
                    return true; // values within this filter combine with OR
                }
            }

            return false;
        });
    }
}
