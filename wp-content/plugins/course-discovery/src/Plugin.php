<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery;

final class Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function boot(): void
    {
        add_action('init', [$this, 'registerPostTypes']);
        add_action('init', [$this, 'registerTaxonomies']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('plugins_loaded', [$this, 'runMigrations']);
    }

    public function registerPostTypes(): void
    {
        // Post type registrations live in src/PostType.
    }

    public function registerTaxonomies(): void
    {
        // Taxonomy registrations live in src/Taxonomy.
    }

    public function registerRestRoutes(): void
    {
        // REST controllers live in src/REST.
    }

    public function runMigrations(): void
    {
        // Migration runners live in src/Migration.
    }
}
