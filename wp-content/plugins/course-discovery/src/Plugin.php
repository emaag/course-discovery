<?php

declare(strict_types=1);

namespace OxfordInternational\CourseDiscovery;

use OxfordInternational\CourseDiscovery\Field\CourseFieldGroup;
use OxfordInternational\CourseDiscovery\Field\FieldGroupRegistrar;
use OxfordInternational\CourseDiscovery\Field\ProviderFieldGroup;
use OxfordInternational\CourseDiscovery\Frontend\CourseArchiveTemplate;
use OxfordInternational\CourseDiscovery\Migration\CreateFilterIndexTables;
use OxfordInternational\CourseDiscovery\Migration\FilterIndexSync;
use OxfordInternational\CourseDiscovery\Migration\Migration;
use OxfordInternational\CourseDiscovery\Migration\MigrationRunner;
use OxfordInternational\CourseDiscovery\PostType\CoursePostType;
use OxfordInternational\CourseDiscovery\PostType\InstructorPostType;
use OxfordInternational\CourseDiscovery\PostType\PostTypeRegistrar;
use OxfordInternational\CourseDiscovery\PostType\ProviderPostType;
use OxfordInternational\CourseDiscovery\REST\CourseSearchController;
use OxfordInternational\CourseDiscovery\REST\FilterOptionsController;
use OxfordInternational\CourseDiscovery\REST\RestController;
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
        add_action('acf/init', [$this, 'registerFieldGroups']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('plugins_loaded', [$this, 'runMigrations']);

        (new CourseArchiveTemplate())->registerHooks();
        (new FilterIndexSync())->registerHooks();
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

    public function registerFieldGroups(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        /**
         * @param list<FieldGroupRegistrar> $registrars
         */
        $registrars = apply_filters('course_discovery_field_groups', [
            new CourseFieldGroup(),
            new ProviderFieldGroup(),
        ]);

        foreach ($registrars as $registrar) {
            $registrar->register();
        }
    }

    public function registerRestRoutes(): void
    {
        /**
         * @param list<RestController> $controllers
         */
        $controllers = apply_filters('course_discovery_rest_controllers', [
            new CourseSearchController(),
            new FilterOptionsController(),
        ]);

        foreach ($controllers as $controller) {
            $controller->register();
        }
    }

    public function runMigrations(): void
    {
        /**
         * @param list<Migration> $migrations
         */
        $migrations = apply_filters('course_discovery_migrations', [
            new CreateFilterIndexTables(),
        ]);

        (new MigrationRunner())->run($migrations);
    }
}
