<?php

/**
 * Course listing template — served at the site's front page (`/`)
 * regardless of the active theme, via Frontend\CourseArchiveTemplate's
 * `template_include` hook (the Course post type's own archive URL,
 * `/courses/`, 301-redirects to `/` — see that class). Reads filter
 * selections from $_GET and renders through the same
 * Filter\FilterPipeline / Query\CourseQueryBuilder the REST API uses, so
 * this works correctly with JavaScript disabled — assets/js/frontend.js
 * progressively enhances it to fetch course-discovery/v1/courses instead
 * of doing a full page reload, without changing the underlying filtering
 * logic at all.
 */

declare(strict_types=1);

use OxfordInternational\CourseDiscovery\Filter\FilterCriteria;
use OxfordInternational\CourseDiscovery\Filter\FilterPipeline;
use OxfordInternational\CourseDiscovery\Frontend\FilterFieldRenderer;
use OxfordInternational\CourseDiscovery\Query\CourseQueryBuilder;
use OxfordInternational\CourseDiscovery\Query\FilterOptionsProvider;

if (! defined('ABSPATH')) {
    exit;
}

$criteria = FilterCriteria::fromArray([
    'search' => isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : null,
    'providers' => isset($_GET['providers']) ? array_map('intval', (array) wp_unslash($_GET['providers'])) : [],
    'locations' => isset($_GET['locations']) ? array_map('sanitize_text_field', (array) wp_unslash($_GET['locations'])) : [],
    'start_dates' => isset($_GET['start_dates']) ? array_map('sanitize_text_field', (array) wp_unslash($_GET['start_dates'])) : [],
    'categories' => isset($_GET['categories']) ? array_map('intval', (array) wp_unslash($_GET['categories'])) : [],
]);

$page = isset($_GET['course_page']) ? max(1, (int) $_GET['course_page']) : 1;

$builder = (new CourseQueryBuilder())->setPage($page)->setPerPage(9);
(new FilterPipeline())->apply($builder, $criteria);
$result = $builder->execute();

$options = (new FilterOptionsProvider())->compute();
$archiveUrl = home_url('/');

get_header();
?>

<main id="primary" class="site-main course-discovery-archive">
    <h1><?php esc_html_e('Courses', 'course-discovery'); ?></h1>

    <form
        method="get"
        action="<?php echo esc_url($archiveUrl); ?>"
        class="course-discovery-filters"
        data-course-discovery-form
    >
        <div class="course-discovery-field course-discovery-field--search">
            <label for="course-discovery-search"><?php esc_html_e('Search courses', 'course-discovery'); ?></label>
            <input
                type="search"
                id="course-discovery-search"
                name="search"
                value="<?php echo esc_attr((string) $criteria->search()); ?>"
                placeholder="<?php esc_attr_e('Search by name or description…', 'course-discovery'); ?>"
            />
        </div>

        <?php
        FilterFieldRenderer::renderCombobox(
            'providers',
            __('Providers', 'course-discovery'),
            $options['providers'],
            'id',
            'name',
            $criteria->providerIds(),
        );

        FilterFieldRenderer::renderCombobox(
            'locations',
            __('Locations', 'course-discovery'),
            $options['locations'],
            'slug',
            'name',
            $criteria->locationSlugs(),
        );

        FilterFieldRenderer::renderCombobox(
            'categories',
            __('Categories', 'course-discovery'),
            $options['categories'],
            'id',
            'name',
            $criteria->categoryIds(),
        );

        FilterFieldRenderer::renderCombobox(
            'start_dates',
            __('Start dates', 'course-discovery'),
            $options['start_dates'],
            'value',
            'label',
            $criteria->startDates(),
        );
        ?>

        <div class="course-discovery-filters__actions">
            <button type="submit"><?php esc_html_e('Apply filters', 'course-discovery'); ?></button>
            <a href="<?php echo esc_url($archiveUrl); ?>" class="course-discovery-filters__reset">
                <?php esc_html_e('Reset', 'course-discovery'); ?>
            </a>
        </div>
    </form>

    <p
        class="course-discovery-results-count"
        role="status"
        aria-live="polite"
        data-course-discovery-count
    >
        <?php
        printf(
            /* translators: %d: number of matching courses */
            esc_html(_n('%d course found', '%d courses found', $result->total(), 'course-discovery')),
            (int) $result->total(),
        );
        ?>
    </p>

    <ul class="course-discovery-results" data-course-discovery-results>
        <?php foreach ($result->courses() as $course) : ?>
            <?php require __DIR__ . '/partials/course-card.php'; ?>
        <?php endforeach; ?>
    </ul>

    <nav
        class="course-discovery-pagination"
        aria-label="<?php esc_attr_e('Course results pages', 'course-discovery'); ?>"
        data-course-discovery-pagination
    >
        <?php if ($result->page() > 1) : ?>
            <a href="<?php echo esc_url(add_query_arg('course_page', $result->page() - 1)); ?>">
                <?php esc_html_e('Previous', 'course-discovery'); ?>
            </a>
        <?php endif; ?>
        <span>
            <?php
            printf(
                /* translators: 1: current page, 2: total pages */
                esc_html__('Page %1$d of %2$d', 'course-discovery'),
                (int) $result->page(),
                (int) $result->totalPages(),
            );
            ?>
        </span>
        <?php if ($result->page() < $result->totalPages()) : ?>
            <a href="<?php echo esc_url(add_query_arg('course_page', $result->page() + 1)); ?>">
                <?php esc_html_e('Next', 'course-discovery'); ?>
            </a>
        <?php endif; ?>
    </nav>
</main>

<?php get_footer(); ?>
