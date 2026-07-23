<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Filter;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\FilterPipeline;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use OxfordInternational\CourseDiscovery\Tests\Support\CourseFactory;
use PHPUnit\Framework\TestCase;

final class FilterPipelineTest extends TestCase
{
    public function test_criteria_across_every_filter_type_are_all_applied(): void
    {
        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray([
            'search' => 'design',
            'providers' => [5],
            'locations' => ['india'],
            'start_dates' => ['09-2026'],
            'categories' => [2],
        ]);

        (new FilterPipeline())->apply($builder, $criteria);

        self::assertSame('design', $builder->searchTerm());
        self::assertNotSame([], $builder->taxQuery());
        // provider + location + start date each contribute one predicate
        self::assertCount(3, $builder->postFilterPredicates());
    }

    public function test_a_course_matching_every_criterion_passes_all_predicates(): void
    {
        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray([
            'providers' => [5],
            'locations' => ['india'],
            'start_dates' => ['09-2026'],
        ]);

        (new FilterPipeline())->apply($builder, $criteria);

        $matching = CourseFactory::make([
            'providerIds' => [5],
            'locations' => ['India'],
            'startDates' => ['09-2026'],
        ]);

        foreach ($builder->postFilterPredicates() as $predicate) {
            self::assertTrue($predicate($matching));
        }
    }

    public function test_a_course_failing_one_criterion_fails_the_pipeline_overall(): void
    {
        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray([
            'providers' => [5],
            'locations' => ['india'],
        ]);

        (new FilterPipeline())->apply($builder, $criteria);

        // Right provider, wrong location — AND across filters means this should fail overall.
        $partiallyMatching = CourseFactory::make([
            'providerIds' => [5],
            'locations' => ['China'],
        ]);

        $results = array_map(
            static fn (callable $predicate): bool => $predicate($partiallyMatching),
            $builder->postFilterPredicates(),
        );

        self::assertContains(false, $results);
    }

    public function test_no_criteria_leaves_the_builder_untouched(): void
    {
        $builder = new CourseQueryBuilder();
        (new FilterPipeline())->apply($builder, FilterCriteria::fromArray([]));

        self::assertNull($builder->searchTerm());
        self::assertSame([], $builder->taxQuery());
        self::assertSame([], $builder->postFilterPredicates());
    }
}
