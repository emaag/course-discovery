<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Field;

/**
 * One implementation per ACF field group, registered in code
 * (`acf_add_local_field_group`) rather than left as UI-only config, so the
 * schema is versioned alongside the domain model that reads it.
 */
interface FieldGroupRegistrar
{
    public function key(): string;

    public function register(): void;
}
