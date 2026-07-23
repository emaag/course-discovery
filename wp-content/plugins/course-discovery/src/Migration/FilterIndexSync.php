<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Migration;

use OxfordInternational\CourseDiscovery\Domain\Model\Course;
use OxfordInternational\CourseDiscovery\Domain\Model\Provider;
use OxfordInternational\CourseDiscovery\Domain\ValueObject\StartDate;
use WP_Post;

/** Keeps the two filter-index tables in sync with each Course's real ACF data on save/delete. */
final class FilterIndexSync
{
    public function registerHooks(): void
    {
        add_action('save_post_course', [$this, 'handleSave'], 10, 3);
        add_action('before_delete_post', [$this, 'handleDelete']);
    }

    public function handleSave(int $postId, WP_Post $post, bool $update): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        if ($post->post_status !== 'publish') {
            $this->remove($postId);

            return;
        }

        $this->sync(Course::fromPost($post));
    }

    public function handleDelete(int $postId): void
    {
        if (get_post_type($postId) === 'course') {
            $this->remove($postId);
        }
    }

    public function sync(Course $course): void
    {
        global $wpdb;

        $courseId = $course->id()->toInt();
        $this->remove($courseId);

        foreach (self::providerRows($course) as $row) {
            $wpdb->insert(FilterIndexTables::providers(), array_merge(['course_id' => $courseId], $row));
        }

        foreach (self::startDateRows($course) as $row) {
            $wpdb->insert(FilterIndexTables::startDates(), array_merge(['course_id' => $courseId], $row));
        }
    }

    public function remove(int $courseId): void
    {
        global $wpdb;

        $wpdb->delete(FilterIndexTables::providers(), ['course_id' => $courseId], ['%d']);
        $wpdb->delete(FilterIndexTables::startDates(), ['course_id' => $courseId], ['%d']);
    }

    /**
     * Pure — no WordPress DB dependency, unit-testable directly against a
     * fabricated Course.
     *
     * @return list<array{provider_id: int, location_slug: string}>
     */
    public static function providerRows(Course $course): array
    {
        return array_map(
            static fn (Provider $provider): array => [
                'provider_id' => $provider->id()->toInt(),
                'location_slug' => $provider->location()->slug(),
            ],
            $course->providers(),
        );
    }

    /** @return list<array{start_date: string}> */
    public static function startDateRows(Course $course): array
    {
        return array_map(
            static fn (StartDate $date): array => ['start_date' => $date->toStorageString()],
            $course->startDates(),
        );
    }
}
