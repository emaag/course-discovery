<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Taxonomy;

final class CourseCategoryTaxonomy implements TaxonomyRegistrar
{
    public function slug(): string
    {
        return 'course_category';
    }

    public function objectTypes(): array
    {
        return ['course'];
    }

    public function register(): void
    {
        register_taxonomy($this->slug(), $this->objectTypes(), [
            'labels' => [
                'name' => __('Categories', 'course-discovery'),
                'singular_name' => __('Category', 'course-discovery'),
                'search_items' => __('Search Categories', 'course-discovery'),
                'all_items' => __('All Categories', 'course-discovery'),
                'parent_item' => __('Parent Category', 'course-discovery'),
                'edit_item' => __('Edit Category', 'course-discovery'),
                'add_new_item' => __('Add New Category', 'course-discovery'),
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'course-category'],
        ]);
    }
}
