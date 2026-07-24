<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use OxfordInternational\CourseDiscovery\Filter\Filter;
use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\FilterPipeline;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use OxfordInternational\CourseDiscovery\Query\FilterOptionsProvider;
use WP_UnitTestCase;

/**
 * Proves each of the five extensibility examples the brief names can
 * actually be used by third-party code — a real add_filter() callback in
 * each test, none of them touching any existing Filter/controller class.
 */
final class ExtensibilityHooksIntegrationTest extends WP_UnitTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_course_discovery_filters_allows_registering_a_new_filter(): void
    {
        $cheapCourse = $this->create_course('Cheap Course');
        update_field('price', 50, $cheapCourse);

        $expensiveCourse = $this->create_course('Expensive Course');
        update_field('price', 5000, $expensiveCourse);

        add_filter('course_discovery_filters', static function (array $filters): array {
            $filters[] = new class () implements Filter {
                public function key(): string
                {
                    return 'min_price';
                }

                public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
                {
                    $builder->addPostFilterPredicate(
                        static fn ($course): bool => $course->price()->amount() >= 1000,
                    );
                }
            };

            return $filters;
        });

        $builder = new CourseQueryBuilder();
        (new FilterPipeline())->apply($builder, FilterCriteria::fromArray([]));
        $ids = array_map(static fn ($c): int => $c->id()->toInt(), $builder->execute()->courses());

        self::assertContains($expensiveCourse, $ids);
        self::assertNotContains(
            $cheapCourse,
            $ids,
            'A third-party filter registered via course_discovery_filters should apply without touching any existing Filter class.',
        );
    }

    public function test_course_discovery_query_args_can_modify_the_wp_query_args(): void
    {
        $courseOne = $this->create_course('Course One');
        $this->create_course('Course Two');

        add_filter('course_discovery_query_args', static function (array $args) use ($courseOne): array {
            $args['p'] = $courseOne;

            return $args;
        });

        $builder = new CourseQueryBuilder();
        (new FilterPipeline())->apply($builder, FilterCriteria::fromArray([]));
        $ids = array_map(static fn ($c): int => $c->id()->toInt(), $builder->execute()->courses());

        self::assertSame([$courseOne], $ids);
    }

    public function test_course_discovery_order_courses_can_customise_ordering(): void
    {
        $courseA = $this->create_course('Alpha Course');
        update_field('price', 100, $courseA);

        $courseB = $this->create_course('Beta Course');
        update_field('price', 500, $courseB);

        // Default ordering is alphabetical by title (Alpha, then Beta) —
        // this hook flips it to price descending, proving a third party
        // can override ordering without touching CourseQueryBuilder.
        add_filter('course_discovery_order_courses', static function (array $courses): array {
            usort($courses, static fn ($a, $b): int => $b->price()->amount() <=> $a->price()->amount());

            return $courses;
        });

        $builder = new CourseQueryBuilder();
        (new FilterPipeline())->apply($builder, FilterCriteria::fromArray([]));
        $names = array_map(static fn ($c): string => $c->name(), $builder->execute()->courses());

        self::assertSame(['Beta Course', 'Alpha Course'], $names);
    }

    public function test_course_discovery_transform_criteria_can_rewrite_raw_criteria(): void
    {
        add_filter('course_discovery_transform_criteria', static function (array $raw): array {
            if (isset($raw['q']) && ! isset($raw['search'])) {
                $raw['search'] = $raw['q'];
            }

            return $raw;
        });

        $criteria = FilterCriteria::fromArray(['q' => 'legacy param name']);

        self::assertSame(
            'legacy param name',
            $criteria->search(),
            'A third party should be able to rewrite raw criteria (e.g. map a legacy param) before it is typed.',
        );
    }

    public function test_course_discovery_filter_options_can_alter_returned_options(): void
    {
        add_filter('course_discovery_filter_options', static function (array $options): array {
            $options['providers'][] = ['id' => 999999, 'name' => 'Injected Provider'];

            return $options;
        });

        $options = (new FilterOptionsProvider())->compute();

        self::assertContains(['id' => 999999, 'name' => 'Injected Provider'], $options['providers']);
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
