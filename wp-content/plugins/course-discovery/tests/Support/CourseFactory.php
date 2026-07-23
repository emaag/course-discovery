<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Tests\Support;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Domain\Model\Instructor;
use OxfordInternational\CourseDiscovery\Domain\Model\Provider;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\CategoryTerm;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Location;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\PostId;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Price;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;

/** Builds fabricated Course graphs for tests, without any WP_Post/WordPress dependency. */
final class CourseFactory
{
    /**
     * @param array{
     *     id?: int,
     *     name?: string,
     *     price?: float,
     *     providerIds?: list<int>,
     *     locations?: list<string>,
     *     startDates?: list<string>,
     *     categories?: list<array{id: int, name: string, slug: string}>,
     * } $overrides
     */
    public static function make(array $overrides = []): Course
    {
        $providerIds = $overrides['providerIds'] ?? [1];
        $locations = $overrides['locations'] ?? ['India'];
        $providers = [];

        foreach ($providerIds as $index => $providerId) {
            $providers[] = new Provider(
                new PostId($providerId),
                sprintf('Provider %d', $providerId),
                new Location($locations[$index] ?? $locations[array_key_last($locations)]),
            );
        }

        $categories = array_map(
            static fn (array $c): CategoryTerm => new CategoryTerm($c['id'], $c['name'], $c['slug']),
            $overrides['categories'] ?? [['id' => 1, 'name' => 'Design', 'slug' => 'design']],
        );

        $startDates = array_map(
            StartDate::fromString(...),
            $overrides['startDates'] ?? ['09-2026'],
        );

        return new Course(
            new PostId($overrides['id'] ?? 100),
            $overrides['name'] ?? 'Sample Course',
            'Short description',
            'Long description',
            new Price($overrides['price'] ?? 100.0),
            [new Instructor(new PostId(1), 'Sample Instructor')],
            $providers,
            $startDates,
            $categories,
        );
    }
}
