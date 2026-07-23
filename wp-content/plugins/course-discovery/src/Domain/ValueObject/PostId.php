<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Domain\ValueObject;

use InvalidArgumentException;

final class PostId
{
    private readonly int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException(sprintf('Post ID must be a positive integer, %d given.', $value));
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
