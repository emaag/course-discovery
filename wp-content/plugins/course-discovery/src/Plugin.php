<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery;

use OxfordInternational\CourseDiscovery\PostType\CoursePostType;
use OxfordInternational\CourseDiscovery\PostType\InstructorPostType;
use OxfordInternational\CourseDiscovery\PostType\PostTypeRegistrar;
use OxfordInternational\CourseDiscovery\PostType\ProviderPostType;
use OxfordInternational\CourseDiscovery\Taxonomy\CourseCategoryTaxonomy;
use OxfordInternational\CourseDiscovery\Taxonomy\TaxonomyRegistrar;

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
        /**
         * Third parties can add/remove post types here without touching
         * this class or any existing registrar.
         *
         * @param list<PostTypeRegistrar> $registrars
         */
        $registrars = apply_filters('course_discovery_post_types', [
            new CoursePostType(),
            new InstructorPostType(),
            new ProviderPostType(),
        ]);

        foreach ($registrars as $registrar) {
            $registrar->register();
        }
    }

    public function registerTaxonomies(): void
    {
        /**
         * @param list<TaxonomyRegistrar> $registrars
         */
        $registrars = apply_filters('course_discovery_taxonomies', [
            new CourseCategoryTaxonomy(),
        ]);

        foreach ($registrars as $registrar) {
            $registrar->register();
        }
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
