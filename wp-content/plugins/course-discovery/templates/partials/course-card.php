<?php

/**
 * Expects $course (Domain\Model\Course) in scope — included from
 * archive-course.php's render loop.
 *
 * @var \OxfordInternational\CourseDiscovery\Domain\Model\Course $course
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$locations = $course->locations();
$categories = $course->categories();
$startDates = $course->startDates();
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
        </dl>
    </article>
</li>
