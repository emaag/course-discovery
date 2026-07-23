<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Domain\ValueObject;

use InvalidArgumentException;

/**
 * A single numeric price point.
 *
 * Deliberately holds one amount, per the brief's current requirement — a
 * future `PriceRange` (min/max) or a `list<Price>` for multiple price
 * points can sit alongside this without changing Price itself, since
 * nothing outside Course/Price depends on there being exactly one.
 */
final class Price
{
    private readonly float $amount;

    private readonly string $currency;

    public function __construct(float $amount, string $currency = 'GBP')
    {
        if ($amount < 0) {
            throw new InvalidArgumentException(sprintf('Price amount cannot be negative, %.2f given.', $amount));
        }

        if (trim($currency) === '') {
            throw new InvalidArgumentException('Currency code cannot be empty.');
        }

        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function format(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->amount, 2));
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
