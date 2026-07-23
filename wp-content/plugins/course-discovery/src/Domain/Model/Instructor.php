<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Domain\Model;

use OxfordInternational\CourseDiscovery\Domain\ValueObject\PostId;
use WP_Post;

final class Instructor
{
    public function __construct(
        private readonly PostId $id,
        private readonly string $name,
        private readonly string $bio = '',
    ) {
    }

    public static function fromPost(WP_Post $post): self
    {
        return new self(
            new PostId((int) $post->ID),
            $post->post_title,
            $post->post_content,
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

    public function bio(): string
    {
        return $this->bio;
    }
}
