<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * A course start date entered as {month}-{year} (e.g. "09-2026").
 *
 * Stored as separate month/year ints rather than a DateTime so equality,
 * chronological sorting and the storage format stay exact and don't drift
 * through timezone or day-of-month handling that a full date doesn't need.
 */
final class StartDate
{
    private readonly int $month;

    private readonly int $year;

    public function __construct(int $month, int $year)
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException(sprintf('Month must be between 1 and 12, %d given.', $month));
        }

        $this->month = $month;
        $this->year = $year;
    }

    public static function fromString(string $value): self
    {
        if (! preg_match('/^(\d{1,2})-(\d{4})$/', trim($value), $matches)) {
            throw new InvalidArgumentException(sprintf('Start date "%s" is not in {month}-{year} format.', $value));
        }

        return new self((int) $matches[1], (int) $matches[2]);
    }

    public function month(): int
    {
        return $this->month;
    }

    public function year(): int
    {
        return $this->year;
    }

    /** A plain-int sort key (e.g. 202609) so chronological ordering falls out of a numeric sort/comparison. */
    public function sortKey(): int
    {
        return ($this->year * 100) + $this->month;
    }

    public function compareTo(self $other): int
    {
        return $this->sortKey() <=> $other->sortKey();
    }

    public function format(string $format = 'F Y'): string
    {
        return (new DateTimeImmutable(sprintf('%04d-%02d-01', $this->year, $this->month)))->format($format);
    }

    public function toStorageString(): string
    {
        return sprintf('%02d-%04d', $this->month, $this->year);
    }

    public function equals(self $other): bool
    {
        return $this->month === $other->month && $this->year === $other->year;
    }

    public function __toString(): string
    {
        return $this->toStorageString();
    }
}
