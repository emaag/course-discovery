<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\FilterPipeline;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use WP_UnitTestCase;

/**
 * The brief's AND/OR composition, against real posts/ACF data end to end:
 * (provider = A OR provider = B) AND (category = X). Values *within* one
 * filter combine with OR; the filters themselves combine with AND.
 */
final class FilterPipelineIntegrationTest extends WP_UnitTestCase
{
    private int $providerA;

    private int $providerB;

    private int $providerC;

    private int $categoryX;

    private int $categoryY;

    /** @var array<string, int> course title => post ID */
    private array $courses = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->providerA = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'Provider A']);
        $this->providerB = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'Provider B']);
        $this->providerC = $this->factory()->post->create(['post_type' => 'provider', 'post_title' => 'Provider C']);
        update_field('location', 'Wherever', $this->providerA);
        update_field('location', 'Wherever', $this->providerB);
        update_field('location', 'Wherever', $this->providerC);

        $categoryX = wp_insert_term('Category X', 'course_category');
        $categoryY = wp_insert_term('Category Y', 'course_category');
        $this->categoryX = $categoryX['term_id'];
        $this->categoryY = $categoryY['term_id'];

        // Provider A, Category X
        $this->courses['A-X'] = $this->create_course('Course A-X', [$this->providerA], [$this->categoryX]);
        // Provider A, Category Y
        $this->courses['A-Y'] = $this->create_course('Course A-Y', [$this->providerA], [$this->categoryY]);
        // Provider B, Category X
        $this->courses['B-X'] = $this->create_course('Course B-X', [$this->providerB], [$this->categoryX]);
        // Provider C, Category Y (unrelated to A/B and X)
        $this->courses['C-Y'] = $this->create_course('Course C-Y', [$this->providerC], [$this->categoryY]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_and_across_filters_provider_and_category(): void
    {
        $ids = $this->run_query(['providers' => [$this->providerA], 'categories' => [$this->categoryX]]);

        self::assertSame([$this->courses['A-X']], $ids);
    }

    public function test_or_within_provider_filter(): void
    {
        $ids = $this->run_query(['providers' => [$this->providerA, $this->providerB]]);

        sort($ids);
        $expected = [$this->courses['A-X'], $this->courses['A-Y'], $this->courses['B-X']];
        sort($expected);

        self::assertSame($expected, $ids);
        self::assertNotContains($this->courses['C-Y'], $ids);
    }

    public function test_combined_or_within_provider_and_and_with_category(): void
    {
        // (provider = A OR provider = B) AND (category = X)
        $ids = $this->run_query([
            'providers' => [$this->providerA, $this->providerB],
            'categories' => [$this->categoryX],
        ]);

        sort($ids);
        $expected = [$this->courses['A-X'], $this->courses['B-X']];
        sort($expected);

        self::assertSame($expected, $ids, '(provider=A OR provider=B) AND category=X should match exactly A-X and B-X.');
        self::assertNotContains($this->courses['A-Y'], $ids, 'A-Y has the right provider but the wrong category.');
        self::assertNotContains($this->courses['C-Y'], $ids);
    }

    public function test_no_criteria_selected_returns_every_course(): void
    {
        $ids = $this->run_query([]);

        self::assertCount(4, $ids);
        foreach ($this->courses as $courseId) {
            self::assertContains($courseId, $ids);
        }
    }

    public function test_a_combination_matching_nothing_returns_empty(): void
    {
        // Provider C is never paired with Category X.
        $ids = $this->run_query(['providers' => [$this->providerC], 'categories' => [$this->categoryX]]);

        self::assertSame([], $ids);
    }

    /**
     * @param array<string, mixed> $rawCriteria
     * @return list<int>
     */
    private function run_query(array $rawCriteria): array
    {
        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray($rawCriteria);
        (new FilterPipeline())->apply($builder, $criteria);
        $result = $builder->execute();

        return array_map(static fn ($course): int => $course->id()->toInt(), $result->courses());
    }

    /**
     * @param list<int> $providerIds
     * @param list<int> $categoryIds
     */
    private function create_course(string $title, array $providerIds, array $categoryIds): int
    {
        $courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => $title,
            'post_status' => 'publish',
        ]);

        update_field('providers', $providerIds, $courseId);
        wp_set_object_terms($courseId, $categoryIds, 'course_category');

        return $courseId;
    }
}
