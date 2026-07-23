<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\PostType;

final class InstructorPostType implements PostTypeRegistrar
{
    public function slug(): string
    {
        return 'instructor';
    }

    public function register(): void
    {
        register_post_type($this->slug(), [
            'labels' => [
                'name' => __('Instructors', 'course-discovery'),
                'singular_name' => __('Instructor', 'course-discovery'),
                'add_new_item' => __('Add New Instructor', 'course-discovery'),
                'edit_item' => __('Edit Instructor', 'course-discovery'),
                'search_items' => __('Search Instructors', 'course-discovery'),
                'not_found' => __('No instructors found', 'course-discovery'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'instructors'],
            'menu_icon' => 'dashicons-businessperson',
            'supports' => ['title', 'editor', 'thumbnail'],
        ]);
    }
}
