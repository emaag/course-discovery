<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Domain\Model;

use OxfordInternational\CourseDiscovery\Domain\ValueObject\Location;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\PostId;
use WP_Post;

final class Provider
{
    public function __construct(
        private readonly PostId $id,
        private readonly string $name,
        private readonly Location $location,
    ) {
    }

    /** Expects an ACF text field named `location` on the `provider` post type. */
    public static function fromPost(WP_Post $post): self
    {
        $locationName = 'Unknown';

        if (function_exists('get_field')) {
            $value = get_field('location', $post->ID);

            if (is_string($value) && trim($value) !== '') {
                $locationName = $value;
            }
        }

        return new self(
            new PostId((int) $post->ID),
            $post->post_title,
            new Location($locationName),
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

    public function location(): Location
    {
        return $this->location;
    }
}
