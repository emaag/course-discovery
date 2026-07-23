<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\Taxonomy;

/** One implementation per taxonomy, composed the same way as PostTypeRegistrar. */
interface TaxonomyRegistrar
{
    public function slug(): string;

    /** @return list<string> Post type slugs this taxonomy attaches to. */
    public function objectTypes(): array;

    public function register(): void;
}
