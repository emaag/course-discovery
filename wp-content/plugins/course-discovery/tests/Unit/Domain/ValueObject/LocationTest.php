<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Domain\ValueObject;

use InvalidArgumentException;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Location;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    public function test_it_trims_the_name(): void
    {
        self::assertSame('India', (new Location('  India  '))->name());
    }

    public function test_it_rejects_an_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Location('   ');
    }

    public function test_slug_is_lowercase_and_hyphenated(): void
    {
        self::assertSame('united-kingdom', (new Location('United Kingdom'))->slug());
    }

    public function test_equality_is_by_slug_not_exact_casing(): void
    {
        self::assertTrue((new Location('India'))->equals(new Location('india')));
        self::assertFalse((new Location('India'))->equals(new Location('China')));
    }
}
