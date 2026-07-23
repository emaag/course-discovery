<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Unit\Domain\ValueObject;

use InvalidArgumentException;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Price;
use PHPUnit\Framework\TestCase;

final class PriceTest extends TestCase
{
    public function test_it_defaults_to_gbp(): void
    {
        $price = new Price(199.5);

        self::assertSame(199.5, $price->amount());
        self::assertSame('GBP', $price->currency());
        self::assertSame('GBP 199.50', $price->format());
    }

    public function test_it_normalises_currency_to_uppercase(): void
    {
        self::assertSame('USD', (new Price(10, 'usd'))->currency());
    }

    public function test_it_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Price(-0.01);
    }

    public function test_it_rejects_an_empty_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Price(10, '  ');
    }

    public function test_equality_is_by_amount_and_currency(): void
    {
        self::assertTrue((new Price(10, 'GBP'))->equals(new Price(10, 'gbp')));
        self::assertFalse((new Price(10, 'GBP'))->equals(new Price(10, 'USD')));
        self::assertFalse((new Price(10))->equals(new Price(20)));
    }
}
