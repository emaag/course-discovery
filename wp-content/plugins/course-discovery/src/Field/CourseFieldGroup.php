<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Field;

/** Field names here must stay in sync with Domain\Model\Course::fromPost(). */
final class CourseFieldGroup implements FieldGroupRegistrar
{
    public function key(): string
    {
        return 'group_course_discovery_course';
    }

    public function register(): void
    {
        acf_add_local_field_group([
            'key' => $this->key(),
            'title' => __('Course Details', 'course-discovery'),
            'fields' => [
                [
                    'key' => 'field_course_short_description',
                    'label' => __('Short Description', 'course-discovery'),
                    'name' => 'short_description',
                    'type' => 'textarea',
                    'rows' => 2,
                    'required' => 1,
                ],
                [
                    'key' => 'field_course_price',
                    'label' => __('Price', 'course-discovery'),
                    'name' => 'price',
                    'type' => 'number',
                    'required' => 1,
                    'min' => 0,
                    'step' => 0.01,
                    'instructions' => __(
                        'A single numeric price. Designed to be extended to a price range or multiple price points later.',
                        'course-discovery'
                    ),
                ],
                [
                    'key' => 'field_course_instructors',
                    'label' => __('Instructors', 'course-discovery'),
                    'name' => 'instructors',
                    'type' => 'relationship',
                    'post_type' => ['instructor'],
                    'filters' => ['search'],
                    'return_format' => 'id',
                ],
                [
                    'key' => 'field_course_providers',
                    'label' => __('Providers', 'course-discovery'),
                    'name' => 'providers',
                    'type' => 'relationship',
                    'post_type' => ['provider'],
                    'filters' => ['search'],
                    'return_format' => 'id',
                    'instructions' => __(
                        'Locations are derived automatically from the selected Provider(s) — there is no separate Location field.',
                        'course-discovery'
                    ),
                ],
                [
                    'key' => 'field_course_start_dates',
                    'label' => __('Start Dates', 'course-discovery'),
                    'name' => 'start_dates',
                    'type' => 'repeater',
                    'layout' => 'table',
                    'button_label' => __('Add Start Date', 'course-discovery'),
                    'sub_fields' => [
                        [
                            'key' => 'field_course_start_date_value',
                            'label' => __('Month-Year', 'course-discovery'),
                            'name' => 'start_date',
                            'type' => 'text',
                            'placeholder' => 'MM-YYYY',
                            'required' => 1,
                            'instructions' => __('Format: {month}-{year}, e.g. 09-2026.', 'course-discovery'),
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'course',
                    ],
                ],
            ],
        ]);
    }
}
