<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\REST;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Domain\Model\Instructor;
use OxfordInternational\CourseDiscovery\Domain\Model\Provider;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\CategoryTerm;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Location;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;

/**
 * Converts a Course into a REST-response-friendly array. Kept separate
 * from Domain\Model\Course so the domain layer never has to know about
 * REST response shape — purely a read of Course's own typed getters, so
 * it has no WordPress dependency and is unit-testable directly.
 */
final class CourseTransformer
{
    /** @return array<string, mixed> */
    public function toArray(Course $course): array
    {
        return [
            'id' => $course->id()->toInt(),
            'name' => $course->name(),
            'short_description' => $course->shortDescription(),
            'long_description' => $course->longDescription(),
            'price' => [
                'amount' => $course->price()->amount(),
                'currency' => $course->price()->currency(),
                'formatted' => $course->price()->format(),
            ],
            'instructors' => array_map(
                static fn (Instructor $instructor): array => [
                    'id' => $instructor->id()->toInt(),
                    'name' => $instructor->name(),
                ],
                $course->instructors(),
            ),
            'providers' => array_map(
                static fn (Provider $provider): array => [
                    'id' => $provider->id()->toInt(),
                    'name' => $provider->name(),
                ],
                $course->providers(),
            ),
            'locations' => array_map(
                static fn (Location $location): array => [
                    'slug' => $location->slug(),
                    'name' => $location->name(),
                ],
                $course->locations(),
            ),
            'start_dates' => array_map(
                static fn (StartDate $date): array => [
                    'value' => $date->toStorageString(),
                    'label' => $date->format('F Y'),
                ],
                $course->startDates(),
            ),
            'categories' => array_map(
                static fn (CategoryTerm $category): array => [
                    'id' => $category->id(),
                    'name' => $category->name(),
                    'slug' => $category->slug(),
                    'parent_id' => $category->parentId(),
                ],
                $course->categories(),
            ),
        ];
    }
}
