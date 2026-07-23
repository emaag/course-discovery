<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Query;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use WP_Post;
use WP_Query;

/**
 * A typed, fluent abstraction over WP_Query for Course search — domain
 * code never builds raw WP_Query arg arrays inline.
 *
 * Always fetches the full matching set (`posts_per_page => -1`) rather
 * than paginating via WP_Query directly: `Filter` implementations may add
 * either a tax_query clause (categories — real, indexed taxonomy terms)
 * or an in-PHP predicate (providers, locations, start dates — backed by
 * ACF relationship/repeater fields WP_Query's meta_query can't reliably
 * IN-match against, see Filter\ProviderFilter). Predicates can only be
 * applied after hydration, so final pagination happens in
 * CourseResultAssembler once every filter has had its say. Fine at this
 * project's scale; see Performance & Scalability in the README for the
 * evolution path once it isn't.
 */
final class CourseQueryBuilder
{
    /** @var list<array<string, mixed>> */
    private array $taxQuery = [];

    private ?string $searchTerm = null;

    /** @var list<callable(Course): bool> */
    private array $postFilterPredicates = [];

    private int $page = 1;

    private int $perPage = 10;

    /** @param array<string, mixed> $clause */
    public function addTaxClause(array $clause): static
    {
        $this->taxQuery[] = $clause;

        return $this;
    }

    public function addSearchTerm(string $term): static
    {
        $this->searchTerm = $term;

        return $this;
    }

    /** @param callable(Course): bool $predicate */
    public function addPostFilterPredicate(callable $predicate): static
    {
        $this->postFilterPredicates[] = $predicate;

        return $this;
    }

    public function setPage(int $page): static
    {
        $this->page = max(1, $page);

        return $this;
    }

    public function setPerPage(int $perPage): static
    {
        $this->perPage = max(1, $perPage);

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function taxQuery(): array
    {
        return $this->taxQuery;
    }

    public function searchTerm(): ?string
    {
        return $this->searchTerm;
    }

    /** @return list<callable(Course): bool> */
    public function postFilterPredicates(): array
    {
        return $this->postFilterPredicates;
    }

    public function execute(): CourseQueryResult
    {
        return (new CourseResultAssembler())->assemble(
            $this->fetchOrderedCourses(),
            $this->postFilterPredicates,
            $this->page,
            $this->perPage,
        );
    }

    /**
     * Every course matching this builder's criteria, in final order,
     * unpaginated — for callers that need the whole matching set (e.g.
     * deriving available filter options) rather than one page of results.
     *
     * @return list<Course>
     */
    public function executeAll(): array
    {
        return (new CourseResultAssembler())->filter($this->fetchOrderedCourses(), $this->postFilterPredicates);
    }

    /** @return list<Course> */
    private function fetchOrderedCourses(): array
    {
        CourseSearchClause::registerHooks();

        $args = [
            'post_type' => 'course',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        if ($this->taxQuery !== []) {
            $args['tax_query'] = array_merge(['relation' => 'AND'], $this->taxQuery);
        }

        if ($this->searchTerm !== null) {
            $args['course_discovery_search_term'] = $this->searchTerm;
        }

        /**
         * Modify the final WP_Query args before execution — e.g. add a
         * meta_query clause for a new, SQL-native filter.
         *
         * @param array<string, mixed> $args
         */
        $args = apply_filters('course_discovery_query_args', $args, $this);

        $query = new WP_Query($args);

        $courses = array_map(
            static fn (WP_Post $post): Course => Course::fromPost($post),
            $query->posts,
        );

        /**
         * Customise result ordering. Runs on the already-hydrated Course
         * list (not WP_Query's orderby) so ordering can use any domain
         * data — next start date, price — not just what WP_Query can
         * natively order by.
         *
         * @param list<Course> $courses
         */
        return apply_filters('course_discovery_order_courses', $courses);
    }
}
