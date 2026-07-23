<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Domain\ValueObject;

use InvalidArgumentException;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;
use PHPUnit\Framework\TestCase;

final class StartDateTest extends TestCase
{
    public function test_it_parses_month_dash_year(): void
    {
        $date = StartDate::fromString('09-2026');

        self::assertSame(9, $date->month());
        self::assertSame(2026, $date->year());
        self::assertSame('09-2026', $date->toStorageString());
    }

    public function test_it_accepts_a_single_digit_month(): void
    {
        self::assertSame(9, StartDate::fromString('9-2026')->month());
    }

    /** @dataProvider malformedStrings */
    public function test_it_rejects_malformed_strings(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        StartDate::fromString($value);
    }

    /** @return list<array{string}> */
    public static function malformedStrings(): array
    {
        return [
            ['2026-09'],
            ['September-2026'],
            ['09/2026'],
            [''],
            ['09-26'],
        ];
    }

    public function test_month_out_of_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StartDate(13, 2026);
    }

    public function test_it_formats_using_the_given_php_date_format(): void
    {
        self::assertSame('September 2026', (new StartDate(9, 2026))->format('F Y'));
        self::assertSame('2026-09', (new StartDate(9, 2026))->format('Y-m'));
    }

    public function test_chronological_ordering_across_year_boundaries(): void
    {
        $dates = [
            StartDate::fromString('01-2027'),
            StartDate::fromString('12-2026'),
            StartDate::fromString('06-2026'),
        ];

        usort($dates, static fn (StartDate $a, StartDate $b): int => $a->compareTo($b));

        self::assertSame(
            ['06-2026', '12-2026', '01-2027'],
            array_map(static fn (StartDate $d): string => $d->toStorageString(), $dates),
        );
    }

    public function test_equality_is_by_month_and_year(): void
    {
        self::assertTrue(StartDate::fromString('09-2026')->equals(StartDate::fromString('9-2026')));
        self::assertFalse(StartDate::fromString('09-2026')->equals(StartDate::fromString('09-2027')));
    }
}
