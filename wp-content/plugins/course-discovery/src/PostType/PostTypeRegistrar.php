<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\PostType;

/**
 * One implementation per post type; Plugin composes a list of these rather
 * than any implementation extending a shared base class, so a third party
 * can add a new post type by hooking `course_discovery_post_types` without
 * touching existing registrars.
 */
interface PostTypeRegistrar
{
    public function slug(): string;

    public function register(): void;
}
