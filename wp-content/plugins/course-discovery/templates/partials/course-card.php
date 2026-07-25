<?php

/**
 * Expects $course (Domain\Model\Course) in scope — included from
 * archive-course.php's render loop.
 *
 * assets/js/frontend.js's courseCardHtml() re-renders this same card
 * client-side after a JS-driven filter/paginate — it can't literally
 * share this template across the PHP/JS boundary, so it duplicates the
 * same fields/order by hand instead. Keep both in sync when changing
 * either — tests/e2e/specs/card-rendering-parity.spec.js asserts both
 * render paths show the same fields in the same order, so a field added
 * to only one of them fails a real test rather than just being missed.
 *
 * @var \OxfordInternational\CourseDiscovery\Domain\Model\Course $course
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$providers = $course->providers();
$locations = $course->locations();
$categories = $course->categories();
$startDates = $course->startDates();
$instructors = $course->instructors();
?>
<li class="course-discovery-card">
    <article>
        <h2 class="course-discovery-card__title"><?php echo esc_html($course->name()); ?></h2>
        <p class="course-discovery-card__description"><?php echo esc_html($course->shortDescription()); ?></p>
        <dl class="course-discovery-card__meta">
            <div>
                <dt><?php esc_html_e('Price', 'course-discovery'); ?></dt>
                <dd><?php echo esc_html($course->price()->format()); ?></dd>
            </div>
            <?php if ($providers !== []) : ?>
                <div>
                    <dt><?php esc_html_e('Providers', 'course-discovery'); ?></dt>
                    <dd><?php echo esc_html(implode(', ', array_map(static fn ($p) => $p->name(), $providers))); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($locations !== []) : ?>
                <div>
                    <dt><?php esc_html_e('Location', 'course-discovery'); ?></dt>
                    <dd><?php echo esc_html(implode(', ', array_map(static fn ($l) => $l->name(), $locations))); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($categories !== []) : ?>
                <div>
                    <dt><?php esc_html_e('Category', 'course-discovery'); ?></dt>
                    <dd><?php echo esc_html(implode(', ', array_map(static fn ($c) => $c->name(), $categories))); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($startDates !== []) : ?>
                <div>
                    <dt><?php esc_html_e('Start dates', 'course-discovery'); ?></dt>
                    <dd><?php echo esc_html(implode(', ', array_map(static fn ($d) => $d->format('F Y'), $startDates))); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($instructors !== []) : ?>
                <div>
                    <dt><?php esc_html_e('Instructors', 'course-discovery'); ?></dt>
                    <dd><?php echo esc_html(implode(', ', array_map(static fn ($i) => $i->name(), $instructors))); ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </article>
</li>
