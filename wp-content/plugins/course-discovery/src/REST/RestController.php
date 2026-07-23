<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery\REST;

/** One implementation per REST route, composed the same way as PostTypeRegistrar/TaxonomyRegistrar. */
interface RestController
{
    public function register(): void;
}
