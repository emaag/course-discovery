<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Field;

use OxfordInternational\CourseDiscovery\Field\CourseFieldGroup;
use PHPUnit\Framework\TestCase;

final class CourseFieldGroupTest extends TestCase
{
    public function test_a_well_formed_start_date_passes(): void
    {
        self::assertTrue(CourseFieldGroup::validateStartDate(true, '09-2026'));
    }

    /** @dataProvider malformedStartDates */
    public function test_a_malformed_start_date_is_rejected_with_a_message(string $value): void
    {
        $result = CourseFieldGroup::validateStartDate(true, $value);

        self::assertIsString($result, 'A malformed start date must be rejected with an error message, not silently accepted.');
    }

    /** @return list<array{string}> */
    public static function malformedStartDates(): array
    {
        return [
            ['13-2026'],
            ['September-2026'],
            ['09/2026'],
            ['not-a-date'],
        ];
    }

    public function test_an_empty_value_is_left_to_the_required_rule(): void
    {
        self::assertTrue(CourseFieldGroup::validateStartDate(true, ''));
    }

    public function test_an_already_failed_validation_is_passed_through_unchanged(): void
    {
        self::assertSame('some other error', CourseFieldGroup::validateStartDate('some other error', '13-2026'));
    }
}
