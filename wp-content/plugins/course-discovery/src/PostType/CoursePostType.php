<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\PostType;

final class CoursePostType implements PostTypeRegistrar
{
    public function slug(): string
    {
        return 'course';
    }

    public function register(): void
    {
        register_post_type($this->slug(), [
            'labels' => [
                'name' => __('Courses', 'course-discovery'),
                'singular_name' => __('Course', 'course-discovery'),
                'add_new_item' => __('Add New Course', 'course-discovery'),
                'edit_item' => __('Edit Course', 'course-discovery'),
                'search_items' => __('Search Courses', 'course-discovery'),
                'not_found' => __('No courses found', 'course-discovery'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'courses'],
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => ['title', 'editor', 'thumbnail'],
        ]);
    }
}
