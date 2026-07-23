<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\FilterPipeline;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use WP_UnitTestCase;

/**
 * Exercises CourseQueryBuilder against the real WP_Query/tax_query/ACF
 * machinery that tests/Unit deliberately avoids depending on — search
 * across all three text fields, real tax_query behaviour, pagination
 * math against a real result set, and ACF hydration end to end.
 */
final class CourseQueryBuilderIntegrationTest extends WP_UnitTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_search_matches_the_course_name(): void
    {
        $matchId = $this->create_course('Zebra Migration Patterns');
        $otherId = $this->create_course('Unrelated Course');

        $ids = $this->search('Zebra Migration');

        self::assertContains($matchId, $ids);
        self::assertNotContains($otherId, $ids);
    }

    public function test_search_matches_the_short_description(): void
    {
        $matchId = $this->create_course('Findable Course');
        update_field('short_description', 'a very distinctive unicorn phrase', $matchId);

        $otherId = $this->create_course('Other Course');
        update_field('short_description', 'nothing special here', $otherId);

        $ids = $this->search('distinctive unicorn');

        self::assertContains(
            $matchId,
            $ids,
            'Search must match short_description, not just post_title/post_content.',
        );
        self::assertNotContains($otherId, $ids);
    }

    public function test_search_matches_the_long_description(): void
    {
        $matchId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'Findable Course',
            'post_content' => 'covers an extremely rare platypus scenario in depth',
            'post_status' => 'publish',
        ]);

        $otherId = $this->create_course('Other Course');

        $ids = $this->search('rare platypus');

        self::assertContains(
            $matchId,
            $ids,
            'Search must match the long description (post_content).',
        );
        self::assertNotContains($otherId, $ids);
    }

    public function test_category_filter_uses_a_real_tax_query_including_child_terms(): void
    {
        $parentTerm = wp_insert_term('Design', 'course_category');
        $childTerm = wp_insert_term('Graphic Design', 'course_category', ['parent' => $parentTerm['term_id']]);

        $matchingCourseId = $this->create_course('Branding Basics');
        wp_set_object_terms($matchingCourseId, [$childTerm['term_id']], 'course_category');

        $nonMatchingCourseId = $this->create_course('Unrelated Course');

        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray(['categories' => [$parentTerm['term_id']]]);
        (new FilterPipeline())->apply($builder, $criteria);
        $result = $builder->execute();

        $ids = array_map(static fn ($course): int => $course->id()->toInt(), $result->courses());

        self::assertContains(
            $matchingCourseId,
            $ids,
            'Selecting a parent category should match courses tagged with its child term.',
        );
        self::assertNotContains($nonMatchingCourseId, $ids);
    }

    public function test_pagination_returns_the_correct_subset_and_totals(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->create_course(sprintf('Course %02d', $i));
        }

        $builder = (new CourseQueryBuilder())->setPage(2)->setPerPage(10);
        (new FilterPipeline())->apply($builder, FilterCriteria::fromArray([]));
        $result = $builder->execute();

        self::assertSame(25, $result->total());
        self::assertSame(3, $result->totalPages());
        self::assertSame(2, $result->page());
        self::assertCount(10, $result->courses());

        $lastPage = (new CourseQueryBuilder())->setPage(3)->setPerPage(10);
        (new FilterPipeline())->apply($lastPage, FilterCriteria::fromArray([]));
        self::assertCount(5, $lastPage->execute()->courses());
    }

    public function test_provider_and_location_filters_combine_with_and_against_real_data(): void
    {
        $providerIndia = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'India Provider']);
        update_field('location', 'India', $providerIndia);

        $providerChina = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'China Provider']);
        update_field('location', 'China', $providerChina);

        $courseInIndia = $this->create_course('Course In India');
        update_field('providers', [$providerIndia], $courseInIndia);

        $courseInChina = $this->create_course('Course In China');
        update_field('providers', [$providerChina], $courseInChina);

        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray([
            'providers' => [$providerIndia],
            'locations' => ['india'],
        ]);
        (new FilterPipeline())->apply($builder, $criteria);
        $result = $builder->execute();

        $ids = array_map(static fn ($course): int => $course->id()->toInt(), $result->courses());

        self::assertContains($courseInIndia, $ids);
        self::assertNotContains(
            $courseInChina,
            $ids,
            'Provider AND Location must both match — a course failing either should be excluded.',
        );
    }

    public function test_hydrated_course_reflects_real_stored_acf_data(): void
    {
        $providerId = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'Test Provider']);
        update_field('location', 'Canada', $providerId);

        $courseId = $this->create_course('Hydration Check');
        update_field('price', 249.5, $courseId);
        update_field('providers', [$providerId], $courseId);
        update_field('start_dates', [['start_date' => '09-2026']], $courseId);

        $ids = $this->search('Hydration Check');
        self::assertCount(1, $ids);

        $builder = new CourseQueryBuilder();
        (new FilterPipeline())->apply($builder, FilterCriteria::fromArray(['search' => 'Hydration Check']));
        $course = $builder->execute()->courses()[0];

        self::assertSame(249.5, $course->price()->amount());
        self::assertSame(['Canada'], array_map(static fn ($l): string => $l->name(), $course->locations()));
        self::assertSame(['09-2026'], array_map(static fn ($d): string => (string) $d, $course->startDates()));
    }

    /** @return list<int> */
    private function search(string $term): array
    {
        $builder = new CourseQueryBuilder();
        (new FilterPipeline())->apply($builder, FilterCriteria::fromArray(['search' => $term]));

        return array_map(
            static fn ($course): int => $course->id()->toInt(),
            $builder->execute()->courses(),
        );
    }

    private function create_course(string $title): int
    {
        return $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => $title,
            'post_status' => 'publish',
        ]);
    }
}
