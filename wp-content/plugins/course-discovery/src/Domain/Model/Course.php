<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Domain\Model;

use OxfordInternational\CourseDiscovery\Domain\ValueObject\CategoryTerm;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Location;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\PostId;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\Price;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;
use WP_Post;
use WP_Term;

/**
 * Expects these ACF fields on the `course` post type: `short_description`
 * (text), `price` (number), `instructors` (relationship to `instructor`),
 * `providers` (relationship to `provider`), `start_dates` (repeater with a
 * `start_date` sub-field storing "{month}-{year}" strings). Categories come
 * from the native `course_category` taxonomy, not ACF. Long description is
 * the post's own content.
 */
final class Course
{
    /**
     * @param list<Instructor>   $instructors
     * @param list<Provider>     $providers
     * @param list<StartDate>    $startDates
     * @param list<CategoryTerm> $categories
     */
    public function __construct(
        private readonly PostId $id,
        private readonly string $name,
        private readonly string $shortDescription,
        private readonly string $longDescription,
        private readonly Price $price,
        private readonly array $instructors,
        private readonly array $providers,
        private readonly array $startDates,
        private readonly array $categories,
    ) {
    }

    public static function fromPost(WP_Post $post): self
    {
        return new self(
            new PostId((int) $post->ID),
            $post->post_title,
            (string) self::acfField('short_description', $post->ID, ''),
            $post->post_content,
            new Price((float) self::acfField('price', $post->ID, 0.0)),
            self::hydrateInstructors($post->ID),
            self::hydrateProviders($post->ID),
            self::hydrateStartDates($post->ID),
            self::hydrateCategories($post->ID),
        );
    }

    public function id(): PostId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function shortDescription(): string
    {
        return $this->shortDescription;
    }

    public function longDescription(): string
    {
        return $this->longDescription;
    }

    public function price(): Price
    {
        return $this->price;
    }

    /** @return list<Instructor> */
    public function instructors(): array
    {
        return $this->instructors;
    }

    /** @return list<Provider> */
    public function providers(): array
    {
        return $this->providers;
    }

    /** @return list<StartDate> */
    public function startDates(): array
    {
        return $this->startDates;
    }

    /** @return list<CategoryTerm> */
    public function categories(): array
    {
        return $this->categories;
    }

    /**
     * Derived from providers, de-duplicated by slug — never stored directly
     * on the Course, so it can't drift out of sync with its providers.
     *
     * @return list<Location>
     */
    public function locations(): array
    {
        $seen = [];
        $locations = [];

        foreach ($this->providers as $provider) {
            $location = $provider->location();

            if (isset($seen[$location->slug()])) {
                continue;
            }

            $seen[$location->slug()] = true;
            $locations[] = $location;
        }

        return $locations;
    }

    /** @return list<Instructor> */
    private static function hydrateInstructors(int $postId): array
    {
        return array_map(
            static fn (WP_Post $post): Instructor => Instructor::fromPost($post),
            self::relatedPosts($postId, 'instructors'),
        );
    }

    /** @return list<Provider> */
    private static function hydrateProviders(int $postId): array
    {
        return array_map(
            static fn (WP_Post $post): Provider => Provider::fromPost($post),
            self::relatedPosts($postId, 'providers'),
        );
    }

    /** @return list<StartDate> */
    private static function hydrateStartDates(int $postId): array
    {
        $raw = self::acfField('start_dates', $postId, []);

        if (! is_array($raw)) {
            return [];
        }

        $dates = [];

        foreach ($raw as $row) {
            $value = is_array($row) ? ($row['start_date'] ?? null) : $row;

            if (! is_string($value) || $value === '') {
                continue;
            }

            $dates[] = StartDate::fromString($value);
        }

        usort($dates, static fn (StartDate $a, StartDate $b): int => $a->compareTo($b));

        return $dates;
    }

    /** @return list<CategoryTerm> */
    private static function hydrateCategories(int $postId): array
    {
        if (! function_exists('get_the_terms')) {
            return [];
        }

        $terms = get_the_terms($postId, 'course_category');

        if (! is_array($terms)) {
            return [];
        }

        return array_map(
            static fn (WP_Term $term): CategoryTerm => new CategoryTerm(
                (int) $term->term_id,
                $term->name,
                $term->slug,
                $term->parent > 0 ? (int) $term->parent : null,
            ),
            $terms,
        );
    }

    /** @return list<WP_Post> */
    private static function relatedPosts(int $postId, string $fieldName): array
    {
        $related = self::acfField($fieldName, $postId, []);

        if (! is_array($related)) {
            return [];
        }

        $posts = [];

        foreach ($related as $item) {
            $post = $item instanceof WP_Post ? $item : get_post((int) $item);

            if ($post instanceof WP_Post) {
                $posts[] = $post;
            }
        }

        return $posts;
    }

    private static function acfField(string $fieldName, int $postId, mixed $default): mixed
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field($fieldName, $postId);

        return $value === null || $value === false ? $default : $value;
    }
}
