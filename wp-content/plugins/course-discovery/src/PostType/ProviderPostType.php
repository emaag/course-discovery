<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\PostType;

final class ProviderPostType implements PostTypeRegistrar
{
    public function slug(): string
    {
        return 'provider';
    }

    public function register(): void
    {
        register_post_type($this->slug(), [
            'labels' => [
                'name' => __('Providers', 'course-discovery'),
                'singular_name' => __('Provider', 'course-discovery'),
                'add_new_item' => __('Add New Provider', 'course-discovery'),
                'edit_item' => __('Edit Provider', 'course-discovery'),
                'search_items' => __('Search Providers', 'course-discovery'),
                'not_found' => __('No providers found', 'course-discovery'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'providers'],
            'menu_icon' => 'dashicons-building',
            'supports' => ['title', 'editor', 'thumbnail'],
        ]);
    }
}
