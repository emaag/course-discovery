<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Query;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;

/**
 * Pure filter + paginate logic over an already-fetched, already-ordered
 * list of Course objects — no WordPress dependency, so the predicate
 * composition (AND across predicates; any OR-within-a-filter logic is
 * baked into each predicate by its Filter) and the pagination math can be
 * unit tested directly against fabricated Course objects.
 *
 * Deliberately paginates in PHP rather than via WP_Query's LIMIT/OFFSET:
 * some criteria (provider, location, start date) can't be reliably
 * expressed as WP_Query args at all — see Filter\ProviderFilter's
 * docblock — so the full matching set has to be assembled in PHP before
 * pagination can be correct. Fine at this project's scale; the
 * README's Performance & Scalability section documents the evolution
 * path (a denormalised lookup table) for when it isn't.
 */
final class CourseResultAssembler
{
    /**
     * Applies every predicate (AND across predicates; any OR-within-a-
     * filter logic is baked into each predicate by its Filter) without
     * pagination — used directly by callers that need the whole matching
     * set (e.g. deriving available filter options).
     *
     * @param list<Course>                 $courses
     * @param list<callable(Course): bool> $predicates
     * @return list<Course>
     */
    public function filter(array $courses, array $predicates): array
    {
        return array_values(array_filter(
            $courses,
            static function (Course $course) use ($predicates): bool {
                foreach ($predicates as $predicate) {
                    if (! $predicate($course)) {
                        return false;
                    }
                }

                return true;
            },
        ));
    }

    /**
     * @param list<Course>                 $courses  Already in the desired final order.
     * @param list<callable(Course): bool> $predicates
     */
    public function assemble(array $courses, array $predicates, int $page, int $perPage): CourseQueryResult
    {
        $filtered = $this->filter($courses, $predicates);

        $total = count($filtered);
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return new CourseQueryResult(
            array_slice($filtered, $offset, $perPage),
            $total,
            $totalPages,
            $page,
            $perPage,
        );
    }
}
