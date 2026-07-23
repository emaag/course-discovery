<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Domain\ValueObject;

use InvalidArgumentException;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\PostId;
use PHPUnit\Framework\TestCase;

final class PostIdTest extends TestCase
{
    public function test_it_holds_a_positive_integer(): void
    {
        $id = new PostId(42);

        self::assertSame(42, $id->toInt());
        self::assertSame('42', (string) $id);
    }

    /** @dataProvider nonPositiveIntegers */
    public function test_it_rejects_non_positive_values(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PostId($value);
    }

    /** @return list<array{int}> */
    public static function nonPositiveIntegers(): array
    {
        return [[0], [-1], [-100]];
    }

    public function test_equality_is_by_value(): void
    {
        self::assertTrue((new PostId(7))->equals(new PostId(7)));
        self::assertFalse((new PostId(7))->equals(new PostId(8)));
    }
}
