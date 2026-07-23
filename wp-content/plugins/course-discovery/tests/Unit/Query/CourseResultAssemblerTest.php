<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Query;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Query\CourseResultAssembler;
use OxfordInternational\CourseDiscovery\Tests\Support\CourseFactory;
use PHPUnit\Framework\TestCase;

final class CourseResultAssemblerTest extends TestCase
{
    public function test_filter_applies_predicates_without_pagination(): void
    {
        $courses = [
            CourseFactory::make(['id' => 1, 'providerIds' => [5]]),
            CourseFactory::make(['id' => 2, 'providerIds' => [9]]),
            CourseFactory::make(['id' => 3, 'providerIds' => [5]]),
        ];

        $providerIs5 = static fn (Course $c): bool => in_array(5, array_map(
            static fn ($p) => $p->id()->toInt(),
            $c->providers(),
        ), true);

        $filtered = (new CourseResultAssembler())->filter($courses, [$providerIs5]);

        self::assertSame([1, 3], array_map(static fn (Course $c): int => $c->id()->toInt(), $filtered));
    }

    public function test_filter_with_no_predicates_returns_every_course_unpaginated(): void
    {
        $courses = array_map(static fn (int $id): Course => CourseFactory::make(['id' => $id]), range(1, 15));

        self::assertCount(15, (new CourseResultAssembler())->filter($courses, []));
    }

    public function test_predicates_are_combined_with_and(): void
    {
        $courses = [
            CourseFactory::make(['id' => 1, 'providerIds' => [5], 'locations' => ['India']]),
            CourseFactory::make(['id' => 2, 'providerIds' => [5], 'locations' => ['China']]),
            CourseFactory::make(['id' => 3, 'providerIds' => [9], 'locations' => ['India']]),
        ];

        $providerIs5 = static fn (Course $c): bool => in_array(5, array_map(
            static fn ($p) => $p->id()->toInt(),
            $c->providers(),
        ), true);

        $locationIsIndia = static fn (Course $c): bool => in_array('india', array_map(
            static fn ($l) => $l->slug(),
            $c->locations(),
        ), true);

        $result = (new CourseResultAssembler())->assemble($courses, [$providerIs5, $locationIsIndia], 1, 10);

        self::assertSame(1, $result->total());
        self::assertSame(1, $result->courses()[0]->id()->toInt());
    }

    public function test_no_predicates_returns_every_course(): void
    {
        $courses = [CourseFactory::make(['id' => 1]), CourseFactory::make(['id' => 2])];

        $result = (new CourseResultAssembler())->assemble($courses, [], 1, 10);

        self::assertSame(2, $result->total());
    }

    public function test_pagination_slices_correctly_and_reports_metadata(): void
    {
        $courses = array_map(
            static fn (int $id): Course => CourseFactory::make(['id' => $id]),
            range(1, 25),
        );

        $page2 = (new CourseResultAssembler())->assemble($courses, [], 2, 10);

        self::assertSame(25, $page2->total());
        self::assertSame(3, $page2->totalPages());
        self::assertSame(2, $page2->page());
        self::assertSame(10, $page2->perPage());
        self::assertCount(10, $page2->courses());
        self::assertSame(11, $page2->courses()[0]->id()->toInt());

        $page3 = (new CourseResultAssembler())->assemble($courses, [], 3, 10);
        self::assertCount(5, $page3->courses());
    }

    public function test_a_page_beyond_the_last_is_clamped_to_the_last_page(): void
    {
        $courses = [CourseFactory::make(['id' => 1]), CourseFactory::make(['id' => 2])];

        $result = (new CourseResultAssembler())->assemble($courses, [], 99, 10);

        self::assertSame(1, $result->page());
        self::assertCount(2, $result->courses());
    }

    public function test_an_empty_result_set_still_reports_one_total_page(): void
    {
        $alwaysFalse = static fn (Course $c): bool => false;

        $result = (new CourseResultAssembler())->assemble([CourseFactory::make()], [$alwaysFalse], 1, 10);

        self::assertSame(0, $result->total());
        self::assertSame(1, $result->totalPages());
        self::assertSame([], $result->courses());
    }
}
