<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Domain\ValueObject;

use InvalidArgumentException;

/**
 * A course location, always derived from its Provider(s) — never held
 * directly as Course data. See Course::locations().
 */
final class Location
{
    private readonly string $name;

    public function __construct(string $name)
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Location name cannot be empty.');
        }

        $this->name = $trimmed;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** Plain-PHP slugify (no wpdb/i18n dependency) so this stays unit-testable without a WordPress bootstrap. */
    public function slug(): string
    {
        $slug = strtolower($this->name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }

    public function equals(self $other): bool
    {
        return $this->slug() === $other->slug();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
