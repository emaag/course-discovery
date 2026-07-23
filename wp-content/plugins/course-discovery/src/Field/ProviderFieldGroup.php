<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Field;

/** Field names here must stay in sync with Domain\Model\Provider::fromPost(). */
final class ProviderFieldGroup implements FieldGroupRegistrar
{
    public function key(): string
    {
        return 'group_course_discovery_provider';
    }

    public function register(): void
    {
        acf_add_local_field_group([
            'key' => $this->key(),
            'title' => __('Provider Details', 'course-discovery'),
            'fields' => [
                [
                    'key' => 'field_provider_location',
                    'label' => __('Location', 'course-discovery'),
                    'name' => 'location',
                    'type' => 'text',
                    'required' => 1,
                    'instructions' => __(
                        'City/region/country this provider operates from. Courses derive their Location from this field via their selected Provider(s).',
                        'course-discovery'
                    ),
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'provider',
                    ],
                ],
            ],
        ]);
    }
}
