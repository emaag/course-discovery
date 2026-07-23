<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Filter;

/** A single typed selection across all filters — the input to the whole Filter/Query layer. */
final class FilterCriteria
{
    /**
     * @param list<int>    $providerIds
     * @param list<string> $locationSlugs
     * @param list<string> $startDates {month}-{year} strings
     * @param list<int>    $categoryIds
     */
    public function __construct(
        private readonly ?string $search = null,
        private readonly array $providerIds = [],
        private readonly array $locationSlugs = [],
        private readonly array $startDates = [],
        private readonly array $categoryIds = [],
    ) {
    }

    public function search(): ?string
    {
        return $this->search;
    }

    /** @return list<int> */
    public function providerIds(): array
    {
        return $this->providerIds;
    }

    /** @return list<string> */
    public function locationSlugs(): array
    {
        return $this->locationSlugs;
    }

    /** @return list<string> */
    public function startDates(): array
    {
        return $this->startDates;
    }

    /** @return list<int> */
    public function categoryIds(): array
    {
        return $this->categoryIds;
    }

    /**
     * Builds criteria from raw request input (e.g. REST query params).
     * Applies `course_discovery_transform_criteria` first, so a third
     * party can rewrite incoming criteria — map a legacy param name,
     * expand a saved-search shortcut — before it's turned into a typed
     * FilterCriteria, without touching this class.
     *
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (function_exists('apply_filters')) {
            /** @var array<string, mixed> $raw */
            $raw = apply_filters('course_discovery_transform_criteria', $raw);
        }

        return new self(
            isset($raw['search']) && $raw['search'] !== '' ? (string) $raw['search'] : null,
            self::intList($raw['providers'] ?? []),
            self::stringList($raw['locations'] ?? []),
            self::stringList($raw['start_dates'] ?? []),
            self::intList($raw['categories'] ?? []),
        );
    }

    /** @return list<int> */
    private static function intList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(intval(...), $value));
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(strval(...), $value));
    }
}
