<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
});

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'course-discovery-theme-style',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
});
