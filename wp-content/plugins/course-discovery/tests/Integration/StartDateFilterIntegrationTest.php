<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\FilterPipeline;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use OxfordInternational\CourseDiscovery\Query\FilterOptionsProvider;
use WP_UnitTestCase;

final class StartDateFilterIntegrationTest extends WP_UnitTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_start_date_options_are_chronologically_ordered_regardless_of_creation_order(): void
    {
        // Deliberately created out of chronological order.
        $this->create_course_with_dates('June Course', ['06-2027']);
        $this->create_course_with_dates('January Course', ['01-2027']);
        $this->create_course_with_dates('September Course', ['09-2026']);
        $this->create_course_with_dates('March Course', ['03-2027']);

        $options = (new FilterOptionsProvider())->compute();
        $values = array_map(static fn (array $d): string => $d['value'], $options['start_dates']);

        self::assertSame(['09-2026', '01-2027', '03-2027', '06-2027'], $values);
    }

    public function test_filtering_by_a_single_start_date(): void
    {
        $septemberCourse = $this->create_course_with_dates('September Course', ['09-2026']);
        $januaryCourse = $this->create_course_with_dates('January Course', ['01-2027']);

        $ids = $this->run_query(['09-2026']);

        self::assertSame([$septemberCourse], $ids);
        self::assertNotContains($januaryCourse, $ids);
    }

    public function test_filtering_by_multiple_start_dates_combines_with_or(): void
    {
        $septemberCourse = $this->create_course_with_dates('September Course', ['09-2026']);
        $januaryCourse = $this->create_course_with_dates('January Course', ['01-2027']);
        $juneCourse = $this->create_course_with_dates('June Course', ['06-2027']);

        $ids = $this->run_query(['09-2026', '06-2027']);

        sort($ids);
        $expected = [$septemberCourse, $juneCourse];
        sort($expected);

        self::assertSame($expected, $ids);
        self::assertNotContains($januaryCourse, $ids);
    }

    public function test_a_course_with_multiple_start_dates_matches_any_selected_one(): void
    {
        $courseId = $this->create_course_with_dates('Multi-Date Course', ['09-2026', '01-2027']);

        $ids = $this->run_query(['01-2027']);

        self::assertContains($courseId, $ids);
    }

    /** @param list<string> $dates */
    private function create_course_with_dates(string $title, array $dates): int
    {
        $courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => $title,
            'post_status' => 'publish',
        ]);

        update_field(
            'start_dates',
            array_map(static fn (string $date): array => ['start_date' => $date], $dates),
            $courseId,
        );

        return $courseId;
    }

    /**
     * @param list<string> $selectedDates
     * @return list<int>
     */
    private function run_query(array $selectedDates): array
    {
        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray(['start_dates' => $selectedDates]);
        (new FilterPipeline())->apply($builder, $criteria);
        $result = $builder->execute();

        return array_map(static fn ($course): int => $course->id()->toInt(), $result->courses());
    }
}
