<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Integration;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use WP_UnitTestCase;

/**
 * `acf/validate_value` (Field\CourseFieldGroup::validateStartDate) only
 * protects data entered through the real wp-admin form — it's never
 * consulted by direct postmeta writes (ACF's own `update_field()`, a
 * legacy row from before validation existed, a future import script).
 * This proves the second line of defence: Course::fromPost() must not
 * let one malformed value take the whole page down.
 */
final class MalformedStartDateIntegrationTest extends WP_UnitTestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_a_malformed_start_date_written_directly_is_skipped_not_fatal(): void
    {
        $courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'Course With Bad Data',
            'post_status' => 'publish',
        ]);

        // Bypasses update_field()/acf/validate_value entirely — simulates
        // a row that predates validation, or any non-admin-form write path.
        update_post_meta($courseId, 'start_dates', [
            ['start_date' => '09-2026'],
            ['start_date' => 'not-a-real-date'],
            ['start_date' => '13-2026'],
        ]);

        $course = Course::fromPost(get_post($courseId));

        self::assertSame(
            ['09-2026'],
            array_map(static fn ($date): string => (string) $date, $course->startDates()),
            'The one well-formed date should still hydrate; the two malformed ones should be skipped, not crash the whole page.',
        );
    }

    public function test_a_course_with_only_malformed_dates_hydrates_with_an_empty_list(): void
    {
        $courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'All Bad Dates',
            'post_status' => 'publish',
        ]);

        update_post_meta($courseId, 'start_dates', [
            ['start_date' => 'garbage'],
        ]);

        $course = Course::fromPost(get_post($courseId));

        self::assertSame([], $course->startDates());
    }
}
