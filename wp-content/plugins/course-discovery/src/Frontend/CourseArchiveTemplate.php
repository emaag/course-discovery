<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Frontend;

/**
 * Serves the plugin's own template for the Course listing at the site's
 * front page (`/`), independent of whatever theme is active — the theme
 * only needs to provide get_header()/get_footer(), consistent with the
 * README's "theme is a thin rendering surface" design decision.
 *
 * The Course post type's own archive URL (`/courses/`) still exists at
 * the WordPress level (has_archive => true), so it's 301-redirected to
 * `/` rather than left as a duplicate — one canonical URL for the
 * listing, not two.
 */
final class CourseArchiveTemplate
{
    public function registerHooks(): void
    {
        add_action('template_redirect', [$this, 'redirectArchiveToHome']);
        add_filter('template_include', [$this, 'template']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function redirectArchiveToHome(): void
    {
        if (is_post_type_archive('course') && ! is_front_page()) {
            wp_safe_redirect(home_url('/'), 301);
            exit;
        }
    }

    public function template(string $template): string
    {
        if (is_front_page() || is_post_type_archive('course')) {
            return COURSE_DISCOVERY_PATH . 'templates/archive-course.php';
        }

        return $template;
    }

    public function enqueueAssets(): void
    {
        if (! is_front_page() && ! is_post_type_archive('course')) {
            return;
        }

        wp_enqueue_style(
            'course-discovery-frontend',
            COURSE_DISCOVERY_URL . 'assets/css/frontend.css',
            [],
            COURSE_DISCOVERY_VERSION,
        );

        wp_enqueue_script(
            'course-discovery-frontend',
            COURSE_DISCOVERY_URL . 'assets/js/frontend.js',
            [],
            COURSE_DISCOVERY_VERSION,
            true,
        );

        wp_localize_script('course-discovery-frontend', 'CourseDiscoveryConfig', [
            'restUrl' => esc_url_raw(rest_url('course-discovery/v1/')),
        ]);
    }
}
